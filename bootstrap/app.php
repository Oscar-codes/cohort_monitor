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

// ─── 0. Composer autoloader (for vendor packages) ───────────
$composerAutoload = APP_ROOT . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ─── 1. Load environment variables ──────────────────────────
loadEnv(APP_ROOT . '/.env');

// ─── 2. Configure error reporting ───────────────────────────
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ─── 3. Set timezone ────────────────────────────────────────
date_default_timezone_set('UTC');

// ─── 4. Register PSR-4 autoloader ───────────────────────────
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
