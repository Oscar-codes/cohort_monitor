<?php

namespace App\Services;

use App\Repositories\CohortRepository;
use DateTime;

/**
 * CoachCalendarService
 *
 * Business logic for the Coach Calendar view.
 * Enriches in-progress cohort data with calculated fields:
 * % completion, days remaining, duration, phase status.
 */
class CoachCalendarService
{
    private CohortRepository $cohortRepo;

    public function __construct()
    {
        $this->cohortRepo = new CohortRepository();
    }

    /**
     * Get active coach entries enriched with calculated fields.
     *
     * Each entry includes:
     * - All cohort fields from DB
     * - pct_completion (1-99)
     * - days_remaining
     * - days_elapsed
     * - duration_days
     * - phase_status (early | mid | advanced | finishing)
     */
    public function getActiveCoaches(array $filters = []): array
    {
        $rows = $this->cohortRepo->findActiveCoaches($filters);
        $today = new DateTime('today');

        return array_map(function (array $row) use ($today) {
            return $this->enrichCoachEntry($row, $today);
        }, $rows);
    }

    /**
     * Get list of distinct coach names for the filter dropdown.
     *
     * @return string[]
     */
    public function getActiveCoachNames(): array
    {
        return $this->cohortRepo->findActiveCoachNames();
    }

    /**
     * Get available bootcamp types from active coach cohorts.
     *
     * @return string[]
     */
    public function getBootcampTypes(): array
    {
        return $this->cohortRepo->findBootcampTypes();
    }

    /**
     * Group coach entries by coach name for the calendar view.
     *
     * @return array<string, array> coach_name => [entries]
     */
    public function groupByCoach(array $entries): array
    {
        $grouped = [];
        foreach ($entries as $entry) {
            $coach = $entry['assigned_coach'];
            $grouped[$coach][] = $entry;
        }
        return $grouped;
    }

    /**
     * Get summary KPIs for the calendar header.
     */
    public function getSummaryStats(array $entries): array
    {
        $coaches = [];
        $totalCohorts = count($entries);
        $avgCompletion = 0;
        $finishing = 0;

        foreach ($entries as $e) {
            $coaches[$e['assigned_coach']] = true;
            $avgCompletion += $e['pct_completion'];
            if ($e['phase_status'] === 'finishing') {
                $finishing++;
            }
        }

        return [
            'total_coaches'   => count($coaches),
            'total_cohorts'   => $totalCohorts,
            'avg_completion'  => $totalCohorts > 0 ? round($avgCompletion / $totalCohorts, 1) : 0,
            'finishing_soon'  => $finishing,
        ];
    }

    // ─── Private helpers ─────────────────────────────────

    /**
     * Enrich a cohort row with calculated coach calendar fields.
     */
    private function enrichCoachEntry(array $row, DateTime $today): array
    {
        $start = new DateTime($row['start_date']);
        $end   = new DateTime($row['end_date']);

        $totalDays   = max(1, (int) $start->diff($end)->days);
        $daysElapsed = max(0, (int) $start->diff($today)->days);
        $daysRemaining = max(0, (int) $today->diff($end)->days);

        // Clamp completion to 1-99 (guaranteed by SQL WHERE clause)
        $pct = min(99, max(1, (int) round(($daysElapsed / $totalDays) * 100)));

        // Phase: early (1-25%) | mid (26-50%) | advanced (51-75%) | finishing (76-99%)
        if ($pct <= 25) {
            $phase = 'early';
        } elseif ($pct <= 50) {
            $phase = 'mid';
        } elseif ($pct <= 75) {
            $phase = 'advanced';
        } else {
            $phase = 'finishing';
        }

        $row['pct_completion']  = $pct;
        $row['days_elapsed']    = $daysElapsed;
        $row['days_remaining']  = $daysRemaining;
        $row['duration_days']   = $totalDays;
        $row['phase_status']    = $phase;

        return $row;
    }
}
