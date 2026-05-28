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
                   WHERE cc.section_id = :cid';
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
                    COALESCE(cs.section_code, CONCAT("Sección ", cs.id)) AS cohort_name,
                    cs.section_code AS cohort_code,
                    cs.id AS cohort_id
             FROM cohort_comments cc
             JOIN users u   ON u.id = cc.user_id
             JOIN cohort_sections cs ON cs.id = cc.section_id
             WHERE cc.category = "risk"
             ORDER BY cc.created_at DESC'
        );
    }

    /** Create a comment. */
    public function create(array $data): int
    {
        $cohortRef = $this->resolveCohortRefBySection((int) $data['cohort_id']);
        if ($cohortRef === null) {
            throw new \RuntimeException('No se encontró relación cohort-section para el comentario.');
        }

        $this->db->execute(
            'INSERT INTO cohort_comments (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month, section_id, user_id, category, body, created_at)
             VALUES (:family_id, :type_code, :cohort_year, :cohort_month, :section_id, :user_id, :category, :body, NOW())',
            [
                'family_id'   => $cohortRef['bootcamp_family_id'],
                'type_code'   => $cohortRef['cohort_type_code'],
                'cohort_year' => $cohortRef['cohort_year'],
                'cohort_month'=> $cohortRef['cohort_month'],
                'section_id'  => $data['cohort_id'],
                'user_id'     => $data['user_id'],
                'category'    => $data['category'] ?? 'general',
                'body'        => $data['body'],
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /** Count risk items per cohort (for dashboard badges). */
    public function countRisksByCohort(): array
    {
        return $this->db->query(
            'SELECT section_id AS cohort_id, COUNT(*) AS risk_count
             FROM cohort_comments
             WHERE category = "risk"
             GROUP BY section_id'
        );
    }

    private function resolveCohortRefBySection(int $sectionId): ?array
    {
        $rows = $this->db->query(
            'SELECT bootcamp_family_id, cohort_type_code, cohort_year, cohort_month
             FROM cohort_section_memberships
             WHERE section_id = :sid
             ORDER BY cohort_year DESC, cohort_month DESC
             LIMIT 1',
            ['sid' => $sectionId]
        );

        return $rows[0] ?? null;
    }
}
