<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * CommentRepository — Data-access for cohort_comments table.
 */
class CommentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Get comments for a cohort, optionally filtered by category. */
    public function findByCohort(int $cohortId, ?string $category = null): array
    {
        $sql    = 'SELECT cc.*, u.full_name AS author_name, u.role AS author_role
                   FROM cohort_comments cc
                   JOIN users u ON u.id = cc.user_id
                   WHERE cc.cohort_id = :cid';
        $params = ['cid' => $cohortId];

        if ($category) {
            $sql            .= ' AND cc.category = :cat';
            $params['cat']   = $category;
        }

        $sql .= ' ORDER BY cc.created_at DESC';
        return $this->db->query($sql, $params);
    }

    /** Get ALL risk comments across cohorts (for alerts). */
    public function findAllRisks(): array
    {
        return $this->db->query(
            'SELECT cc.*, u.full_name AS author_name, u.role AS author_role,
                    c.name AS cohort_name, c.cohort_code
             FROM cohort_comments cc
             JOIN users u   ON u.id = cc.user_id
             JOIN cohorts c ON c.id = cc.cohort_id
             WHERE cc.category = "risk"
             ORDER BY cc.created_at DESC'
        );
    }

    /** Create a comment. */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO cohort_comments (cohort_id, user_id, category, body, created_at)
             VALUES (:cohort_id, :user_id, :category, :body, NOW())',
            [
                'cohort_id' => $data['cohort_id'],
                'user_id'   => $data['user_id'],
                'category'  => $data['category'] ?? 'general',
                'body'      => $data['body'],
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /** Count risk items per cohort (for dashboard badges). */
    public function countRisksByCohort(): array
    {
        return $this->db->query(
            'SELECT cohort_id, COUNT(*) AS risk_count
             FROM cohort_comments
             WHERE category = "risk"
             GROUP BY cohort_id'
        );
    }
}
