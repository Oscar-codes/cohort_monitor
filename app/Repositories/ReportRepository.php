<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * ReportRepository
 *
 * Database access layer for the Reports module.
 * Provides filtered queries and aggregated metrics for cohort reports.
 */
class ReportRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get filtered cohorts with their at_risk status from marketing_stages.
     *
     * @param array $filters ['area' => string|null, 'date_from' => string|null, 'date_to' => string|null]
     * @return array
     */
    public function getFilteredCohorts(array $filters = []): array
    {
        $sql = "
            SELECT
                   cs.id,
                   COALESCE(cs.section_code, CONCAT('SEC-', cs.id)) AS cohort_code,
                   COALESCE(cs.section_code, CONCAT('Sección ', cs.id)) AS name,
                   cs.start_date,
                   cs.end_date,
                   cs.training_status,
                   COALESCE(LOWER(r.route_name), 'academic') AS area,
                   p.project_name AS related_project,
                   ch.coach_name AS assigned_coach,
                   COALESCE(b.bootcamp_name, bf.family_name) AS bootcamp_type,
                   COALESCE(cs.total_students_target, 0) AS total_admission_target,
                   COALESCE(cs.b2b_target, 0) AS b2b_admission_target,
                   COALESCE(m.b2b_admissions, 0) AS b2b_admissions,
                   COALESCE(m.b2c_admissions, 0) AS b2c_admissions,
                   CASE WHEN EXISTS (
                       SELECT 1 FROM marketing_stages ms
                                             JOIN cohort_section_memberships csm2
                                                 ON csm2.bootcamp_family_id = ms.bootcamp_family_id
                                                AND csm2.cohort_type_code = ms.cohort_type_code
                                                AND csm2.cohort_year = ms.cohort_year
                                                AND csm2.cohort_month = ms.cohort_month
                                             WHERE csm2.section_id = cs.id
                         AND ms.status = 'at_risk'
                   ) THEN 1 ELSE 0 END AS at_risk
            FROM cohort_sections cs
            LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
            LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
            LEFT JOIN routes r ON r.id = bf.route_id
            LEFT JOIN projects p ON p.id = cs.project_id
            LEFT JOIN coaches ch ON ch.id = cs.coach_id
            LEFT JOIN (
                SELECT
                    section_id,
                    SUM(CASE WHEN cohort_type_code = 'B2B' THEN actual_students ELSE 0 END) AS b2b_admissions,
                    SUM(CASE WHEN cohort_type_code = 'B2C' THEN actual_students ELSE 0 END) AS b2c_admissions
                FROM cohort_section_memberships
                GROUP BY section_id
            ) m ON m.section_id = cs.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND LOWER(r.route_name) = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (cs.end_date IS NULL OR cs.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (cs.start_date IS NULL OR cs.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY cs.start_date DESC, name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get metrics grouped by area, applying the same filters.
     *
     * @param array $filters
     * @return array  ['academic' => [...], 'marketing' => [...], 'admissions' => [...]]
     */
    public function getMetricsByArea(array $filters = []): array
    {
        $sql = "
            SELECT
                LOWER(r.route_name) AS area,
                COUNT(*)                                                           AS total,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM marketing_stages ms
                                        JOIN cohort_section_memberships csm2
                                            ON csm2.bootcamp_family_id = ms.bootcamp_family_id
                                         AND csm2.cohort_type_code = ms.cohort_type_code
                                         AND csm2.cohort_year = ms.cohort_year
                                         AND csm2.cohort_month = ms.cohort_month
                                        WHERE csm2.section_id = cs.id
                      AND ms.status = 'at_risk'
                ) THEN 1 ELSE 0 END)                                              AS at_risk,
                SUM(CASE WHEN cs.training_status = 'completed' THEN 1 ELSE 0 END)  AS completed,
                SUM(CASE WHEN cs.training_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress
            FROM cohort_sections cs
            LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
            LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
            LEFT JOIN routes r ON r.id = bf.route_id
            WHERE r.route_name IS NOT NULL
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND LOWER(r.route_name) = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (cs.end_date IS NULL OR cs.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (cs.start_date IS NULL OR cs.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " GROUP BY LOWER(r.route_name)";

        $rows = $this->db->query($sql, $params);

        // Index by area
        $result = [];
        foreach ($rows as $row) {
            $result[$row['area']] = $row;
        }

        return $result;
    }

    /**
     * Get global metrics by training_status, applying filters.
     *
     * @param array $filters
     * @return array ['completed' => int, 'in_progress' => int, 'planned' => int, 'cancelled' => int, 'pending_reschedule' => int]
     */
    public function getMetricsByStatus(array $filters = []): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN cs.training_status = 'completed' OR (cs.training_status NOT IN ('completed', 'cancelled', 'pending_reschedule') AND cs.end_date IS NOT NULL AND cs.end_date < CURDATE()) THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN cs.training_status NOT IN ('completed', 'cancelled', 'pending_reschedule') AND cs.start_date IS NOT NULL AND cs.start_date <= CURDATE() AND (cs.end_date IS NULL OR cs.end_date >= CURDATE()) THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN cs.training_status NOT IN ('completed', 'cancelled', 'pending_reschedule') AND (cs.start_date IS NULL OR cs.start_date > CURDATE()) THEN 1 ELSE 0 END) AS planned,
                SUM(CASE WHEN cs.training_status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN cs.training_status = 'pending_reschedule' THEN 1 ELSE 0 END) AS pending_reschedule
            FROM cohort_sections cs
            LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
            LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
            LEFT JOIN routes r ON r.id = bf.route_id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND LOWER(r.route_name) = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (cs.end_date IS NULL OR cs.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (cs.start_date IS NULL OR cs.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $rows = $this->db->query($sql, $params);

        return $rows[0] ?? [
            'completed'   => 0,
            'in_progress' => 0,
            'planned' => 0,
            'cancelled'   => 0,
            'pending_reschedule' => 0,
        ];
    }

    /**
     * Get distinct areas available in the database.
     */
    public function getDistinctAreas(): array
    {
        return $this->db->query(
            "SELECT DISTINCT LOWER(r.route_name) AS area
             FROM cohort_sections cs
             LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
             LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
             LEFT JOIN routes r ON r.id = bf.route_id
             WHERE r.route_name IS NOT NULL
             ORDER BY area"
        );
    }
}
