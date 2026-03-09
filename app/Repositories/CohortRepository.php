<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * CohortRepository
 *
 * Handles all database access for the Cohorts table.
 * This layer should contain no business logic — only queries.
 */
class CohortRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all cohorts ordered by start date (ascending).
     */
    public function findAll(): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts ORDER BY start_date IS NULL ASC, start_date ASC, id ASC'
        );
    }

    /**
     * Find cohorts using combinable filters with default start-date sorting.
     *
     * Supported filters:
     * - bootcamp_type: exact bootcamp type string
     * - start_date: "from" date — excludes cohorts that ended before this date (overlap)
     * - end_date: "to" date — excludes cohorts that start after this date (overlap)
     * - business_model: b2b | b2c
     * - cohort_status: upcoming | in_progress | completed
     */
    public function findByFilters(array $filters): array
    {
        $sql = 'SELECT * FROM cohorts';
        $where = [];
        $params = [];

        if (!empty($filters['bootcamp_type'])) {
            $where[] = 'bootcamp_type = :bootcamp_type';
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        if (!empty($filters['related_project'])) {
            $where[] = 'related_project = :related_project';
            $params['related_project'] = $filters['related_project'];
        }

        // Date range overlap: show cohorts active during [start_date, end_date]
        if (!empty($filters['start_date'])) {
            // Exclude cohorts that ended before the filter's start date
            $where[] = '(end_date IS NULL OR end_date >= :start_date)';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            // Exclude cohorts that start after the filter's end date
            $where[] = '(start_date IS NULL OR start_date <= :end_date)';
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['business_model'])) {
            if ($filters['business_model'] === 'b2b') {
                $where[] = '(b2b_admission_target > 0 OR b2b_admissions > 0)';
            }

            if ($filters['business_model'] === 'b2c') {
                $where[] = 'b2c_admissions > 0';
            }
        }

        if (!empty($filters['cohort_status'])) {
            if ($filters['cohort_status'] === 'upcoming') {
                $where[] = 'start_date IS NOT NULL AND start_date > CURDATE()';
            }

            if ($filters['cohort_status'] === 'in_progress') {
                $where[] = 'start_date IS NOT NULL AND start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())';
            }

            if ($filters['cohort_status'] === 'completed') {
                $where[] = 'end_date IS NOT NULL AND end_date < CURDATE()';
            }
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY start_date IS NULL ASC, start_date ASC, id ASC';

        return $this->db->query($sql, $params);
    }

    /**
     * Return all available bootcamp types for filtering.
     *
     * @return string[]
     */
    public function findBootcampTypes(): array
    {
        $rows = $this->db->query(
            'SELECT DISTINCT bootcamp_type
             FROM cohorts
               WHERE bootcamp_type IS NOT NULL AND bootcamp_type <> \'\'
             ORDER BY bootcamp_type ASC'
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['bootcamp_type'],
            $rows
        ));
    }

    /**
     * Return all available project names for filtering.
     *
     * @return string[]
     */
    public function findProjectNames(): array
    {
        $rows = $this->db->query(
            'SELECT DISTINCT related_project
             FROM cohorts
               WHERE related_project IS NOT NULL AND related_project <> \'\'
             ORDER BY related_project ASC'
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['related_project'],
            $rows
        ));
    }

    /**
     * Find a single cohort by ID.
     */
    public function findById(int $id): ?array
    {
        $results = $this->db->query(
            'SELECT * FROM cohorts WHERE id = :id LIMIT 1',
            ['id' => $id]
        );

        return $results[0] ?? null;
    }

    /**
     * Insert a new cohort and return its ID.
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO cohorts (
                cohort_code, name, correlative_number,
                total_admission_target, b2b_admission_target, b2b_admissions, b2c_admissions,
                admission_deadline_date, start_date, end_date,
                related_project, assigned_coach, bootcamp_type, area,
                assigned_class_schedule, training_status,
                created_at, updated_at
            ) VALUES (
                :cohort_code, :name, :correlative_number,
                :total_admission_target, :b2b_admission_target, :b2b_admissions, :b2c_admissions,
                :admission_deadline_date, :start_date, :end_date,
                :related_project, :assigned_coach, :bootcamp_type, :area,
                :assigned_class_schedule, :training_status,
                NOW(), NOW()
            )',
            [
                'cohort_code'              => $data['cohort_code'],
                'name'                     => $data['name'],
                'correlative_number'       => $data['correlative_number'] ?? 0,
                'total_admission_target'   => $data['total_admission_target'] ?? 0,
                'b2b_admission_target'     => $data['b2b_admission_target'] ?? 0,
                'b2b_admissions'           => $data['b2b_admissions'] ?? 0,
                'b2c_admissions'           => $data['b2c_admissions'] ?? 0,
                'admission_deadline_date'  => $data['admission_deadline_date'] ?? null,
                'start_date'               => $data['start_date'] ?? null,
                'end_date'                 => $data['end_date'] ?? null,
                'related_project'          => $data['related_project'] ?? null,
                'assigned_coach'           => $data['assigned_coach'] ?? null,
                'bootcamp_type'            => $data['bootcamp_type'] ?? null,
                'area'                     => $data['area'] ?? null,
                'assigned_class_schedule'  => $data['assigned_class_schedule'] ?? null,
                'training_status'          => $data['training_status'] ?? 'not_started',
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing cohort.
     */
    public function update(int $id, array $data): bool
    {
        $rows = $this->db->execute(
            'UPDATE cohorts
             SET cohort_code              = :cohort_code,
                 name                     = :name,
                 correlative_number       = :correlative_number,
                 total_admission_target   = :total_admission_target,
                 b2b_admission_target     = :b2b_admission_target,
                 b2b_admissions           = :b2b_admissions,
                 b2c_admissions           = :b2c_admissions,
                 admission_deadline_date  = :admission_deadline_date,
                 start_date               = :start_date,
                 end_date                 = :end_date,
                 related_project          = :related_project,
                 assigned_coach           = :assigned_coach,
                 bootcamp_type            = :bootcamp_type,
                 area                     = :area,
                 assigned_class_schedule  = :assigned_class_schedule,
                 training_status          = :training_status,
                 updated_at               = NOW()
             WHERE id = :id',
            [
                'id'                       => $id,
                'cohort_code'              => $data['cohort_code'],
                'name'                     => $data['name'],
                'correlative_number'       => $data['correlative_number'] ?? 0,
                'total_admission_target'   => $data['total_admission_target'] ?? 0,
                'b2b_admission_target'     => $data['b2b_admission_target'] ?? 0,
                'b2b_admissions'           => $data['b2b_admissions'] ?? 0,
                'b2c_admissions'           => $data['b2c_admissions'] ?? 0,
                'admission_deadline_date'  => $data['admission_deadline_date'] ?? null,
                'start_date'               => $data['start_date'] ?? null,
                'end_date'                 => $data['end_date'] ?? null,
                'related_project'          => $data['related_project'] ?? null,
                'assigned_coach'           => $data['assigned_coach'] ?? null,
                'bootcamp_type'            => $data['bootcamp_type'] ?? null,
                'area'                     => $data['area'] ?? null,
                'assigned_class_schedule'  => $data['assigned_class_schedule'] ?? null,
                'training_status'          => $data['training_status'] ?? 'not_started',
            ]
        );

        return $rows > 0;
    }

    /**
     * Update only specific fields of a cohort (partial update).
     * Dynamically builds the SQL query based on provided fields.
     *
     * @param int   $id   Cohort ID
     * @param array $data Associative array of field => value to update
     * @return bool Success status
     */
    public function updatePartial(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Build SET clause dynamically
        $setClauses = [];
        $params = ['id' => $id];

        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        $setString = implode(', ', $setClauses);
        $sql = "UPDATE cohorts SET {$setString}, updated_at = NOW() WHERE id = :id";

        $rows = $this->db->execute($sql, $params);
        return $rows > 0;
    }

    /**
     * Delete a cohort by ID.
     */
    public function delete(int $id): bool
    {
        $rows = $this->db->execute(
            'DELETE FROM cohorts WHERE id = :id',
            ['id' => $id]
        );

        return $rows > 0;
    }

    /**
     * Count cohorts, optionally filtered by training status.
     */
    public function count(?string $trainingStatus = null): int
    {
        if ($trainingStatus) {
            $result = $this->db->query(
                'SELECT COUNT(*) as total FROM cohorts WHERE training_status = :status',
                ['status' => $trainingStatus]
            );
        } else {
            $result = $this->db->query('SELECT COUNT(*) as total FROM cohorts');
        }

        return (int) ($result[0]['total'] ?? 0);
    }

    /**
     * Get aggregated dashboard stats in a single query.
     *
     * @return array{total: int, in_progress: int, completed: int, not_started: int, total_target: int, total_b2b: int, total_b2c: int}
     */
    public function getDashboardStats(): array
    {
        $rows = $this->db->query("
            SELECT
                COUNT(*)                                                              AS total,
                SUM(CASE WHEN training_status = 'in_progress' THEN 1 ELSE 0 END)     AS in_progress,
                SUM(CASE WHEN training_status = 'completed'   THEN 1 ELSE 0 END)     AS completed,
                SUM(CASE WHEN training_status = 'not_started' THEN 1 ELSE 0 END)     AS not_started,
                COALESCE(SUM(total_admission_target), 0)                              AS total_target,
                COALESCE(SUM(b2b_admissions), 0)                                      AS total_b2b,
                COALESCE(SUM(b2c_admissions), 0)                                      AS total_b2c
            FROM cohorts
        ");

        return $rows[0] ?? [
            'total' => 0, 'in_progress' => 0, 'completed' => 0, 'not_started' => 0,
            'total_target' => 0, 'total_b2b' => 0, 'total_b2c' => 0,
        ];
    }

    /**
     * Get upcoming cohorts starting within the next N days.
     */
    public function findUpcoming(int $days = 30, int $limit = 10): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts
             WHERE start_date IS NOT NULL AND start_date >= CURDATE() AND start_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY start_date ASC
             LIMIT ' . (int) $limit,
            ['days' => $days]
        );
    }

    /**
     * Get cohort counts grouped by bootcamp type.
     */
    public function countByBootcampType(): array
    {
        return $this->db->query("
            SELECT COALESCE(bootcamp_type, 'Sin tipo') AS bootcamp_type, COUNT(*) AS total
            FROM cohorts
            GROUP BY bootcamp_type
            ORDER BY total DESC
        ");
    }

    /**
     * Get cohorts with active coaches (in-progress: started but not ended).
     *
     * Filters for cohorts where:
     * - assigned_coach is not empty
     * - start_date <= today (already started)
     * - end_date >= today (not yet finished)
     * This yields % completion between 1-99%.
     *
     * Optional filters: coach name, bootcamp_type.
     */
    public function findActiveCoaches(array $filters = []): array
    {
        $sql = "
            SELECT *
            FROM cohorts
            WHERE assigned_coach IS NOT NULL
              AND assigned_coach <> ''
              AND start_date IS NOT NULL
              AND end_date IS NOT NULL
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
        ";
        $params = [];

        if (!empty($filters['coach'])) {
            $sql .= " AND assigned_coach = :coach";
            $params['coach'] = $filters['coach'];
        }

        if (!empty($filters['bootcamp_type'])) {
            $sql .= " AND bootcamp_type = :bootcamp_type";
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        $sql .= " ORDER BY assigned_coach ASC, start_date ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get distinct active coach names from in-progress cohorts.
     *
     * @return string[]
     */
    public function findActiveCoachNames(): array
    {
        $rows = $this->db->query("
            SELECT DISTINCT assigned_coach
            FROM cohorts
            WHERE assigned_coach IS NOT NULL
              AND assigned_coach <> ''
              AND start_date IS NOT NULL
              AND end_date IS NOT NULL
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
            ORDER BY assigned_coach ASC
        ");

        return array_map(fn(array $row) => $row['assigned_coach'], $rows);
    }
}
