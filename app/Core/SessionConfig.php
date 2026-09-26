<?php

namespace App\Core;

/**
 * SessionConfig — Centralized session/cookie configuration.
 *
 * Calculates cookie parameters from environment variables so that the same
 * hardening applies to {@see session_start()} (set via {@see apply()}) and to
 * {@see setcookie()} calls used during logout.
 *
 * Environment variables used:
 *   - APP_DEBUG   (bool) when true, disables the Secure flag (local http).
 *   - APP_URL     (string) inspected for scheme to drive the Secure flag.
 *   - SESSION_COOKIE_SECURE  (0/1) explicit override for the Secure flag.
 *   - SESSION_COOKIE_HTTPONLY (0/1) override for the HttpOnly flag (default 1).
 *   - SESSION_COOKIE_SAMESITE (string) one of Lax|Strict|None (default Lax).
 *   - SESSION_LIFETIME (int) idle timeout in seconds (default 7200).
 *   - SESSION_ABSOLUTE_LIFETIME (int) hard cap regardless of activity.
 */
final class SessionConfig
{
    public static function lifetime(): int
    {
        return self::envInt('SESSION_LIFETIME', 7200);
    }

    public static function absoluteLifetime(): int
    {
        return self::envInt('SESSION_ABSOLUTE_LIFETIME', 8 * 3600);
    }

    /**
     * Cookie params used by both {@see session_start()} and the logout cookie
     * invalidation call. Includes a sentinel sentinel to avoid future drift.
     *
     * @return array{
     *   lifetime:int,
     *   path:string,
     *   domain:string,
     *   secure:bool,
     *   httponly:bool,
     *   samesite:string
     * }
     */
    public static function cookieParams(string $cookieName): array
    {
        $secure = self::resolveSecure();
        $httponly = self::envBool('SESSION_COOKIE_HTTPONLY', true);
        $samesite = strtoupper(self::envString('SESSION_COOKIE_SAMESITE', 'Lax'));
        if (!in_array($samesite, ['LAX', 'STRICT', 'NONE'], true)) {
            $samesite = 'LAX';
        }

        $params = session_get_cookie_params();
        return [
            'lifetime' => self::lifetime(),
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
            'name'     => $cookieName,
        ];
    }

    /**
     * Apply cookie params + gc_maxlifetime before session_start().
     */
    public static function apply(string $cookieName): void
    {
        $params = self::cookieParams($cookieName);
        session_set_cookie_params([
            'lifetime' => $params['lifetime'],
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        ini_set('session.gc_maxlifetime', (string) self::absoluteLifetime());
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
    }

    /**
     * Issue an explicit cookie-clearing setcookie() that matches the
     * production cookie params (so the browser drops the cookie even when
     * the URL traversed a proxy that modified other attributes).
     */
    public static function expireCookie(string $cookieName): void
    {
        $params = self::cookieParams($cookieName);
        setcookie(
            $cookieName,
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]
        );
    }

    /**
     * Resolve the Secure flag: explicit env override > APP_URL scheme
     * > APP_DEBUG-inferred scheme (dev=false, prod=true).
     */
    private static function resolveSecure(): bool
    {
        if (env('SESSION_COOKIE_SECURE') !== null) {
            return self::envBool('SESSION_COOKIE_SECURE', false);
        }
        $appUrl = (string) env('APP_URL', '');
        if ($appUrl !== '') {
            return str_starts_with(strtolower($appUrl), 'https://');
        }
        // Fall back to debug heuristic: dev → http → false; otherwise true.
        return !self::envBool('APP_DEBUG', false);
    }

    private static function envInt(string $key, int $default): int
    {
        $raw = env($key);
        if ($raw === null || $raw === '' || $raw === false) {
            return $default;
        }
        $n = (int) $raw;
        return $n > 0 ? $n : $default;
    }

    private static function envBool(string $key, bool $default): bool
    {
        $raw = env($key);
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (is_bool($raw)) {
            return $raw;
        }
        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    private static function envString(string $key, string $default): string
    {
        $raw = env($key);
        if ($raw === null || $raw === '' || $raw === false) {
            return $default;
        }
        return (string) $raw;
    }
}
