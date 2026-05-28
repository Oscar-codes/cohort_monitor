<?php
/**
 * Script para importar uno o varios archivos SQL contra la BD configurada por entorno.
 * Usa mysqli::multi_query para ejecutar cada archivo completo.
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
$dumpFiles = array_slice($argv, 1);
if (empty($dumpFiles)) {
    $dumpFiles = [
        __DIR__ . '/schema.sql',
        __DIR__ . '/registros_cohort_plan.sql',
    ];
}

echo "Conectando a MySQL ({$host}:{$port})...\n";

$mysqli = new mysqli($host, $user, $password, $database, $port);

if ($mysqli->connect_error) {
    die("Error de conexion: " . $mysqli->connect_error . "\n");
}

$mysqli->set_charset('utf8mb4');
echo "Conexion exitosa!\n\n";

foreach ($dumpFiles as $dumpFile) {
    if (!file_exists($dumpFile)) {
        die("No se encontro el archivo: {$dumpFile}\n");
    }

    $sql = file_get_contents($dumpFile);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/USE\s+cohort_monitor\s*;/i', 'USE ' . $database . ';', $sql);
    $size = round(strlen($sql) / 1024, 1);
    echo "Leyendo SQL ({$size} KB): " . basename($dumpFile) . "\n";
    echo "Importando...\n\n";

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
            exit(1);
        }

        echo "Importacion completada! ({$i} statements procesados)\n\n";
    } else {
        die("Error ejecutando dump: " . $mysqli->error . "\n");
    }
}

// Verificar tablas
echo "Tablas en la BD '{$database}':\n";
$result = $mysqli->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        $countResult = $mysqli->query("SELECT COUNT(*) as c FROM `{$table}`");
        $count = $countResult ? $countResult->fetch_assoc()['c'] : '?';
        echo "  - {$table} ({$count} registros)\n";
    }
} else {
    echo "  No se pudieron listar tablas: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "\nListo!\n";
