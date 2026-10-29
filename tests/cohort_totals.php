<?php
declare(strict_types=1);

// DATA-003 regression: Dashboard and Plan Maestro totals come from one query
// (CohortRepository::getTotals) and one percentage formula (CohortService::admissionPct).
//  - Without filters both screens show the same cohorts; since DASH-007 the Dashboard
//    admissions block is the current start year, equal to Plan Maestro filtered by a
//    start range on that whole year (same admissions, target and %, 1 decimal).
//  - With a Plan Maestro filter (status, bootcamp type) its cards change, match the sum of
//    the rows it lists, and the filter is visible as a chip; with none it says so.
//  - The Dashboard states its scope and cut-off date (El Salvador business date).
// Fixture: 12 cohorts incl. cancelled, without dates, without type and over-admitted.
// Pages run in child PHP processes through the real Router, controllers and views.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_totals.php

// 02:00 UTC on 2026-10-06 is 20:00 on 2026-10-05 in El Salvador: the cut-off
// date must be 05/10/2026 even though the UTC date is already the 6th.
const NOW_UTC = '2026-10-06 02:00:00';
const CUTOFF = '05/10/2026';
const RESULT_MARKER = '@@DATA003@@';

define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Services\CohortService;

/** code => [stored status, start, end, bootcamp type, total target, b2b target, b2b adm, b2c adm] */
function fixture(): array
{
    return [
        'RUN_A'      => ['not_started', '2026-09-01', '2026-12-01', 'Full Stack', 30, 10, 8, 15],
        'RUN_B'      => ['in_progress', '2026-08-01', '2026-11-01', 'Data',       25,  0, 0, 20],
        'DONE_A'     => ['completed',   '2026-01-01', '2026-05-01', 'Full Stack', 40, 20, 20, 18],
        'DONE_STALE' => ['in_progress', '2026-02-01', '2026-06-01', 'UX/UI',      20,  5, 5, 12],
        'NEXT_A'     => ['not_started', '2026-11-01', '2027-02-01', 'Data',       35, 15, 2, 3],
        'NEXT_B'     => ['completed',   '2026-12-01', '2027-03-01', 'UX/UI',      10,  0, 0, 0],
        'CANCEL_A'   => ['cancelled',   '2026-09-15', '2026-12-15', 'Full Stack', 30, 10, 4, 6],
        'CANCEL_B'   => ['cancelled',   null,         null,         null,         15,  0, 0, 1],
        'NODATE_A'   => ['not_started', null,         null,         'Data',       20,  5, 1, 2],
        'NODATE_B'   => ['not_started', null,         '2026-12-31', '',           12,  0, 0, 5],
        'NOTYPE_A'   => ['not_started', '2026-09-20', '2026-12-20', null,         18,  6, 6, 9],
        'OVERADMIT'  => ['in_progress', '2026-09-10', '2026-12-10', 'AI',         10, 12, 12, 4],
    ];
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

/** SQLite stand-in for the one MySQL-only construct on the Dashboard (findUpcoming). */
final class SqlitePdo extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = preg_replace('/DATE_ADD\((.+?), INTERVAL (:\w+) DAY\)/', "date($1, '+' || $2 || ' days')", $query);
        return parent::prepare($query, $options);
    }
}

if (($argv[1] ?? '') === '--page') {
    runPage(json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR));
    exit(0);
}

function page(string $path): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode(['path' => $path]))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$path}): " . substr($output, -800));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    check($result['status'] === 200, "{$path} status {$result['status']}");
    check(!preg_match('/(Fatal error|Warning|Notice|Deprecated):/', $result['body']), "PHP error in {$path}: " . substr($result['body'], 0, 600));
    return $result;
}

function num(string $text): int
{
    return (int) str_replace(',', '', $text);
}

/** Plan Maestro: summary cards, the rows it lists and its filter chips. */
function master(string $query = ''): array
{
    $html = page('/cohorts/master' . $query)['body'];
    preg_match('/<strong>(\d+)<\/strong>\s*<small>Cohortes filtradas<\/small>/', $html, $total);
    preg_match('/<strong>(\d+) \/ (\d+)<\/strong>\s*<small>Inscritos \(([\d.]+)%\)<\/small>/', $html, $adm);
    preg_match_all('/<span>(\d+) \/ (\d+)<\/span>\s*<span>\d+%<\/span>/', $html, $rows);
    preg_match('/data-active-filters="(\d+)">(.*?)<\/div>/s', $html, $chips);
    check(isset($total[1], $adm[3], $chips[1]), "Plan Maestro{$query}: cards/chips not found");
    return [
        'cards' => ['cohorts' => (int) $total[1], 'admissions' => (int) $adm[1], 'target' => (int) $adm[2], 'pct' => (float) $adm[3]],
        'rows'  => ['cohorts' => count($rows[0]), 'admissions' => array_sum(array_map('intval', $rows[1])), 'target' => array_sum(array_map('intval', $rows[2]))],
        'chips' => ['count' => (int) $chips[1], 'text' => trim(preg_replace('/\s+/', ' ', strip_tags($chips[2])))],
    ];
}

// ─── 1. One percentage formula ───────────────────────────────────────────
check(CohortService::admissionPct(153, 265) === 57.7, 'pct 1 decimal');
check(CohortService::admissionPct(1, 3) === 33.3, 'pct rounding');
check(CohortService::admissionPct(5, 0) === 0.0 && CohortService::admissionPct(0, 0) === 0.0, 'pct with target 0');
check(CohortService::admissionPct(16, 10) === 100.0, 'pct capped at 100');

// Expected values straight from the fixture (no filters: every row, cancelled included).
$fx = fixture();
$sum = static fn(array $rows): array => [
    'cohorts'    => count($rows),
    'admissions' => array_sum(array_map(static fn($r) => $r[6] + $r[7], $rows)),
    'target'     => array_sum(array_map(static fn($r) => $r[4], $rows)),
];
$all = $sum($fx);
$all['pct'] = round($all['admissions'] / $all['target'] * 100, 1);
// DASH-007: the admissions block counts cohorts that start in 2026 (current year).
$year = $sum(array_filter($fx, static fn($r) => str_starts_with((string) $r[1], '2026')));
$year['pct'] = round($year['admissions'] / $year['target'] * 100, 1);

// ─── 2. Without filters: same cohorts; admissions block = Plan Maestro 2026 ─
$dash = page('/')['body'];
preg_match('/Total cohortes<\/p>\s*<h3 class="metric-card__value">([\d,]+)<\/h3>/', $dash, $dTotal);
preg_match('/Actuales<\/span>\s*<strong>([\d,]+)<\/strong>/', $dash, $dAdm);
preg_match('/Meta<\/span>\s*<strong>([\d,]+)<\/strong>/', $dash, $dTarget);
preg_match('/Progreso de admisiones.*?kodigo-pill__dot" aria-hidden="true"><\/span>([\d.]+)%<\/span>/s', $dash, $dPct);
check(isset($dTotal[1], $dAdm[1], $dTarget[1], $dPct[1]), 'Dashboard numbers not found');
$dashboard = ['cohorts' => num($dTotal[1]), 'admissions' => num($dAdm[1]), 'target' => num($dTarget[1]), 'pct' => (float) $dPct[1]];
check($dashboard === ['cohorts' => $all['cohorts']] + array_diff_key($year, ['cohorts' => 0]), 'Dashboard ' . json_encode($dashboard) . ' vs fixture ' . json_encode(['all' => $all, '2026' => $year]));

$m = master();
check($m['cards'] === $all, 'Plan Maestro ' . json_encode($m['cards']) . ' vs fixture ' . json_encode($all));
check($m['cards']['cohorts'] === $dashboard['cohorts'], 'Plan Maestro cohorts = Dashboard Total cohortes');
check($m['rows'] === array_diff_key($m['cards'], ['pct' => 0]), 'Plan Maestro cards = sum of its rows');
check($m['chips'] === ['count' => 0, 'text' => 'Sin filtros: todas las cohortes'], 'Plan Maestro says it has no filters');
$m2026 = master('?start_date=2026-01-01&end_date=2026-12-31');
check($m2026['cards'] === $year, 'Plan Maestro 2026 ' . json_encode($m2026['cards']) . ' vs fixture ' . json_encode($year));
check(array_diff_key($m2026['cards'], ['cohorts' => 0]) === array_diff_key($dashboard, ['cohorts' => 0]), 'Dashboard admissions block = Plan Maestro 2026');

// Dashboard scope line with the El Salvador cut-off date.
check(preg_match('/data-dashboard-scope>.*?Totales y estados: todas las cohortes \(incluye canceladas y sin fechas\), corte ' . preg_quote(CUTOFF, '/') . ' &middot; Admisiones: cohortes que inician en 2026/s', $dash) === 1, 'Dashboard scope + cut-off ' . CUTOFF);
check(!str_contains($dash, '06/10/2026'), 'Dashboard must not show the UTC date');

// ─── 3. With filters: Plan Maestro changes, matches its rows, shows the filter ─
$cases = [
    '?cohort_status=cancelled' => [array_filter($fx, static fn($r) => $r[0] === 'cancelled'), 'Estado: Cancelado'],
    '?bootcamp_type=Data'      => [array_filter($fx, static fn($r) => $r[3] === 'Data'), 'Bootcamp: Data'],
];
foreach ($cases as $query => [$rows, $chip]) {
    $want = $sum($rows);
    $f = master($query);
    check($f['rows'] === $want, "{$query}: rows " . json_encode($f['rows']) . ' vs ' . json_encode($want));
    check(array_diff_key($f['cards'], ['pct' => 0]) === $want, "{$query}: cards = sum of the rows listed");
    check($f['cards']['pct'] === CohortService::admissionPct($want['admissions'], $want['target']), "{$query}: pct");
    check($f['cards']['cohorts'] !== $dashboard['cohorts'], "{$query}: differs from the Dashboard");
    check($f['chips'] === ['count' => 1, 'text' => 'Filtros activos ' . $chip], "{$query}: visible chip, got " . json_encode($f['chips']));
}

// /cohorts uses the same chips.
$list = page('/cohorts?cohort_status=cancelled')['body'];
check(preg_match('/data-active-filters="1">.*?Estado:\s*<strong>Cancelado<\/strong>/s', $list) === 1, '/cohorts shows the same chip');

echo 'Sin filtros: ' . json_encode($all) . ', inicio en 2026: ' . json_encode($year) . " (Dashboard = Plan Maestro)\n";
echo "PASS: one totals query + one % formula (1 decimal) for Dashboard and Plan Maestro, filtered totals = listed rows, visible filters/scope (synthetic SQLite).\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one GET against a fresh in-memory database, as admin.
// ═════════════════════════════════════════════════════════════════════════
function runPage(array $spec): void
{
    $pdo = new SqlitePdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->sqliteCreateFunction('CURDATE', static fn(): string => '2026-10-05', 0);
    $pdo->sqliteCreateFunction('DATE_FORMAT', static function (?string $date, string $format): ?string {
        return $date === null ? null : date(strtr($format, ['%Y' => 'Y', '%m' => 'm', '%b' => 'M']), strtotime($date));
    }, 2);
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
    CREATE TABLE cohort_comments (id INTEGER PRIMARY KEY, cohort_id INTEGER, section_id INTEGER, user_id INTEGER,
        category TEXT, body TEXT, created_at TEXT);
    CREATE TABLE marketing_stages (id INTEGER PRIMARY KEY, cohort_id INTEGER, stage_name TEXT, status TEXT,
        notes TEXT, updated_by INTEGER, updated_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (cohort_code, name, training_status, start_date, end_date, bootcamp_type,
        total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions,
        financial_target_revenue, financial_actual_revenue)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach (fixture() as $code => [$st, $s, $e, $type, $target, $b2bTarget, $b2b, $b2c]) {
        $insert->execute([$code, $code, $st, $s, $e, $type, $target, $b2bTarget, max(0, $target - $b2bTarget), $b2b, $b2c, $target * 100, ($b2b + $b2c) * 90]);
    }

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    App\Services\CohortStatus::freezeNow(NOW_UTC);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'DATA-003 probe', 'email' => '', 'role' => 'admin'],
        App\Core\Csrf::SESSION_KEY => 'test-csrf',
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $spec['path'];
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    parse_str((string) parse_url($spec['path'], PHP_URL_QUERY), $_GET);
    $_POST = [];
    $_REQUEST = $_GET;

    register_shutdown_function(static function (): void {
        $body = '';
        while (ob_get_level() > 0) {
            $body = ob_get_clean() . $body;
        }
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR], true)) {
            $body .= "\nFatal error: " . $error['message'];
        }
        echo RESULT_MARKER, json_encode(['status' => http_response_code() ?: 200, 'body' => $body], JSON_INVALID_UTF8_SUBSTITUTE);
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
