<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\CohortStatus;

/**
 * CohortRepository
 *
 * Data access layer for the new normalized `cohorts` table.
 * The `cohorts` table is now the single source of truth for cohort records
 * with the following shape:
 *
 *   id, cohort_code, name, correlative_number,
 *   total_admission_target, b2b_admission_target, b2c_admission_target,
 *   b2b_admissions, b2c_admissions,
 *   incaf_enrolled, incaf_b2b, incaf_b2c,
 *   financial_target_revenue, financial_actual_revenue,
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

    /** First cohorts in the same order as findAll(), without loading the full list. */
    public function findFirst(int $limit = 5): array
    {
        $limit = max(1, min(100, $limit));
        return $this->db->query(
            $this->baseSelect() . ' ORDER BY c.start_date IS NULL ASC, c.start_date ASC, c.id ASC LIMIT ' . $limit
        );
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

    /**
     * Distinct months present in cohort start dates, newest first.
     *
     * @return string[] List of YYYY-MM keys
     */
    public function findAvailableMonths(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT DATE_FORMAT(c.start_date, '%Y-%m') AS period_key
             FROM cohorts c
             WHERE c.start_date IS NOT NULL
             ORDER BY period_key DESC"
        );

        return array_values(array_map(
            static fn(array $row): string => (string) ($row['period_key'] ?? ''),
            $rows
        ));
    }

    /**
     * Distinct calendar years present in cohort start dates, newest first.
     *
     * @return string[] List of YYYY keys
     */
    public function findAvailableYears(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT DATE_FORMAT(c.start_date, '%Y') AS period_key
             FROM cohorts c
             WHERE c.start_date IS NOT NULL
             ORDER BY period_key DESC"
        );

        return array_values(array_map(
            static fn(array $row): string => (string) ($row['period_key'] ?? ''),
            $rows
        ));
    }

    /** DASH-007: cohorts without start date, which belong to no year. */
    public function countWithoutStartDate(): int
    {
        return (int) ($this->db->query('SELECT COUNT(*) AS total FROM cohorts c WHERE c.start_date IS NULL')[0]['total'] ?? 0);
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
                total_admission_target, b2b_admission_target, b2c_admission_target,
                b2b_admissions, b2c_admissions,
                incaf_enrolled, incaf_b2b, incaf_b2c,
                financial_target_revenue, financial_actual_revenue,
                admission_deadline_date, start_date, end_date,
                related_project, assigned_coach, bootcamp_type, area,
                assigned_class_schedule, training_status
            ) VALUES (
                :cohort_code, :name, :correlative_number,
                :total_admission_target, :b2b_admission_target, :b2c_admission_target,
                :b2b_admissions, :b2c_admissions,
                :incaf_enrolled, :incaf_b2b, :incaf_b2c,
                :financial_target_revenue, :financial_actual_revenue,
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
                b2c_admission_target = :b2c_admission_target,
                b2b_admissions = :b2b_admissions,
                b2c_admissions = :b2c_admissions,
                incaf_enrolled = :incaf_enrolled,
                incaf_b2b = :incaf_b2b,
                incaf_b2c = :incaf_b2c,
                financial_target_revenue = :financial_target_revenue,
                financial_actual_revenue = :financial_actual_revenue,
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

    public function count(?string $status = null): int
    {
        if ($status) {
            $result = $this->db->query(
                'SELECT COUNT(*) AS total FROM cohorts c WHERE ' . CohortStatus::sqlCase('c') . ' = :status',
                ['status' => $status]
            );
        } else {
            $result = $this->db->query('SELECT COUNT(*) AS total FROM cohorts');
        }

        return (int) ($result[0]['total'] ?? 0);
    }

    /**
     * Cohort counts per status for the Dashboard (DATA-001 rule).
     * Totals of admissions / target / revenue come from getTotals().
     */
    public function getDashboardStats(): array
    {
        $status = CohortStatus::sqlCase('c');
        $row = $this->db->query("
            SELECT
                SUM(CASE WHEN {$status} = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN {$status} = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN {$status} = 'not_started' THEN 1 ELSE 0 END) AS not_started,
                SUM(CASE WHEN {$status} = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN {$status} = 'no_dates' THEN 1 ELSE 0 END) AS no_dates
            FROM cohorts c
        ")[0] ?? [];

        // SUM() over an empty table is NULL: report zeros instead.
        return array_map('intval', $row + array_fill_keys(array_keys(CohortStatus::LABELS), 0));
    }

    /**
     * DATA-003: the single totals query shared by Dashboard (no filters) and
     * Plan Maestro (its filters, same buildFilters() as the row list).
     * Cancelled cohorts and cohorts without dates or type are included.
     *
     * @return array{cohorts: int, target: int, b2b_admissions: int, b2c_admissions: int,
     *               admissions: int, revenue_target: float, revenue_actual: float}
     */
    public function getTotals(array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = 'SELECT
                COUNT(*) AS cohorts,
                COALESCE(SUM(c.total_admission_target), 0) AS target,
                COALESCE(SUM(c.b2b_admissions), 0) AS b2b_admissions,
                COALESCE(SUM(c.b2c_admissions), 0) AS b2c_admissions,
                COALESCE(SUM(c.financial_target_revenue), 0) AS revenue_target,
                COALESCE(SUM(c.financial_actual_revenue), 0) AS revenue_actual
            FROM cohorts c';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->db->query($sql, $params)[0] ?? [];
        $b2b = (int) ($row['b2b_admissions'] ?? 0);
        $b2c = (int) ($row['b2c_admissions'] ?? 0);

        return [
            'cohorts'        => (int) ($row['cohorts'] ?? 0),
            'target'         => (int) ($row['target'] ?? 0),
            'b2b_admissions' => $b2b,
            'b2c_admissions' => $b2c,
            'admissions'     => $b2b + $b2c,
            'revenue_target' => (float) ($row['revenue_target'] ?? 0),
            'revenue_actual' => (float) ($row['revenue_actual'] ?? 0),
        ];
    }

    /**
     * DASH-012: cohorts starting in (today, today + $days], cancelled excluded.
     * Same filter as /cohorts?upcoming_days=N, so the list and its link agree.
     */
    public function findUpcoming(int $days = 30, int $limit = 10): array
    {
        [$where, $params] = $this->buildFilters(['upcoming_days' => $days]);
        if ($where === []) {
            return [];
        }

        return $this->db->query(
            $this->baseSelect() . ' WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.start_date ASC, c.cohort_code ASC
             LIMIT ' . (int) $limit,
            $params
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
     * Returns financial revenue amounts for the filtered cohorts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByMonth(array $filters = []): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(c.start_date, '%Y-%m') AS period_key,
                DATE_FORMAT(c.start_date, '%b %Y') AS period_label,
                COALESCE(SUM(c.financial_target_revenue), 0) AS financial_target_revenue,
                COALESCE(SUM(c.financial_actual_revenue), 0) AS financial_actual_revenue,
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
     * Returns financial revenue amounts for the filtered cohorts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFinancialByBootcamp(array $filters = []): array
    {
        $sql = "
            SELECT
                COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin cohorte') AS bootcamp_name,
                COALESCE(SUM(c.financial_target_revenue), 0) AS financial_target_revenue,
                COALESCE(SUM(c.financial_actual_revenue), 0) AS financial_actual_revenue,
                COUNT(DISTINCT c.id) AS cohorts_total
            FROM cohorts c";

        [$where, $params] = $this->buildFilters($filters);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= "
            GROUP BY COALESCE(NULLIF(c.bootcamp_type, ''), 'Sin cohorte')
            ORDER BY COALESCE(SUM(c.financial_actual_revenue), 0) DESC, bootcamp_name ASC";

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
                c.b2c_admission_target,
                c.b2b_admissions,
                c.b2c_admissions,
                c.incaf_enrolled,
                c.incaf_b2b,
                c.incaf_b2c,
                c.financial_target_revenue,
                c.financial_actual_revenue,
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
                " . CohortStatus::sqlCase('c') . " AS effective_status,
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

        if (!empty($filters['month'])) {
            $where[] = "DATE_FORMAT(c.start_date, '%Y-%m') = :period_month";
            $params['period_month'] = (string) $filters['month'];
        }

        if (!empty($filters['year'])) {
            $where[] = "DATE_FORMAT(c.start_date, '%Y') = :period_year";
            $params['period_year'] = (string) $filters['year'];
        }

        if (isset($filters['target_min']) && $filters['target_min'] !== '') {
            $where[] = 'c.financial_target_revenue >= :target_min';
            $params['target_min'] = (float) $filters['target_min'];
        }

        if (isset($filters['target_max']) && $filters['target_max'] !== '') {
            $where[] = 'c.financial_target_revenue <= :target_max';
            $params['target_max'] = (float) $filters['target_max'];
        }

        // COH-003 / PM-003: the Desde/Hasta range applies to the START date only
        // (inclusive bounds, either side optional). A NULL start_date never
        // satisfies the comparison, so with any bound set cohorts without dates
        // are excluded; without a range they still show up as "Sin fechas".
        // Single rule for Cohortes, Plan Maestro, Finanzas, Marketing and exports.
        if (!empty($filters['start_date'])) {
            $where[] = 'c.start_date >= :start_date';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'c.start_date <= :end_date';
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['business_model'])) {
            if ($filters['business_model'] === 'b2b') {
                $where[] = 'c.b2b_admission_target > 0';
            }
            if ($filters['business_model'] === 'b2c') {
                // Compare instead of subtracting: both columns are INT UNSIGNED and
                // `total - b2b` overflows (BIGINT UNSIGNED out of range) when b2b > total.
                $where[] = 'c.total_admission_target > c.b2b_admission_target';
            }
        }

        if (!empty($filters['cohort_status']) && CohortStatus::isValid($filters['cohort_status'])) {
            $where[] = CohortStatus::sqlCase('c') . ' = :cohort_status';
            $params['cohort_status'] = $filters['cohort_status'];
        }

        // DASH-004: "Completado" with end_date inside the calendar year.
        // Independent of the start_date / end_date range filter.
        if (!empty($filters['closed_year']) && self::isValidClosedYear((string) $filters['closed_year'])) {
            $where[] = CohortStatus::sqlCase('c') . " = 'completed'";
            $where[] = 'c.end_date >= :closed_from AND c.end_date <= :closed_to';
            $params['closed_from'] = $filters['closed_year'] . '-01-01';
            $params['closed_to'] = $filters['closed_year'] . '-12-31';
        }

        // DASH-012: start_date in (today, today + N]; "No iniciado" already
        // means start_date > today and not cancelled.
        if (!empty($filters['upcoming_days']) && self::isValidUpcomingDays((string) $filters['upcoming_days'])) {
            $where[] = CohortStatus::sqlCase('c') . " = 'not_started'";
            $where[] = 'c.start_date <= :upcoming_to';
            $params['upcoming_to'] = CohortStatus::todayPlus((int) $filters['upcoming_days']);
        }

        return [$where, $params];
    }

    public static function isValidClosedYear(string $value): bool
    {
        return preg_match('/^(19|20)\d{2}$/', $value) === 1;
    }

    public static function isValidUpcomingDays(string $value): bool
    {
        return preg_match('/^\d{1,3}$/', $value) === 1 && (int) $value >= 1 && (int) $value <= 365;
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
        $allowedArea    = ['academic', 'marketing', 'admissions'];

        // DATA-001: missing dates stay NULL ("Sin fechas"); only dates the user
        // entered are written (previously NULL became today's date).
        $startDate = $this->normalizeDate($data['start_date'] ?? null);
        $endDate   = $this->normalizeDate($data['end_date'] ?? null);

        // DATA-001: training_status is kept in sync with the date rule (Power BI
        // reads it). Only 'cancelled' is a manual state and is never overwritten;
        // reactivation passes any other value and gets the status by dates.
        $status = CohortStatus::storedValue($data['training_status'] ?? null, $startDate, $endDate);

        $area = $data['area'] ?? null;
        if ($area !== null && !in_array($area, $allowedArea, true)) {
            $area = null;
        }

        $totalTarget = max(0, (int) ($data['total_admission_target'] ?? 0));
        $b2bTarget   = max(0, (int) ($data['b2b_admission_target'] ?? 0));
        $b2cTarget   = max(0, (int) ($data['b2c_admission_target'] ?? 0));

        return [
            'cohort_code'              => trim((string) ($data['cohort_code'] ?? '')),
            'name'                     => trim((string) ($data['name'] ?? '')),
            'correlative_number'       => max(0, (int) ($data['correlative_number'] ?? 0)),
            'total_admission_target'   => $totalTarget,
            'b2b_admission_target'     => $b2bTarget,
            'b2c_admission_target'     => $b2cTarget,
            'b2b_admissions'           => max(0, (int) ($data['b2b_admissions'] ?? 0)),
            'b2c_admissions'           => max(0, (int) ($data['b2c_admissions'] ?? 0)),
            'incaf_enrolled'           => $this->nullableNonNegativeInt($data['incaf_enrolled'] ?? null),
            'incaf_b2b'                => $this->nullableNonNegativeInt($data['incaf_b2b'] ?? null),
            'incaf_b2c'                => $this->nullableNonNegativeInt($data['incaf_b2c'] ?? null),
            'financial_target_revenue' => max(0.0, (float) ($data['financial_target_revenue'] ?? 0)),
            'financial_actual_revenue' => max(0.0, (float) ($data['financial_actual_revenue'] ?? 0)),
            'admission_deadline_date'  => $this->normalizeDate($data['admission_deadline_date'] ?? null),
            'start_date'               => $startDate,
            'end_date'                 => $endDate,
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

    private function nullableNonNegativeInt($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        return max(0, (int) $value);
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
