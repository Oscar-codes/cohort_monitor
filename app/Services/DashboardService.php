<?php

namespace App\Services;

use App\Repositories\CohortRepository;

/**
 * DashboardService
 *
 * Business logic for the dashboard summary.
 */
class DashboardService
{
    private CohortRepository $cohortRepo;

    public function __construct()
    {
        $this->cohortRepo = new CohortRepository();
    }

    /**
     * Aggregate summary statistics for the dashboard.
     *
     * @return array{totalCohorts: int, totalStudents: int, activeCohorts: int}
     */
    public function getSummaryStats(): array
    {
        $cohorts = $this->cohortRepo->findAll();

        $totalCohorts  = count($cohorts);
        $activeCohorts = count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'in_progress'));

        // Placeholder — student count would come from a StudentRepository
        $totalStudents = 0;

        return [
            'totalCohorts'  => $totalCohorts,
            'totalStudents' => $totalStudents,
            'activeCohorts' => $activeCohorts,
        ];
    }
}
