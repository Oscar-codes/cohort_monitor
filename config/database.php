<?php

/**
 * Database Configuration
 *
 * Values are loaded from environment variables with sensible defaults.
 * Copy .env.example to .env and adjust for your environment.
 */

return [
    'host'     => env('DB_HOST', env('MYSQLHOST', '127.0.0.1')),
    'port'     => env('DB_PORT', env('MYSQLPORT', '3306')),
    'database' => env('DB_DATABASE', env('MYSQLDATABASE', 'cohort_monitor')),
    'username' => env('DB_USERNAME', env('MYSQLUSER', 'root')),
    'password' => env('DB_PASSWORD', env('MYSQLPASSWORD', '')),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
];
