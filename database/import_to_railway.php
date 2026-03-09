<?php
/**
 * Script para importar el dump SQL al MySQL de Railway
 * Usa mysqli::multi_query para ejecutar el dump completo
 */

$host     = 'mainline.proxy.rlwy.net';
$port     = 26351;
$user     = 'root';
$password = 'DvbeBrhnPeKDxVCkgBDInPetvffhMJCB';
$database = 'railway';
$dumpFile = __DIR__ . '/railway_dump.sql';

echo "Conectando a Railway MySQL ({$host}:{$port})...\n";

$mysqli = new mysqli($host, $user, $password, $database, $port);

if ($mysqli->connect_error) {
    die("Error de conexion: " . $mysqli->connect_error . "\n");
}

$mysqli->set_charset('utf8mb4');
echo "Conexion exitosa!\n\n";

// Leer el dump
if (!file_exists($dumpFile)) {
    die("No se encontro el archivo: {$dumpFile}\n");
}

$sql = file_get_contents($dumpFile);
// Quitar BOM si existe (UTF-8 BOM = EF BB BF)
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
$size = round(strlen($sql) / 1024, 1);
echo "Leyendo dump ({$size} KB)...\n";
echo "Importando...\n\n";

// Ejecutar todo el dump con multi_query
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
        echo "Importacion completada! ({$i} statements procesados)\n\n";
    }
} else {
    die("Error ejecutando dump: " . $mysqli->error . "\n");
}

// Verificar tablas
echo "Tablas en la BD 'railway':\n";
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
