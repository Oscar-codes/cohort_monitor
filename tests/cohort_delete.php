<?php
declare(strict_types=1);

// COH-009 regression: deleting a cohort.
//  - Every non-admin role gets 403 on DELETE /cohorts/{id} and never sees the button.
//  - Admin can only delete after re-typing the cohort_code (also on direct calls).
//  - Each deletion is written to audit_log (user, date, IP, previous row) in the
//    same transaction as the DELETE: if the audit insert fails, nothing is deleted.
// Each scenario runs in its own PHP process through the real Router (method
// override + CSRF), controllers, services and views, because the cohort views
// declare global functions and the guards end the request with exit.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_delete.php

const DELETABLE_ID = 1;
const DELETABLE_CODE = 'TSTDEL1';
const IN_PROGRESS_ID = 2;
const IN_PROGRESS_CODE = 'TSTPROG2';
const RESULT_MARKER = '@@COH009@@';

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

/** Run one request in a child process and return what it observed. */
function scenario(string $role, string $method, string $path, array $post = [], bool $breakAudit = false): array
{
    $spec = compact('role', 'method', 'path', 'post', 'breakAudit');
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --scenario ' . escapeshellarg(base64_encode(json_encode($spec))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$role} {$method} {$path}): " . substr($output, -500));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    $result['output'] = substr($output, 0, $pos);
    return $result;
}

function deleteRequest(string $role, int $id, ?string $code, bool $breakAudit = false): array
{
    $post = ['_method' => 'DELETE', '_csrf' => 'test-csrf'];
    if ($code !== null) {
        $post['confirm_code'] = $code;
    }
    return scenario($role, 'POST', '/cohorts/' . $id, $post, $breakAudit);
}

// ─── 1. Non-admin roles: 403 on direct call, no button anywhere ─────────
$nonAdminRoles = ['admissions_b2b', 'admissions_b2c', 'finance', 'marketing'];
$report = [];
foreach ($nonAdminRoles as $role) {
    $del = deleteRequest($role, DELETABLE_ID, DELETABLE_CODE);
    check($del['status'] === 403, "{$role}: DELETE must be 403, got {$del['status']}");
    check(in_array(DELETABLE_ID, $del['cohort_ids'], true), "{$role}: cohort must remain");
    check($del['audit'] === [], "{$role}: nothing audited");

    $list = scenario($role, 'GET', '/cohorts');
    $show = scenario($role, 'GET', '/cohorts/' . DELETABLE_ID);
    foreach (['list' => $list, 'show' => $show] as $page => $res) {
        check(in_array($res['status'], [200, 403], true), "{$role}: {$page} status {$res['status']}");
        check($res['delete_forms'] === 0, "{$role}: {$page} must not render a delete form");
    }
    $report[] = sprintf('%-15s DELETE=%d  /cohorts=%d (forms %d)  /cohorts/%d=%d (forms %d)',
        $role, $del['status'], $list['status'], $list['delete_forms'], DELETABLE_ID, $show['status'], $show['delete_forms']);
}
// Sanity: the role-gating is real, admissions roles do see the list page.
check(scenario('admissions_b2b', 'GET', '/cohorts')['body_has_cohort'], 'admissions_b2b list must render cohorts');

// ─── 2. Admin sees the button with the typed-confirmation attributes ─────
$adminList = scenario('admin', 'GET', '/cohorts');
check($adminList['status'] === 200 && $adminList['delete_forms'] > 0, 'admin list must render delete forms');
check(in_array(DELETABLE_CODE, $adminList['confirm_codes'], true), 'admin list: data-confirm-code for deletable cohort');
check(!in_array(IN_PROGRESS_CODE, $adminList['confirm_codes'], true), 'admin list: no delete for in_progress cohort');
$adminShow = scenario('admin', 'GET', '/cohorts/' . DELETABLE_ID);
check($adminShow['delete_forms'] === 1 && $adminShow['confirm_codes'] === [DELETABLE_CODE], 'admin show: one delete form with code');
$report[] = sprintf('%-15s /cohorts=%d (forms %d)  /cohorts/%d=%d (forms %d)',
    'admin', $adminList['status'], $adminList['delete_forms'], DELETABLE_ID, $adminShow['status'], $adminShow['delete_forms']);

// ─── 3. Admin direct calls without the right code delete nothing ─────────
foreach ([null, '', 'OTRO', strtolower(DELETABLE_CODE), IN_PROGRESS_CODE] as $badCode) {
    $res = deleteRequest('admin', DELETABLE_ID, $badCode);
    $label = var_export($badCode, true);
    check(in_array(DELETABLE_ID, $res['cohort_ids'], true), "admin code {$label}: cohort must remain");
    check($res['audit'] === [], "admin code {$label}: nothing audited");
    check(str_contains((string) ($res['flash']['error'] ?? ''), 'no coincide'), "admin code {$label}: error flash");
}

// In-progress cohorts stay protected even with the right code.
$res = deleteRequest('admin', IN_PROGRESS_ID, IN_PROGRESS_CODE);
check(in_array(IN_PROGRESS_ID, $res['cohort_ids'], true) && $res['audit'] === [], 'in_progress cohort must remain');

// ─── 4. Admin with the right code: deleted and audited ───────────────────
$res = deleteRequest('admin', DELETABLE_ID, ' ' . DELETABLE_CODE . ' ');
check($res['status'] === 302, 'successful delete redirects');
check(!in_array(DELETABLE_ID, $res['cohort_ids'], true), 'cohort must be deleted');
check(in_array(IN_PROGRESS_ID, $res['cohort_ids'], true), 'other cohorts untouched');
check($res['comments_for_deleted'] === 0, 'cohort_comments cascade');
check(count($res['audit']) === 1, 'exactly one audit row');
$audit = $res['audit'][0];
$old = json_decode((string) $audit['old_values'], true);
$new = json_decode((string) $audit['new_values'], true);
check((int) $audit['user_id'] === 1, 'audit user_id');
check($audit['action'] === 'delete_cohort' && $audit['entity_type'] === 'cohort', 'audit action/entity');
check((int) $audit['entity_id'] === DELETABLE_ID, 'audit entity_id');
check($audit['ip_address'] === '203.0.113.9', 'audit IP');
check(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $audit['created_at']) === 1, 'audit date');
check(($old['cohort_code'] ?? null) === DELETABLE_CODE && ($old['name'] ?? null) === 'Cohorte borrable', 'audit previous values');
check((int) ($old['b2c_admissions'] ?? -1) === 7 && ($old['training_status'] ?? null) === 'not_started', 'audit previous row complete');
check(($new['cohort_code'] ?? null) === DELETABLE_CODE, 'audit cohort_code in new_values');

// ─── 5. If the audit insert fails, the DELETE is rolled back ─────────────
$res = deleteRequest('admin', DELETABLE_ID, DELETABLE_CODE, true);
check(str_contains($res['output'], 'no such table: audit_log'), 'audit insert must have failed');
check(in_array(DELETABLE_ID, $res['cohort_ids'], true), 'audit failure must roll back the delete');

echo implode("\n", $report), "\n";
echo "PASS: role gating (403 + no button), typed cohort_code confirmation, audit_log entry and rollback (synthetic SQLite).\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one request against a fresh in-memory database.
// ═════════════════════════════════════════════════════════════════════════
function runScenario(array $spec): void
{
    define('APP_ROOT', dirname(__DIR__));
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        }
    });

    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->exec('PRAGMA foreign_keys = ON');
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
    CREATE TABLE cohort_comments (id INTEGER PRIMARY KEY AUTOINCREMENT,
        cohort_id INTEGER NOT NULL REFERENCES cohorts(id) ON DELETE CASCADE,
        user_id INTEGER NOT NULL, category TEXT, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, entity_type TEXT,
        entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT, created_at TEXT);");
    $future = date('Y-m-d', strtotime('+60 days'));
    $futureEnd = date('Y-m-d', strtotime('+150 days'));
    $past = date('Y-m-d', strtotime('-30 days'));
    $pastEnd = date('Y-m-d', strtotime('+60 days'));
    $insert = $pdo->prepare('INSERT INTO cohorts (id, cohort_code, name, b2b_admissions, b2c_admissions, start_date, end_date, bootcamp_type, training_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([DELETABLE_ID, DELETABLE_CODE, 'Cohorte borrable', 3, 7, $future, $futureEnd, 'Test', 'not_started']);
    $insert->execute([IN_PROGRESS_ID, IN_PROGRESS_CODE, 'Cohorte en curso', 1, 1, $past, $pastEnd, 'Test', 'in_progress']);
    $pdo->exec("INSERT INTO users (id, username, full_name, role) VALUES (1, 'probe', 'COH-009 probe', '{$spec['role']}')");
    $pdo->exec("INSERT INTO cohort_comments (cohort_id, user_id, category, body) VALUES (1, 1, 'general', 'x')");
    if ($spec['breakAudit']) {
        $pdo->exec('DROP TABLE audit_log');
    }

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    // Synthetic authenticated session; authentication itself is outside this test.
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'COH-009 probe', 'email' => '', 'role' => $spec['role']],
        App\Core\Csrf::SESSION_KEY => 'test-csrf',
    ];
    $_SERVER['REQUEST_METHOD'] = $spec['method'];
    $_SERVER['REQUEST_URI'] = $spec['path'];
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    $_GET = [];
    $_POST = $spec['post'];
    $_REQUEST = $_POST;

    register_shutdown_function(static function () use ($pdo): void {
        $body = '';
        while (ob_get_level() > 0) {
            $body = ob_get_clean() . $body;
        }
        preg_match_all('/data-confirm-code="([^"]*)"/', $body, $codes);
        $hasAudit = (bool) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'audit_log'")->fetchColumn();
        echo RESULT_MARKER, json_encode([
            'status'               => http_response_code() ?: 200, // CLI reports false for the implicit 200
            'delete_forms'         => substr_count($body, 'name="_method" value="DELETE"'),
            'confirm_codes'        => array_values(array_unique($codes[1])),
            'body_has_cohort'      => str_contains($body, DELETABLE_CODE),
            'cohort_ids'           => array_map('intval', $pdo->query('SELECT id FROM cohorts ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)),
            'comments_for_deleted' => (int) $pdo->query('SELECT COUNT(*) FROM cohort_comments WHERE cohort_id = ' . DELETABLE_ID)->fetchColumn(),
            'audit'                => $hasAudit ? $pdo->query('SELECT * FROM audit_log')->fetchAll() : [],
            'flash'                => $_SESSION['_flash'] ?? [],
        ]);
    });

    ob_start();
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
