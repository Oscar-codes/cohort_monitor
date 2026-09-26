<?php

namespace App\Core;

/**
 * Csrf — Synchronizer-Token pattern for state-changing requests.
 *
 * Generates a per-session token, exposes helpers for views (csrf_field),
 * and verifies the token submitted with POST/PUT/DELETE requests on demand.
 *
 * The default constant-time compare is `hash_equals` (PHP 5.6+).
 */
class Csrf
{
    public const FIELD_NAME = '_csrf';
    public const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Auth::boot();

        $session = &self::sessionBucket();
        if (empty($session[self::SESSION_KEY]) || !is_string($session[self::SESSION_KEY])) {
            $session[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $session[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }

    public static function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }
        $session = &self::sessionBucket();
        $expected = $session[self::SESSION_KEY] ?? null;
        if (!is_string($expected) || $expected === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }

    /**
     * Validate the current request's CSRF token. If validation fails, set 419
     * and halt. Use this from middleware or before any state-changing action.
     */
    public static function verifyOrDie(): void
    {
        $token = $_POST[self::FIELD_NAME] ?? $_REQUEST[self::FIELD_NAME] ?? null;
        if (!self::verify(is_string($token) ? $token : null)) {
            http_response_code(419);
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>419 — Sesion invalida</h1>'
                . '<p>No se pudo validar la sesion del formulario. Recarga la pagina e intenta de nuevo.</p>';
            exit;
        }
    }

    /**
     * Rotate token (e.g. after login, privilege escalation, password change).
     */
    public static function rotate(): void
    {
        $session = &self::sessionBucket();
        $session[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }

    /**
     * @return array<string, mixed>
     */
    private static function &sessionBucket(): array
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        return $_SESSION;
    }
}
