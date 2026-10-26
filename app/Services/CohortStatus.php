<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/**
 * CohortStatus — the single cohort status rule (DATA-001, decision D1).
 *
 *   cancelled (manual action, stored in cohorts.training_status) → Cancelado
 *   no start_date                                                → Sin fechas
 *   today <  start_date                                          → No iniciado
 *   start_date <= today and (no end_date or today <= end_date)   → En progreso
 *   today >  end_date                                            → Completado
 *
 * derive() (PHP) and sqlCase() (SQL) implement the same rule and must stay
 * side by side; tests/cohort_status_rule.php checks they always agree.
 * Every view, filter, count, report and export reads the status from here.
 */
final class CohortStatus
{
    public const NOT_STARTED = 'not_started';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';
    public const CANCELLED   = 'cancelled';
    public const NO_DATES    = 'no_dates';

    /** Business date for "today": El Salvador, independent of the app/DB timezone. */
    public const TIMEZONE = 'America/El_Salvador';

    /** Display order used by cards, filters and charts. */
    public const LABELS = [
        self::NOT_STARTED => 'No iniciado',
        self::IN_PROGRESS => 'En progreso',
        self::COMPLETED   => 'Completado',
        self::CANCELLED   => 'Cancelado',
        self::NO_DATES    => 'Sin fechas',
    ];

    /** Kodigo pill tones. */
    public const TONES = [
        self::NOT_STARTED => 'neutral',
        self::IN_PROGRESS => 'info',
        self::COMPLETED   => 'success',
        self::CANCELLED   => 'danger',
        self::NO_DATES    => 'warning',
    ];

    /** Bootstrap icons. */
    public const ICONS = [
        self::NOT_STARTED => 'bi-hourglass-split',
        self::IN_PROGRESS => 'bi-play-circle',
        self::COMPLETED   => 'bi-check-circle',
        self::CANCELLED   => 'bi-x-circle',
        self::NO_DATES    => 'bi-calendar-x',
    ];

    /** Statuses that still allow deleting the cohort (COH-009). */
    public const DELETABLE = [self::NOT_STARTED, self::NO_DATES, self::CANCELLED];

    private static ?string $frozenToday = null;
    private static ?DateTimeImmutable $frozenNow = null;

    /** Today's business date (Y-m-d) in El Salvador (UTC-6, no DST). */
    public static function today(): string
    {
        if (self::$frozenToday !== null) {
            return self::$frozenToday;
        }

        return (self::$frozenNow ?? new DateTimeImmutable('now'))
            ->setTimezone(new DateTimeZone(self::TIMEZONE))
            ->format('Y-m-d');
    }

    /** Pin "today" (tests only); null restores the real clock. */
    public static function freezeToday(?string $date): void
    {
        self::$frozenToday = $date === null ? null : self::assertDate($date);
    }

    /** Pin the current instant, e.g. '2026-10-06 00:00:00' UTC (tests only); null restores the real clock. */
    public static function freezeNow(?string $utcDateTime): void
    {
        self::$frozenNow = $utcDateTime === null ? null : new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
    }

    /**
     * Derive the status of one cohort.
     *
     * @param string|null $stored training_status as stored (only 'cancelled' matters)
     */
    public static function derive(?string $stored, ?string $startDate, ?string $endDate, ?string $today = null): string
    {
        $today ??= self::today();
        $start = self::dateOnly($startDate);
        $end = self::dateOnly($endDate);

        if ($stored === self::CANCELLED) {
            return self::CANCELLED;
        }
        if ($start === null) {
            return self::NO_DATES;
        }
        if ($today < $start) {
            return self::NOT_STARTED;
        }
        if ($end === null || $today <= $end) {
            return self::IN_PROGRESS;
        }

        return self::COMPLETED;
    }

    /** derive() for a cohort row (training_status, start_date, end_date). */
    public static function forRow(array $cohort, ?string $today = null): string
    {
        return self::derive(
            isset($cohort['training_status']) ? (string) $cohort['training_status'] : null,
            isset($cohort['start_date']) ? (string) $cohort['start_date'] : null,
            isset($cohort['end_date']) ? (string) $cohort['end_date'] : null,
            $today,
        );
    }

    /**
     * Same rule as derive() as a SQL CASE expression over table alias $alias.
     * "today" is inlined as a validated Y-m-d literal: native prepares
     * (EMULATE_PREPARES=false) do not allow reusing one named parameter.
     */
    public static function sqlCase(string $alias = 'c', ?string $today = null): string
    {
        if (preg_match('/^[a-z_]+$/', $alias) !== 1) {
            throw new \InvalidArgumentException('Invalid table alias.');
        }
        $t = self::assertDate($today ?? self::today());

        return "(CASE"
            . " WHEN {$alias}.training_status = 'cancelled' THEN 'cancelled'"
            . " WHEN {$alias}.start_date IS NULL THEN 'no_dates'"
            . " WHEN '{$t}' < {$alias}.start_date THEN 'not_started'"
            . " WHEN {$alias}.end_date IS NULL OR '{$t}' <= {$alias}.end_date THEN 'in_progress'"
            . " ELSE 'completed' END)";
    }

    /**
     * Value to persist in cohorts.training_status (kept in sync for Power BI).
     * A cancelled cohort stays cancelled unless $keepCancelled is false
     * (reactivation); 'no_dates' is stored as 'not_started' (ENUM column).
     */
    public static function storedValue(?string $stored, ?string $startDate, ?string $endDate, bool $keepCancelled = true): string
    {
        if ($keepCancelled && $stored === self::CANCELLED) {
            return self::CANCELLED;
        }

        $status = self::derive(null, $startDate, $endDate);
        return $status === self::NO_DATES ? self::NOT_STARTED : $status;
    }

    /**
     * Training progress by dates (DET-001), same "today" as the status rule:
     * 0 if today <= start, 100 if today >= end, otherwise elapsed / total days
     * rounded to 1 decimal. Null when start or end is missing.
     */
    public static function trainingProgress(?string $startDate, ?string $endDate, ?string $today = null): ?float
    {
        $start = self::dateOnly($startDate);
        $end = self::dateOnly($endDate);
        if ($start === null || $end === null) {
            return null;
        }

        $today ??= self::today();
        if ($today <= $start) {
            return 0.0;
        }
        if ($today >= $end) {
            return 100.0;
        }

        $startDay = new DateTimeImmutable($start);
        $elapsed = $startDay->diff(new DateTimeImmutable($today))->days;
        $total = $startDay->diff(new DateTimeImmutable($end))->days;

        return round($elapsed / $total * 100, 1);
    }

    /** trainingProgress() as shown in Detalle: "9.4 %", "Sin fecha de fin" or "Sin fechas". */
    public static function trainingProgressLabel(?string $startDate, ?string $endDate, ?string $today = null): string
    {
        if (self::dateOnly($startDate) === null) {
            return 'Sin fechas';
        }
        $progress = self::trainingProgress($startDate, $endDate, $today);

        return $progress === null ? 'Sin fecha de fin' : number_format($progress, 1, '.', '') . ' %';
    }

    /** today() + $days as Y-m-d (windows such as "next 30 days"). */
    public static function todayPlus(int $days, ?string $today = null): string
    {
        return (new DateTimeImmutable($today ?? self::today()))->modify(sprintf('%+d days', $days))->format('Y-m-d');
    }

    public static function label(?string $status): string
    {
        return self::LABELS[$status ?? ''] ?? '—';
    }

    public static function tone(?string $status): string
    {
        return self::TONES[$status ?? ''] ?? 'neutral';
    }

    public static function icon(?string $status): string
    {
        return self::ICONS[$status ?? ''] ?? 'bi-info-circle';
    }

    public static function isValid(?string $status): bool
    {
        return isset(self::LABELS[$status ?? '']);
    }

    private static function dateOnly(?string $date): ?string
    {
        $date = trim((string) $date);
        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        return substr($date, 0, 10);
    }

    private static function assertDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new \InvalidArgumentException('Invalid date for cohort status rule.');
        }

        return $date;
    }
}
