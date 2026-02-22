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
     * Find cohorts by status.
     */
    public function findByStatus(string $status): array
    {
        return $this->db->query(
            'SELECT * FROM cohorts WHERE status = :status ORDER BY created_at DESC',
            ['status' => $status]
        );
    }

    /**
     * Insert a new cohort and return its ID.
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO cohorts (name, description, start_date, end_date, status, created_at, updated_at)
             VALUES (:name, :description, :start_date, :end_date, :status, NOW(), NOW())',
            [
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date'  => $data['start_date'] ?? null,
                'end_date'    => $data['end_date'] ?? null,
                'status'      => $data['status'] ?? 'active',
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
             SET name = :name,
                 description = :description,
                 start_date = :start_date,
                 end_date = :end_date,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id'          => $id,
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date'  => $data['start_date'] ?? null,
                'end_date'    => $data['end_date'] ?? null,
                'status'      => $data['status'] ?? 'active',
            ]
        );

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
     * Count cohorts, optionally filtered by status.
     */
    public function count(?string $status = null): int
    {
        if ($status) {
            $result = $this->db->query(
                'SELECT COUNT(*) as total FROM cohorts WHERE status = :status',
                ['status' => $status]
            );
        } else {
            $result = $this->db->query('SELECT COUNT(*) as total FROM cohorts');
        }

        return (int) ($result[0]['total'] ?? 0);
    }
}
