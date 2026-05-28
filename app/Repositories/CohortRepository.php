<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * CohortRepository
 *
 * Compatibility repository over the normalized schema.
 * Keeps the same public API consumed by services/controllers.
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
        $sql = $this->baseSelect() . ' ORDER BY cs.start_date IS NULL ASC, cs.start_date ASC, cs.id ASC';
        return $this->db->query($sql);
    }

    public function findByFilters(array $filters): array
    {
        $sql = $this->baseSelect();
        [$where, $params] = $this->buildFilters($filters);

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY cs.start_date IS NULL ASC, cs.start_date ASC, cs.id ASC';
        return $this->db->query($sql, $params);
    }

    public function findBootcampTypes(): array
    {
        $rows = $this->db->query(
            'SELECT DISTINCT COALESCE(b.bootcamp_name, bf.family_name) AS bootcamp_type
             FROM cohort_sections cs
             LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
             LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
             WHERE COALESCE(b.bootcamp_name, bf.family_name) IS NOT NULL
             ORDER BY bootcamp_type ASC'
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['bootcamp_type'],
            $rows
        ));
    }

    public function findProjectNames(): array
    {
        $rows = $this->db->query(
            'SELECT DISTINCT p.project_name AS related_project
             FROM cohort_sections cs
             JOIN projects p ON p.id = cs.project_id
             WHERE p.project_name IS NOT NULL AND p.project_name <> ""
             ORDER BY p.project_name ASC'
        );

        return array_values(array_map(
            static fn(array $row): string => (string) $row['related_project'],
            $rows
        ));
    }

    public function findById(int $id): ?array
    {
        $rows = $this->db->query(
            $this->baseSelect() . ' WHERE cs.id = :id LIMIT 1',
            ['id' => $id]
        );

        return $rows[0] ?? null;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $this->ensureCohortTypes();

            $routeId = $this->getOrCreateRouteId($data['area'] ?? null);
            $familyId = $this->getOrCreateBootcampFamilyId(
                $data['bootcamp_type'] ?? ($data['name'] ?? 'General'),
                $routeId
            );
            $bootcampId = $this->getOrCreateBootcampId(
                $familyId,
                $data['bootcamp_type'] ?? ($data['name'] ?? 'General')
            );
            $projectId = $this->getOrCreateProjectId($data['related_project'] ?? null);
            $coachId = $this->getOrCreateCoachId($data['assigned_coach'] ?? null);

            $sectionCode = trim((string) ($data['cohort_code'] ?? ''));
            if ($sectionCode === '') {
                $sectionCode = 'SEC-' . strtoupper(substr(uniqid(), -6));
            }

            $this->db->execute(
                'INSERT INTO cohort_sections (
                    section_code, bootcamp_id, project_id, coach_id,
                    start_date, end_date,
                    training_date_50, training_date_75,
                    b2b_target, b2c_target, total_students_target,
                    calendar_pattern, source_row_number, training_status,
                    status, created_at, updated_at
                ) VALUES (
                    :section_code, :bootcamp_id, :project_id, :coach_id,
                    :start_date, :end_date,
                    :training_date_50, :training_date_75,
                    :b2b_target, :b2c_target, :total_target,
                    :calendar_pattern, :source_row_number, :training_status,
                    "active", NOW(), NOW()
                )',
                [
                    'section_code'     => $sectionCode,
                    'bootcamp_id'      => $bootcampId,
                    'project_id'       => $projectId,
                    'coach_id'         => $coachId,
                    'start_date'       => $data['start_date'] ?? date('Y-m-d'),
                    'end_date'         => $data['end_date'] ?? ($data['start_date'] ?? date('Y-m-d')),
                    'training_date_50' => null,
                    'training_date_75' => null,
                    'b2b_target'       => (int) ($data['b2b_admission_target'] ?? 0),
                    'b2c_target'       => max(0, (int) ($data['b2c_admission_target'] ?? ((int) ($data['total_admission_target'] ?? 0) - (int) ($data['b2b_admission_target'] ?? 0)))),
                    'total_target'     => (int) ($data['total_admission_target'] ?? 0),
                    'calendar_pattern' => $data['assigned_class_schedule'] ?? null,
                    'source_row_number'=> (int) ($data['correlative_number'] ?? 0),
                    'training_status'  => $data['training_status'] ?? 'not_started',
                ]
            );

            $sectionId = (int) $this->db->lastInsertId();
            $this->upsertMemberships($sectionId, $familyId, $data);

            $this->db->commit();
            return $sectionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $row = $this->findById($id);
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $this->ensureCohortTypes();

            $routeId = $this->getOrCreateRouteId($data['area'] ?? ($row['area'] ?? null));
            $familyId = $this->getOrCreateBootcampFamilyId(
                $data['bootcamp_type'] ?? ($row['bootcamp_type'] ?? 'General'),
                $routeId
            );
            $bootcampId = $this->getOrCreateBootcampId($familyId, $data['bootcamp_type'] ?? ($row['bootcamp_type'] ?? 'General'));
            $projectId = $this->getOrCreateProjectId($data['related_project'] ?? ($row['related_project'] ?? null));
            $coachId = $this->getOrCreateCoachId($data['assigned_coach'] ?? ($row['assigned_coach'] ?? null));

            $rows = $this->db->execute(
                'UPDATE cohort_sections
                 SET section_code = :section_code,
                     bootcamp_id = :bootcamp_id,
                     project_id = :project_id,
                     coach_id = :coach_id,
                     start_date = :start_date,
                     end_date = :end_date,
                     b2b_target = :b2b_target,
                     b2c_target = :b2c_target,
                     total_students_target = :total_target,
                     calendar_pattern = :calendar_pattern,
                     source_row_number = :source_row_number,
                     training_status = :training_status,
                     updated_at = NOW()
                 WHERE id = :id',
                [
                    'id'              => $id,
                    'section_code'    => $data['cohort_code'] ?? $row['cohort_code'],
                    'bootcamp_id'     => $bootcampId,
                    'project_id'      => $projectId,
                    'coach_id'        => $coachId,
                    'start_date'      => $data['start_date'] ?? $row['start_date'],
                    'end_date'        => $data['end_date'] ?? $row['end_date'],
                    'b2b_target'      => (int) ($data['b2b_admission_target'] ?? $row['b2b_admission_target']),
                    'b2c_target'      => max(0, (int) ($data['b2c_admission_target'] ?? ((int) ($data['total_admission_target'] ?? $row['total_admission_target']) - (int) ($data['b2b_admission_target'] ?? $row['b2b_admission_target'])))),
                    'total_target'    => (int) ($data['total_admission_target'] ?? $row['total_admission_target']),
                    'calendar_pattern'=> $data['assigned_class_schedule'] ?? $row['assigned_class_schedule'],
                    'source_row_number'=> (int) ($data['correlative_number'] ?? $row['correlative_number']),
                    'training_status' => $data['training_status'] ?? $row['training_status'],
                ]
            );

            $this->upsertMemberships($id, $familyId, $data + $row);

            $this->db->commit();
            return $rows > 0;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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
        $rows = $this->db->execute('DELETE FROM cohort_sections WHERE id = :id', ['id' => $id]);
        return $rows > 0;
    }

    public function count(?string $trainingStatus = null): int
    {
        if ($trainingStatus) {
            $result = $this->db->query(
                'SELECT COUNT(*) AS total FROM cohort_sections WHERE training_status = :status',
                ['status' => $trainingStatus]
            );
        } else {
            $result = $this->db->query('SELECT COUNT(*) AS total FROM cohort_sections');
        }

        return (int) ($result[0]['total'] ?? 0);
    }

    public function getDashboardStats(): array
    {
        $rows = $this->db->query('
            SELECT
                COUNT(*)                                                          AS total,
                SUM(CASE WHEN cs.training_status = "in_progress" THEN 1 ELSE 0 END)  AS in_progress,
                SUM(CASE WHEN cs.training_status = "completed" THEN 1 ELSE 0 END)    AS completed,
                SUM(CASE WHEN cs.training_status = "not_started" THEN 1 ELSE 0 END)  AS not_started,
                COALESCE(SUM(cs.total_students_target), 0)                              AS total_target,
                COALESCE(SUM(m.b2b_admissions), 0)                                       AS total_b2b,
                COALESCE(SUM(m.b2c_admissions), 0)                                       AS total_b2c
            FROM cohort_sections cs
            LEFT JOIN (
                SELECT
                    section_id,
                    SUM(CASE WHEN cohort_type_code = "B2B" THEN actual_students ELSE 0 END) AS b2b_admissions,
                    SUM(CASE WHEN cohort_type_code = "B2C" THEN actual_students ELSE 0 END) AS b2c_admissions
                FROM cohort_section_memberships
                GROUP BY section_id
            ) m ON m.section_id = cs.id
        ');

        return $rows[0] ?? [
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'not_started' => 0,
            'total_target' => 0,
            'total_b2b' => 0,
            'total_b2c' => 0,
        ];
    }

    public function findUpcoming(int $days = 30, int $limit = 10): array
    {
        return $this->db->query(
            $this->baseSelect() . '
             WHERE cs.start_date IS NOT NULL
               AND cs.start_date >= CURDATE()
               AND cs.start_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY cs.start_date ASC
             LIMIT ' . (int) $limit,
            ['days' => $days]
        );
    }

    public function countByBootcampType(): array
    {
        return $this->db->query('
            SELECT COALESCE(b.bootcamp_name, bf.family_name, "Sin tipo") AS bootcamp_type, COUNT(*) AS total
            FROM cohort_sections cs
            LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
            LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
            GROUP BY COALESCE(b.bootcamp_name, bf.family_name, "Sin tipo")
            ORDER BY total DESC
        ');
    }

    public function findActiveCoaches(array $filters = []): array
    {
        $sql = $this->baseSelect() . '
            WHERE ch.coach_name IS NOT NULL
              AND ch.coach_name <> ""
              AND cs.start_date IS NOT NULL
              AND cs.end_date IS NOT NULL
              AND cs.start_date <= CURDATE()
              AND cs.end_date >= CURDATE()';
        $params = [];

        if (!empty($filters['coach'])) {
            $sql .= ' AND ch.coach_name = :coach';
            $params['coach'] = $filters['coach'];
        }

        if (!empty($filters['bootcamp_type'])) {
            $sql .= ' AND COALESCE(b.bootcamp_name, bf.family_name) = :bootcamp_type';
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        $sql .= ' ORDER BY ch.coach_name ASC, cs.start_date ASC';
        return $this->db->query($sql, $params);
    }

    public function findActiveCoachNames(): array
    {
        $rows = $this->db->query('
            SELECT DISTINCT ch.coach_name AS assigned_coach
            FROM cohort_sections cs
            JOIN coaches ch ON ch.id = cs.coach_id
            WHERE ch.coach_name IS NOT NULL
              AND ch.coach_name <> ""
              AND cs.start_date IS NOT NULL
              AND cs.end_date IS NOT NULL
              AND cs.start_date <= CURDATE()
              AND cs.end_date >= CURDATE()
            ORDER BY ch.coach_name ASC
        ');

        return array_map(static fn(array $row): string => (string) $row['assigned_coach'], $rows);
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                cs.id,
                COALESCE(NULLIF(cs.section_code, ""), CONCAT("SEC-", cs.id)) AS cohort_code,
                COALESCE(
                    CONCAT_WS(" - ", NULLIF(cs.section_code, ""), COALESCE(b.bootcamp_name, bf.family_name)),
                    CONCAT("Sección ", cs.id)
                ) AS name,
                COALESCE(cs.source_row_number, 0) AS correlative_number,
                COALESCE(cs.total_students_target, 0) AS total_admission_target,
                COALESCE(cs.b2b_target, 0) AS b2b_admission_target,
                COALESCE(cs.b2c_target, 0) AS b2c_admission_target,
                COALESCE(m.b2b_admissions, 0) AS b2b_admissions,
                COALESCE(m.b2c_admissions, 0) AS b2c_admissions,
                COALESCE(m.total_target_revenue, 0) AS financial_target_revenue,
                COALESCE(m.total_actual_revenue, 0) AS financial_actual_revenue,
                NULL AS admission_deadline_date,
                cs.start_date,
                cs.end_date,
                cs.start_time,
                cs.end_time,
                p.project_name AS related_project,
                ch.coach_name AS assigned_coach,
                COALESCE(b.bootcamp_name, bf.family_name) AS bootcamp_type,
                LOWER(r.route_name) AS area,
                cs.calendar_pattern AS assigned_class_schedule,
                COALESCE(cd.class_days, "—") AS class_days,
                CASE
                    WHEN cs.start_time IS NOT NULL AND cs.end_time IS NOT NULL THEN CONCAT(DATE_FORMAT(cs.start_time, "%H:%i"), " - ", DATE_FORMAT(cs.end_time, "%H:%i"))
                    WHEN cs.start_time IS NOT NULL THEN CONCAT(DATE_FORMAT(cs.start_time, "%H:%i"), " - ", "--:--")
                    ELSE "—"
                END AS class_time,
                cs.training_status,
                cs.training_date_50,
                cs.training_date_75,
                cs.status,
                cs.created_at,
                cs.updated_at
            FROM cohort_sections cs
            LEFT JOIN bootcamps b ON b.id = cs.bootcamp_id
            LEFT JOIN bootcamp_families bf ON bf.id = b.family_id
            LEFT JOIN routes r ON r.id = bf.route_id
            LEFT JOIN projects p ON p.id = cs.project_id
            LEFT JOIN coaches ch ON ch.id = cs.coach_id
            LEFT JOIN (
                SELECT
                    section_id,
                    SUM(CASE WHEN cohort_type_code = "B2B" THEN actual_students ELSE 0 END) AS b2b_admissions,
                    SUM(CASE WHEN cohort_type_code = "B2C" THEN actual_students ELSE 0 END) AS b2c_admissions,
                    COALESCE(SUM(target_revenue), 0) AS total_target_revenue,
                    COALESCE(SUM(actual_revenue), 0) AS total_actual_revenue
                FROM cohort_section_memberships
                GROUP BY section_id
            ) m ON m.section_id = cs.id
            LEFT JOIN (
                SELECT
                    ccd.section_id,
                    GROUP_CONCAT(w.day_name_es ORDER BY ccd.day_position SEPARATOR ", ") AS class_days
                FROM cohort_section_class_days ccd
                JOIN weekdays w ON w.id = ccd.weekday_id
                GROUP BY ccd.section_id
            ) cd ON cd.section_id = cs.id';
    }

    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(
                cs.section_code LIKE :search
                OR COALESCE(b.bootcamp_name, bf.family_name) LIKE :search
                OR ch.coach_name LIKE :search
                OR p.project_name LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['bootcamp_type'])) {
            $where[] = 'COALESCE(b.bootcamp_name, bf.family_name) = :bootcamp_type';
            $params['bootcamp_type'] = $filters['bootcamp_type'];
        }

        if (!empty($filters['related_project'])) {
            $where[] = 'p.project_name = :related_project';
            $params['related_project'] = $filters['related_project'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = '(cs.end_date IS NULL OR cs.end_date >= :start_date)';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = '(cs.start_date IS NULL OR cs.start_date <= :end_date)';
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['business_model'])) {
            if ($filters['business_model'] === 'b2b') {
                $where[] = 'cs.b2b_target > 0';
            }
            if ($filters['business_model'] === 'b2c') {
                $where[] = 'cs.total_students_target > cs.b2b_target';
            }
        }

        if (!empty($filters['cohort_status'])) {
            if ($filters['cohort_status'] === 'upcoming') {
                $where[] = 'cs.start_date IS NOT NULL AND cs.start_date > CURDATE()';
            }
            if ($filters['cohort_status'] === 'in_progress') {
                $where[] = 'cs.start_date IS NOT NULL AND cs.start_date <= CURDATE() AND (cs.end_date IS NULL OR cs.end_date >= CURDATE())';
            }
            if ($filters['cohort_status'] === 'completed') {
                $where[] = 'cs.end_date IS NOT NULL AND cs.end_date < CURDATE()';
            }
        }

        return [$where, $params];
    }

    private function upsertMemberships(int $sectionId, int $familyId, array $data): void
    {
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $year = (int) date('Y', strtotime($startDate));
        $month = (int) date('n', strtotime($startDate));

        $b2bTarget = max(0, (int) ($data['b2b_admission_target'] ?? 0));
        $totalTarget = max(0, (int) ($data['total_admission_target'] ?? 0));
        $b2cTarget = max(0, (int) ($data['b2c_admission_target'] ?? ($totalTarget - $b2bTarget)));
        $b2bActual = max(0, (int) ($data['b2b_admissions'] ?? 0));
        $b2cActual = max(0, (int) ($data['b2c_admissions'] ?? 0));
        $targetRevenue = (float) ($data['financial_target_revenue'] ?? 0);
        $actualRevenue = (float) ($data['financial_actual_revenue'] ?? 0);

        [$b2bTargetRevenue, $b2cTargetRevenue] = $this->splitRevenueByType($targetRevenue, $b2bTarget, $b2cTarget);
        [$b2bActualRevenue, $b2cActualRevenue] = $this->splitRevenueByType($actualRevenue, $b2bActual, $b2cActual);

        $this->db->execute('DELETE FROM cohort_section_memberships WHERE section_id = :sid', ['sid' => $sectionId]);

        if ($b2bTarget > 0 || $b2bActual > 0) {
            $this->ensureCohortExists($familyId, 'B2B', $year, $month);
            $this->db->execute(
                'INSERT INTO cohort_section_memberships (
                    section_id, bootcamp_family_id, cohort_type_code, cohort_year, cohort_month,
                    target_students, actual_students, target_revenue, actual_revenue
                ) VALUES (
                    :section_id, :family_id, "B2B", :cohort_year, :cohort_month, :target_students, :actual_students, :target_revenue, :actual_revenue
                )',
                [
                    'section_id'     => $sectionId,
                    'family_id'      => $familyId,
                    'cohort_year'    => $year,
                    'cohort_month'   => $month,
                    'target_students'=> $b2bTarget,
                    'actual_students'=> $b2bActual,
                    'target_revenue' => $b2bTargetRevenue,
                    'actual_revenue' => $b2bActualRevenue,
                ]
            );
        }

        if ($b2cTarget > 0 || $b2cActual > 0 || ($b2bTarget === 0 && $b2bActual === 0)) {
            $this->ensureCohortExists($familyId, 'B2C', $year, $month);
            $this->db->execute(
                'INSERT INTO cohort_section_memberships (
                    section_id, bootcamp_family_id, cohort_type_code, cohort_year, cohort_month,
                    target_students, actual_students, target_revenue, actual_revenue
                ) VALUES (
                    :section_id, :family_id, "B2C", :cohort_year, :cohort_month, :target_students, :actual_students, :target_revenue, :actual_revenue
                )',
                [
                    'section_id'     => $sectionId,
                    'family_id'      => $familyId,
                    'cohort_year'    => $year,
                    'cohort_month'   => $month,
                    'target_students'=> $b2cTarget,
                    'actual_students'=> $b2cActual,
                    'target_revenue' => $b2cTargetRevenue,
                    'actual_revenue' => $b2cActualRevenue,
                ]
            );
        }
    }

    /**
     * Split a section-level revenue amount between B2B and B2C by weights.
     *
     * @return array{0: float, 1: float}
     */
    private function splitRevenueByType(float $total, int $b2bWeight, int $b2cWeight): array
    {
        $total = max(0.0, $total);
        $sum = max(0, $b2bWeight) + max(0, $b2cWeight);

        if ($sum <= 0) {
            return [0.0, 0.0];
        }

        $b2b = round($total * ($b2bWeight / $sum), 2);
        $b2c = round($total - $b2b, 2);

        return [$b2b, $b2c];
    }

    private function ensureCohortTypes(): void
    {
        $this->db->execute('INSERT IGNORE INTO cohort_types (code, name) VALUES ("B2B", "B2B")');
        $this->db->execute('INSERT IGNORE INTO cohort_types (code, name) VALUES ("B2C", "B2C")');
    }

    private function ensureCohortExists(int $familyId, string $typeCode, int $year, int $month): void
    {
        $key = sprintf('%d-%s-%04d-%02d', $familyId, $typeCode, $year, $month);

        $this->db->execute(
            'INSERT INTO cohorts (
                bootcamp_family_id, cohort_type_code, cohort_year, cohort_month,
                cohort_key, status, created_at, updated_at
            ) VALUES (
                :family_id, :type_code, :cohort_year, :cohort_month,
                :cohort_key, "active", NOW(), NOW()
            ) ON DUPLICATE KEY UPDATE updated_at = NOW()',
            [
                'family_id'   => $familyId,
                'type_code'   => $typeCode,
                'cohort_year' => $year,
                'cohort_month'=> $month,
                'cohort_key'  => $key,
            ]
        );
    }

    private function getOrCreateRouteId(?string $area): ?int
    {
        $name = trim((string) $area);
        if ($name === '') {
            return null;
        }

        $rows = $this->db->query('SELECT id FROM routes WHERE route_name = :name LIMIT 1', ['name' => $name]);
        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        $this->db->execute('INSERT INTO routes (route_name) VALUES (:name)', ['name' => $name]);
        return (int) $this->db->lastInsertId();
    }

    private function getOrCreateBootcampFamilyId(?string $familyName, ?int $routeId): int
    {
        $name = trim((string) $familyName);
        if ($name === '') {
            $name = 'General';
        }

        $rows = $this->db->query('SELECT id FROM bootcamp_families WHERE family_name = :name LIMIT 1', ['name' => $name]);
        if (!empty($rows[0]['id'])) {
            $id = (int) $rows[0]['id'];
            if ($routeId !== null) {
                $this->db->execute('UPDATE bootcamp_families SET route_id = :route_id WHERE id = :id AND route_id IS NULL', [
                    'route_id' => $routeId,
                    'id' => $id,
                ]);
            }
            return $id;
        }

        $this->db->execute(
            'INSERT INTO bootcamp_families (family_name, route_id, created_at) VALUES (:name, :route_id, NOW())',
            ['name' => $name, 'route_id' => $routeId]
        );

        return (int) $this->db->lastInsertId();
    }

    private function getOrCreateBootcampId(int $familyId, ?string $bootcampName): int
    {
        $name = trim((string) $bootcampName);
        if ($name === '') {
            $name = 'Bootcamp ' . $familyId;
        }

        $rows = $this->db->query(
            'SELECT id FROM bootcamps WHERE family_id = :family_id AND bootcamp_name = :name LIMIT 1',
            ['family_id' => $familyId, 'name' => $name]
        );
        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $name), 0, 10));
        if ($code === '') {
            $code = 'BC' . $familyId;
        }
        $code .= '-' . strtoupper(substr(uniqid(), -4));

        $this->db->execute(
            'INSERT INTO bootcamps (family_id, bootcamp_code, bootcamp_name, is_active)
             VALUES (:family_id, :bootcamp_code, :bootcamp_name, 1)',
            [
                'family_id'     => $familyId,
                'bootcamp_code' => $code,
                'bootcamp_name' => $name,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    private function getOrCreateProjectId(?string $projectName): ?int
    {
        $name = trim((string) $projectName);
        if ($name === '') {
            return null;
        }

        $rows = $this->db->query('SELECT id FROM projects WHERE project_name = :name LIMIT 1', ['name' => $name]);
        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        $this->db->execute('INSERT INTO projects (project_name) VALUES (:name)', ['name' => $name]);
        return (int) $this->db->lastInsertId();
    }

    private function getOrCreateCoachId(?string $coachName): ?int
    {
        $name = trim((string) $coachName);
        if ($name === '') {
            return null;
        }

        $rows = $this->db->query('SELECT id FROM coaches WHERE coach_name = :name LIMIT 1', ['name' => $name]);
        if (!empty($rows[0]['id'])) {
            return (int) $rows[0]['id'];
        }

        $this->db->execute('INSERT INTO coaches (coach_name, is_active) VALUES (:name, 1)', ['name' => $name]);
        return (int) $this->db->lastInsertId();
    }
}
