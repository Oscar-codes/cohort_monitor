<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\MarketingStageRepository;
use App\Repositories\AuditRepository;

/**
 * MarketingService — Business logic for marketing workflow stages.
 */
class MarketingService
{
    private MarketingStageRepository $stageRepo;
    private AuditRepository          $auditRepo;

    public const STAGE_LABELS = [
        'workflow_campaign' => 'Workflow Campaña',
        'campaign_build'    => 'Construcción de Campaña y Workflow',
        'campaign_start'    => 'Inicio de Campaña',
        'lead_funnel'       => 'Funnel de Leads',
    ];

    public const STATUS_LABELS = [
        'completed' => 'Completada',
        'pending'   => 'Pendiente a iniciar',
        'at_risk'   => 'En riesgo',
    ];

    public function __construct()
    {
        $this->stageRepo = new MarketingStageRepository();
        $this->auditRepo = new AuditRepository();
    }

    /**
     * Get stages for a cohort (ensure they exist first).
     */
    public function getStagesForCohort(int $cohortId): array
    {
        $this->stageRepo->ensureStagesForCohort($cohortId);
        return $this->stageRepo->findByCohort($cohortId);
    }

    /**
     * Update a single stage status.
     */
    public function updateStage(int $cohortId, string $stageName, string $status, ?string $riskNotes = null): void
    {
        $validStages   = array_keys(self::STAGE_LABELS);
        $validStatuses = array_keys(self::STATUS_LABELS);

        if (!in_array($stageName, $validStages, true)) {
            throw new \InvalidArgumentException('Etapa de marketing no válida.');
        }
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException('Estado no válido.');
        }
        if ($status === 'at_risk' && empty($riskNotes)) {
            throw new \InvalidArgumentException('Cuando el estado es "En riesgo" debe documentar la condición.');
        }

        // Get old state for audit
        $old = $this->stageRepo->findByCohort($cohortId);
        $oldStage = null;
        foreach ($old as $s) {
            if ($s['stage_name'] === $stageName) {
                $oldStage = $s;
                break;
            }
        }

        $this->stageRepo->upsert($cohortId, $stageName, $status, $riskNotes, Auth::id());

        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'update_marketing_stage',
            'entity_type' => 'marketing_stage',
            'entity_id'   => $cohortId,
            'old_values'  => $oldStage ? ['status' => $oldStage['status']] : null,
            'new_values'  => ['stage' => $stageName, 'status' => $status],
        ]);
    }

    /**
     * Get all at-risk stages (for admin alerts).
     */
    public function getAtRiskStages(): array
    {
        return $this->stageRepo->findAtRisk();
    }
}
