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
     * Get all cohorts ordered by creation date.
     */
    public function findAll(): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts ORDER BY created_at DESC'
        );
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
     * Find cohorts by training status.
     */
    public function findByTrainingStatus(string $status): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts WHERE training_status = :status ORDER BY created_at DESC',
            ['status' => $status]
        );
    }

    /**
     * Find cohorts by bootcamp type.
     */
    public function findByBootcampType(string $type): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts WHERE bootcamp_type = :type ORDER BY created_at DESC',
            ['type' => $type]
        );
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
}
