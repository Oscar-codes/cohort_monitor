<?php
declare(strict_types=1);

// DATA-002 regression: required cohort fields (nombre, proyecto, fecha de inicio,
// fecha de fin). bootcamp_type stays optional until the official bootcamp list.
//  - Create: missing any of them blocks the save; the message lists only what is missing.
//  - Admin edit of an old incomplete cohort must complete it.
//  - Limited roles (admissions_b2b, admissions_b2c, finance) are never blocked.
//  - Cancelled cohorts are exempt on edit (except the name, NOT NULL in the table).
//  - Reactivating requires everything except bootcamp_type (plus coach and schedule).
//  - Import rejects rows without project or dates, and files without the project column.
//  - Forms: required + asterisk (none on cancelled cohorts or read-only fields) and
//    the "según el código actual" enrolment-origin help line.
// Each scenario runs in its own PHP process through the real Router (method
// override + CSRF), controllers, services and views; imports call the service.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_required_fields.php

const OLD_INCOMPLETE_ID = 1;   // not started, no project, no dates
const CANCELLED_INCOMPLETE_ID = 2; // cancelled, no project, no end date
const CANCELLED_NO_BOOTCAMP_ID = 3; // cancelled, complete except bootcamp_type
const RESULT_MARKER = '@@DATA002@@';
const HELP_TEXT = 'Según el código actual: los inscritos B2B y B2C se capturan manualmente en Cohort Monitor.';

if (($argv[1] ?? '') === '--scenario') {
    runScenario(json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR));
    exit(0);
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

/** Run one request (or one import) in a child process and return what it observed. */
function scenario(string $role, string $method, string $path, array $post = [], ?string $csv = null): array
{
    $spec = compact('role', 'method', 'path', 'post', 'csv');
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --scenario ' . escapeshellarg(base64_encode(json_encode($spec))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$role} {$method} {$path}): " . substr($output, -500));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    $result['output'] = substr($output, 0, $pos);
    return $result;
}

/** Complete admin form (every field of CohortController::collectFormData). */
function adminForm(string $code, array $overrides = []): array
{
    return array_merge([
        '_csrf' => 'test-csrf',
        'cohort_code' => $code, 'name' => 'Cohorte ' . $code, 'correlative_number' => '1',
        'total_admission_target' => '10', 'b2b_admission_target' => '5', 'b2c_admission_target' => '5',
        'b2b_admissions' => '2', 'b2c_admissions' => '3',
        'financial_target_revenue' => '0', 'financial_actual_revenue' => '0',
        'start_date' => '2026-11-02', 'end_date' => '2027-01-29',
        'related_project' => 'KODIGO', 'assigned_coach' => 'Coach Prueba', 'bootcamp_type' => 'FSJ',
        'area' => 'academic', 'assigned_class_schedule' => 'Lun-Mié 18:00-20:00',
    ], $overrides);
}

function update(string $role, int $id, array $post): array
{
    return scenario($role, 'POST', '/cohorts/' . $id, ['_method' => 'PUT', '_csrf' => 'test-csrf'] + $post);
}

function cohort(array $res, int $id): ?array
{
    foreach ($res['cohorts'] as $row) {
        if ((int) $row['id'] === $id) {
            return $row;
        }
    }
    return null;
}

function flashError(array $res): string
{
    return (string) ($res['flash']['error'] ?? '');
}

/** Attribute string of the <input> with this id. */
function inputTag(string $body, string $id): string
{
    check(preg_match('/<input\b[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>/s', $body, $m) === 1, "input #{$id} rendered");
    return $m[0];
}

function labelHasAsterisk(string $body, string $for): bool
{
    check(preg_match('/<label for="' . preg_quote($for, '/') . '"[^>]*>(.*?)<\/label>/s', $body, $m) === 1, "label for {$for}");
    return str_contains($m[1], 'text-danger">*');
}

$report = [];

// ─── 1. Create: each required field missing blocks the save ─────────────
$labels = ['name' => 'nombre', 'related_project' => 'proyecto', 'start_date' => 'fecha de inicio', 'end_date' => 'fecha de fin'];
foreach ($labels as $field => $label) {
    $res = scenario('admin', 'POST', '/cohorts', adminForm('NEW1', [$field => '  ']));
    check(flashError($res) === "Faltan campos obligatorios: {$label}.", "create without {$field}: got '" . flashError($res) . "'");
    check(count($res['cohorts']) === 3, "create without {$field}: nothing inserted");
    $report[] = sprintf('crear sin %-16s -> %s', $field, flashError($res));
}
$res = scenario('admin', 'POST', '/cohorts', adminForm('NEW1', ['name' => '', 'start_date' => '', 'end_date' => '']));
check(flashError($res) === 'Faltan campos obligatorios: nombre, fecha de inicio, fecha de fin.', 'create: lists only the missing fields');
$res = scenario('admin', 'POST', '/cohorts', adminForm('NEW1', ['bootcamp_type' => '']));
check(flashError($res) === '' && count($res['cohorts']) === 4, 'create without bootcamp_type is allowed');
check(cohort($res, 4)['bootcamp_type'] === null && cohort($res, 4)['related_project'] === 'KODIGO', 'created row stored');
$report[] = 'crear sin bootcamp_type     -> guardada (no obligatorio todavía)';

// ─── 2. Admin edit of an old incomplete cohort must complete it ─────────
$res = update('admin', OLD_INCOMPLETE_ID, adminForm('OLDINC', ['related_project' => '', 'start_date' => '', 'end_date' => '', 'assigned_coach' => 'Otro coach']));
check(flashError($res) === 'Faltan campos obligatorios: proyecto, fecha de inicio, fecha de fin.', 'admin edit old incomplete: got ' . flashError($res));
check(cohort($res, OLD_INCOMPLETE_ID)['assigned_coach'] === 'Coach Viejo', 'admin edit old incomplete: nothing saved');
$res = update('admin', OLD_INCOMPLETE_ID, adminForm('OLDINC', ['bootcamp_type' => '']));
check(flashError($res) === '' && cohort($res, OLD_INCOMPLETE_ID)['related_project'] === 'KODIGO', 'admin edit completing the cohort saves');
$report[] = 'admin edita vieja incompleta -> bloqueada hasta completar proyecto y fechas';

// ─── 3. Limited roles are never blocked by the old incomplete data ──────
foreach (['admissions_b2b' => ['b2b_admissions', '4'], 'admissions_b2c' => ['b2c_admissions', '6'], 'finance' => ['financial_actual_revenue', '150.50']] as $role => [$field, $value]) {
    $res = update($role, OLD_INCOMPLETE_ID, adminForm('OLDINC', ['related_project' => '', 'start_date' => '', 'end_date' => '', $field => $value]));
    check(flashError($res) === '', "{$role}: not blocked, got " . flashError($res));
    $row = cohort($res, OLD_INCOMPLETE_ID);
    check((float) $row[$field] === (float) $value, "{$role}: {$field} saved");
    check($row['related_project'] === null && $row['start_date'] === null, "{$role}: other fields untouched");
    $report[] = sprintf('%-15s edita %-24s -> guardado (cohorte sigue incompleta)', $role, $field);
}

// ─── 4. Cancelled cohorts are exempt on edit (name still required) ──────
$res = update('admin', CANCELLED_INCOMPLETE_ID, adminForm('CANINC', ['related_project' => '', 'start_date' => '2026-01-10', 'end_date' => '', 'assigned_coach' => 'Coach Nuevo']));
check(flashError($res) === '', 'cancelled edit: exempt, got ' . flashError($res));
check(cohort($res, CANCELLED_INCOMPLETE_ID)['assigned_coach'] === 'Coach Nuevo', 'cancelled edit saved');
check(cohort($res, CANCELLED_INCOMPLETE_ID)['training_status'] === 'cancelled', 'cancelled edit keeps status');
$res = update('admin', CANCELLED_INCOMPLETE_ID, adminForm('CANINC', ['name' => '', 'related_project' => '', 'end_date' => '']));
check(flashError($res) === 'Faltan campos obligatorios: nombre.', 'cancelled edit: only the name is required, got ' . flashError($res));
$report[] = 'admin edita cancelada incompleta -> guardada (exenta, salvo nombre)';

// ─── 5. Reactivation requires everything except bootcamp_type ───────────
$reactivate = ['_csrf' => 'test-csrf', 'target_status' => 'reactivate'];
$res = scenario('admin', 'POST', '/cohorts/' . CANCELLED_INCOMPLETE_ID . '/status', $reactivate);
check(flashError($res) === 'Antes de reactivar la cohorte debes completar: proyecto, fecha de fin.', 'reactivate incomplete: got ' . flashError($res));
check(cohort($res, CANCELLED_INCOMPLETE_ID)['training_status'] === 'cancelled', 'reactivate incomplete: stays cancelled');
$res = scenario('admin', 'POST', '/cohorts/' . CANCELLED_NO_BOOTCAMP_ID . '/status', $reactivate);
check(flashError($res) === '', 'reactivate without bootcamp_type: allowed, got ' . flashError($res));
check(cohort($res, CANCELLED_NO_BOOTCAMP_ID)['training_status'] !== 'cancelled', 'reactivated');
$report[] = 'reactivar sin proyecto/fin -> bloqueada; sin bootcamp_type -> reactivada';

// ─── 6. Import: rows without project or dates are rejected ──────────────
$csv = "name,bootcamp_name,project,start_date,end_date\n"
    . "Completa,FSJ,KODIGO,2026-11-02,2027-01-29\n"   // row 2
    . "Sin proyecto,FSJ,,2026-11-02,2027-01-29\n"      // row 3
    . "Sin inicio,FSJ,KODIGO,,2027-01-29\n"            // row 4
    . "Sin fin,FSJ,KODIGO,2026-11-02,\n"               // row 5
    . ",,,,2027-01-29\n"                               // row 6
    . "Sin bootcamp,,KODIGO,2026-12-01,2027-02-26\n";  // row 7
$res = scenario('admin', 'IMPORT', '', [], $csv);
$errors = array_column($res['import']['errors'], 'message', 'row');
check($res['import']['inserted'] === 2 && $res['import']['failed'] === 4, 'import: 2 inserted, 4 failed');
check(($errors[3] ?? '') === 'Faltan campos obligatorios: proyecto.', 'import row without project');
check(($errors[4] ?? '') === 'Faltan campos obligatorios: fecha de inicio.', 'import row without start');
check(($errors[5] ?? '') === 'Faltan campos obligatorios: fecha de fin.', 'import row without end');
check(($errors[6] ?? '') === 'Faltan campos obligatorios: nombre, proyecto, fecha de inicio.', 'import row missing several');
check(!isset($errors[2]) && !isset($errors[7]), 'import: complete rows (with or without bootcamp) pass');
$res = scenario('admin', 'IMPORT', '', [], "name,start_date,end_date\nSin columna,2026-11-02,2027-01-29\n");
check($res['import']['inserted'] === 0, 'import without project column inserts nothing');
check(in_array('Falta la columna obligatoria: related_project', array_column($res['import']['errors'], 'message'), true), 'import: project column required');
$report[] = 'importar: sin proyecto / sin inicio / sin fin -> filas rechazadas; sin columna project -> archivo rechazado';

// ─── 7. Forms: required + asterisk, cancelled and limited roles, help line ─
$body = scenario('admin', 'GET', '/cohorts/create')['body'];
foreach (['name', 'related_project', 'start_date', 'end_date'] as $id) {
    check(str_contains(inputTag($body, $id), ' required'), "create: #{$id} required");
    check(labelHasAsterisk($body, $id), "create: #{$id} asterisk");
}
check(!str_contains(inputTag($body, 'bootcamp_type'), ' required') && !labelHasAsterisk($body, 'bootcamp_type'), 'create: bootcamp_type optional');
check(str_contains($body, HELP_TEXT), 'create: enrolment origin help');

$body = scenario('admin', 'GET', '/cohorts/' . OLD_INCOMPLETE_ID . '/edit')['body'];
foreach (['related_project', 'start_date', 'end_date'] as $id) {
    check(str_contains(inputTag($body, $id), ' required') && labelHasAsterisk($body, $id), "admin edit: #{$id} required + asterisk");
}
check(!str_contains(inputTag($body, 'bootcamp_type'), ' required'), 'admin edit: bootcamp_type optional');
check(str_contains($body, HELP_TEXT), 'edit: enrolment origin help');

$body = scenario('admin', 'GET', '/cohorts/' . CANCELLED_INCOMPLETE_ID . '/edit')['body'];
foreach (['related_project', 'start_date', 'end_date'] as $id) {
    check(!str_contains(inputTag($body, $id), ' required') && !labelHasAsterisk($body, $id), "cancelled edit: #{$id} not required");
}
check(str_contains(inputTag($body, 'name'), ' required'), 'cancelled edit: name still required');

$body = scenario('admissions_b2b', 'GET', '/cohorts/' . OLD_INCOMPLETE_ID . '/edit')['body'];
foreach (['related_project', 'start_date', 'end_date'] as $id) {
    check(!str_contains(inputTag($body, $id), ' required'), "admissions_b2b edit: read-only #{$id} not required");
}
check(str_contains($body, HELP_TEXT), 'admissions_b2b edit: enrolment origin help');
$report[] = 'formularios: required + * en create/edit admin; no en cancelada ni en solo lectura; ayuda de origen visible';

echo implode("\n", $report), "\n";
echo "PASS: DATA-002 required fields on create, admin edit, import and reactivation; limited roles and cancelled exempt (synthetic SQLite).\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one request (or import) against a fresh in-memory database.
// ═════════════════════════════════════════════════════════════════════════
function runScenario(array $spec): void
{
    define('APP_ROOT', dirname(__DIR__));
    require APP_ROOT . '/vendor/autoload.php'; // PhpSpreadsheet for the import scenarios
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        }
    }, true, true);

    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->exec("CREATE TABLE cohorts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, cohort_code TEXT UNIQUE NOT NULL, name TEXT NOT NULL,
        correlative_number INTEGER NOT NULL DEFAULT 0,
        total_admission_target INTEGER NOT NULL DEFAULT 0, b2b_admission_target INTEGER NOT NULL DEFAULT 0,
        b2c_admission_target INTEGER NOT NULL DEFAULT 0, b2b_admissions INTEGER NOT NULL DEFAULT 0,
        b2c_admissions INTEGER NOT NULL DEFAULT 0, incaf_enrolled INTEGER, incaf_b2b INTEGER, incaf_b2c INTEGER,
        financial_target_revenue NUMERIC NOT NULL DEFAULT 0, financial_actual_revenue NUMERIC NOT NULL DEFAULT 0,
        admission_deadline_date TEXT, start_date TEXT, end_date TEXT, related_project TEXT, assigned_coach TEXT,
        bootcamp_type TEXT, area TEXT, assigned_class_schedule TEXT,
        training_status TEXT NOT NULL DEFAULT 'not_started',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, full_name TEXT, role TEXT, is_active INTEGER DEFAULT 1);
    CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, entity_type TEXT,
        entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT, created_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (id, cohort_code, name, b2b_admission_target, b2c_admission_target,
        start_date, end_date, related_project, assigned_coach, bootcamp_type, assigned_class_schedule, training_status)
        VALUES (?, ?, ?, 5, 5, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([OLD_INCOMPLETE_ID, 'OLDINC', 'Vieja incompleta', null, null, null, 'Coach Viejo', 'PY', 'Lun 18:00', 'not_started']);
    $insert->execute([CANCELLED_INCOMPLETE_ID, 'CANINC', 'Cancelada incompleta', '2026-01-10', null, null, 'Coach', 'PY', 'Lun 18:00', 'cancelled']);
    $insert->execute([CANCELLED_NO_BOOTCAMP_ID, 'CANNOBC', 'Cancelada sin bootcamp', '2026-12-07', '2027-02-26', 'KODIGO', 'Coach', null, 'Mar 18:00', 'cancelled']);
    $pdo->exec("INSERT INTO users (id, username, full_name, role) VALUES (1, 'probe', 'DATA-002 probe', '{$spec['role']}')");

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    // Synthetic authenticated session; authentication itself is outside this test.
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'DATA-002 probe', 'email' => '', 'role' => $spec['role']],
        App\Core\Csrf::SESSION_KEY => 'test-csrf',
    ];

    $import = null;
    register_shutdown_function(static function () use ($pdo, &$import): void {
        $body = '';
        while (ob_get_level() > 0) {
            $body = ob_get_clean() . $body;
        }
        echo RESULT_MARKER, json_encode([
            'status'  => http_response_code() ?: 200, // CLI reports false for the implicit 200
            'body'    => $body,
            'cohorts' => $pdo->query('SELECT * FROM cohorts ORDER BY id')->fetchAll(),
            'flash'   => $_SESSION['_flash'] ?? [],
            'import'  => $import,
        ]);
    });

    ob_start();
    if ($spec['method'] === 'IMPORT') {
        $file = tempnam(sys_get_temp_dir(), 'data002') . '.csv';
        file_put_contents($file, $spec['csv']);
        try {
            $import = (new App\Services\CohortImportService())->processFile($file, 'prueba.csv');
        } finally {
            @unlink($file);
            @unlink(substr($file, 0, -4));
        }
        return;
    }

    $_SERVER['REQUEST_METHOD'] = $spec['method'];
    $_SERVER['REQUEST_URI'] = $spec['path'];
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    $_GET = [];
    $_POST = $spec['post'];
    $_REQUEST = $_POST;

    $router = new App\Core\Router();
    require APP_ROOT . '/routes/web.php';
    $router->dispatch();
}

// Same contracts as bootstrap/app.php helpers, without reading .env.
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}
function csrf_token(): string
{
    return App\Core\Csrf::token();
}
function csrf_field(): string
{
    return App\Core\Csrf::field();
}
