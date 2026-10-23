<?php
declare(strict_types=1);

// DATA-001 regression: one cohort status rule for the whole app.
//  1. CohortStatus::derive() (PHP) and CohortStatus::sqlCase() (SQL) agree on every
//     edge case: today = start, today = end, day before / after, no dates, only one
//     date, cancelled mid-course, stale stored values (dates always win).
//  2. With a fixed date, Dashboard, Cohortes (cards + filter per status), Plan Maestro,
//     Reportes, Detalle and the Plan Maestro CSV report the same counts per status, and
//     the cards add up to the total.
//  3. A cohort starting 2026-10-05 shows "En progreso" that day with no manual action.
//  4. Saving a cancelled cohort (edit dates, import) never overwrites 'cancelled';
//     other saves keep training_status in sync with the rule (Power BI).
//  5. Only admin can cancel / reactivate (POST /cohorts/{id}/status), with reason + audit.
//  6. No English or synonym status labels in the rendered HTML.
// Pages run in child PHP processes through the real Router, controllers and views
// (views declare global functions and exports end with exit).
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_status_rule.php

const TODAY = '2026-10-05';
const RESULT_MARKER = '@@DATA001@@';

define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Services\CohortStatus;

/** Fixture: code => [stored training_status, start_date, end_date, expected status at TODAY]. */
function fixture(): array
{
    $d = static fn(int $days): string => date('Y-m-d', strtotime(TODAY . " {$days} days"));
    return [
        'AIGSK28'      => ['not_started', TODAY,   $d(90),  'in_progress'], // today = start, stale stored value
        'ENDS_TODAY'   => ['in_progress', $d(-90), TODAY,   'in_progress'], // today = end
        'STARTS_TMRW'  => ['not_started', $d(1),   $d(60),  'not_started'], // day before start
        'ENDED_YDAY'   => ['in_progress', $d(-90), $d(-1),  'completed'],   // day after end, stale stored value
        'NO_DATES'     => ['not_started', null,    null,    'no_dates'],
        'ONLY_END'     => ['not_started', null,    $d(-5),  'no_dates'],
        'ONLY_START_P' => ['not_started', $d(-10), null,    'in_progress'],
        'ONLY_START_F' => ['not_started', $d(10),  null,    'not_started'],
        'CANCEL_MID'   => ['cancelled',   $d(-10), $d(10),  'cancelled'],   // cancelled while running
        'CANCEL_NODT'  => ['cancelled',   null,    null,    'cancelled'],
        'STORED_DONE'  => ['completed',   $d(30),  $d(90),  'not_started'], // dates win over stored 'completed'
        'RUNNING'      => ['not_started', $d(-30), $d(30),  'in_progress'],
    ];
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

/** SQLite stand-in for the one MySQL-only construct on these pages (findUpcoming). */
final class SqlitePdo extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = preg_replace('/DATE_ADD\((.+?), INTERVAL (:\w+) DAY\)/', "date($1, '+' || $2 || ' days')", $query);
        return parent::prepare($query, $options);
    }
}

/** Fresh in-memory database with the fixture; injected as the Database singleton. */
function bootDatabase(): PDO
{
    $pdo = new SqlitePdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->sqliteCreateFunction('CURDATE', static fn(): string => TODAY, 0);
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
    $pdo->exec("INSERT INTO users (id, username, full_name, role) VALUES (1, 'probe', 'DATA-001 probe', 'admin')");

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    CohortStatus::freezeToday(TODAY);

    return $pdo;
}

function signIn(string $role): void
{
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'DATA-001 probe', 'email' => '', 'role' => $role],
        App\Core\Csrf::SESSION_KEY => 'test-csrf',
    ];
}

if (($argv[1] ?? '') === '--page') {
    runPage(json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR));
    exit(0);
}

/** Request one page in a child process; returns status, body and resulting DB rows. */
function page(string $role, string $method, string $path, array $post = []): array
{
    $spec = compact('role', 'method', 'path', 'post');
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode($spec))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$role} {$method} {$path}): " . substr($output, -800));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    check(!preg_match('/(Fatal error|Warning|Notice|Deprecated):/', (string) $result['body']), "PHP error in {$path}: " . substr($result['body'], 0, 600));
    return $result;
}

$expected = array_map(static fn(array $row): string => $row[3], fixture());
$expectedCounts = array_fill_keys(array_keys(CohortStatus::LABELS), 0);
foreach ($expected as $status) {
    $expectedCounts[$status]++;
}

// ─── 1. PHP rule = SQL rule on every edge case ───────────────────────────
$pdo = bootDatabase();
$sqlRows = $pdo->query('SELECT c.cohort_code, ' . CohortStatus::sqlCase('c') . ' AS s FROM cohorts c')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach (fixture() as $code => [$stored, $start, $end, $want]) {
    check(CohortStatus::derive($stored, $start, $end) === $want, "derive {$code}: want {$want}, got " . CohortStatus::derive($stored, $start, $end));
    check($sqlRows[$code] === $want, "sqlCase {$code}: want {$want}, got {$sqlRows[$code]}");
}
// AIGSK28: starts 2026-10-05 → "No iniciado" the day before, "En progreso" from that day on.
check(CohortStatus::derive('not_started', '2026-10-05', '2027-01-05', '2026-10-04') === 'not_started', 'AIGSK28 day before');
check(CohortStatus::derive('not_started', '2026-10-05', '2027-01-05', '2026-10-05') === 'in_progress', 'AIGSK28 on start day');
check(CohortStatus::label('in_progress') === 'En progreso', 'AIGSK28 label');
// "Today" uses the El Salvador business date (UTC-6, no DST), not the app's UTC.
CohortStatus::freezeToday(null);
$utc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
check(CohortStatus::today() === $utc->modify('-6 hours')->format('Y-m-d'), 'today() uses America/El_Salvador');
CohortStatus::freezeToday(TODAY);
check(CohortStatus::sqlCase('c', '2026-10-05') !== '' && array_keys(CohortStatus::LABELS) === ['not_started', 'in_progress', 'completed', 'cancelled', 'no_dates'], 'label set');
try {
    CohortStatus::sqlCase('c', "2026-10-05' OR '1'='1");
    check(false, 'sqlCase must reject a non-date literal');
} catch (InvalidArgumentException) {
}

// ─── 1b. Migration 021 view = PHP rule, also 18:00–24:00 in El Salvador ──
// The migration file is executed as-is on SQLite; only MySQL-only syntax is
// translated mechanically (CREATE OR REPLACE, DATE_SUB(... INTERVAL n HOUR)).
// UTC_TIMESTAMP() returns the pinned instant, like MySQL's server UTC clock.
$migration = (string) file_get_contents(APP_ROOT . '/database/migrations/021_cohort_status_view.sql');
$migrationSql = preg_replace('/^\s*--.*$/m', '', $migration);
check(str_contains($migrationSql, 'DATE(DATE_SUB(UTC_TIMESTAMP(), INTERVAL 6 HOUR))'), '021 uses the El Salvador date');
check(stripos($migrationSql, 'CURDATE') === false && stripos($migrationSql, 'CONVERT_TZ') === false, '021 does not use CURDATE()/CONVERT_TZ()');
$utcClock = '';
$pdo->sqliteCreateFunction('UTC_TIMESTAMP', static function () use (&$utcClock): string {
    return $utcClock;
}, 0);
$pdo->exec(preg_replace(
    ['/CREATE OR REPLACE VIEW/', '/DATE_SUB\((.+?), INTERVAL (\d+) HOUR\)/'],
    ['CREATE VIEW', "datetime($1, '-$2 hours')"],
    $migrationSql
));
CohortStatus::freezeToday(null);
// UTC instant => El Salvador business date. From 18:00 SV (00:00 UTC) the UTC date
// is already the next day; the business date must not change until 00:00 SV.
$instants = [
    '2026-10-05 17:59:59' => '2026-10-05', // 11:59 SV
    '2026-10-05 23:59:59' => '2026-10-05', // 17:59 SV
    '2026-10-06 00:00:00' => '2026-10-05', // 18:00 SV, UTC date already 10-06
    '2026-10-06 03:30:00' => '2026-10-05', // 21:30 SV
    '2026-10-06 05:59:59' => '2026-10-05', // 23:59:59 SV
    '2026-10-06 06:00:00' => '2026-10-06', // 00:00 SV
];
foreach ($instants as $utc => $svDate) {
    CohortStatus::freezeNow($utc);
    $utcClock = $utc;
    check(CohortStatus::today() === $svDate, "today() at {$utc} UTC: want {$svDate}, got " . CohortStatus::today());
    $viewRows = $pdo->query('SELECT v.cohort_code, v.effective_status, v.effective_label, v.status_date,
            c.training_status, c.start_date, c.end_date
        FROM v_cohort_status v JOIN cohorts c ON c.id = v.cohort_id')->fetchAll();
    check(count($viewRows) === count(fixture()), 'view returns every cohort');
    foreach ($viewRows as $row) {
        $php = CohortStatus::forRow($row);
        check($row['status_date'] === $svDate, "view date at {$utc}: {$row['status_date']}");
        check($row['effective_status'] === $php, "view vs PHP {$row['cohort_code']} at {$utc}: {$row['effective_status']} vs {$php}");
        check($row['effective_label'] === CohortStatus::label($php), "view label {$row['cohort_code']}");
    }
    $byCode = array_column($viewRows, 'effective_status', 'cohort_code');
    $boundary = $svDate === '2026-10-05' ? ['in_progress', 'not_started'] : ['completed', 'in_progress'];
    check([$byCode['ENDS_TODAY'], $byCode['STARTS_TMRW']] === $boundary, "end/start borders at {$utc} UTC");
}
CohortStatus::freezeNow(null);
CohortStatus::freezeToday(TODAY);

// ─── 2. Saves keep training_status in sync and never overwrite 'cancelled' ─
signIn('admin');
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$service = new App\Services\CohortService();
$repo = new App\Repositories\CohortRepository();
$idOf = $pdo->query('SELECT cohort_code, id FROM cohorts')->fetchAll(PDO::FETCH_KEY_PAIR);
$stored = static fn(string $code): string => (string) $pdo->query("SELECT training_status FROM cohorts WHERE cohort_code = '{$code}'")->fetchColumn();

// Editing any field of a cohort without dates creates no dates: it stays "Sin fechas".
$service->updateCohortPartial((int) $idOf['NO_DATES'], ['b2c_admissions' => 2, 'assigned_coach' => 'Otro coach']);
$noDates = $pdo->query("SELECT training_status, start_date, end_date, b2c_admissions FROM cohorts WHERE cohort_code = 'NO_DATES'")->fetch();
check((int) $noDates['b2c_admissions'] === 2, 'no-dates edit was saved');
check($noDates['start_date'] === null && $noDates['end_date'] === null, 'editing a cohort without dates must not create dates');
check(CohortStatus::forRow($noDates) === 'no_dates', 'cohort without dates stays "Sin fechas" after edit');
check($pdo->query("SELECT " . CohortStatus::sqlCase('c') . " FROM cohorts c WHERE cohort_code = 'NO_DATES'")->fetchColumn() === 'no_dates', 'SQL still "Sin fechas"');
// Only a start date: editing keeps end_date NULL (no copy of the start date).
$service->updateCohortPartial((int) $idOf['ONLY_START_P'], ['b2c_admissions' => 1]);
check($pdo->query("SELECT end_date FROM cohorts WHERE cohort_code = 'ONLY_START_P'")->fetchColumn() === null, 'missing end_date stays NULL');

// Editing dates of a cancelled cohort keeps it cancelled.
$service->updateCohortPartial((int) $idOf['CANCEL_MID'], ['start_date' => '2026-01-01', 'end_date' => '2026-02-01']);
check($stored('CANCEL_MID') === 'cancelled', 'edit dates: cancelled must stay cancelled');
// Editing another field (finance) of a cancelled cohort keeps it cancelled.
$service->updateCohortPartial((int) $idOf['CANCEL_MID'], ['financial_actual_revenue' => '10.50']);
check($stored('CANCEL_MID') === 'cancelled', 'edit field: cancelled must stay cancelled');
// Any other save writes the derived status (stale 'not_started' → in_progress).
$service->updateCohortPartial((int) $idOf['AIGSK28'], ['b2c_admissions' => 3]);
check($stored('AIGSK28') === 'in_progress', 'save syncs training_status with the rule');
$service->updateCohortPartial((int) $idOf['STORED_DONE'], ['b2c_admissions' => 1]);
check($stored('STORED_DONE') === 'not_started', 'stored completed with future dates is re-synced');
// Import path: rows are normalized with STATUS_MAP and stored via CohortRepository::create().
$importRow = static fn(string $code, string $statusText, ?string $start, ?string $end): array => [
    'cohort_code' => $code, 'name' => $code, 'start_date' => $start, 'end_date' => $end,
    'training_status' => App\Services\CohortImportService::STATUS_MAP[mb_strtolower($statusText)],
];
$repo->create($importRow('IMP_CANCEL', 'Cancelado', '2026-09-01', '2026-12-01'));
check($stored('IMP_CANCEL') === 'cancelled', 'import Cancelado stays cancelled');
$repo->create($importRow('IMP_DONE', 'Completado', '2026-11-01', '2027-01-01'));
check($stored('IMP_DONE') === 'not_started', 'import Completado with future dates is stored by the rule');
$repo->create($importRow('IMP_LEGACY', 'Planificado', '2026-09-01', '2026-12-01'));
check($stored('IMP_LEGACY') === 'in_progress', 'legacy "Planificado" accepted, stored by the rule');
CohortStatus::freezeToday(null);

// ─── 3. Same counts everywhere (child processes, fixed date) ─────────────
// Dashboard
$dash = page('admin', 'GET', '/');
preg_match('/Cohortes por estado<\/caption>.*?<tbody>(.*?)<\/tbody>/s', $dash['body'], $m);
preg_match_all('/<tr><td>([^<]+)<\/td><td>(\d+)<\/td><\/tr>/', $m[1] ?? '', $rows, PREG_SET_ORDER);
$dashCounts = [];
foreach ($rows as [, $label, $count]) {
    $dashCounts[array_search($label, CohortStatus::LABELS, true)] = (int) $count;
}
check($dashCounts === $expectedCounts, 'Dashboard counts ' . json_encode($dashCounts));

// Cohortes: one card per status, cards add up to the total, each filter = its card.
$list = page('admin', 'GET', '/cohorts');
preg_match_all('/data-status-card="([a-z_]+)">(\d+)</', $list['body'], $cards, PREG_SET_ORDER);
$cardCounts = [];
foreach ($cards as [, $key, $count]) {
    $cardCounts[$key] = (int) $count;
}
$total = $cardCounts['total'];
unset($cardCounts['total']);
check($cardCounts === $expectedCounts, 'Cohortes cards ' . json_encode($cardCounts));
check(array_sum($cardCounts) === $total && $total === count($expected), 'cards add up to the total');
foreach (CohortStatus::LABELS as $status => $label) {
    $filtered = page('admin', 'GET', '/cohorts?cohort_status=' . $status);
    preg_match('/data-status-card="total">(\d+)</', $filtered['body'], $t);
    check((int) $t[1] === $expectedCounts[$status], "filter {$status}: {$t[1]} rows vs card {$expectedCounts[$status]}");
    check(str_contains($filtered['body'], '<strong>' . $label . '</strong>'), "filter chip shows label {$label}");
}

// Plan Maestro (pills) and its CSV export.
$pillCounts = static function (string $html): array {
    $counts = array_fill_keys(array_keys(CohortStatus::LABELS), 0);
    foreach (CohortStatus::LABELS as $status => $label) {
        $counts[$status] = preg_match_all('/kodigo-pill__dot" aria-hidden="true"><\/span>\s*' . preg_quote($label, '/') . '\s*<\/span>/', $html);
    }
    return $counts;
};
$master = page('admin', 'GET', '/cohorts/master');
check($pillCounts($master['body']) === $expectedCounts, 'Plan Maestro counts ' . json_encode($pillCounts($master['body'])));
$csv = page('admin', 'GET', '/cohorts/master/export/csv');
$lines = array_map('str_getcsv', array_filter(explode("\n", preg_replace('/^\xEF\xBB\xBF/', '', $csv['body']))));
$statusCol = array_search('Estado', $lines[0], true);
$csvCounts = array_fill_keys(array_keys(CohortStatus::LABELS), 0);
foreach (array_slice($lines, 1) as $line) {
    $csvCounts[array_search($line[$statusCol], CohortStatus::LABELS, true)]++;
}
check($csvCounts === $expectedCounts, 'Plan Maestro CSV counts ' . json_encode($csvCounts));

// Reportes: status cards and table pills.
$reports = page('admin', 'GET', '/reports');
$reportCards = [];
foreach (CohortStatus::LABELS as $status => $label) {
    preg_match('/<strong>' . preg_quote($label, '/') . '<\/strong>\s*<span>(\d+)<\/span>/', $reports['body'], $rc);
    $reportCards[$status] = (int) ($rc[1] ?? -1);
}
check($reportCards === $expectedCounts, 'Reportes cards ' . json_encode($reportCards));
check($pillCounts(preg_replace('/<div class="reports-mobile-list">.*/s', '', $reports['body'])) === $expectedCounts, 'Reportes table pills');

// Reportes PDF (HTML before Dompdf; Dompdf/PhpSpreadsheet are not installed locally).
CohortStatus::freezeToday(TODAY);
$reportService = new App\Services\ReportService();
$pdfHtml = (new ReflectionMethod($reportService, 'buildPdfHtml'))->invoke($reportService, $reportService->getReportData([]), []);
foreach (CohortStatus::LABELS as $status => $label) {
    check(str_contains($pdfHtml, '<th>' . $label . '</th>'), "PDF header {$label}");
}
CohortStatus::freezeToday(null);

// Detalle: the badge of every cohort matches the rule.
$pages = [];
$detailCounts = array_fill_keys(array_keys(CohortStatus::LABELS), 0);
foreach (fixture() as $code => $row) {
    $detail = page('admin', 'GET', '/cohorts/' . $idOf[$code]);
    preg_match('/aria-hidden="true"><\/i>([^<]+)<\/span>/', $detail['body'], $badge);
    $status = array_search(trim($badge[1] ?? ''), CohortStatus::LABELS, true);
    check($status === $row[3], "Detalle {$code}: want {$row[3]}, got " . ($badge[1] ?? '?'));
    $detailCounts[$status]++;
    $pages[] = $detail['body'];
}
check($detailCounts === $expectedCounts, 'Detalle counts');

// ─── 4. Only admin can cancel / reactivate ───────────────────────────────
$running = $idOf['RUNNING'];
foreach (['finance', 'admissions_b2b', 'admissions_b2c', 'marketing'] as $role) {
    $res = page($role, 'POST', "/cohorts/{$running}/status", ['_csrf' => 'test-csrf', 'target_status' => 'cancelled', 'status_reason' => 'x']);
    check($res['status'] === 403, "{$role} cancel must be 403, got {$res['status']}");
    check($res['stored']['RUNNING'] === 'not_started' && $res['audit'] === [], "{$role}: nothing changed");
    $show = page($role, 'GET', "/cohorts/{$running}");
    check(!str_contains($show['body'], '/status"'), "{$role}: no status actions in Detalle");
}
$noReason = page('admin', 'POST', "/cohorts/{$running}/status", ['_csrf' => 'test-csrf', 'target_status' => 'cancelled']);
check($noReason['stored']['RUNNING'] === 'not_started' && str_contains((string) ($noReason['flash']['error'] ?? ''), 'motivo'), 'cancel requires a reason');
$complete = page('admin', 'POST', "/cohorts/{$running}/status", ['_csrf' => 'test-csrf', 'target_status' => 'completed']);
check($complete['stored']['RUNNING'] === 'not_started' && $complete['audit'] === [], 'manual "completed" is rejected (dates rule)');
$cancel = page('admin', 'POST', "/cohorts/{$running}/status", ['_csrf' => 'test-csrf', 'target_status' => 'cancelled', 'status_reason' => 'Sin quórum']);
check($cancel['stored']['RUNNING'] === 'cancelled', 'admin cancels');
$audit = $cancel['audit'][0] ?? [];
$auditNew = json_decode((string) ($audit['new_values'] ?? '{}'), true);
check(($audit['action'] ?? '') === 'transition_cohort_status' && (int) $audit['user_id'] === 1 && ($auditNew['reason'] ?? '') === 'Sin quórum', 'cancel audited with reason');
$reactivate = page('admin', 'POST', '/cohorts/' . $idOf['CANCEL_MID'] . '/status', ['_csrf' => 'test-csrf', 'target_status' => 'reactivate']);
check($reactivate['stored']['CANCEL_MID'] === 'in_progress', 'reactivate → status by dates');
check(str_contains((string) ($reactivate['flash']['success'] ?? ''), 'En progreso'), 'reactivate flash shows the new label');
$adminShow = page('admin', 'GET', '/cohorts/' . $idOf['CANCEL_MID']);
check(str_contains($adminShow['body'], 'value="reactivate"') && !str_contains($adminShow['body'], 'value="completed"'), 'Detalle: only cancel/reactivate actions');

// ─── 5. No English labels or synonyms in any rendered page ───────────────
$pages = array_merge($pages, [$dash['body'], $list['body'], $master['body'], $reports['body'], $adminShow['body'], $pdfHtml]);
foreach ($pages as $html) {
    $text = strip_tags(preg_replace('/<(script|style)\b.*?<\/\1>/si', '', $html));
    foreach (['Cancelled', 'Planificad', 'En ejecuci', 'Completadas', 'No iniciadas'] as $banned) {
        check(stripos($text, $banned) === false, "banned status label '{$banned}' found");
    }
}

echo 'Counts at ' . TODAY . ': ' . json_encode(array_combine(array_values(CohortStatus::LABELS), array_values($expectedCounts)), JSON_UNESCAPED_UNICODE) . "\n";
echo "PASS: one status rule (PHP = SQL), same counts in Dashboard/Cohortes/Plan Maestro/CSV/Reportes/PDF/Detalle, filters = cards, cancelled never overwritten, admin-only cancel/reactivate, no synonyms (synthetic SQLite).\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one request against a fresh in-memory database.
// ═════════════════════════════════════════════════════════════════════════
function runPage(array $spec): void
{
    $pdo = bootDatabase();
    signIn($spec['role']);
    $path = $spec['path'];
    $_SERVER['REQUEST_METHOD'] = $spec['method'];
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    parse_str((string) parse_url($path, PHP_URL_QUERY), $_GET);
    $_POST = $spec['post'];
    $_REQUEST = $_GET + $_POST;

    register_shutdown_function(static function () use ($pdo): void {
        $body = '';
        while (ob_get_level() > 0) {
            $body = ob_get_clean() . $body;
        }
        $hasError = error_get_last();
        echo RESULT_MARKER, json_encode([
            'status' => http_response_code() ?: 200, // CLI reports false for the implicit 200
            'body'   => $body . ($hasError && in_array($hasError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR], true) ? "\nFatal error: " . $hasError['message'] : ''),
            'stored' => $pdo->query('SELECT cohort_code, training_status FROM cohorts')->fetchAll(PDO::FETCH_KEY_PAIR),
            'audit'  => $pdo->query('SELECT * FROM audit_log')->fetchAll(),
            'flash'  => $_SESSION['_flash'] ?? [],
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
