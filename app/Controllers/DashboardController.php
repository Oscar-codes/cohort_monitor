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
        $this->dashboardService = new DashboardService();
    }

    /**
     * Display the main dashboard.
     */
    public function index(): void
    {
        $stats = $this->dashboardService->getSummaryStats();

        $this->view('dashboard.index', [
            'pageTitle'    => 'Dashboard',
            'activePage'   => 'dashboard',
            'totalCohorts' => $stats['totalCohorts'],
            'totalStudents'=> $stats['totalStudents'],
            'activeCohorts'=> $stats['activeCohorts'],
        ]);
    }
}
