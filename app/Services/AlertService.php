<?php

namespace App\Services;

use App\Repositories\CommentRepository;
use App\Repositories\AuditRepository;
use App\Core\Auth;

/**
 * AlertService — Aggregates risk data for the admin alerts dashboard.
 */
class AlertService
{
    private CommentRepository        $commentRepo;
    private MarketingService         $marketingService;

    public function __construct()
    {
        $this->commentRepo    = new CommentRepository();
        $this->marketingService = new MarketingService();
    }

    /**
     * Get all alert data for the admin dashboard.
     */
    public function getAlertsSummary(): array
    {
        return [
            'risk_comments'       => $this->commentRepo->findAllRisks(),
            'at_risk_stages'      => $this->marketingService->getAtRiskStages(),
            'risks_by_cohort'     => $this->commentRepo->countRisksByCohort(),
        ];
    }

    /**
     * Add a risk comment to a cohort.
     */
    public function addComment(int $cohortId, string $body, string $category = 'general'): int
    {
        $auditRepo = new AuditRepository();

        $id = $this->commentRepo->create([
            'cohort_id' => $cohortId,
            'user_id'   => Auth::id(),
            'category'  => $category,
            'body'      => $body,
        ]);

        $auditRepo->log([
            'user_id'     => Auth::id(),
            'action'      => 'add_comment',
            'entity_type' => 'cohort_comment',
            'entity_id'   => $cohortId,
            'new_values'  => ['category' => $category, 'body' => mb_substr($body, 0, 100)],
        ]);

        return $id;
    }

    /**
     * Get comments for a specific cohort.
     */
    public function getCommentsForCohort(int $cohortId, ?string $category = null): array
    {
        return $this->commentRepo->findByCohort($cohortId, $category);
    }
}
