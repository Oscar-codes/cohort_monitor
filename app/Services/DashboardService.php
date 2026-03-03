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
        $cohorts = $this->cohortRepo->findAll();

        $totalCohorts   = count($cohorts);
        $activeCohorts  = count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'in_progress'));
        $completedCohorts = count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'completed'));
        $notStarted     = count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'not_started'));

        // Admission totals
        $totalTarget     = 0;
        $totalAdmissions = 0;
        foreach ($cohorts as $c) {
            $totalTarget     += (int) ($c['total_admission_target'] ?? 0);
            $totalAdmissions += (int) ($c['b2b_admissions'] ?? 0) + (int) ($c['b2c_admissions'] ?? 0);
        }
        $admissionPct = $totalTarget > 0 ? round(($totalAdmissions / $totalTarget) * 100, 1) : 0;

        // Risk alerts
        $riskComments  = $this->commentRepo->findAllRisks();
        $atRiskStages  = $this->marketingService->getAtRiskStages();
        $totalAlerts   = count($riskComments) + count($atRiskStages);

        // Upcoming cohorts (starting within next 30 days)
        $today     = date('Y-m-d');
        $in30days  = date('Y-m-d', strtotime('+30 days'));
        $upcoming  = array_filter($cohorts, function ($c) use ($today, $in30days) {
            $start = $c['start_date'] ?? null;
            return $start && $start >= $today && $start <= $in30days;
        });

        // Recent 5 cohorts
        $recentCohorts = array_slice($cohorts, 0, 5);

        // Cohorts by bootcamp type
        $byType = [];
        foreach ($cohorts as $c) {
            $type = $c['bootcamp_type'] ?? 'Sin tipo';
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        // Status breakdown for chart
        $statusBreakdown = [
            'in_progress' => $activeCohorts,
            'completed'   => $completedCohorts,
            'not_started' => $notStarted,
        ];

        return [
            'totalCohorts'      => $totalCohorts,
            'totalStudents'     => 0, // placeholder
            'activeCohorts'     => $activeCohorts,
            'completedCohorts'  => $completedCohorts,
            'notStartedCohorts' => $notStarted,
            'totalTarget'       => $totalTarget,
            'totalAdmissions'   => $totalAdmissions,
            'admissionPct'      => $admissionPct,
            'totalAlerts'       => $totalAlerts,
            'riskComments'      => array_slice($riskComments, 0, 5),
            'atRiskStages'      => array_slice($atRiskStages, 0, 5),
            'upcomingCohorts'   => array_values($upcoming),
            'recentCohorts'     => $recentCohorts,
            'byType'            => $byType,
            'statusBreakdown'   => $statusBreakdown,
        ];
    }
}
