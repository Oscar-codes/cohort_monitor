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

$pick = static function (...$values): string {
    foreach ($values as $value) {
        if ($value === null) {
            continue;
        }

        $candidate = trim((string) $value);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
};

$host = $pick(env('DB_HOST', null), env('MYSQLHOST', null), $fallbackHost, '127.0.0.1');
if (strtolower($host) === 'localhost') {
    $host = '127.0.0.1';
}

$port = $pick(env('DB_PORT', null), env('MYSQLPORT', null), $parsedUrl['port'] ?? null, '3306');
$database = $pick(env('DB_DATABASE', null), env('MYSQLDATABASE', null), ltrim((string) ($parsedUrl['path'] ?? ''), '/'), 'cohort_monitor');
$username = $pick(env('DB_USERNAME', null), env('MYSQLUSER', null), $parsedUrl['user'] ?? null, 'root');
$password = $pick(env('DB_PASSWORD', null), env('MYSQLPASSWORD', null), $parsedUrl['pass'] ?? null, '');
$charset = $pick(env('DB_CHARSET', null), 'utf8mb4');
$socket = $pick(env('DB_SOCKET', null), env('MYSQL_SOCKET', null), '');

return [
    'host'        => $host,
    'port'        => $port,
    'database'    => $database,
    'username'    => $username,
    'password'    => $password,
    'charset'     => $charset,
    'unix_socket' => $socket,
];
