<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * CohortRepository
 *
 * Data access layer for the new normalized `cohorts` table.
 * The `cohorts` table is now the single source of truth for cohort records
 * with the following shape:
 *
 *   id, cohort_code, name, correlative_number,
 *   total_admission_target, b2b_admission_target,
 *   b2b_admissions, b2c_admissions,
 *   admission_deadline_date, start_date, end_date,
 *   related_project, assigned_coach, bootcamp_type, area,
 *   assigned_class_schedule, training_status,
 *   created_at, updated_at
 *
 * Public API kept stable so the rest of the application (controllers,
 * services and views) does not need to change.
 */
class CohortRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        $sql = $this->baseSelect() . ' ORDER BY c.start_date IS NULL ASC, c.start_date ASC, c.id ASC';
        return $this->db->query($sql);
    }

    public function findByFilters(array $filters): array
    {
        $sql = $this->baseSelect();
        [$where, $params] = $this->buildFilters($filters);

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.start_date IS NULL ASC, c.start_date ASC, c.id ASC';
        return $this->db->query($sql, $params);
    }

    public function findBootcampTypes(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT c.bootcamp_type
             FROM cohorts c
             WHERE c.bootcamp_type IS NOT NULL AND c.bootcamp_type <> ''
             ORDER BY c.bootcamp_type ASC"
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['bootcamp_type'],
            $rows
        ));
    }

    public function findProjectNames(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT c.related_project
             FROM cohorts c
             WHERE c.related_project IS NOT NULL AND c.related_project <> ''
             ORDER BY c.related_project ASC"
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['related_project'],
            $rows
        ));
    }

    public function findCoachNames(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT c.assigned_coach
             FROM cohorts c
             WHERE c.assigned_coach IS NOT NULL AND c.assigned_coach <> ''
             ORDER BY c.assigned_coach ASC"
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['assigned_coach'],
            $rows
        ));
    }

    public function findById(int $id): ?array
    {
        $rows = $this->db->query(
            $this->baseSelect() . ' WHERE c.id = :id LIMIT 1',
            ['id' => $id]
        );

        return $rows[0] ?? null;
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO cohorts (
                cohort_code, name, correlative_number,
                total_admission_target, b2b_admission_target,
                b2b_admissions, b2c_admissions,
                admission_deadline_date, start_date, end_date,
                related_project, assigned_coach, bootcamp_type, area,
                assigned_class_schedule, training_status
            ) VALUES (
                :cohort_code, :name, :correlative_number,
                :total_admission_target, :b2b_admission_target,
                :b2b_admissions, :b2c_admissions,
                :admission_deadline_date, :start_date, :end_date,
                :related_project, :assigned_coach, :bootcamp_type, :area,
                :assigned_class_schedule, :training_status
            )',
            $this->prepareData($data)
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $rows = $this->db->execute(
            'UPDATE cohorts SET
                cohort_code = :cohort_code,
                name = :name,
                correlative_number = :correlative_number,
                total_admission_target = :total_admission_target,
                b2b_admission_target = :b2b_admission_target,
                b2b_admissions = :b2b_admissions,
                b2c_admissions = :b2c_admissions,
                admission_deadline_date = :admission_deadline_date,
                start_date = :start_date,
                end_date = :end_date,
                related_project = :related_project,
                assigned_coach = :assigned_coach,
                bootcamp_type = :bootcamp_type,
                area = :area,
                assigned_class_schedule = :assigned_class_schedule,
                training_status = :training_status
             WHERE id = :id',
            $this->prepareData($data) + ['id' => $id]
        );

        return $rows > 0;
    }

    public function updatePartial(int $id, array $data): bool
    {
        $current = $this->findById($id);
        if (!$current) {
            return false;
        }

        $merged = array_merge($current, $data);
        return $this->update($id, $merged);
    }

    public function delete(int $id): bool
    {
        $rows = $this->db->execute('DELETE FROM cohorts WHERE id = :id', ['id' => $id]);
        return $rows > 0;
    }

    public function count(?string $trainingStatus = null): int
    {
        if ($trainingStatus) {
            $result = $this->db->query(
                'SELECT COUNT(*) AS total FROM cohorts WHERE training_status = :status',
                ['status' => $trainingStatus]
            );
        } else {
            $result = $this->db->query('SELECT COUNT(*) AS total FROM cohorts');
        }

        return (int) ($result[0]['total'] ?? 0);
    }

    public function getDashboardStats(): array
    {
        $rows = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN c.training_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN c.training_status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN c.training_status = 'not_started' THEN 1 ELSE 0 END) AS not_started,
                SUM(CASE WHEN c.training_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                COALESCE(SUM(c.total_admission_target), 0) AS total_target,
                COALESCE(SUM(c.b2b_admissions), 0) AS total_b2b,
                COALESCE(SUM(c.b2c_admissions), 0) AS total_b2c
            FROM cohorts c
        ");

        return $rows[0] ?? [
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'not_started' => 0,
            'cancelled' => 0,
            'total_target' => 0,
            'total_b2b' => 0,
            'total_b2c' => 0,
        ];
    }

    public function findUpcoming(int $days = 30, int $limit = 10): array
    {
        return $this->db->query(
            $this->baseSelect() . '
             WHERE c.start_date IS NOT NULL
               AND c.start_date >= CURDATE()
               AND c.start_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY c.start_date ASC
             LIMIT ' . (int) $limit,
            ['days' => $days]
        );
    }

    public function countByBootcampType(): array
    {
        return $this->db->query("
            SELECT COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin tipo') AS bootcamp_type,
                   COUNT(*) AS total
            FROM cohorts c
            GROUP BY COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin tipo')
            ORDER BY total DESC
        ");
    }

    /**
     * Financial aggregation by cohort start month.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByMonth(array $filters = []): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(c.start_date, '%Y-%m') AS period_key,
                DATE_FORMAT(c.start_date, '%b %Y') AS period_label,
                COALESCE(SUM(c.b2b_admission_target), 0) AS target_revenue,
                COALESCE(SUM(c.b2b_admissions), 0) AS actual_revenue,
                COUNT(DISTINCT c.id) AS cohorts_total
            FROM cohorts c";

        [$where, $params] = $this->buildFilters($filters);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= "
            GROUP BY DATE_FORMAT(c.start_date, '%Y-%m'), DATE_FORMAT(c.start_date, '%b %Y')
            ORDER BY DATE_FORMAT(c.start_date, '%Y-%m') ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Financial aggregation by bootcamp.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByBootcamp(array $filters = []): array
    {
        $sql = "
            SELECT
                COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin cohorte') AS bootcamp_name,
                COALESCE(SUM(c.b2b_admission_target), 0) AS target_revenue,
                COALESCE(SUM(c.b2b_admissions), 0) AS actual_revenue,
                COUNT(DISTINCT c.id) AS cohorts_total
            FROM cohorts c";

        [$where, $params] = $this->buildFilters($filters);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= "
            GROUP BY COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin cohorte')
            ORDER BY actual_revenue DESC, bootcamp_name ASC";

        return $this->db->query($sql, $params);
    }

    public function findActiveCoaches(array $filters = []): array
    {
        $sql = $this->baseSelect() . "
            WHERE c.assigned_coach IS NOT NULL
              AND c.assigned_coach <> ''
              AND c.start_date IS NOT NULL
              AND c.end_date IS NOT NULL
              AND c.start_date <= CURDATE()
              AND c.end_date >= CURDATE()";
        $params = [];

        if (!empty($filters['coach'])) {
            $sql .= ' AND c.assigned_coach = :coach';
            $params['coach'] = $filters['coach'];
        }

        if (!empty($filters['bootcamp_type'])) {
            $sql .= ' AND c.bootcamp_type = :bootcamp_type';
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        $sql .= ' ORDER BY c.assigned_coach ASC, c.start_date ASC';
        return $this->db->query($sql, $params);
    }

    public function findActiveCoachNames(): array
    {
        $rows = $this->db->query("
            SELECT DISTINCT c.assigned_coach
            FROM cohorts c
            WHERE c.assigned_coach IS NOT NULL
              AND c.assigned_coach <> ''
              AND c.start_date IS NOT NULL
              AND c.end_date IS NOT NULL
              AND c.start_date <= CURDATE()
              AND c.end_date >= CURDATE()
            ORDER BY c.assigned_coach ASC
        ");

        return array_map(static fn(array $row): string => (string) $row['assigned_coach'], $rows);
    }

    /**
     * Returns the column list consumed by the rest of the application.
     * The schema field names are preserved to avoid breaking views/services.
     */
    private function baseSelect(): string
    {
        return "
            SELECT
                c.id,
                c.cohort_code,
                c.name,
                c.correlative_number,
                c.total_admission_target,
                c.b2b_admission_target,
                0 AS b2c_admission_target,
                c.b2b_admissions,
                c.b2c_admissions,
                0 AS financial_target_revenue,
                0 AS financial_actual_revenue,
                c.admission_deadline_date,
                c.start_date,
                c.end_date,
                NULL AS start_time,
                NULL AS end_time,
                c.related_project,
                c.assigned_coach,
                c.bootcamp_type,
                c.area,
                c.assigned_class_schedule,
                NULL AS class_days,
                NULL AS class_time,
                c.training_status,
                NULL AS training_date_50,
                NULL AS training_date_75,
                c.training_status AS status,
                c.created_at,
                c.updated_at
            FROM cohorts c";
    }

    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $searchValue = '%' . $this->escapeLike((string) $filters['search']) . '%';
            $where[] = '(
                c.cohort_code LIKE :s0
                OR c.bootcamp_type LIKE :s1
                OR c.name LIKE :s2
                OR c.assigned_coach LIKE :s3
                OR c.related_project LIKE :s4
            )';
            $params['s0'] = $searchValue;
            $params['s1'] = $searchValue;
            $params['s2'] = $searchValue;
            $params['s3'] = $searchValue;
            $params['s4'] = $searchValue;
        }

        if (!empty($filters['bootcamp_type'])) {
            $where[] = 'c.bootcamp_type = :bootcamp_type';
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        if (!empty($filters['related_project'])) {
            $where[] = 'c.related_project = :related_project';
            $params['related_project'] = $filters['related_project'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = '(c.end_date IS NULL OR c.end_date >= :start_date)';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = '(c.start_date IS NULL OR c.start_date <= :end_date)';
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['business_model'])) {
            if ($filters['business_model'] === 'b2b') {
                $where[] = 'c.b2b_admission_target > 0';
            }
            if ($filters['business_model'] === 'b2c') {
                $where[] = '(c.total_admission_target - c.b2b_admission_target) > 0';
            }
        }

        if (!empty($filters['cohort_status'])) {
            $map = [
                'not_started'    => "c.training_status = 'not_started'",
                'in_progress'    => "c.training_status = 'in_progress'",
                'completed'      => "c.training_status = 'completed'",
                'cancelled'      => "c.training_status = 'cancelled'",
            ];
            if (isset($map[$filters['cohort_status']])) {
                $where[] = $map[$filters['cohort_status']];
            }
        }

        return [$where, $params];
    }

    private function escapeLike(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '%'  => '\\%',
            '_'  => '\\_',
        ]);
    }

    /**
     * Normalize user-supplied data to the schema of the `cohorts` table.
     */
    private function prepareData(array $data): array
    {
        $allowedStatus = ['not_started', 'in_progress', 'completed', 'cancelled'];
        $allowedArea    = ['academic', 'marketing', 'admissions'];

        $status = $data['training_status'] ?? 'not_started';
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'not_started';
        }

        $area = $data['area'] ?? null;
        if ($area !== null && !in_array($area, $allowedArea, true)) {
            $area = null;
        }

        $totalTarget = max(0, (int) ($data['total_admission_target'] ?? 0));
        $b2bTarget   = max(0, (int) ($data['b2b_admission_target'] ?? 0));

        return [
            'cohort_code'              => trim((string) ($data['cohort_code'] ?? '')),
            'name'                     => trim((string) ($data['name'] ?? '')),
            'correlative_number'       => max(0, (int) ($data['correlative_number'] ?? 0)),
            'total_admission_target'   => $totalTarget,
            'b2b_admission_target'     => $b2bTarget,
            'b2b_admissions'           => max(0, (int) ($data['b2b_admissions'] ?? 0)),
            'b2c_admissions'           => max(0, (int) ($data['b2c_admissions'] ?? 0)),
            'admission_deadline_date'  => $this->normalizeDate($data['admission_deadline_date'] ?? null),
            'start_date'               => $this->normalizeDate($data['start_date'] ?? null) ?? date('Y-m-d'),
            'end_date'                 => $this->normalizeDate($data['end_date'] ?? null) ?? ($this->normalizeDate($data['start_date'] ?? null) ?? date('Y-m-d')),
            'related_project'          => $this->emptyToNull($data['related_project'] ?? null),
            'assigned_coach'           => $this->emptyToNull($data['assigned_coach'] ?? null),
            'bootcamp_type'            => $this->emptyToNull($data['bootcamp_type'] ?? null),
            'area'                     => $area,
            'assigned_class_schedule'  => $this->emptyToNull($data['assigned_class_schedule'] ?? null),
            'training_status'          => $status,
        ];
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }
        $ts = strtotime((string) $value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function emptyToNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}