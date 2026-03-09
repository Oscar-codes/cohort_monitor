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
            SELECT c.*,
                   CASE WHEN EXISTS (
                       SELECT 1 FROM marketing_stages ms
                       WHERE ms.cohort_id = c.id AND ms.status = 'at_risk'
                   ) THEN 1 ELSE 0 END AS at_risk
            FROM cohorts c
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND c.area = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (c.end_date IS NULL OR c.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (c.start_date IS NULL OR c.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY c.start_date DESC, c.name ASC";

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
                c.area,
                COUNT(*)                                                           AS total,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM marketing_stages ms
                    WHERE ms.cohort_id = c.id AND ms.status = 'at_risk'
                ) THEN 1 ELSE 0 END)                                              AS at_risk,
                SUM(CASE WHEN c.training_status = 'completed' THEN 1 ELSE 0 END)  AS completed,
                SUM(CASE WHEN c.training_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress
            FROM cohorts c
            WHERE c.area IS NOT NULL
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND c.area = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (c.end_date IS NULL OR c.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (c.start_date IS NULL OR c.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " GROUP BY c.area";

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
     * @return array ['completed' => int, 'in_progress' => int, 'not_started' => int, 'cancelled' => int]
     */
    public function getMetricsByStatus(array $filters = []): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN c.training_status = 'completed'   THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN c.training_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN c.training_status = 'not_started' THEN 1 ELSE 0 END) AS not_started,
                SUM(CASE WHEN c.training_status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled
            FROM cohorts c
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['area'])) {
            $sql .= " AND c.area = :area";
            $params['area'] = $filters['area'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND (c.end_date IS NULL OR c.end_date >= :date_from)";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND (c.start_date IS NULL OR c.start_date <= :date_to)";
            $params['date_to'] = $filters['date_to'];
        }

        $rows = $this->db->query($sql, $params);

        return $rows[0] ?? [
            'completed'   => 0,
            'in_progress' => 0,
            'not_started' => 0,
            'cancelled'   => 0,
        ];
    }

    /**
     * Get distinct areas available in the database.
     */
    public function getDistinctAreas(): array
    {
        return $this->db->query(
            "SELECT DISTINCT area FROM cohorts WHERE area IS NOT NULL ORDER BY area"
        );
    }
}
