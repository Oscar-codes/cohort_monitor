<?php

/**
 * Database Configuration
 *
 * Values are loaded from environment variables with sensible defaults.
 * Supports Railway-style URL variables and optional unix socket usage.
 */

$databaseUrl = env('DATABASE_URL', env('MYSQL_URL', env('MYSQL_PUBLIC_URL', '')));
$parsedUrl = null;

if (is_string($databaseUrl) && $databaseUrl !== '' && str_starts_with($databaseUrl, 'mysql://')) {
    $tmp = parse_url($databaseUrl);
    if (is_array($tmp)) {
        $parsedUrl = $tmp;
    }
}

$fallbackHost = (string) ($parsedUrl['host'] ?? '127.0.0.1');
if ($fallbackHost === '' || strtolower($fallbackHost) === 'localhost') {
    $fallbackHost = '127.0.0.1';
}

return [
    'host'        => (string) env('DB_HOST', env('MYSQLHOST', $fallbackHost)),
    'port'        => (string) env('DB_PORT', env('MYSQLPORT', (string) ($parsedUrl['port'] ?? '3306'))),
    'database'    => (string) env('DB_DATABASE', env('MYSQLDATABASE', ltrim((string) ($parsedUrl['path'] ?? '/cohort_monitor'), '/'))),
    'username'    => (string) env('DB_USERNAME', env('MYSQLUSER', (string) ($parsedUrl['user'] ?? 'root'))),
    'password'    => (string) env('DB_PASSWORD', env('MYSQLPASSWORD', (string) ($parsedUrl['pass'] ?? ''))),
    'charset'     => (string) env('DB_CHARSET', 'utf8mb4'),
    'unix_socket' => (string) env('DB_SOCKET', env('MYSQL_SOCKET', '')),
];
