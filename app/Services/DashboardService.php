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
        $totalAdmissions = (int) $stats['total_b2b'] + (int) $stats['total_b2c'];
        $admissionPct    = $totalTarget > 0 ? round(($totalAdmissions / $totalTarget) * 100, 1) : 0;

        // Risk alerts
        $riskComments = $this->commentRepo->findAllRisks();
        $atRiskStages = $this->marketingService->getAtRiskStages();
        $totalAlerts  = count($riskComments) + count($atRiskStages);

        // Upcoming cohorts (next 30 days) — dedicated query with LIMIT
        $upcoming = $this->cohortRepo->findUpcoming(30, 10);

        // Recent 5 cohorts
        $recentCohorts = $this->cohortRepo->findAll();
        $recentCohorts = array_slice($recentCohorts, 0, 5);

        // Cohorts by bootcamp type — aggregated in SQL
        $typeRows = $this->cohortRepo->countByBootcampType();
        $byType = [];
        foreach ($typeRows as $row) {
            $byType[$row['bootcamp_type']] = (int) $row['total'];
        }

        $activeCohorts  = (int) $stats['in_progress'];
        $completedCohorts = (int) $stats['completed'];
        $notStarted     = (int) $stats['not_started'];

        $statusBreakdown = [
            'in_progress' => $activeCohorts,
            'completed'   => $completedCohorts,
            'not_started' => $notStarted,
        ];

        return [
            'totalCohorts'      => (int) $stats['total'],
            'totalStudents'     => 0,
            'activeCohorts'     => $activeCohorts,
            'completedCohorts'  => $completedCohorts,
            'notStartedCohorts' => $notStarted,
            'totalTarget'       => $totalTarget,
            'totalAdmissions'   => $totalAdmissions,
            'admissionPct'      => $admissionPct,
            'totalAlerts'       => $totalAlerts,
            'riskComments'      => array_slice($riskComments, 0, 5),
            'atRiskStages'      => array_slice($atRiskStages, 0, 5),
            'upcomingCohorts'   => $upcoming,
            'recentCohorts'     => $recentCohorts,
            'byType'            => $byType,
            'statusBreakdown'   => $statusBreakdown,
        ];
    }
}
