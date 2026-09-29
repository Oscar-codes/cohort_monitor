<?php

namespace App\Services;

use App\Core\Database;

/**
 * LoginAttemptService — Rate-limiting and lockout for the login flow.
 *
 * Counts failed attempts per (identifier_hash, ip_address) within sliding
 * windows. After a configurable number of failures (default 5) inside a
 * short window (default 10 min), the same actor is locked for a cool-off
 * period (default 15 min). After a stronger threshold (default 10 failures)
 * inside a long window (default 24 h), the lockout extends to 24 h to
 * complicate distributed brute force across IPs on the same identifier.
 *
 * Identifier hashing: only sha256(identifier) is persisted. The raw
 * username/email never lands in this table — keeps DB dumps
 * non-disclosive. The hash is salted by SESSION_NAME so dumps cannot be
 * cross-referenced with another deployment.
 */
class LoginAttemptService
{
    public const SHORT_WINDOW = 600;     // 10 min
    public const LONG_WINDOW  = 86400;   // 24 h
    public const SHORT_MAX    = 5;
    public const LONG_MAX     = 10;
    public const SHORT_LOCKOUT= 900;     // 15 min
    public const LONG_LOCKOUT = 86400;  // 24 h

    private const REASON_INVALID   = 'invalid_credentials';
    private const REASON_LOCKED    = 'locked';
    private const REASON_BAD_HASH  = 'invalid_hash';
    private const REASON_SUCCESS   = 'success';

    /**
     * Detect if (identifier, ip) is currently locked.
     * Returns the lockout expires_at timestamp (unix seconds) or null.
     */
    public function lockoutUntil(string $identifier, string $ip): ?int
    {
        $hash = $this->hashIdentifier($identifier);

        // Long-window override: 10+ failures in 24h -> locked for 24h.
        $longCount = $this->countFailuresSince($hash, $ip, time() - self::LONG_WINDOW);
        if ($longCount >= self::LONG_MAX) {
            $latest = $this->latestFailureSince($hash, $ip, time() - self::LONG_LOCKOUT);
            if ($latest !== null) {
                return $latest + self::LONG_LOCKOUT;
            }
        }

        // Short-window lockout: 5 failures in 10 min -> 15 min cool-off.
        $shortCount = $this->countFailuresSince($hash, $ip, time() - self::SHORT_WINDOW);
        if ($shortCount >= self::SHORT_MAX) {
            $latest = $this->latestFailureSince($hash, $ip, time() - self::SHORT_LOCKOUT);
            if ($latest !== null) {
                return $latest + self::SHORT_LOCKOUT;
            }
        }

        return null;
    }

    /**
     * Persist a failed attempt with reason. Increments internal counters
     * that drive subsequent lockoutUntil() calls.
     */
    public function recordFailure(string $identifier, string $ip, string $reason = self::REASON_INVALID): void
    {
        $this->insert($identifier, $ip, $reason, 0);
    }

    /**
     * Persist a successful login. Cleans up prior failure rows for the
     * same (identifier, ip) pair so the counter resets.
     */
    public function recordSuccess(string $identifier, string $ip): void
    {
        $hash = $this->hashIdentifier($identifier);
        $pdo  = $this->pdo();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE identifier_hash = :h AND ip_address = :ip AND success = 0');
        $stmt->execute(['h' => $hash, 'ip' => $ip]);
        $this->insert($identifier, $ip, self::REASON_SUCCESS, 1);
    }

    /**
     * Convenience helper: returns true if the (identifier, ip) is locked.
     */
    public function isLocked(string $identifier, string $ip): bool
    {
        return $this->lockoutUntil($identifier, $ip) !== null
            && $this->lockoutUntil($identifier, $ip) > time();
    }

    /**
     * Compute identifier hash. Uses sha256 with a salt derived from the
     * SESSION_COOKIE_SAMESITE constant + the cookie name. This prevents
     * joining two deployments' attempts tables via the same raw
     * username.
     */
    private function hashIdentifier(string $identifier): string
    {
        $salt = 'cohort_monitor_session_login_v1';
        return hash('sha256', $salt . '|' . strtolower(trim($identifier)));
    }

    private function pdo(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    private function countFailuresSince(string $hash, string $ip, int $since): int
    {
        $pdo  = $this->pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier_hash = :h
               AND (ip_address = :ip OR :ip_empty = "")
               AND success = 0
               AND created_at >= FROM_UNIXTIME(:since)'
        );
        $stmt->execute(['h' => $hash, 'ip' => $ip, 'ip_empty' => $ip, 'since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    private function latestFailureSince(string $hash, string $ip, int $since): ?int
    {
        $pdo  = $this->pdo();
        $stmt = $pdo->prepare(
            'SELECT UNIX_TIMESTAMP(created_at) FROM login_attempts
             WHERE identifier_hash = :h
               AND (ip_address = :ip OR :ip_empty = "")
               AND success = 0
               AND created_at >= FROM_UNIXTIME(:since)
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute(['h' => $hash, 'ip' => $ip, 'ip_empty' => $ip, 'since' => $since]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    private function insert(string $identifier, string $ip, string $reason, int $success): void
    {
        $hash  = $this->hashIdentifier($identifier);
        $pdo   = $this->pdo();
        $ua    = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $ip    = $ip === '' ? null : $ip;
        $stmt  = $pdo->prepare(
            'INSERT INTO login_attempts (identifier_hash, ip_address, user_agent, success, reason, created_at)
             VALUES (:h, :ip, :ua, :ok, :r, NOW())'
        );
        $stmt->execute([
            'h'  => $hash,
            'ip' => $ip,
            'ua' => $ua,
            'ok' => $success,
            'r'  => $reason,
        ]);
    }

    public function reasonForInvalid(): string { return self::REASON_INVALID; }
    public function reasonForBadHash(): string  { return self::REASON_BAD_HASH; }
    public function reasonForLocked(): string  { return self::REASON_LOCKED; }
}
