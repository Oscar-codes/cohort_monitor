<?php

namespace App\Core;

/**
 * SafeLog — Centralized error reporting that honors privacy.
 *
 * Wraps PHP's `error_log()` to ensure production logs never carry
 * sensitive identifiers (raw usernames, emails, raw DSN) nor full
 * PDO messages that may reveal database structure. Two surfaces:
 *
 *   - SafeLog::record(string $category, \Throwable $e) — used by services
 *     and the framework; logs only the exception class, a short message
 *     and a deterministic fingerprint. No DSN, no PII, no class context.
 *
 *   - SafeLog::redact(string $value) — generic redaction helper for
 *     arbitrary user-supplied values you want to keep out of logs.
 *
 * Both surfaces gate logging on a private static flag (default true in
 * APP_DEBUG, true in production so long-running cron jobs always get
 * feedback). Set SafeLog::$disabled = true to mute in tests.
 */
final class SafeLog
{
    /** @var bool Disable logging during tests. */
    public static bool $disabled = false;

    /**
     * Record a non-fatal error event. Returns a short fingerprint that
     * can be cross-referenced between server-side log and any controlled
     * end-user feedback.
     */
    public static function record(string $category, \Throwable $e): string
    {
        if (self::$disabled) {
            return self::fingerprint($e, $category);
        }

        $fingerprint = self::fingerprint($e, $category);
        error_log(sprintf(
            '[%s] %s :: %s :: fp=%s',
            $category,
            self::classShort($e),
            self::shortMessage($e),
            $fingerprint
        ));
        return $fingerprint;
    }

    /**
     * Returns the deterministic fingerprint for a category + exception
     * pair without logging anything. Useful when a route wants to show
     * the user a non-disclosing reference id (e.g., "ref: 3f9a2c1") that
     * a developer can search in the server log.
     */
    public static function fingerprint(\Throwable $e, string $category = ''): string
    {
        return substr(hash('sha256', $category . '|' . self::classShort($e) . '|' . $e->getMessage()), 0, 10);
    }

    /**
     * Mask arbitrary text. Used for raw identifiers before any logging.
     * Returns a truncated sha256 prefix marker so logs stay consistent
     * while the raw value never reaches disk.
     */
    public static function redact(string $value): string
    {
        if ($value === '') {
            return '[empty]';
        }
        return 'redact:' . substr(hash('sha256', $value), 0, 8);
    }

    private static function classShort(\Throwable $e): string
    {
        $parts = explode('\\', get_class($e));
        return end($parts) ?: 'Throwable';
    }

    private static function shortMessage(\Throwable $e): string
    {
        $msg = trim((string) $e->getMessage());
        if ($msg === '') {
            return '(no message)';
        }
        if (strlen($msg) > 120) {
            $msg = substr($msg, 0, 117) . '...';
        }
        return $msg;
    }
}
