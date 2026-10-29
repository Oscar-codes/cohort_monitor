<?php
declare(strict_types=1);

// DASH-007 regression: "Progreso de admisiones" (Dashboard) by year of start_date.
//  - Bars are B2B / B2C / Pendiente as % of the target, 1 decimal, adding up to
//    exactly 100.0 (largest remainder); every bar shows % and count ("43.2 % (1,602)").
//  - Over-admission: Pendiente = 0, shares over the admissions (still 100 %);
//    the badge keeps CohortService::admissionPct (capped at 100.0 %) and adds
//    "· +N sobre la meta" (N = admissions - target).
//  - Target 0 / no cohorts in the year: empty state, no division by zero.
//  - ?year= only accepts a 4-digit year present in start_date; anything else
//    falls back to the current El Salvador year. Options newest first.
//  - The year recalculates the block (cards, bars, badge) and the "Completado"
//    card (closed_year = year, "Cerradas en <año>", "Ver historial" link).
//  - Cohorts without start date are in no year: "N cohortes sin fecha de inicio no se incluyen".
//  - Cancelled cohorts stay in the sums. Same totals query as Plan Maestro (DATA-003).
// Pages run in child PHP processes through the real Router, controllers and views.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/dashboard_admissions_year.php

const NOW_UTC = '2026-10-06 02:00:00';   // 2026-10-05 in El Salvador
const RESULT_MARKER = '@@DASH007@@';

define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Services\CohortService;

/**
 * code => [stored status, start, end, total target, b2b target, b2b adm, b2c adm]
 * 2026 adds up to the audit example: B2B 1,602, B2C 825, target 3,708.
 */
function fixture(): array
{
    return [
        'A26'      => ['in_progress', '2026-08-01', '2026-12-01', 1500, 900, 900, 300],
        'B26'      => ['in_progress', '2026-01-10', '2026-06-30', 1508, 500, 500, 400],   // completed, closed in 2026
        'CAN26'    => ['cancelled',   '2026-03-01', '2026-09-01',  700, 200, 202, 125],   // cancelled: still summed
        'DEC25'    => ['in_progress', '2025-12-01', '2026-02-15',   50,  10,  10,  15],   // year 2025, closed in 2026
        'Y25'      => ['completed',   '2025-03-01', '2025-08-01',  100,  30,  30,  20],
        'O24'      => ['completed',   '2024-01-01', '2024-05-01',   10,   9,   9,   6],   // over-admitted
        'Z23'      => ['completed',   '2023-01-01', '2023-06-01',    0,   0,   0,   3],   // target 0
        'F27'      => ['not_started', '2027-02-01', '2027-05-01',   30,   0,   0,   0],
        'NOSTART1' => ['not_started', null,         null,           40,  10,   5,   0],
        'NOSTART2' => ['cancelled',   null,         '2026-05-01',   20,   0,   0,   2],
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

function page(string $path, string $now = NOW_UTC): string
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode(['path' => $path, 'now' => $now]))) . ' 2>&1';
    $output = (string) shell_exec($cmd);
    $pos = strrpos($output, RESULT_MARKER);
    check($pos !== false, "no result from child ({$path}): " . substr($output, -800));
    $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
    check($result['status'] === 200, "{$path} status {$result['status']}: " . substr($result['body'], -600));
    check(!preg_match('/(Fatal error|Warning|Notice|Deprecated|DivisionByZero)/', $result['body']), "PHP error in {$path}: " . substr($result['body'], 0, 600));
    return $result['body'];
}

function num(string $text): int
{
    return (int) str_replace(',', '', $text);
}

/** Everything the admissions block and the "Completado" card render. */
function dashboard(string $query = '', string $now = NOW_UTC): array
{
    $html = page('/' . $query, $now);
    preg_match('/<select[^>]*id="admissionsYear"[^>]*>(.*?)<\/select>/s', $html, $select);
    check(isset($select[1]), "{$query}: year selector not found");
    preg_match_all('/<option value="(\d{4})"\s*(selected)?>/', $select[1], $options, PREG_SET_ORDER);
    $selected = array_values(array_filter($options, static fn(array $o): bool => ($o[2] ?? '') === 'selected'));
    check(count($selected) === 1, "{$query}: exactly one selected year");

    preg_match('/data-dashboard-admissions-pct><span class="kodigo-pill__dot" aria-hidden="true"><\/span>([\d.]+)%([^<]*)<\/span>/', $html, $badge);
    preg_match('/data-dashboard-card="closed_year">([\d,]+)<\/h3>.*?<span>Cerradas en (\d{4})<\/span>\s*<a href="\/cohorts\?closed_year=(\d{4})">Ver historial<\/a>/s', $html, $closed);
    check(isset($badge[1], $closed[3]), "{$query}: badge or Completado card not found");

    preg_match('/<textarea id="cohort-dashboard-data"[^>]*>(.*?)<\/textarea>/s', $html, $raw);
    $payload = json_decode(html_entity_decode($raw[1] ?? '', ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR)['admissions'];

    $out = [
        'years'    => array_column($options, 1),
        'year'     => $selected[0][1],
        'badge'    => (float) $badge[1],
        'badgeOver' => html_entity_decode($badge[2], ENT_QUOTES, 'UTF-8'),
        'scope'    => preg_match('/data-dashboard-scope>\s*<i [^>]*><\/i>([^<]+)<\/p>/', $html, $sc) === 1 ? html_entity_decode(trim($sc[1]), ENT_QUOTES, 'UTF-8') : null,
        'closed'   => [num($closed[1]), $closed[2], $closed[3]],
        'payload'  => $payload,
        'empty'    => preg_match('/data-dashboard-admissions-empty>.*?<h4 class="kodigo-empty__title">([^<]+)<\/h4>/s', $html, $e) === 1 ? $e[1] : null,
        'over'     => str_contains($html, 'data-dashboard-over-admitted'),
        'noStart'  => preg_match('/data-dashboard-no-start>.*?<\/i>([^<]+)<\/p>/s', $html, $n) === 1 ? trim($n[1]) : null,
        'subtitle' => preg_match('/Progreso de admisiones<\/h3>\s*<p class="kodigo-card__subtitle">([^<]+)<\/p>/', $html, $s) === 1 ? $s[1] : null,
        'cards'    => null,
        'bars'     => [],
    ];
    if (preg_match('/Actuales<\/span>\s*<strong>([\d,]+)<\/strong>.*?Meta<\/span>\s*<strong>([\d,]+)<\/strong>.*?Pendiente<\/span>\s*<strong>([\d,]+)<\/strong>/s', $html, $c) === 1) {
        $out['cards'] = ['admissions' => num($c[1]), 'target' => num($c[2]), 'pending' => num($c[3])];
    }
    preg_match_all('/data-admissions-bar="(b2b|b2c|pending)"><i [^>]*><\/i> (B2B|B2C|Pendiente) ([^<]+)<\/span>/', $html, $bars, PREG_SET_ORDER);
    foreach ($bars as [, $key, , $text]) {
        $out['bars'][$key] = $text;
    }
    return $out;
}

/** Inscritos card of Plan Maestro for a start-date range (same getTotals). */
function masterRange(string $from, string $to): array
{
    $html = page("/cohorts/master?start_date={$from}&end_date={$to}");
    preg_match('/<strong>(\d+) \/ (\d+)<\/strong>\s*<small>Inscritos \(([\d.]+)%\)<\/small>/', $html, $adm);
    check(isset($adm[3]), 'Plan Maestro Inscritos card not found');
    return ['admissions' => (int) $adm[1], 'target' => (int) $adm[2], 'pct' => (float) $adm[3]];
}

// ─── 1. admissionShares(): largest remainder, always 100.0 ───────────────
$shares = static fn(int $b2b, int $b2c, int $target): array => CohortService::admissionShares($b2b, $b2c, $target)['pct'];
check($shares(1602, 825, 3708) === ['b2b' => 43.2, 'b2c' => 22.3, 'pending' => 34.5], 'audit example: ' . json_encode($shares(1602, 825, 3708)));
check($shares(1, 1, 3) === ['b2b' => 33.4, 'b2c' => 33.3, 'pending' => 33.3], 'thirds: ties go to the first bar');
check($shares(333, 333, 1000) === ['b2b' => 33.3, 'b2c' => 33.3, 'pending' => 33.4], 'thirds of 1000');
check($shares(40, 35, 150) === ['b2b' => 26.7, 'b2c' => 23.3, 'pending' => 50.0], '2025 fixture');
check($shares(0, 0, 10) === ['b2b' => 0.0, 'b2c' => 0.0, 'pending' => 100.0], 'nothing admitted');
check($shares(10, 0, 10) === ['b2b' => 100.0, 'b2c' => 0.0, 'pending' => 0.0], 'exactly the target');
$over = CohortService::admissionShares(9, 6, 10);
check($over['over_admitted'] && $over['base'] === 15 && $over['counts']['pending'] === 0 && $over['pct'] === ['b2b' => 60.0, 'b2c' => 40.0, 'pending' => 0.0], 'over-admission: ' . json_encode($over));
foreach ([[0, 0, 0], [0, 3, 0], [5, 5, -1]] as [$b, $c, $t]) {
    $zero = CohortService::admissionShares($b, $c, $t);
    check($zero['base'] === 0 && $zero['pct'] === ['b2b' => 0.0, 'b2c' => 0.0, 'pending' => 0.0], "target {$t}: empty");
}
mt_srand(7);
for ($i = 0; $i < 5000; $i++) {
    $target = mt_rand(1, 20000);
    $b2b = mt_rand(0, $target);
    $b2c = mt_rand(0, (int) ($target * 1.3));
    $r = CohortService::admissionShares($b2b, $b2c, $target);
    $tenths = array_map(static fn(float $p): int => (int) round($p * 10), $r['pct']);
    check(array_sum($tenths) === 1000, "sum 100.0 for {$b2b}/{$b2c}/{$target}: " . json_encode($r['pct']));
    foreach ($r['counts'] as $key => $count) {
        check(abs($r['pct'][$key] - $count * 100 / $r['base']) < 0.1, "{$key} within 0.1 of the exact share for {$b2b}/{$b2c}/{$target}");
    }
}
// The badge formula is unchanged (capped at 100.0).
check(CohortService::admissionPct(15, 10) === 100.0 && CohortService::admissionPct(2427, 3708) === 65.5, 'admissionPct unchanged');

// ─── 2. Default year = current El Salvador year (2026) ───────────────────
$d = dashboard();
check($d['years'] === ['2027', '2026', '2025', '2024', '2023'], 'options newest first, only start years: ' . json_encode($d['years']));
check($d['year'] === '2026', 'default year 2026');
check($d['subtitle'] === 'Avance contra la meta de las cohortes que inician en 2026.', 'subtitle: ' . $d['subtitle']);
check($d['cards'] === ['admissions' => 2427, 'target' => 3708, 'pending' => 1281], '2026 cards (cancelled included): ' . json_encode($d['cards']));
check($d['badge'] === 65.5, '2026 badge ' . $d['badge']);
check($d['bars'] === ['b2b' => '43.2 % (1,602)', 'b2c' => '22.3 % (825)', 'pending' => '34.5 % (1,281)'], '2026 bars: ' . json_encode($d['bars']));
check(array_column($d['payload']['bars'], 'text') === array_values($d['bars']), 'chart labels = page labels');
check(array_column($d['payload']['bars'], 'pct') === [43.2, 22.3, 34.5] && array_column($d['payload']['bars'], 'count') === [1602, 825, 1281], 'chart series in % with counts');
check(round(array_sum(array_column($d['payload']['bars'], 'pct')), 1) === 100.0, 'chart adds up to 100');
check($d['payload']['empty'] === false && $d['payload']['year'] === '2026', 'payload year');
check($d['closed'] === [2, '2026', '2026'], 'Completado 2026 (B26 + DEC25 closed in 2026): ' . json_encode($d['closed']));
check($d['noStart'] === '2 cohortes sin fecha de inicio no se incluyen.', 'no start date note: ' . $d['noStart']);
check($d['empty'] === null && $d['over'] === false && $d['badgeOver'] === '', '2026 not empty, not over-admitted, plain badge');
check($d['scope'] === 'Totales y estados: todas las cohortes (incluye canceladas y sin fechas), corte 05/10/2026 · Admisiones: cohortes que inician en 2026', 'scope line: ' . $d['scope']);

// Same totals query as Plan Maestro: start range on the whole year.
$m = masterRange('2026-01-01', '2026-12-31');
check($m === ['admissions' => 2427, 'target' => 3708, 'pct' => 65.5], 'Plan Maestro 2026 = Dashboard 2026: ' . json_encode($m));

// ─── 3. Changing the year recalculates cards, bars, badge and Completado ─
$y25 = dashboard('?year=2025');
check($y25['year'] === '2025' && $y25['subtitle'] === 'Avance contra la meta de las cohortes que inician en 2025.', '2025 selected');
check($y25['cards'] === ['admissions' => 75, 'target' => 150, 'pending' => 75], '2025 cards (DEC25 counts in 2025): ' . json_encode($y25['cards']));
check($y25['badge'] === 50.0 && $y25['badgeOver'] === '', '2025 badge');
check(str_ends_with((string) $y25['scope'], '· Admisiones: cohortes que inician en 2025') && str_contains((string) $y25['scope'], 'corte 05/10/2026'), '2025 scope line: ' . $y25['scope']);
check($y25['bars'] === ['b2b' => '26.7 % (40)', 'b2c' => '23.3 % (35)', 'pending' => '50.0 % (75)'], '2025 bars: ' . json_encode($y25['bars']));
check($y25['closed'] === [1, '2025', '2025'], 'Completado 2025 (Y25 only; DEC25 closed in 2026): ' . json_encode($y25['closed']));
check(masterRange('2025-01-01', '2025-12-31') === ['admissions' => 75, 'target' => 150, 'pct' => 50.0], 'Plan Maestro 2025 = Dashboard 2025');
// The Completado card = its "Ver historial" link (DASH-004).
preg_match('/data-status-card="total">(\d+)</', page('/cohorts?closed_year=2025'), $hist);
check((int) ($hist[1] ?? -1) === 1, 'Ver historial 2025 lists 1 cohort');

// Over-admission (2024): Pendiente 0, shares over admissions, badge capped.
$y24 = dashboard('?year=2024');
check($y24['cards'] === ['admissions' => 15, 'target' => 10, 'pending' => 0], '2024 cards: ' . json_encode($y24['cards']));
check($y24['bars'] === ['b2b' => '60.0 % (9)', 'b2c' => '40.0 % (6)', 'pending' => '0.0 % (0)'], '2024 bars: ' . json_encode($y24['bars']));
check($y24['badge'] === 100.0 && $y24['over'], '2024 badge 100.0 % + over-admission note');
check($y24['badgeOver'] === ' · +5 sobre la meta', '2024 badge "+5 sobre la meta" (15 - 10): ' . json_encode($y24['badgeOver']));
check($y24['closed'] === [1, '2024', '2024'], 'Completado 2024');

// Target 0 (2023): empty state, no bars, no division by zero.
$y23 = dashboard('?year=2023');
check($y23['empty'] === 'Sin meta de admisiones en 2023' && $y23['bars'] === [] && $y23['cards'] === null, '2023 empty state: ' . json_encode($y23['empty']));
check($y23['payload']['empty'] === true && $y23['badge'] === 0.0 && $y23['badgeOver'] === '', '2023 chart skipped, badge 0.0 % (3 admitted, target 0: no "+N")');
check($y23['closed'] === [1, '2023', '2023'], 'Completado 2023');

// Future year with target and nothing admitted.
$y27 = dashboard('?year=2027');
check($y27['bars'] === ['b2b' => '0.0 % (0)', 'b2c' => '0.0 % (0)', 'pending' => '100.0 % (30)'] && $y27['closed'] === [0, '2027', '2027'], '2027');

// ─── 4. Invalid ?year= falls back to the current year ────────────────────
foreach (['?year=2022', '?year=abcd', '?year=20261', '?year=226', '?year=%202025', '?year[]=2025', '?year=2025-01', '?year=2025%0A'] as $bad) {
    $b = dashboard($bad);
    check($b['year'] === '2026' && $b['cards'] === $d['cards'] && $b['bars'] === $d['bars'] && $b['closed'] === $d['closed'], "{$bad} falls back to 2026: " . json_encode([$b['year'], $b['cards']]));
}

// ─── 5. Current year without cohorts: clear empty state ──────────────────
$y28 = dashboard('', '2028-03-01 12:00:00');
check($y28['year'] === '2028' && $y28['years'][0] === '2028', '2028 is the current year and is offered: ' . json_encode($y28['years']));
check($y28['empty'] === 'Sin cohortes en 2028' && $y28['bars'] === [] && $y28['badge'] === 0.0, '2028 empty state: ' . json_encode($y28['empty']));
check($y28['closed'] === [0, '2028', '2028'], 'Completado 2028');

echo "2026: B2B {$d['bars']['b2b']}, B2C {$d['bars']['b2c']}, Pendiente {$d['bars']['pending']}, badge {$d['badge']}%\n";
echo "PASS: DASH-007 admissions by start year — % + count per bar, exactly 100.0 (largest remainder), over-admission, target 0, empty year, ?year= validation, Completado follows the year, same totals as Plan Maestro (synthetic SQLite).\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one GET against a fresh in-memory database, as admin.
// ═════════════════════════════════════════════════════════════════════════
function runPage(array $spec): void
{
    $pdo = new SqlitePdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    App\Services\CohortStatus::freezeNow($spec['now']);
    $today = App\Services\CohortStatus::today();
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
        training_status TEXT NOT NULL DEFAULT 'not_started',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, full_name TEXT, role TEXT, is_active INTEGER DEFAULT 1);
    CREATE TABLE cohort_comments (id INTEGER PRIMARY KEY, cohort_id INTEGER, section_id INTEGER, user_id INTEGER,
        category TEXT, body TEXT, created_at TEXT);
    CREATE TABLE marketing_stages (id INTEGER PRIMARY KEY, cohort_id INTEGER, stage_name TEXT, status TEXT,
        notes TEXT, updated_by INTEGER, updated_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (cohort_code, name, training_status, start_date, end_date, bootcamp_type,
        total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach (fixture() as $code => [$st, $s, $e, $target, $b2bTarget, $b2b, $b2c]) {
        $insert->execute([$code, 'Cohorte ' . strtolower($code), $st, $s, $e, 'Full Stack', $target, $b2bTarget, max(0, $target - $b2bTarget), $b2b, $b2c]);
    }

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'DASH-007 probe', 'email' => '', 'role' => 'admin'],
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
        echo RESULT_MARKER, json_encode(['status' => http_response_code() ?: 200, 'body' => $body]);
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
