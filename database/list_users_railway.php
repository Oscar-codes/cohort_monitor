<?php
/**
 * Lista usuarios de la BD de Railway con estado de acceso.
 * Uso: php database/list_users_railway.php
 */

$mysqlUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL');
$parsed = null;
if ($mysqlUrl && str_starts_with($mysqlUrl, 'mysql://')) {
    $parsed = parse_url($mysqlUrl) ?: null;
}

$pick = static function (...$values): string {
    foreach ($values as $value) {
        if ($value === null || $value === false) {
            continue;
        }

        $candidate = trim((string) $value);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
};

$host = $pick(getenv('DB_HOST'), getenv('MYSQLHOST'), $parsed['host'] ?? null, '127.0.0.1');
$port = (int) $pick(getenv('DB_PORT'), getenv('MYSQLPORT'), $parsed['port'] ?? null, '3306');
$user = $pick(getenv('DB_USERNAME'), getenv('MYSQLUSER'), $parsed['user'] ?? null, 'root');
$password = $pick(getenv('DB_PASSWORD'), getenv('MYSQLPASSWORD'), $parsed['pass'] ?? null, '');
$database = $pick(getenv('DB_DATABASE'), getenv('MYSQLDATABASE'), ltrim((string) ($parsed['path'] ?? ''), '/'), 'cohort_monitor');

echo "Conectando a MySQL ({$host}:{$port}) DB={$database}...\n";

$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error) {
    die("Error de conexion: " . $mysqli->connect_error . "\n");
}

$mysqli->set_charset('utf8mb4');

$sql = "SELECT id, username, email, role, is_active, last_login_at, updated_at
        FROM users
        ORDER BY id ASC";

$result = $mysqli->query($sql);
if (!$result) {
    die("Error en consulta: " . $mysqli->error . "\n");
}

echo "\nUsuarios disponibles:\n";
echo str_repeat('-', 120) . "\n";
printf("%-5s %-22s %-38s %-16s %-8s %-20s %-20s\n", 'ID', 'USERNAME', 'EMAIL', 'ROLE', 'ACTIVE', 'LAST_LOGIN', 'UPDATED_AT');
echo str_repeat('-', 120) . "\n";

while ($row = $result->fetch_assoc()) {
    printf(
        "%-5s %-22s %-38s %-16s %-8s %-20s %-20s\n",
        (string) $row['id'],
        (string) $row['username'],
        (string) $row['email'],
        (string) $row['role'],
        ((int) $row['is_active'] === 1 ? 'yes' : 'no'),
        (string) ($row['last_login_at'] ?? '-'),
        (string) ($row['updated_at'] ?? '-')
    );
}

echo str_repeat('-', 120) . "\n";

$result->free();
$mysqli->close();
