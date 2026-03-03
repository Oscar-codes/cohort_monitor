<?php

/**
 * Database Configuration
 *
 * Values are loaded from environment variables with sensible defaults.
 * Copy .env.example to .env and adjust for your environment.
 */

// Railway provides MYSQL_URL — parse it as ultimate fallback
$railwayUrl = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL');
$parsed = null;
if ($railwayUrl && str_starts_with($railwayUrl, 'mysql://')) {
    $parsed = parse_url($railwayUrl);
}

return [
    'host'     => env('DB_HOST', env('MYSQLHOST', $parsed['host'] ?? '127.0.0.1')),
    'port'     => env('DB_PORT', env('MYSQLPORT', (string)($parsed['port'] ?? '3306'))),
    'database' => env('DB_DATABASE', env('MYSQLDATABASE', ltrim($parsed['path'] ?? '/cohort_monitor', '/'))),
    'username' => env('DB_USERNAME', env('MYSQLUSER', $parsed['user'] ?? 'root')),
    'password' => env('DB_PASSWORD', env('MYSQLPASSWORD', $parsed['pass'] ?? '')),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
];
