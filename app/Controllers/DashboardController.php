<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\DashboardService;

/**
 * DashboardController
 *
 * Handles the main dashboard view with summary statistics.
 */
class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        Auth::requireLogin();
        if (!Auth::isAdmin()) {
            header('Location: ' . Auth::defaultPath());
            exit;
        }
        $this->dashboardService = new DashboardService();
    }

    /**
     * Display the main dashboard.
     */
    public function index(): void
    {
        try {
            // DASH-007: ?year= of the admissions block, validated by the service.
            $stats = $this->dashboardService->getSummaryStats($this->input('year'));
        } catch (\Throwable $e) {
            $this->logException($e, 'DashboardController@index');
            http_response_code(500);

            // Safe fallbacks to keep the view functional.
            $stats = [
                'totalCohorts'      => 0,
                'totalStudents'     => 0,
                'activeCohorts'     => 0,
                'completedCohorts'  => 0,
                'plannedCohorts'    => 0,
                'totalTarget'       => 0,
                'totalAdmissions'   => 0,
                'totalB2bAdmissions'=> 0,
                'totalB2cAdmissions'=> 0,
                'admissionPct'      => 0,
                'admissionShares'   => \App\Services\CohortService::admissionShares(0, 0, 0),
                'admissionsCohorts' => 0,
                'withoutStartDate'  => 0,
                'totalAlerts'       => 0,
                'riskComments'      => [],
                'atRiskStages'      => [],
                'upcomingCohorts'   => [],
                'recentCohorts'     => [],
                'byType'            => [],
                'statusBreakdown'   => [],
                'loadError'         => 'No se pudo cargar el dashboard en este momento.',
            ];
        }

        $this->view('dashboard.index', array_merge(
            [
                'pageTitle'  => 'Dashboard',
                'activePage' => 'dashboard',
                'styles'     => [
                    '/assets/vendor/apexcharts/apexcharts.css',
                ],
                'scripts'    => [
                    '/assets/vendor/apexcharts/apexcharts.min.js',
                    '/assets/js/dashboard.js',
                ],
            ],
            $stats
        ));
    }
}
