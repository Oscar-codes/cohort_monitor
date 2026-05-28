<?php
/**
 * Ejecuta el esquema SQL contra la BD configurada por entorno.
 * Uso: php run_migration_railway.php [archivo_sql]
 */

$mysqlUrl = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL');
$parsed = null;
if ($mysqlUrl && str_starts_with($mysqlUrl, 'mysql://')) {
    $parsed = parse_url($mysqlUrl) ?: null;
}

$host     = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: ($parsed['host'] ?? '127.0.0.1');
$port     = (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: ($parsed['port'] ?? 3306));
$user     = getenv('DB_USERNAME') ?: getenv('MYSQLUSER') ?: ($parsed['user'] ?? 'root');
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: ($parsed['pass'] ?? '');
$database = getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: ltrim((string) ($parsed['path'] ?? '/cohort_monitor'), '/');

// Archivo SQL por argumento o por defecto el nuevo esquema base
$migrationFile = $argv[1] ?? __DIR__ . '/schema.sql';

if (!file_exists($migrationFile)) {
    die("No se encontró el archivo: {$migrationFile}\n");
}

echo "Conectando a MySQL ({$host}:{$port})...\n";

$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset('utf8mb4');
echo "Conexión exitosa!\n\n";

$sql = file_get_contents($migrationFile);
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

// Reemplazar "USE cohort_monitor;" por "USE railway;" para Railway
$sql = preg_replace('/USE\s+cohort_monitor\s*;/i', 'USE railway;', $sql);

$size = round(strlen($sql) / 1024, 1);
echo "Ejecutando SQL: " . basename($migrationFile) . " ({$size} KB)...\n";

if ($mysqli->multi_query($sql)) {
    $i = 0;
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        $i++;
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        echo "Error en statement #{$i}: " . $mysqli->error . "\n";
    } else {
        echo "Ejecución completada! ({$i} statements procesados)\n\n";
    }
} else {
    die("Error ejecutando SQL: " . $mysqli->error . "\n");
}

// Verificar conteo de cohorts
$result = $mysqli->query("SELECT COUNT(*) as total FROM cohorts");
if ($result) {
    $count = $result->fetch_assoc()['total'];
    echo "Total de cohorts en BD: {$count}\n";
} else {
    echo "No se pudo verificar: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "Listo!\n";
