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
    /** "Próximos inicios" window in days (DASH-012). */
    public const UPCOMING_DAYS = 30;

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
     * DASH-007: year of the admissions block. Only a 4-digit year present in
     * the cohort start dates is accepted; anything else falls back to the
     * current El Salvador year.
     *
     * @param string[] $availableYears
     */
    public static function resolveYear(mixed $requested, array $availableYears): string
    {
        $requested = is_string($requested) ? $requested : '';
        if (preg_match('/^\d{4}$/D', $requested) === 1 && in_array($requested, $availableYears, true)) {
            return $requested;
        }

        return substr(CohortStatus::today(), 0, 4);
    }

    /**
     * Aggregate summary statistics for the dashboard.
     *
     * @param mixed $requestedYear ?year= of the admissions block (DASH-007)
     */
    public function getSummaryStats(mixed $requestedYear = null): array
    {
        // Status counts (DATA-001) + the shared totals query with no filters
        // (DATA-003): same numbers Plan Maestro shows without filters.
        $stats  = $this->cohortRepo->getDashboardStats();
        $totals = $this->cohortRepo->getTotals();

        // DASH-007: the admissions block uses the same totals query filtered by
        // the calendar year of start_date; cohorts without start date are in no
        // year. Cancelled cohorts stay included, as in the global totals.
        $availableYears = $this->cohortRepo->findAvailableYears();
        $year = self::resolveYear($requestedYear, $availableYears);
        $yearOptions = array_values(array_unique(array_merge($availableYears, [$year])));
        rsort($yearOptions, SORT_STRING);
        $yearTotals = $this->cohortRepo->getTotals(['year' => $year]);

        $totalTarget        = $yearTotals['target'];
        $totalB2bAdmissions = $yearTotals['b2b_admissions'];
        $totalB2cAdmissions = $yearTotals['b2c_admissions'];
        $totalAdmissions    = $yearTotals['admissions'];
        $admissionPct       = CohortService::admissionPct($totalAdmissions, $totalTarget);
        $admissionShares    = CohortService::admissionShares($totalB2bAdmissions, $totalB2cAdmissions, $totalTarget);
        // Badge with over-admission: admissionPct stays capped at 100.0; the
        // admissions above the target are shown next to it ("+N sobre la meta").
        $overTarget         = $totalTarget > 0 ? max(0, $totalAdmissions - $totalTarget) : 0;

        // Risk alerts
        $riskComments = $this->commentRepo->findAllRisks(5);
        $atRiskStages = $this->marketingService->getAtRiskStages(5);
        $totalAlerts  = $this->commentRepo->countAllRisks() + $this->marketingService->countAtRiskStages();

        // DASH-012: upcoming starts in (today, today + 30]; the count uses the
        // same filter as the "Ver agenda" link (/cohorts?upcoming_days=30).
        $upcoming      = $this->cohortRepo->findUpcoming(self::UPCOMING_DAYS, 10);
        $upcomingCount = $this->cohortRepo->getTotals(['upcoming_days' => self::UPCOMING_DAYS])['cohorts'];

        // DASH-004: completed cohorts whose end_date falls in the year chosen in
        // the admissions block (DASH-007; default: current year), same filter
        // as the "Ver historial" link (/cohorts?closed_year=YYYY).
        $closedYear      = $year;
        $closedThisYear  = $this->cohortRepo->getTotals(['closed_year' => $closedYear])['cohorts'];

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
            'admissionShares'   => $admissionShares,
            'admissionsOverTarget' => $overTarget,
            'admissionsYear'    => $year,
            'admissionsYears'   => $yearOptions,
            'admissionsCohorts' => $yearTotals['cohorts'],
            'withoutStartDate'  => $this->cohortRepo->countWithoutStartDate(),
            'totalAlerts'       => $totalAlerts,
            'riskComments'      => $riskComments,
            'atRiskStages'      => $atRiskStages,
            'upcomingCohorts'   => $upcoming,
            'upcomingCount'     => $upcomingCount,
            'upcomingDays'      => self::UPCOMING_DAYS,
            'upcomingUntil'     => CohortStatus::todayPlus(self::UPCOMING_DAYS),
            'closedYear'        => $closedYear,
            'closedThisYear'    => $closedThisYear,
            'recentCohorts'     => $recentCohorts,
            'byType'            => $byType,
            'statusBreakdown'   => $statusBreakdown,
            'cutoffDate'        => CohortStatus::today(),
        ];
    }
}
