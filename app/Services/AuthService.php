<?php

namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;

/**
 * AuthService — Login / logout business logic.
 *
 * CM-SEC-003 changes:
 *   - Removes plaintext password fallback (legacy migration path).
 *   - Tracks failed attempts via {@see LoginAttemptService} for rate-limit
 *     and lockout decisions.
 *   - Treats missing user, inactive user, missing hash and wrong password
 *     as the SAME outcome (`null`) with the SAME generic reason at
 *     controller level, to prevent user enumeration.
 *   - Audits both successful and failed logins with reason enums so an
 *     admin can detect brute-force activity without revealing which
 *     account was targeted.
 */
class AuthService
{
    private ?UserRepository       $userRepo = null;
    private ?AuditRepository      $auditRepo = null;
    private ?LoginAttemptService $attemptSvc = null;

    public function __construct()
    {
    }

    private function userRepo(): UserRepository
    {
        if ($this->userRepo === null) {
            $this->userRepo = new UserRepository();
        }

        return $this->userRepo;
    }

    private function auditRepo(): AuditRepository
    {
        if ($this->auditRepo === null) {
            $this->auditRepo = new AuditRepository();
        }

        return $this->auditRepo;
    }

    private function attemptSvc(): LoginAttemptService
    {
        if ($this->attemptSvc === null) {
            $this->attemptSvc = new LoginAttemptService();
        }

        return $this->attemptSvc;
    }

    /**
     * Attempt to authenticate a user.
     *
     * @return array|null  User row on success, null on any failure.
     */
    public function attempt(string $identifier, string $password): ?array
    {
        $normalizedIdentifier = trim($identifier);
        $ip = self::clientIp();

        if ($normalizedIdentifier === '' || $password === '') {
            return null;
        }

        // Rate-limit gate: lock short-circuits everything else.
        if ($this->attemptSvc()->isLocked($normalizedIdentifier, $ip)) {
            error_log('[auth] login blocked by lockout for ip=' . $ip);
            try {
                $this->auditRepo()->log([
                    'user_id'     => null,
                    'action'      => 'login_blocked',
                    'entity_type' => 'auth',
                    'new_values'  => ['reason' => 'lockout'],
                ]);
            } catch (\Throwable $e) {
                error_log('[auth] lockout audit failed: ' . $e->getMessage());
            }
            return null;
        }

        $user = $this->userRepo()->findByLoginIdentifier($normalizedIdentifier);
        $hash = is_array($user) ? (string) ($user['password_hash'] ?? '') : '';
        $isValidHash = $hash !== '' && password_verify($password, $hash);

        if (!$user || !$isValidHash || empty($user['is_active'])) {
            $reason = !$user
                ? $this->attemptSvc()->reasonForInvalid()
                : (!$isValidHash
                    ? ($hash === '' ? $this->attemptSvc()->reasonForBadHash() : $this->attemptSvc()->reasonForInvalid())
                    : $this->attemptSvc()->reasonForInvalid());
            $this->attemptSvc()->recordFailure($normalizedIdentifier, $ip, $reason);
            error_log('[auth] login failed reason=' . $reason . ' ip=' . $ip);
            return null;
        }

        // Constant-time upgrade: if the hash used a different cost than the
        // current default, refresh it. We no longer support the plaintext
        // fallback path; users without a hash must use password reset.
        $needsRehash = password_needs_rehash($hash, PASSWORD_DEFAULT);
        if ($needsRehash) {
            try {
                $this->userRepo()->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            } catch (\Throwable $e) {
                error_log('[auth] rehash failed for user id=' . (int) $user['id'] . ': ' . $e->getMessage());
            }
        }

        // Success path
        Auth::login($user);
        $this->attemptSvc()->recordSuccess($normalizedIdentifier, $ip);

        try {
            $this->userRepo()->updateLastLogin((int) $user['id']);
        } catch (\Throwable $e) {
            error_log('[auth] updateLastLogin failed: ' . $e->getMessage());
        }

        try {
            $this->auditRepo()->log([
                'user_id'     => $user['id'],
                'action'      => 'login',
                'entity_type' => 'user',
                'entity_key'  => (string) $user['id'],
            ]);
        } catch (\Throwable $e) {
            error_log('[auth] login audit failed: ' . $e->getMessage());
        }

        return $user;
    }

    /** Log out current user. */
    public function logout(): void
    {
        $userId = Auth::id();
        $ip     = self::clientIp();
        if ($userId) {
            try {
                $this->attemptSvc()->recordSuccess((string) $userId, $ip);
            } catch (\Throwable) {
                // ignore — table might not exist on first deploy, audit is best effort
            }
            try {
                $this->auditRepo()->log([
                    'user_id'     => $userId,
                    'action'      => 'logout',
                    'entity_type' => 'user',
                    'entity_key'  => (string) $userId,
                ]);
            } catch (\Throwable $e) {
                error_log('[auth] logout audit failed: ' . $e->getMessage());
            }
        }
        Auth::logout();
    }

    /**
     * Best-effort client IP honoring common proxy headers; mirrors
     * the logic in App\Core\Auth so login failures can be attributed
     * to the same source as session-bind checks.
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
        return '';
    }
}
