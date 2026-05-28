<?php
/**
 * Ejecuta uno o varios archivos SQL contra la BD configurada por entorno.
 * Uso: php run_migration_railway.php [archivo_sql_1] [archivo_sql_2] ...
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

// Archivos SQL por argumentos o por defecto esquema + registros
$migrationFiles = array_slice($argv, 1);
if (empty($migrationFiles)) {
    $migrationFiles = [
        __DIR__ . '/schema.sql',
        __DIR__ . '/registros_cohort_plan.sql',
    ];
}

echo "Conectando a MySQL ({$host}:{$port})...\n";

$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset('utf8mb4');
echo "Conexión exitosa!\n\n";

foreach ($migrationFiles as $migrationFile) {
    if (!file_exists($migrationFile)) {
        die("No se encontró el archivo: {$migrationFile}\n");
    }

    $sql = file_get_contents($migrationFile);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $sql = preg_replace('/USE\s+cohort_monitor\s*;/i', 'USE ' . $database . ';', $sql);

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
            exit(1);
        }

        echo "Ejecución completada! ({$i} statements procesados)\n\n";
    } else {
        die("Error ejecutando SQL: " . $mysqli->error . "\n");
    }
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
