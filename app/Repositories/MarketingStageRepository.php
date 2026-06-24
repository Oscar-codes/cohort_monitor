<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * MarketingStageRepository — Data-access for marketing_stages table.
 */
class MarketingStageRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Get all stages for a cohort (always 4 rows). */
    public function findByCohort(int $cohortId): array
    {
        return $this->db->query(
            'SELECT DISTINCT ms.*, ms.stage_code AS stage_name, u.full_name AS updated_by_name
             FROM marketing_stages ms
             LEFT JOIN users u ON u.id = ms.updated_by
             JOIN cohort_section_memberships csm
               ON csm.bootcamp_family_id = ms.bootcamp_family_id
              AND csm.cohort_type_code = ms.cohort_type_code
              AND csm.cohort_year = ms.cohort_year
              AND csm.cohort_month = ms.cohort_month
             WHERE csm.section_id = :cid
             ORDER BY FIELD(ms.stage_code, "strategy","content","ads","organic","events","partnerships","analytics")',
            ['cid' => $cohortId]
        );
    }

    /** Upsert a single stage row. */
    public function upsert(int $cohortId, string $stageName, string $status, ?string $riskNotes, ?int $updatedBy): void
    {
        $cohortRef = $this->resolveCohortRefBySection($cohortId);
        if ($cohortRef === null) {
            throw new \RuntimeException('No se encontró la relación cohort-section para la sección seleccionada.');
        }

        $this->db->execute(
            'INSERT INTO marketing_stages (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month, stage_code, status, risk_notes, updated_by, created_at, updated_at)
             VALUES (:family_id, :type_code, :cohort_year, :cohort_month, :stage, :status, :risk, :uid, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                status     = VALUES(status),
                risk_notes = VALUES(risk_notes),
                updated_by = VALUES(updated_by),
                updated_at = NOW()',
            [
                'family_id'   => $cohortRef['bootcamp_family_id'],
                'type_code'   => $cohortRef['cohort_type_code'],
                'cohort_year' => $cohortRef['cohort_year'],
                'cohort_month'=> $cohortRef['cohort_month'],
                'stage'       => $stageName,
                'status'      => $status,
                'risk'        => $riskNotes,
                'uid'         => $updatedBy,
            ]
        );
    }

    /** Get all stages that are at_risk across all cohorts. */
    public function findAtRisk(): array
    {
        return $this->db->query(
                        'SELECT ms.*, ms.stage_code AS stage_name, cs.section_code AS cohort_code,
                                        COALESCE(cs.section_code, CONCAT("Sección ", cs.id)) AS cohort_name,
                                        cs.id AS cohort_id,
                                        u.full_name AS updated_by_name
             FROM marketing_stages ms
                         JOIN cohort_section_memberships csm
                             ON csm.bootcamp_family_id = ms.bootcamp_family_id
                            AND csm.cohort_type_code = ms.cohort_type_code
                            AND csm.cohort_year = ms.cohort_year
                            AND csm.cohort_month = ms.cohort_month
                         JOIN cohort_sections cs ON cs.id = csm.section_id
             LEFT JOIN users u ON u.id = ms.updated_by
             WHERE ms.status = "at_risk"
             ORDER BY ms.updated_at DESC'
        );
    }

    /** Ensure all 4 stages exist for a cohort (initialise if missing). */
    public function ensureStagesForCohort(int $cohortId): void
    {
        $cohortRef = $this->resolveCohortRefBySection($cohortId);
        if ($cohortRef === null) {
            return;
        }

        $stages = ['strategy', 'content', 'ads', 'organic', 'events', 'partnerships', 'analytics'];
        foreach ($stages as $s) {
            $this->db->execute(
                'INSERT IGNORE INTO marketing_stages (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month, stage_code, status)
                 VALUES (:family_id, :type_code, :cohort_year, :cohort_month, :stage, "active")',
                [
                    'family_id'   => $cohortRef['bootcamp_family_id'],
                    'type_code'   => $cohortRef['cohort_type_code'],
                    'cohort_year' => $cohortRef['cohort_year'],
                    'cohort_month'=> $cohortRef['cohort_month'],
                    'stage'       => $s,
                ]
            );
        }
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
