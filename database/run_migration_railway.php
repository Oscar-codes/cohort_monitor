<?php
/**
 * Ejecuta una migración SQL contra la BD de Railway.
 * Uso: php run_migration_railway.php <archivo_migracion.sql>
 */

$host     = 'mainline.proxy.rlwy.net';
$port     = 26351;
$user     = 'root';
$password = 'DvbeBrhnPeKDxVCkgBDInPetvffhMJCB';
$database = 'railway';

// Archivo de migración por argumento o por defecto la 009
$migrationFile = $argv[1] ?? __DIR__ . '/migrations/009_seed_cohorts_march2026.sql';

if (!file_exists($migrationFile)) {
    die("No se encontró el archivo: {$migrationFile}\n");
}

echo "Conectando a Railway MySQL ({$host}:{$port})...\n";

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
echo "Ejecutando migración: " . basename($migrationFile) . " ({$size} KB)...\n";

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
        echo "Migración completada! ({$i} statements procesados)\n\n";
    }
} else {
    die("Error ejecutando migración: " . $mysqli->error . "\n");
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
