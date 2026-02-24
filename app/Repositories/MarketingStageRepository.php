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
            'SELECT ms.*, u.full_name AS updated_by_name
             FROM marketing_stages ms
             LEFT JOIN users u ON u.id = ms.updated_by
             WHERE ms.cohort_id = :cid
             ORDER BY FIELD(ms.stage_name, "workflow_campaign","campaign_build","campaign_start","lead_funnel")',
            ['cid' => $cohortId]
        );
    }

    /** Upsert a single stage row. */
    public function upsert(int $cohortId, string $stageName, string $status, ?string $riskNotes, ?int $updatedBy): void
    {
        $this->db->execute(
            'INSERT INTO marketing_stages (cohort_id, stage_name, status, risk_notes, updated_by, created_at, updated_at)
             VALUES (:cid, :stage, :status, :risk, :uid, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                status     = VALUES(status),
                risk_notes = VALUES(risk_notes),
                updated_by = VALUES(updated_by),
                updated_at = NOW()',
            [
                'cid'   => $cohortId,
                'stage' => $stageName,
                'status'=> $status,
                'risk'  => $riskNotes,
                'uid'   => $updatedBy,
            ]
        );
    }

    /** Get all stages that are at_risk across all cohorts. */
    public function findAtRisk(): array
    {
        return $this->db->query(
            'SELECT ms.*, c.name AS cohort_name, c.cohort_code, u.full_name AS updated_by_name
             FROM marketing_stages ms
             JOIN cohorts c ON c.id = ms.cohort_id
             LEFT JOIN users u ON u.id = ms.updated_by
             WHERE ms.status = "at_risk"
             ORDER BY ms.updated_at DESC'
        );
    }

    /** Ensure all 4 stages exist for a cohort (initialise if missing). */
    public function ensureStagesForCohort(int $cohortId): void
    {
        $stages = ['workflow_campaign', 'campaign_build', 'campaign_start', 'lead_funnel'];
        foreach ($stages as $s) {
            $this->db->execute(
                'INSERT IGNORE INTO marketing_stages (cohort_id, stage_name, status)
                 VALUES (:cid, :stage, "pending")',
                ['cid' => $cohortId, 'stage' => $s]
            );
        }
    }
}
