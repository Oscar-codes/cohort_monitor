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
        // Single aggregation query instead of loading all cohorts
        $stats = $this->cohortRepo->getDashboardStats();

        $totalTarget     = (int) $stats['total_target'];
        $totalB2bAdmissions = (int) $stats['total_b2b'];
        $totalB2cAdmissions = (int) $stats['total_b2c'];
        $totalAdmissions = $totalB2bAdmissions + $totalB2cAdmissions;
        $admissionPct    = $totalTarget > 0 ? round(($totalAdmissions / $totalTarget) * 100, 1) : 0;

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
        $cancelled      = (int) ($stats['cancelled'] ?? 0);

        $statusBreakdown = [
            'in_progress' => $activeCohorts,
            'completed'   => $completedCohorts,
            'not_started' => $notStarted,
            'cancelled'   => $cancelled,
        ];

        return [
            'totalCohorts'      => (int) $stats['total'],
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
        ];
    }
}
