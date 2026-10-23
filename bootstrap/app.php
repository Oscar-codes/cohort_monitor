<?php

/**
 * ============================================================
 *  Bootstrap / Application Initialization
 * ============================================================
 *
 * This file is loaded before anything else. It handles:
 *  1. Environment variable loading (.env)
 *  2. PSR-4 compatible autoloader
 *  3. Global helper functions
 *  4. Error reporting configuration
 */

// ─── Hide server version (ACC-002) ──────────────────────────
// php -S on php:8.2-cli ships without php.ini, so expose_php defaults
// to On and every PHP response carries "X-Powered-By: PHP/x.y.z".
// expose_php is PHP_INI_SYSTEM (ini_set can't change it at runtime);
// removing the header before any output is what actually takes effect.
// Done first so even early bootstrap errors don't leak it.
header_remove('X-Powered-By');

// ─── 0. Composer autoloader (for vendor packages) ───────────
$composerAutoload = APP_ROOT . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ─── 1. Load environment variables ──────────────────────────
loadEnv(APP_ROOT . '/.env');

// ─── 2. Register PSR-4 autoloader ───────────────────────────
spl_autoload_register(function (string $class): void {
    // Namespace prefix → base directory mapping
    $prefixes = [
        'App\\' => APP_ROOT . '/app/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─── 3. Configure error reporting (CM-SEC-004 / COH-004) ────
// Details reach the client only with APP_DEBUG on outside production;
// APP_ENV=production (or any unknown value) never displays errors, even
// when APP_DEBUG is true. Uncaught exceptions and fatal errors go through
// ErrorHandler: full detail to storage/logs/app.log, generic page + ref
// to the client.
error_reporting(E_ALL);
ini_set('log_errors', '1');
if (\App\Core\ErrorHandler::showDetails()) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('html_errors', '0');
}
\App\Core\ErrorHandler::register();

// ─── 4. Set timezone ────────────────────────────────────────
date_default_timezone_set('UTC');

// ─── 5. Boot authentication / session ───────────────────────
\App\Core\Auth::boot();

// ═══════════════════════════════════════════════════════════════
//  GLOBAL HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

/**
 * Get an environment variable value.
 *
 * @param string $key     The variable name
 * @param mixed  $default Fallback value if not set
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    // System env vars (Railway, Docker) take priority over .env file
    $sysVal = getenv($key);
    $value = ($sysVal !== false) ? $sysVal : ($_ENV[$key] ?? $_SERVER[$key] ?? false);

    if ($value === false) {
        return $default;
    }

    // Cast common string values to native types
    return match (strtolower((string) $value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        'empty', '(empty)' => '',
        default             => $value,
    };
}

/**
 * Load a .env file into the environment.
 * Simple parser — supports KEY=VALUE, comments (#), and quoted values.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
        if (str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        // Respect externally injected env vars (server/container/CI)
        if (getenv($key) !== false) {
            continue;
        }

        // Remove surrounding quotes
        if (preg_match('/^(["\'])(.*)\\1$/', $value, $m)) {
            $value = $m[2];
        }

        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

/**
 * Dump and die — quick debugging helper.
 */
function dd(mixed ...$vars): never
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit(1);
}

/**
 * Escape HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate an asset URL path.
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/**
 * Return the current CSRF token, generating one if needed.
 */
function csrf_token(): string
{
    return \App\Core\Csrf::token();
}

/**
 * Emit a hidden <input name="_csrf" value="…"> for forms.
 */
function csrf_field(): string
{
    return \App\Core\Csrf::field();
}
