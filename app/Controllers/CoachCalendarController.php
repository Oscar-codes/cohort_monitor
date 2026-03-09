<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\CoachCalendarService;

/**
 * CoachCalendarController
 *
 * Admin-only view showing active coaches assigned to in-progress cohorts.
 * Provides calendar (timeline) and list views.
 */
class CoachCalendarController extends Controller
{
    private CoachCalendarService $calendarService;

    public function __construct()
    {
        Auth::requireLogin();
        Auth::requireRole('admin');
        $this->calendarService = new CoachCalendarService();
    }

    /**
     * Display the coach calendar / list view.
     */
    public function index(): void
    {
        $filters = [
            'coach'         => (string) $this->input('coach', ''),
            'bootcamp_type' => (string) $this->input('bootcamp_type', ''),
        ];

        $activeFilters = array_filter($filters, static fn($v) => $v !== '');

        $entries       = $this->calendarService->getActiveCoaches($activeFilters);
        $stats         = $this->calendarService->getSummaryStats($entries);
        $groupedByCoach = $this->calendarService->groupByCoach($entries);
        $coachNames    = $this->calendarService->getActiveCoachNames();
        $bootcampTypes = $this->calendarService->getBootcampTypes();

        $this->view('coaches.calendar', [
            'pageTitle'      => 'Calendario de Coaches',
            'activePage'     => 'coaches',
            'entries'        => $entries,
            'stats'          => $stats,
            'groupedByCoach' => $groupedByCoach,
            'filters'        => $filters,
            'activeFilters'  => $activeFilters,
            'coachNames'     => $coachNames,
            'bootcampTypes'  => $bootcampTypes,
        ]);
    }
}
