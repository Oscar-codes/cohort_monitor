<?php
declare(strict_types=1);

// OLA 3 regression: Dashboard cards, their links and Detalle follow the single
// status rule (CohortStatus) with the El Salvador date as "today".
//  DASH-003  "En progreso" card = Cohortes card = "Estado de cohortes"; subtitle
//            "Corte dd/mm/aaaa"; changes by itself when a cohort reaches its start.
//  DASH-004  "Completado" card = completed with end_date in the current year
//            ("Cerradas en 2026"), also a cohort Dec 2025 -> Feb 2026; card = its link.
//  DASH-008  "Estado de cohortes" uses the rule, says "Cancelado", adds up to the
//            total and matches the Cohortes cards.
//  DASH-012  "Próximos inicios" = start in (today, today + 30], no cancelled, by date;
//            "Ver agenda" = same set in Cohortes and Plan Maestro.
//  COH-005/006 status filters return as many rows as the matching cards.
//  DET-001   training progress by dates (1 decimal), "Sin fechas" / "Sin fecha de fin".
// Pages run in child PHP processes through the real Router, controllers and views.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/dashboard_detail_rules.php

const TODAY = '2026-10-08';
const RESULT_MARKER = '@@OLA3@@';

define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Repositories\CohortRepository;
use App\Services\CohortStatus;

/**
 * Fixture at TODAY: code => [stored training_status, start_date, end_date].
 * Stored values are deliberately stale where it matters: dates must win.
 */
function fixture(): array
{
    return [
        'DEC_FEB'      => ['in_progress', '2025-12-01', '2026-02-15'], // completed, closed in 2026
        'DONE_2025'    => ['completed',   '2025-06-01', '2025-11-30'], // completed, closed in 2025
        'DONE_YDAY'    => ['in_progress', '2026-08-01', '2026-10-07'], // completed yesterday
        'ENDS_TODAY'   => ['in_progress', '2026-07-01', TODAY],        // still in progress
        'CANCEL_DONE'  => ['cancelled',   '2026-01-10', '2026-03-10'], // cancelled, not "closed"
        'CANCEL_SOON'  => ['cancelled',   '2026-10-13', '2026-12-13'], // cancelled, not upcoming
        'STARTS_TODAY' => ['not_started', TODAY,        '2026-12-08'], // already started
        'STARTS_P1'    => ['not_started', '2026-10-09', '2026-12-09'], // upcoming
        'STARTS_P30'   => ['completed',   '2026-11-07', '2027-01-07'], // upcoming (last day)
        'STARTS_P31'   => ['not_started', '2026-11-08', '2027-01-08'], // outside the window
        'NO_DATES'     => ['not_started', null,         null],
        'ONLY_END'     => ['not_started', null,         '2026-05-01'], // "Sin fechas"
        'ONLY_START'   => ['not_started', '2026-09-01', null],         // "Sin fecha de fin"
        'AIGSK28'      => ['not_started', '2026-10-05', '2026-11-06'], // 9.4 % at TODAY
    ];
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

/** Fresh in-memory database with the fixture; injected as the Database singleton. */
function bootDatabase(string $today): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->sqliteCreateFunction('CURDATE', static fn(): string => $today, 0);
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
        training_status TEXT NOT NULL DEFAULT 'not_started'
            CHECK (training_status IN ('not_started','in_progress','completed','cancelled')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, full_name TEXT, role TEXT, is_active INTEGER DEFAULT 1);
    CREATE TABLE cohort_comments (id INTEGER PRIMARY KEY AUTOINCREMENT, cohort_id INTEGER, section_id INTEGER,
        user_id INTEGER, category TEXT, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE marketing_stages (id INTEGER PRIMARY KEY AUTOINCREMENT, cohort_id INTEGER, stage_name TEXT,
        status TEXT, notes TEXT, updated_by INTEGER, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE cohort_marketing_info (cohort_id INTEGER PRIMARY KEY, campaign_status TEXT DEFAULT 'Active');
    CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, entity_type TEXT,
        entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT, created_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (cohort_code, name, training_status, start_date, end_date, bootcamp_type, area,
        related_project, assigned_coach, assigned_class_schedule, total_admission_target, b2c_admission_target)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 10, 10)');
    foreach (fixture() as $code => [$stored, $start, $end]) {
        $insert->execute([$code, 'Cohorte ' . $code, $stored, $start, $end, 'Test', 'academic', 'Proyecto', 'Coach', 'L-V 18:00']);
    }
    $pdo->exec("INSERT INTO users (id, username, full_name, role) VALUES (1, 'probe', 'OLA3 probe', 'admin')");

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    CohortStatus::freezeToday($today);

    return $pdo;
}

if (($argv[1] ?? '') === '--page') {
    runPage(json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR));
    exit(0);
}

/** Request one page (admin) in a child process with "today" pinned. */
function page(string $path, string $today = TODAY): string
{
    $spec = compact('path', 'today');
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode($spec))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$path}): " . substr($output, -800));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    check($result['status'] === 200, "{$path}: HTTP {$result['status']}");
    check(!preg_match('/(Fatal error|Warning|Notice|Deprecated):/', (string) $result['body']), "PHP error in {$path}: " . substr($result['body'], 0, 600));
    return (string) $result['body'];
}

function cardValue(string $html, string $attribute, string $key): int
{
    check(preg_match('/' . $attribute . '="' . preg_quote($key, '/') . '">([\d,]+)</', $html, $m) === 1, "{$attribute}={$key} not found");
    return (int) str_replace(',', '', $m[1]);
}

/** "Estado de cohortes" (accessible table): label => count. */
function donut(string $html): array
{
    preg_match('/Cohortes por estado<\/caption>.*?<tbody>(.*?)<\/tbody>/s', $html, $m);
    preg_match_all('/<tr><td>([^<]+)<\/td><td>(\d+)<\/td><\/tr>/', $m[1] ?? '', $rows, PREG_SET_ORDER);
    $out = [];
    foreach ($rows as [, $label, $count]) {
        $out[$label] = (int) $count;
    }
    return $out;
}

function listTotal(string $html): int
{
    return cardValue($html, 'data-status-card', 'total');
}

function masterTotal(string $html): int
{
    check(preg_match('/<strong>(\d+)<\/strong>\s*<small>Cohortes filtradas<\/small>/', $html, $m) === 1, 'Plan Maestro total not found');
    return (int) $m[1];
}

function hasChip(string $html, string $label, string $value): bool
{
    return preg_match('/active-filter-chip">\s*' . preg_quote($label, '/') . ':\s*<strong>' . preg_quote($value, '/') . '<\/strong>/u', $html) === 1;
}

/** Cohort codes listed in Cohortes, sorted. */
function listedCodes(string $html): array
{
    preg_match_all('#cohort-mobile-card__code">([^<]+)<#', $html, $m);
    $codes = array_values(array_unique($m[1]));
    sort($codes);
    return $codes;
}

/** Independent expectations computed from the fixture, not from app code. */
$codes = array_keys(fixture());
$idOf = array_flip($codes);
array_walk($idOf, static function (int &$i): void { $i++; });
$expectedStatus = [];
foreach (fixture() as $code => [$stored, $start, $end]) {
    $expectedStatus[$code] = match (true) {
        $stored === 'cancelled' => 'cancelled',
        $start === null         => 'no_dates',
        TODAY < $start          => 'not_started',
        $end === null || TODAY <= $end => 'in_progress',
        default                 => 'completed',
    };
}
$expectedClosed2026 = ['DEC_FEB', 'DONE_YDAY'];
$expectedUpcoming = ['STARTS_P1', 'STARTS_P30'];
$expectedInProgress = array_keys(array_filter($expectedStatus, static fn($s) => $s === 'in_progress'));
check(count($expectedInProgress) === 4, 'fixture sanity: 4 in progress');

// ─── DET-001: progress by dates in the status layer ──────────────────────
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-10-08') === 9.4, 'AIGSK28 at 08/10/2026 = 9.4');
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-10-05') === 0.0, 'today = start -> 0');
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-10-01') === 0.0, 'before start -> 0');
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-11-06') === 100.0, 'today = end -> 100');
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-12-01') === 100.0, 'after end -> 100');
check(CohortStatus::trainingProgress('2026-10-05', '2026-11-06', '2026-10-21') === 50.0, 'half way -> 50');
check(CohortStatus::trainingProgress(null, null, TODAY) === null && CohortStatus::trainingProgress('2026-10-05', null, TODAY) === null, 'missing dates -> null');
check(CohortStatus::trainingProgressLabel('2026-10-05', '2026-11-06', TODAY) === '9.4 %', 'label 9.4 %');
check(CohortStatus::trainingProgressLabel(null, null, TODAY) === 'Sin fechas', 'label Sin fechas');
check(CohortStatus::trainingProgressLabel(null, '2026-05-01', TODAY) === 'Sin fechas', 'only end -> Sin fechas');
check(CohortStatus::trainingProgressLabel('2026-09-01', null, TODAY) === 'Sin fecha de fin', 'label Sin fecha de fin');
// "today" is the El Salvador date: 03:00 UTC on 09/10 is still 08/10 in San Salvador.
CohortStatus::freezeToday(null);
CohortStatus::freezeNow('2026-10-09 03:00:00');
check(CohortStatus::trainingProgressLabel('2026-10-05', '2026-11-06') === '9.4 %', 'progress uses the El Salvador date');
CohortStatus::freezeNow(null);

$detail = static function (string $code, string $today = TODAY) use ($idOf): string {
    $html = page('/cohorts/' . $idOf[$code], $today);
    check(preg_match('/data-training-progress>([^<]+)</', $html, $m) === 1, "Detalle {$code}: progress not found");
    return trim($m[1]);
};
check($detail('AIGSK28') === '9.4 %', 'Detalle AIGSK28 shows 9.4 %');
check($detail('AIGSK28', '2026-10-21') === '50.0 %', 'Detalle recalculates on every request');
check($detail('NO_DATES') === 'Sin fechas', 'Detalle NO_DATES shows Sin fechas');
check($detail('ONLY_END') === 'Sin fechas', 'Detalle ONLY_END shows Sin fechas');
check($detail('ONLY_START') === 'Sin fecha de fin', 'Detalle ONLY_START shows Sin fecha de fin');

// ─── Repository: card queries vs independent expectations ────────────────
bootDatabase(TODAY);
$repo = new CohortRepository();
$codeOf = static fn(array $rows): array => array_map(static fn(array $r): string => $r['cohort_code'], $rows);
check($codeOf($repo->findUpcoming(30, 10)) === $expectedUpcoming, 'findUpcoming = (today, today+30], ordered, no cancelled: ' . json_encode($codeOf($repo->findUpcoming(30, 10))));
check($repo->getTotals(['closed_year' => '2026'])['cohorts'] === count($expectedClosed2026), 'closed_year 2026 count');
$closedRows = $codeOf($repo->findByFilters(['closed_year' => '2026']));
sort($closedRows);
check($closedRows === $expectedClosed2026, 'closed_year 2026 rows = DEC_FEB, DONE_YDAY: ' . json_encode($closedRows));
check($repo->getTotals(['closed_year' => '2025'])['cohorts'] === 1, 'closed_year 2025 = DONE_2025 only');
CohortStatus::freezeToday(null);
CohortStatus::freezeNow('2026-10-09 03:00:00'); // 08/10 21:00 in San Salvador
check($codeOf($repo->findUpcoming(30, 10)) === $expectedUpcoming, 'findUpcoming uses the El Salvador date, not the DB clock');
CohortStatus::freezeNow(null);

// ─── Dashboard ───────────────────────────────────────────────────────────
$dash = page('/');
$list = page('/cohorts');
$donut = donut($dash);

// DASH-008: rule, Spanish labels, adds up to the total, = Cohortes cards.
check(array_keys($donut) === array_values(CohortStatus::LABELS), 'Estado de cohortes labels ' . json_encode(array_keys($donut), JSON_UNESCAPED_UNICODE));
check(isset($donut['Cancelado']) && $donut['Cancelado'] === 2, 'Cancelado bucket');
check(array_sum($donut) === count(fixture()) && listTotal($list) === count(fixture()), 'states add up to the total');
foreach (CohortStatus::LABELS as $status => $label) {
    $want = count(array_filter($expectedStatus, static fn($s) => $s === $status));
    check($donut[$label] === $want, "Estado de cohortes {$label}: {$donut[$label]} vs {$want}");
    check(cardValue($list, 'data-status-card', $status) === $want, "Cohortes card {$label}");
}
$dashText = strip_tags(preg_replace('/<(script|style|textarea)\b.*?<\/\1>/si', '', $dash));
foreach (['Planned', 'In progress', 'Completed', 'Cancelled', 'Not started'] as $english) {
    check(stripos($dashText, $english) === false, "English label '{$english}' on the Dashboard");
}

// DASH-003: "En progreso" card.
$dashInProgress = cardValue($dash, 'data-dashboard-card', 'in_progress');
check($dashInProgress === 4, "Dashboard En progreso = 4, got {$dashInProgress}");
check($dashInProgress === cardValue($list, 'data-status-card', 'in_progress') && $dashInProgress === $donut['En progreso'], 'En progreso card = Cohortes card = Estado de cohortes');
check(str_contains($dash, 'Corte 08/10/2026'), 'En progreso subtitle shows Corte 08/10/2026');
$before = page('/', '2026-10-04');
check(cardValue($before, 'data-dashboard-card', 'in_progress') === 3 && str_contains($before, 'Corte 04/10/2026'), 'day before AIGSK28 starts: 3');
check(cardValue(page('/', '2026-10-05'), 'data-dashboard-card', 'in_progress') === 4, 'AIGSK28 start day: 4 with no manual action');

// DASH-004: "Completado" card = closed in the current year; card = its link.
$dashClosed = cardValue($dash, 'data-dashboard-card', 'closed_year');
check($dashClosed === count($expectedClosed2026), "Completado card = 2 (DEC_FEB, DONE_YDAY), got {$dashClosed}");
check(str_contains($dash, 'Cerradas en 2026'), 'Completado subtitle shows the period');
check(preg_match('#href="(/cohorts\?closed_year=2026)">Ver historial#', $dash, $m) === 1, 'Ver historial link');
$closedList = page($m[1]);
check(listTotal($closedList) === $dashClosed, 'Ver historial results = card');
check(listedCodes($closedList) === $expectedClosed2026, 'Ver historial lists DEC_FEB and DONE_YDAY: ' . json_encode(listedCodes($closedList)));
check(hasChip($closedList, 'Cerradas en', '2026'), 'Cohortes chip Cerradas en: 2026');
$closedMaster = page('/cohorts/master?closed_year=2026');
check(masterTotal($closedMaster) === $dashClosed && hasChip($closedMaster, 'Cerradas en', '2026'), 'Plan Maestro closed_year = card + chip');
check($donut['Completado'] === 3, 'Estado de cohortes keeps every completed cohort (3)');
check(cardValue(page('/', '2027-01-02'), 'data-dashboard-card', 'closed_year') === 0, 'new year: card restarts at 0');

// DASH-012: "Próximos inicios" and "Ver agenda".
check(cardValue($dash, 'data-dashboard-card', 'upcoming') === count($expectedUpcoming), 'Próximos inicios count = 2');
preg_match_all('/data-upcoming-code="([^"]+)"/', $dash, $up);
check($up[1] === $expectedUpcoming, 'Próximos inicios list ' . json_encode($up[1]));
check(str_contains($dash, 'hasta el 07/11/2026'), 'window ends today + 30');
check(preg_match('#<small>(oct|nov)</small>#', $dash) === 1 && preg_match('#<small>(Oct|Nov)</small>#', $dash) === 0, 'month in Spanish');
check(preg_match('#href="(/cohorts\?upcoming_days=30)" class="btn btn-sm btn-outline-primary">Ver agenda#', $dash, $m) === 1, 'Ver agenda link');
$agenda = page($m[1]);
check(listTotal($agenda) === count($expectedUpcoming), 'Ver agenda results = card');
check(listedCodes($agenda) === $expectedUpcoming, 'Ver agenda lists STARTS_P1 and STARTS_P30 only: ' . json_encode(listedCodes($agenda)));
check(hasChip($agenda, 'Inician en', 'próximos 30 días'), 'Cohortes chip Inician en: próximos 30 días');
$agendaMaster = page('/cohorts/master?upcoming_days=30');
check(masterTotal($agendaMaster) === count($expectedUpcoming) && hasChip($agendaMaster, 'Inician en', 'próximos 30 días'), 'Plan Maestro upcoming = card + chip');
check(!hasChip(page('/cohorts?closed_year=abc&upcoming_days=0'), 'Cerradas en', 'abc'), 'invalid window values are ignored');

// COH-005 / COH-006: status filters = cards.
$inProgressList = page('/cohorts?cohort_status=in_progress');
check(listTotal($inProgressList) === $dashInProgress && listTotal($inProgressList) === cardValue($list, 'data-status-card', 'in_progress'), 'COH-005: En progreso filter = Cohortes card = Dashboard card');
$completedList = page('/cohorts?cohort_status=completed');
check(listTotal($completedList) === cardValue($list, 'data-status-card', 'completed') && listTotal($completedList) === $donut['Completado'], 'COH-006: Completado filter = Cohortes card = Estado de cohortes');
check(listTotal(page('/cohorts?cohort_status=completed&closed_year=2026')) === $dashClosed, 'COH-006: Completado + closed_year = Dashboard card');

echo 'Cards at ' . TODAY . ": en progreso {$dashInProgress}, cerradas en 2026 {$dashClosed}, próximos inicios " . count($expectedUpcoming) . "\n";
echo "PASS: DASH-003/004/008/012, COH-005/006 and DET-001 — every card = its link in Cohortes and Plan Maestro, El Salvador date, progress by dates (synthetic SQLite).\n";

function runPage(array $spec): void
{
    $pdo = bootDatabase($spec['today']);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'OLA3 probe', 'email' => '', 'role' => 'admin'],
        App\Core\Csrf::SESSION_KEY => 'test-csrf',
    ];
    $path = $spec['path'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    parse_str((string) parse_url($path, PHP_URL_QUERY), $_GET);
    $_POST = [];
    $_REQUEST = $_GET;

    register_shutdown_function(static function (): void {
        $body = '';
        while (ob_get_level() > 0) {
            $body = ob_get_clean() . $body;
        }
        $hasError = error_get_last();
        echo RESULT_MARKER, json_encode([
            'status' => http_response_code() ?: 200, // CLI reports false for the implicit 200
            'body'   => $body . ($hasError && in_array($hasError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR], true) ? "\nFatal error: " . $hasError['message'] : ''),
        ], JSON_INVALID_UTF8_SUBSTITUTE);
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
