<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\MarketingStageRepository;
use App\Repositories\AuditRepository;
use App\Repositories\CohortMarketingInfoRepository;

/**
 * MarketingService — Business logic for marketing workflow stages.
 */
class MarketingService
{
    private MarketingStageRepository $stageRepo;
    private AuditRepository $auditRepo;
    private CohortMarketingInfoRepository $marketingInfoRepo;

    public const STAGE_LABELS = [
        'strategy'      => 'Estrategia',
        'content'       => 'Contenido',
        'ads'           => 'Paid Ads',
        'organic'       => 'OrgÃ¡nico',
        'events'        => 'Eventos',
        'partnerships'  => 'Partnerships',
        'analytics'     => 'AnalÃ­tica',
    ];

    public const STATUS_LABELS = [
        'active'    => 'Active',
        'completed' => 'Completed',
        'pending'   => 'Active',
        'at_risk'   => 'Active',
    ];

    public function __construct()
    {
        $this->stageRepo = new MarketingStageRepository();
        $this->auditRepo = new AuditRepository();
        $this->marketingInfoRepo = new CohortMarketingInfoRepository();
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
        $status = $this->normalizeStatus($status);
        $validStatuses = ['active', 'completed'];

        if (!in_array($stageName, $validStages, true)) {
            throw new \InvalidArgumentException('Etapa de marketing no vÃ¡lida.');
        }
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException('Estado no vÃ¡lido.');
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
            'entity_key'  => (string) $cohortId,
            'old_values'  => $oldStage ? ['status' => $oldStage['status']] : null,
            'new_values'  => ['stage' => $stageName, 'status' => $status],
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'active', 'pending', 'at_risk' => 'active',
            default => $status,
        };
    }

    /**
     * Get all at-risk stages (for admin alerts).
     */
    public function getAtRiskStages(): array
    {
        return $this->stageRepo->findAtRisk();
    }

    /**
     * Get marketing info for a cohort
     */
    public function getMarketingInfo(int $cohortId): ?array
    {
        return $this->marketingInfoRepo->findByCohort($cohortId);
    }

    /**
     * Update marketing info for a cohort
     */
    public function updateMarketingInfo(int $cohortId, array $data): void
    {
        $validCampaignStatuses = ['Completed', 'Active'];
        
        // Validate campaign_status
        if (!isset($data['campaign_status']) || !in_array($data['campaign_status'], $validCampaignStatuses, true)) {
            throw new \InvalidArgumentException('Estado de campaña no válido');
        }

        // Sanitize text fields
        $textFields = [
            'strategy_notes', 'content_notes', 'ads_notes', 'organic_notes',
            'events_notes', 'partnerships_notes', 'analytics_notes'
        ];
        
        $cleanData = ['campaign_status' => $data['campaign_status']];
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $cleanData[$field] = trim($data[$field]);
            }
        }

        $this->marketingInfoRepo->upsert($cohortId, $cleanData);

        // Log audit
        $this->auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'update_marketing_info',
            'entity_type' => 'cohort_marketing_info',
            'entity_key'  => (string) $cohortId,
            'old_values'  => null,
            'new_values'  => $cleanData,
        ]);
    }
}

