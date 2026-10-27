<?php
declare(strict_types=1);

// COH-003 / PM-003 regression: one rule for the Desde/Hasta range, in
// CohortRepository::buildFilters(), on the cohort START date only.
//  - Desde and/or Hasta: start_date >= Desde, start_date <= Hasta (inclusive).
//  - With any bound set, cohorts without a start date are excluded.
//  - Without a range, cohorts without dates are still listed as "Sin fechas".
//  - Cohortes, Plan Maestro (cards + rows), its CSV/XLSX exports, Marketing and
//    Finanzas return exactly the same cohorts and figures for the same period.
//  - Labels and chips say the range is on the start date (dd/mm/yyyy).
// Pages run in child PHP processes through the real Router, controllers and views.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_date_range.php

const NOW_UTC = '2026-10-06 02:00:00';
const RESULT_MARKER = '@@COH003@@';

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

/** code => [start, end, total target, b2b adm, b2c adm, revenue target, revenue actual] */
function fixture(): array
{
    return [
        'OLD_JAN'    => ['2026-01-10', '2026-05-10', 20, 5, 5, 1000, 900],
        'AUG_TO_OCT' => ['2026-08-15', '2026-10-15', 30, 8, 10, 2000, 1500],
        'AUG_LAST'   => ['2026-08-31', '2026-11-30', 25, 4, 6, 1500, 700],
        'SEP_FIRST'  => ['2026-09-01', '2026-12-01', 40, 10, 12, 3000, 2500],
        'SEP_MID'    => ['2026-09-15', '2026-12-15', 35, 6, 9, 2500, 1200],
        'SEP_LAST'   => ['2026-09-30', '2027-01-30', 15, 2, 3, 1200, 400],
        'OCT_FIRST'  => ['2026-10-01', '2027-01-01', 22, 0, 4, 1800, 300],
        'NO_DATES'   => [null,         null,         18, 1, 2, 900, 100],
        'NO_START'   => [null,         '2026-09-20', 12, 0, 1, 600, 50],
    ];
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

/** SQLite stand-in for the one MySQL-only construct on Marketing (stage seeding). */
final class SqlitePdo extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $query), $options);
    }
}

if (($argv[1] ?? '') === '--page') {
    runPage(json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR));
    exit(0);
}

function page(string $path): string
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode(['path' => $path]))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$path}): " . substr($output, -800));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    check($result['status'] === 200, "{$path} status {$result['status']}");
    $body = base64_decode($result['body']);
    check(!preg_match('/(Fatal error|Warning|Notice|Deprecated):/', $body), "PHP error in {$path}: " . substr($body, 0, 600));
    return $body;
}

/** Fixture codes present in an HTML page, in fixture order. */
function codesIn(string $html, string $linkPattern): array
{
    preg_match_all('#<a href="' . $linkPattern . '"[^>]*>\s*([A-Z_]+)\s*</a>#', $html, $m);
    $found = array_unique($m[1]);
    return array_values(array_intersect(array_keys(fixture()), $found));
}

/** Plan Maestro: cards, the codes it lists and its chips. */
function master(string $query): array
{
    $html = page('/cohorts/master' . $query);
    preg_match('/<strong>(\d+)<\/strong>\s*<small>Cohortes filtradas<\/small>/', $html, $total);
    preg_match('/<strong>(\d+) \/ (\d+)<\/strong>\s*<small>Inscritos/', $html, $adm);
    preg_match('/data-active-filters="(\d+)">(.*?)<\/div>/s', $html, $chips);
    check(isset($total[1], $adm[2], $chips[1]), "Plan Maestro{$query}: cards/chips not found");
    return [
        'codes'  => codesIn($html, '/cohorts/\d+'),
        'totals' => ['cohorts' => (int) $total[1], 'admissions' => (int) $adm[1], 'target' => (int) $adm[2]],
        'chips'  => trim(preg_replace('/\s+/', ' ', strip_tags($chips[2]))),
        'html'   => $html,
    ];
}

/** CSV export: codes and totals (Meta Total, Actual Total, Revenue Meta, Revenue Actual). */
function csvExport(string $query): array
{
    $body = page('/cohorts/master/export/csv' . $query);
    $lines = array_map('str_getcsv', preg_split('/\r?\n/', trim(substr($body, 3))));
    array_shift($lines);
    return summarize(array_map(static fn(array $r): array => [$r[0], (int) $r[7], (int) $r[12], (float) $r[13], (float) $r[14]], $lines));
}

/** XLSX export, read back with PhpSpreadsheet. */
function xlsxExport(string $query): array
{
    $file = tempnam(sys_get_temp_dir(), 'coh003') . '.xlsx';
    file_put_contents($file, page('/cohorts/master/export/xlsx' . $query));
    $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet()->toArray(null, false, false);
    @unlink($file);
    array_shift($rows);
    $rows = array_filter($rows, static fn(array $r): bool => (string) $r[0] !== '');
    return summarize(array_map(static fn(array $r): array => [(string) $r[0], (int) $r[7], (int) $r[12], (float) $r[13], (float) $r[14]], $rows));
}

function summarize(array $rows): array
{
    $codes = array_column($rows, 0);
    return [
        'codes'  => array_values(array_intersect(array_keys(fixture()), $codes)),
        'count'  => count($rows),
        'totals' => [
            'target'         => array_sum(array_column($rows, 1)),
            'admissions'     => array_sum(array_column($rows, 2)),
            'revenue_target' => array_sum(array_column($rows, 3)),
            'revenue_actual' => array_sum(array_column($rows, 4)),
        ],
    ];
}

/** Finanzas: revenue target/actual cards (dollars, 2 decimals). */
function finance(string $query): array
{
    $html = page('/cohorts/finance' . $query . '&year=2026');
    preg_match('/<strong>\$([\d,.]+)<\/strong>\s*<small>Meta revenue/', $html, $target);
    preg_match('/<strong>\$([\d,.]+)<\/strong>\s*<small>Revenue actual/', $html, $actual);
    check(isset($target[1], $actual[1]), "Finanzas{$query}: cards not found");
    return [
        'revenue' => [(float) str_replace(',', '', $target[1]), (float) str_replace(',', '', $actual[1])],
        'html'    => $html,
    ];
}

// ─── Expected sets straight from the fixture ─────────────────────────────
$fx = fixture();
$expect = static function (?string $from, ?string $to) use ($fx): array {
    return array_keys(array_filter($fx, static function (array $r) use ($from, $to): bool {
        if ($from === null && $to === null) {
            return true;
        }
        return $r[0] !== null && ($from === null || $r[0] >= $from) && ($to === null || $r[0] <= $to);
    }));
};
$sumOf = static function (array $codes) use ($fx): array {
    $rows = array_intersect_key($fx, array_flip($codes));
    return [
        'target'         => array_sum(array_column($rows, 2)),
        'admissions'     => array_sum(array_map(static fn($r) => $r[3] + $r[4], $rows)),
        'revenue_target' => (float) array_sum(array_column($rows, 5)),
        'revenue_actual' => (float) array_sum(array_column($rows, 6)),
    ];
};

$cases = [
    'septiembre'      => ['?start_date=2026-09-01&end_date=2026-09-30', '2026-09-01', '2026-09-30', ['SEP_FIRST', 'SEP_MID', 'SEP_LAST']],
    'invertido'       => ['?start_date=2026-09-30&end_date=2026-09-01', '2026-09-01', '2026-09-30', ['SEP_FIRST', 'SEP_MID', 'SEP_LAST']],
    'solo desde'      => ['?start_date=2026-09-01', '2026-09-01', null, ['SEP_FIRST', 'SEP_MID', 'SEP_LAST', 'OCT_FIRST']],
    'solo hasta'      => ['?end_date=2026-09-30', null, '2026-09-30', ['OLD_JAN', 'AUG_TO_OCT', 'AUG_LAST', 'SEP_FIRST', 'SEP_MID', 'SEP_LAST']],
    'sin rango'       => ['?', null, null, array_keys($fx)],
];

foreach ($cases as $name => [$query, $from, $to, $wanted]) {
    check($expect($from, $to) === $wanted, "{$name}: fixture expectation");
    $want = $sumOf($wanted);

    // Cohortes (/cohorts) and Plan Maestro list the same cohorts.
    preg_match_all('#<div class="small text-muted mt-1">([A-Z_]+)</div>#', page('/cohorts' . $query), $listed);
    $list = array_values(array_intersect(array_keys($fx), $listed[1]));
    check($list === $wanted, "{$name}: Cohortes " . json_encode($list) . ' vs ' . json_encode($wanted));

    $m = master($query);
    check($m['codes'] === $wanted, "{$name}: Plan Maestro rows " . json_encode($m['codes']));
    check($m['totals'] === ['cohorts' => count($wanted), 'admissions' => $want['admissions'], 'target' => $want['target']], "{$name}: Plan Maestro cards " . json_encode($m['totals']));

    // Exports: same cohorts and same figures.
    foreach (['CSV' => csvExport($query), 'XLSX' => xlsxExport($query)] as $format => $export) {
        check($export['codes'] === $wanted && $export['count'] === count($wanted), "{$name}: {$format} " . json_encode($export['codes']));
        check($export['totals'] === $want, "{$name}: {$format} totals " . json_encode($export['totals']) . ' vs ' . json_encode($want));
    }

    // Marketing and Finanzas use the same buildFilters().
    $mkt = codesIn(page('/marketing' . $query), '/cohorts/\d+/marketing');
    check($mkt === $wanted, "{$name}: Marketing " . json_encode($mkt));
    // Finanzas always applies its own year filter (defaults to the current year),
    // so cohorts without dates never reach it; with a 2026 range it is a no-op.
    $finWant = $sumOf(array_filter($wanted, static fn(string $c): bool => str_starts_with((string) $fx[$c][0], '2026')));
    $fin = finance($query);
    check($fin['revenue'] === [$finWant['revenue_target'], $finWant['revenue_actual']], "{$name}: Finanzas " . json_encode($fin['revenue']));
    if ($from !== null || $to !== null) {
        check($finWant === $want, "{$name}: Finanzas = Plan Maestro for the same period");
    }

    echo str_pad($name, 12) . ' → ' . implode(', ', $wanted) . "\n";
}

// Specific cases called out by COH-003.
$sep = master('?start_date=2026-09-01&end_date=2026-09-30');
check(!in_array('AUG_TO_OCT', $sep['codes'], true), 'started in August, ends in October: not in September');
check(in_array('SEP_FIRST', $sep['codes'], true) && in_array('SEP_LAST', $sep['codes'], true), 'first and last day of the range are inclusive');
check(!in_array('NO_DATES', $sep['codes'], true) && !in_array('NO_START', $sep['codes'], true), 'without start date: excluded with a range');

// Chips and labels name the start date (dd/mm/yyyy).
check($sep['chips'] === 'Filtros activos Inicio desde: 01/09/2026 Inicio hasta: 30/09/2026', 'Plan Maestro chips: ' . $sep['chips']);
check(str_contains(master('?end_date=2026-09-30')['chips'], 'Inicio hasta: 30/09/2026'), 'Hasta-only chip');
$listSep = page('/cohorts?start_date=2026-09-01&end_date=2026-09-30');
check(preg_match('/Inicio desde:\s*<strong>01\/09\/2026<\/strong>.*?Inicio hasta:\s*<strong>30\/09\/2026<\/strong>/s', $listSep) === 1, '/cohorts chips');
$finSep = finance('?start_date=2026-09-01&end_date=2026-09-30')['html'];
check(preg_match('/<strong>Inicio desde:<\/strong>\s*01\/09\/2026/', $finSep) === 1 && preg_match('/<strong>Inicio hasta:<\/strong>\s*30\/09\/2026/', $finSep) === 1, 'Finanzas chips');
foreach (['/cohorts' => $listSep, '/cohorts/master' => $sep['html'], '/cohorts/finance' => $finSep, '/marketing' => page('/marketing')] as $path => $html) {
    check(str_contains($html, '<label for="start_date" class="form-label">Inicio desde</label>')
        && str_contains($html, '<label for="end_date" class="form-label">Inicio hasta</label>'), "{$path}: field labels");
}

// Without a range the cohort without dates is listed and marked "Sin fechas".
$all = page('/cohorts');
check(preg_match('/Sin fechas.*NO_DATES|NO_DATES.*Sin fechas/s', $all) === 1, '/cohorts without range: NO_DATES shown as Sin fechas');

echo "PASS: start-date range rule, same cohorts and figures in Cohortes, Plan Maestro, CSV, XLSX, Marketing and Finanzas, chips/labels on start date (synthetic SQLite).\n";

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
    $pdo->sqliteCreateFunction('FIELD', static function ($needle, ...$list): int {
        $pos = array_search($needle, $list, false);
        return $pos === false ? 0 : $pos + 1;
    }, -1);
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
        notes TEXT, updated_by INTEGER, updated_at TEXT);
    CREATE TABLE cohort_marketing_info (cohort_id INTEGER PRIMARY KEY, campaign_status TEXT NOT NULL DEFAULT 'Active',
        strategy_notes TEXT, content_notes TEXT, ads_notes TEXT, organic_notes TEXT, events_notes TEXT,
        partnerships_notes TEXT, analytics_notes TEXT, created_at TEXT, updated_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (cohort_code, name, training_status, start_date, end_date, bootcamp_type,
        total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions,
        financial_target_revenue, financial_actual_revenue)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)');
    foreach (fixture() as $code => [$s, $e, $target, $b2b, $b2c, $revTarget, $revActual]) {
        $insert->execute([$code, 'Cohorte ' . strtolower($code), 'not_started', $s, $e, 'Full Stack', $target, $target, $b2b, $b2c, $revTarget, $revActual]);
    }

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    App\Services\CohortStatus::freezeNow(NOW_UTC);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'COH-003 probe', 'email' => '', 'role' => 'admin'],
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
        // base64: the XLSX export is binary.
        echo RESULT_MARKER, json_encode(['status' => http_response_code() ?: 200, 'body' => base64_encode($body)]);
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
