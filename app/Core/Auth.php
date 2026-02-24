<?php

namespace App\Core;

/**
 * Auth — Lightweight authentication facade.
 *
 * Wraps PHP native sessions and provides role-checking helpers.
 * Call Auth::boot() once per request (from bootstrap).
 */
class Auth
{
    private static bool $booted = false;

    /** Start (or resume) the PHP session. */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name('cohort_session');
            session_start();
        }

        self::$booted = true;
    }

    // ─── Session helpers ─────────────────────────────────

    /** Store the authenticated user data in session. */
    public static function login(array $user): void
    {
        self::boot();
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ];
        session_regenerate_id(true);
    }

    /** Destroy the session. */
    public static function logout(): void
    {
        self::boot();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Is there an authenticated user? */
    public static function check(): bool
    {
        self::boot();
        return isset($_SESSION['user']['id']);
    }

    /** Get the full session user array (or null). */
    public static function user(): ?array
    {
        self::boot();
        return $_SESSION['user'] ?? null;
    }

    /** Shortcut: current user ID. */
    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    /** Shortcut: current user role string. */
    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    // ─── Role checks ────────────────────────────────────

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isAdmissionsB2B(): bool
    {
        return self::role() === 'admissions_b2b';
    }

    public static function isAdmissionsB2C(): bool
    {
        return self::role() === 'admissions_b2c';
    }

    public static function isMarketing(): bool
    {
        return self::role() === 'marketing';
    }

    /**
     * Check whether the current user has one of the given roles.
     *
     * @param string|string[] $roles
     */
    public static function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array(self::role(), $roles, true);
    }

    // ─── Cohort Field Permissions ───────────────────────

    /**
     * All cohort fields that can be edited.
     */
    private const ALL_COHORT_FIELDS = [
        'cohort_code',
        'name',
        'correlative_number',
        'total_admission_target',
        'b2b_admission_target',
        'b2b_admissions',
        'b2c_admissions',
        'admission_deadline_date',
        'start_date',
        'end_date',
        'related_project',
        'assigned_coach',
        'bootcamp_type',
        'area',
        'assigned_class_schedule',
        'training_status',
    ];

    /**
     * Fields each role can edit on a cohort.
     */
    private const COHORT_EDITABLE_FIELDS = [
        'admin'          => self::ALL_COHORT_FIELDS,
        'admissions_b2b' => ['b2b_admissions'],
        'admissions_b2c' => ['b2c_admissions'],
        'marketing'      => [], // Marketing edits marketing_stages, not cohort fields
    ];

    /**
     * Get the list of cohort fields the current user can edit.
     *
     * @return string[]
     */
    public static function getEditableCohortFields(): array
    {
        $role = self::role();
        return self::COHORT_EDITABLE_FIELDS[$role] ?? [];
    }

    /**
     * Check if the current user can edit a specific cohort field.
     */
    public static function canEditCohortField(string $field): bool
    {
        return in_array($field, self::getEditableCohortFields(), true);
    }

    /**
     * Check if the current user can create new cohorts.
     */
    public static function canCreateCohort(): bool
    {
        return self::isAdmin();
    }

    /**
     * Check if the current user can delete cohorts.
     */
    public static function canDeleteCohort(): bool
    {
        return self::isAdmin();
    }

    /**
     * Check if the current user can edit any cohort field.
     */
    public static function canEditCohort(): bool
    {
        return count(self::getEditableCohortFields()) > 0;
    }

    /**
     * Filter an array of cohort data to only include fields the user can edit.
     * Returns only the fields that the current role is allowed to modify.
     *
     * @param array $data Full cohort data from form
     * @return array Filtered data with only editable fields
     */
    public static function filterEditableCohortData(array $data): array
    {
        $editable = self::getEditableCohortFields();
        return array_intersect_key($data, array_flip($editable));
    }

    // ─── Guards ─────────────────────────────────────────

    /** Redirect to /login if not authenticated. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /** Abort 403 if the current user doesn't hold one of the given roles. */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            http_response_code(403);
            echo '<h1>403 — Acceso denegado</h1><p>No tienes permiso para acceder a esta sección.</p>';
            exit;
        }
    }

    /** Flash a message into the session (for one-time display). */
    public static function flash(string $key, mixed $value): void
    {
        self::boot();
        $_SESSION['_flash'][$key] = $value;
    }

    /** Retrieve (and clear) a flash message. */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::boot();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
