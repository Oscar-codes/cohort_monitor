<?php

namespace App\Services;

use App\Repositories\CohortRepository;
use App\Repositories\CommentRepository;

/**
 * DashboardService
 *
 * Business logic for the dashboard summary.
 */
class DashboardService
{
    private CohortRepository  $cohortRepo;
    private CommentRepository $commentRepo;
    private MarketingService  $marketingService;

    public function __construct()
    {
        $this->cohortRepo       = new CohortRepository();
        $this->commentRepo      = new CommentRepository();
        $this->marketingService = new MarketingService();
    }

    /**
     * Aggregate summary statistics for the dashboard.
     */
    public function getSummaryStats(): array
    {
        // Status counts (DATA-001) + the shared totals query with no filters
        // (DATA-003): same numbers Plan Maestro shows without filters.
        $stats  = $this->cohortRepo->getDashboardStats();
        $totals = $this->cohortRepo->getTotals();

        $totalTarget        = $totals['target'];
        $totalB2bAdmissions = $totals['b2b_admissions'];
        $totalB2cAdmissions = $totals['b2c_admissions'];
        $totalAdmissions    = $totals['admissions'];
        $admissionPct       = CohortService::admissionPct($totalAdmissions, $totalTarget);

        // Risk alerts
        $riskComments = $this->commentRepo->findAllRisks(5);
        $atRiskStages = $this->marketingService->getAtRiskStages(5);
        $totalAlerts  = $this->commentRepo->countAllRisks() + $this->marketingService->countAtRiskStages();

        // Upcoming cohorts (next 30 days) — dedicated query with LIMIT
        $upcoming = $this->cohortRepo->findUpcoming(30, 10);

        // Recent 5 cohorts
        $recentCohorts = $this->cohortRepo->findFirst(5);

        // Cohorts by bootcamp type — aggregated in SQL
        $typeRows = $this->cohortRepo->countByBootcampType();
        $byType = [];
        foreach ($typeRows as $row) {
            $byType[$row['bootcamp_type']] = (int) $row['total'];
        }

        $activeCohorts  = (int) $stats['in_progress'];
        $completedCohorts = (int) $stats['completed'];
        $notStarted     = (int) ($stats['not_started'] ?? 0);

        // DATA-001: one bucket per CohortStatus value, in its display order;
        // the buckets add up to the total.
        $statusBreakdown = [];
        foreach (array_keys(CohortStatus::LABELS) as $status) {
            $statusBreakdown[$status] = (int) ($stats[$status] ?? 0);
        }

        return [
            'totalCohorts'      => $totals['cohorts'],
            'totalStudents'     => 0,
            'activeCohorts'     => $activeCohorts,
            'completedCohorts'  => $completedCohorts,
            'plannedCohorts'    => $notStarted,
            'totalTarget'       => $totalTarget,
            'totalAdmissions'   => $totalAdmissions,
            'totalB2bAdmissions'=> $totalB2bAdmissions,
            'totalB2cAdmissions'=> $totalB2cAdmissions,
            'admissionPct'      => $admissionPct,
            'totalAlerts'       => $totalAlerts,
            'riskComments'      => $riskComments,
            'atRiskStages'      => $atRiskStages,
            'upcomingCohorts'   => $upcoming,
            'recentCohorts'     => $recentCohorts,
            'byType'            => $byType,
            'statusBreakdown'   => $statusBreakdown,
            'cutoffDate'        => CohortStatus::today(),
        ];
    }
}
