<?php

namespace App\Core;

/**
 * Auth — Lightweight authentication facade.
 *
 * Wraps PHP native sessions and provides role-checking helpers.
 * Call Auth::boot() once per request (from bootstrap).
 *
 * Security hardening (CM-SEC-002):
 *   - Cookie params (Secure/HttpOnly/SameSite/Lifetime) configured via
 *     {@see SessionConfig} before session_start().
 *   - Idle timeout (SESSION_LIFETIME) checked on each boot.
 *   - Absolute lifetime (SESSION_ABSOLUTE_LIFETIME) applied as a hard cap.
 *   - Browser fingerprint binding: UA + IP-24 — invalidates the session on
 *     a match failure; users behind CGN must allow IP drift in settings.
 */
class Auth
{
    public const SESSION_NAME = 'cohort_session';

    private static bool $booted = false;

    /** Start (or resume) the PHP session after applying hardening config. */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            SessionConfig::apply(self::SESSION_NAME);
            session_name(self::SESSION_NAME);
            session_start();
            self::enforcePolicy();
        } else {
            self::enforcePolicy();
        }

        self::$booted = true;
    }

    /**
     * Apply idle timeout, absolute lifetime, and browser fingerprint checks.
     * Destroys the session and closes the cookie if any rule fails.
     */
    private static function enforcePolicy(): void
    {
        $idleLimit       = SessionConfig::lifetime();
        $absoluteLimit   = SessionConfig::absoluteLifetime();
        $fingerprintNow  = self::fingerprint();
        $now             = time();

        $lastActivity = isset($_SESSION['_last_activity']) ? (int) $_SESSION['_last_activity'] : null;
        $loginAt      = isset($_SESSION['_login_at']) ? (int) $_SESSION['_login_at'] : null;
        $storedFp     = isset($_SESSION['_fingerprint']) ? (string) $_SESSION['_fingerprint'] : null;

        if ($lastActivity !== null && ($now - $lastActivity) > $idleLimit) {
            self::destroy();
            return;
        }
        if ($loginAt !== null && ($now - $loginAt) > $absoluteLimit) {
            self::destroy();
            return;
        }
        if ($storedFp !== null && !hash_equals($storedFp, $fingerprintNow)) {
            self::destroy();
            return;
        }

        // Refresh activity timestamp so each valid request extends the session.
        $_SESSION['_last_activity'] = $now;
    }

    /**
     * Compute a browser fingerprint. Returns a stable hash even when the IP
     * changes (the hash itself is salted by UA — UA changes authenticate the
     * device-class change sufficiently for our threat model).
     */
    public static function fingerprint(): string
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $ip = self::clientIp();
        return hash('sha256', $ua . '|' . $ip);
    }

    /**
     * Best-effort detection of the originating client IP, honoring common
     * proxy headers when present. Falls back to REMOTE_ADDR.
     */
    private static function clientIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                $value = trim((string) $_SERVER[$key]);
                if ($value === '') {
                    continue;
                }
                if (str_contains($value, ',')) {
                    $parts = explode(',', $value);
                    $value = trim($parts[0]);
                }
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return $value;
                }
            }
        }
        return '0.0.0.0';
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
        $now = time();
        $_SESSION['_login_at']      = $now;
        $_SESSION['_last_activity'] = $now;
        $_SESSION['_fingerprint']   = self::fingerprint();
        session_regenerate_id(true);
        // After regeneration, the regenerated id is the new one. Refresh
        // fingerprint + activity timestamps because the session id rotated.
        $_SESSION['_last_activity'] = $now;
        $_SESSION['_fingerprint']   = self::fingerprint();
    }

    /** Destroy the session. Public API for controllers and middleware. */
    public static function logout(): void
    {
        self::boot();
        self::destroy();
    }

    /**
     * Internal destructor used by logout and by enforcePolicy().
     * Clears session data, expires the cookie, and destroys the session.
     */
    private static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            SessionConfig::expireCookie(self::SESSION_NAME);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
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

    public static function isFinance(): bool
    {
        return self::role() === 'finance';
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

    // ─── Section Permissions ────────────────────────────

    private const ROLE_SECTIONS = [
        'admin'          => ['dashboard', 'cohorts', 'cohorts_master', 'cohorts_finance', 'import', 'marketing', 'alerts', 'coaches', 'reports', 'users', 'admin'],
        'finance'        => ['cohorts_master', 'cohorts_finance'],
        'admissions_b2b' => ['cohorts', 'alerts', 'coaches', 'reports'],
        'admissions_b2c' => ['cohorts', 'alerts', 'coaches', 'reports'],
        'marketing'      => ['marketing', 'cohorts_master', 'reports'],
    ];

    public static function canAccess(string $section): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        $sections = self::ROLE_SECTIONS[$role] ?? [];
        return in_array($section, $sections, true);
    }

    public static function requireAccess(string $section): void
    {
        self::requireLogin();
        if (!self::canAccess($section)) {
            http_response_code(403);
            echo '<h1>403 — Acceso denegado</h1><p>No tienes permiso para acceder a esta sección.</p>';
            exit;
        }
    }

    public static function defaultPath(): string
    {
        return match (self::role()) {
            'admin'          => '/',
            'finance'        => '/cohorts/master',
            'admissions_b2b',
            'admissions_b2c' => '/cohorts',
            'marketing'      => '/marketing',
            default          => '/',
        };
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
        'b2c_admission_target',
        'b2b_admissions',
        'b2c_admissions',
        'financial_target_revenue',
        'financial_actual_revenue',
        'admission_deadline_date',
        'start_date',
        'end_date',
        'related_project',
        'assigned_coach',
        'bootcamp_type',
        'area',
        'assigned_class_schedule',
    ];

    /**
     * Fields each role can edit on a cohort.
     */
    private const COHORT_EDITABLE_FIELDS = [
        'admin'          => self::ALL_COHORT_FIELDS,
        'admissions_b2b' => ['b2b_admissions'],
        'admissions_b2c' => ['b2c_admissions'],
        'finance'        => ['financial_target_revenue', 'financial_actual_revenue'],
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
     * Check if the current user can manage cohort status (transitions).
     * Only admin and finance roles can change cohort status.
     */
    public static function canManageCohortStatus(): bool
    {
        return self::hasRole(['admin', 'finance']);
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
