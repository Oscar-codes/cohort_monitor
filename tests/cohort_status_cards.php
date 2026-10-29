<?php
declare(strict_types=1);

// COH-007 regression: in Cohortes the status cards (No iniciado, En progreso,
// Completado, Cancelado, Sin fechas) always add up to "Resultados", for every
// combination of estado × rango de inicio × proyecto × población, and each card
// counts exactly the cohorts listed under its status. Original report: range
// 20/07/2026–02/10/2026 + Cancelado listed 19 cohorts while the cards said 0.
// Plan Maestro gets the same check for its cards (Cohortes filtradas,
// Inscritos, Revenue, Riesgo de admisión) and the Estado pill of every row.
// Expected sets are computed here, in plain PHP, from the fixture and the
// documented rules (CohortStatus::derive, start-date range, population), not
// from the repository, so a drift in CohortRepository::buildFilters() fails.
// Pages run in child PHP processes through the real Router, controllers and views.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_status_cards.php

use App\Services\CohortStatus;

const NOW_UTC = '2026-10-06 02:00:00';   // 2026-10-05 in El Salvador
const TODAY = '2026-10-05';
const RESULT_MARKER = '@@COH007@@';
const PARALLEL = 8;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

/**
 * code => [stored training_status, start, end, project, total target, b2b target,
 *          b2b adm, b2c adm, revenue target, revenue actual]
 * Audit range: 2026-07-20 .. 2026-10-02.
 */
function fixture(): array
{
    return [
        // Cancelled: inside, before, after the range, without dates.
        'CAN_IN'      => ['cancelled',   '2026-08-10', '2026-12-10', 'Alpha', 30, 30, 4, 0, 1000, 100],
        'CAN_IN_B2C'  => ['cancelled',   '2026-09-15', '2026-12-15', 'Beta',  20,  0, 0, 3,  800,  50],
        'CAN_EDGE'    => ['cancelled',   '2026-10-02', '2027-01-02', 'Alpha', 25, 10, 2, 2,  900,   0],
        'CAN_BEFORE'  => ['cancelled',   '2026-06-01', '2026-09-01', 'Alpha', 15, 15, 1, 0,  500,  20],
        'CAN_AFTER'   => ['cancelled',   '2026-11-01', '2027-02-01', 'Beta',  18,  6, 0, 1,  700,   0],
        'CAN_NODATE'  => ['cancelled',   null,         null,         'Beta',  10,  0, 0, 0,  300,   0],
        // In progress, including range edges and a cohort without end date.
        'IP_FROM'     => ['in_progress', '2026-07-20', '2026-11-20', 'Beta',  40, 10, 8, 20, 3000, 2800],
        'IP_MID'      => ['in_progress', '2026-08-01', '2026-12-01', 'Alpha', 35, 15, 14, 18, 2500, 2400],
        'IP_NOEND'    => ['not_started', '2026-09-01', null,         'Alpha', 20, 20, 5, 0, 1200, 300],
        'IP_TO'       => ['completed',   '2026-10-02', '2027-01-02', 'Beta',  22,  0, 0, 21, 1800, 1750],
        'IP_NOPROJ'   => ['in_progress', '2026-09-10', '2026-12-10', null,    12,  4, 2, 3,  600, 100],
        'IP_BEFORE'   => ['in_progress', '2026-05-01', '2026-12-31', 'Alpha', 16,  8, 8, 8, 1000, 1000],
        // Completed (one stale stored value: dates win).
        'CO_IN'       => ['completed',   '2026-07-25', '2026-09-30', 'Alpha', 28, 14, 7, 6, 1400, 900],
        'CO_STALE'    => ['in_progress', '2026-08-05', '2026-09-20', 'Beta',  24,  0, 0, 24, 1300, 1300],
        'CO_BEFORE'   => ['completed',   '2026-03-01', '2026-06-01', 'Beta',  14,  7, 7, 7,  800, 800],
        // b2b target above total: B2B only, not B2C.
        'CO_B2B_OVER' => ['completed',   '2026-08-20', '2026-10-01', 'Alpha',  5, 10, 3, 0,  400, 100],
        // Not started (always after today, so never in the audit range).
        'NS_AFTER'    => ['not_started', '2026-12-01', '2027-03-01', 'Alpha', 30, 12, 0, 0, 2000, 0],
        'NS_STALE'    => ['completed',   '2026-10-20', '2027-01-20', 'Beta',  26,  0, 0, 2, 1500, 0],
        // No start date.
        'ND_NONE'     => ['not_started', null,         null,         'Alpha', 18,  6, 1, 2,  900, 100],
        'ND_ENDONLY'  => ['not_started', null,         '2026-09-20', 'Beta',  12,  0, 0, 1,  600, 50],
        'ND_NOPROJ'   => ['not_started', null,         null,         null,     0,  0, 0, 0,    0, 0],
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

/** GET every path in child processes, PARALLEL at a time; path => HTML. */
function pages(array $paths): array
{
    $bodies = [];
    $queue = array_values($paths);
    $running = [];
    while ($queue !== [] || $running !== []) {
        while ($queue !== [] && count($running) < PARALLEL) {
            $path = array_shift($queue);
            $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --page ' . escapeshellarg(base64_encode(json_encode(['path' => $path])));
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
            check(is_resource($proc), "cannot start child for {$path}");
            $running[] = [$path, $proc, $pipes[1]];
        }
        [$path, $proc, $out] = array_shift($running);
        $output = (string) stream_get_contents($out);
        fclose($out);
        proc_close($proc);

        $pos = strrpos($output, RESULT_MARKER);
        check($pos !== false, "no result from child ({$path}): " . substr($output, -800));
        $result = json_decode(substr($output, $pos + strlen(RESULT_MARKER)), true, 512, JSON_THROW_ON_ERROR);
        check($result['status'] === 200, "{$path} status {$result['status']}");
        $body = base64_decode($result['body']);
        check(!preg_match('/(Fatal error|Warning|Notice|Deprecated):/', $body), "PHP error in {$path}: " . substr($body, 0, 600));
        $bodies[$path] = $body;
    }
    return $bodies;
}

/** Fixture codes in fixture order (order-insensitive comparisons). */
function ordered(array $codes): array
{
    return array_values(array_intersect(array_keys(fixture()), array_unique($codes)));
}

/** Cohortes: cards and the codes listed under each status accordion. */
function parseCohortes(string $html): array
{
    preg_match_all('/data-status-card="([a-z_]+)">(\d+)</', $html, $m, PREG_SET_ORDER);
    $cards = [];
    foreach ($m as [, $key, $count]) {
        $cards[$key] = (int) $count;
    }

    // The accordion list ends where the Gantt view starts.
    $list = explode('<div id="view-gantt"', $html, 2)[0];
    $sections = array_fill_keys(array_keys(CohortStatus::LABELS), []);
    $parts = preg_split('/id="accordion-([a-z_]+)"/', $list, -1, PREG_SPLIT_DELIM_CAPTURE);
    for ($i = 1; $i < count($parts); $i += 2) {
        check(array_key_exists($parts[$i], $sections), "unknown accordion {$parts[$i]}");
        preg_match_all('#<div class="small text-muted mt-1">([A-Z_0-9]+)</div>#', $parts[$i + 1], $codes);
        $sections[$parts[$i]] = ordered($codes[1]);
    }
    return ['cards' => $cards, 'sections' => $sections];
}

/** Plan Maestro: cards and, per row, the code and the Estado pill. */
function parseMaster(string $html): array
{
    preg_match('/<strong>(\d+)<\/strong>\s*<small>Cohortes filtradas<\/small>/', $html, $total);
    preg_match('/<strong>(\d+) \/ (\d+)<\/strong>\s*<small>Inscritos/', $html, $adm);
    preg_match('/<strong>\$([\d,.]+)<\/strong>\s*<small>Revenue/', $html, $rev);
    preg_match('/<strong>(\d+)<\/strong>\s*<small>Cohortes con riesgo de admision<\/small>/', $html, $risk);
    check(isset($total[1], $adm[2], $rev[1], $risk[1]), 'Plan Maestro cards not found');

    $labels = array_flip(CohortStatus::LABELS);
    $rows = [];
    $tbody = explode('<tbody>', $html, 2)[1] ?? '';
    foreach (array_slice(explode('<tr>', explode('</tbody>', $tbody, 2)[0]), 1) as $tr) {
        preg_match('#<a href="/cohorts/\d+"[^>]*>\s*([A-Z_0-9]+)\s*</a>#', $tr, $code);
        preg_match_all('#kodigo-pill__dot" aria-hidden="true"></span>([^<]+)</span>#', $tr, $pills);
        $states = array_values(array_filter($pills[1], static fn(string $p): bool => isset($labels[$p])));
        check(isset($code[1]) && count($states) === 1, 'Plan Maestro row without code or Estado: ' . substr(strip_tags($tr), 0, 200));
        $rows[$code[1]] = $labels[$states[0]];
    }
    return [
        'cards' => [
            'cohorts'        => (int) $total[1],
            'admissions'     => (int) $adm[1],
            'target'         => (int) $adm[2],
            'revenue_actual' => (float) str_replace(',', '', $rev[1]),
            'at_risk'        => (int) $risk[1],
        ],
        'rows' => $rows,
    ];
}

// ─── Expected values straight from the fixture ───────────────────────────
$fx = fixture();
$statusOf = array_map(static fn(array $r): string => CohortStatus::derive($r[0], $r[1], $r[2], TODAY), $fx);

// The fixture covers every status inside and outside the audit range.
foreach (CohortStatus::LABELS as $status => $label) {
    check(in_array($status, $statusOf, true), "fixture has no {$status}");
}

$expect = static function (array $f) use ($fx, $statusOf): array {
    return array_keys(array_filter($fx, static function (array $r, string $code) use ($f, $statusOf): bool {
        if ($f['start_date'] !== '' && ($r[1] === null || $r[1] < $f['start_date'])) {
            return false;
        }
        if ($f['end_date'] !== '' && ($r[1] === null || $r[1] > $f['end_date'])) {
            return false;
        }
        if ($f['related_project'] !== '' && $r[3] !== $f['related_project']) {
            return false;
        }
        if ($f['business_model'] === 'b2b' && !($r[5] > 0)) {
            return false;
        }
        if ($f['business_model'] === 'b2c' && !($r[4] > $r[5])) {
            return false;
        }
        return $f['cohort_status'] === '' || $statusOf[$code] === $f['cohort_status'];
    }, ARRAY_FILTER_USE_BOTH));
};

$ranges = [
    'sin rango'   => ['', ''],
    'auditoría'   => ['2026-07-20', '2026-10-02'],
    'solo desde'  => ['2026-07-20', ''],
    'solo hasta'  => ['', '2026-10-02'],
];
$statuses = array_merge([''], array_keys(CohortStatus::LABELS));
$cases = [];
foreach ($ranges as $rangeName => [$from, $to]) {
    // The full matrix on "sin rango" and the audit range; the one-sided
    // ranges with every status and population, without project.
    $projects = in_array($rangeName, ['sin rango', 'auditoría'], true) ? ['', 'Alpha', 'Beta'] : [''];
    foreach ($statuses as $status) {
        foreach ($projects as $project) {
            foreach (['', 'b2b', 'b2c'] as $population) {
                $f = ['start_date' => $from, 'end_date' => $to, 'related_project' => $project, 'business_model' => $population, 'cohort_status' => $status];
                $name = sprintf('%s | estado=%s proyecto=%s población=%s', $rangeName, $status ?: '-', $project ?: '-', $population ?: '-');
                $cases[$name] = $f;
            }
        }
    }
}
// Search on top of every other filter.
$cases['auditoría | cancelado + Alpha + b2b + búsqueda CAN'] = ['start_date' => '2026-07-20', 'end_date' => '2026-10-02', 'related_project' => 'Alpha', 'business_model' => 'b2b', 'cohort_status' => 'cancelled', 'search' => 'CAN'];
// Inverted range is swapped (same set as the audit range).
$cases['auditoría invertida | cancelado'] = ['start_date' => '2026-10-02', 'end_date' => '2026-07-20', 'related_project' => '', 'business_model' => '', 'cohort_status' => 'cancelled'];

$paths = [];
foreach ($cases as $name => $f) {
    $query = '?' . http_build_query(array_filter($f, static fn(string $v): bool => $v !== ''));
    $paths[$name] = ['/cohorts' . $query, '/cohorts/master' . $query];
}
$bodies = pages(array_merge(...array_values($paths)));

$nonEmpty = 0;
foreach ($cases as $name => $f) {
    $f += ['search' => ''];
    $wanted = $expect(array_merge($f, $f['start_date'] !== '' && $f['end_date'] !== '' && $f['start_date'] > $f['end_date']
        ? ['start_date' => $f['end_date'], 'end_date' => $f['start_date']] : []));
    if ($f['search'] !== '') {
        $wanted = array_values(array_filter($wanted, static fn(string $c): bool => str_contains($c, $f['search'])));
    }
    $byStatus = array_fill_keys(array_keys(CohortStatus::LABELS), []);
    foreach ($wanted as $code) {
        $byStatus[$statusOf[$code]][] = $code;
    }
    $nonEmpty += $wanted !== [] ? 1 : 0;

    // Cohortes: Resultados = Σ cards = expected; each card = its accordion.
    [$listPath, $masterPath] = $paths[$name];
    $c = parseCohortes($bodies[$listPath]);
    $total = $c['cards']['total'] ?? null;
    $cards = $c['cards'];
    unset($cards['total']);
    check(array_keys($cards) === array_keys(CohortStatus::LABELS), "{$name}: Cohortes cards " . json_encode(array_keys($cards)));
    check($total === array_sum($cards), "{$name}: Resultados {$total} vs Σ cards " . array_sum($cards) . ' ' . json_encode($cards));
    check($total === count($wanted), "{$name}: Resultados {$total} vs expected " . count($wanted) . ' ' . json_encode($wanted));
    foreach (CohortStatus::LABELS as $status => $label) {
        check($cards[$status] === count($byStatus[$status]), "{$name}: card {$label} {$cards[$status]} vs " . json_encode($byStatus[$status]));
        check($c['sections'][$status] === $byStatus[$status], "{$name}: listed under {$label} " . json_encode($c['sections'][$status]) . ' vs ' . json_encode($byStatus[$status]));
        check(count($c['sections'][$status]) === $cards[$status], "{$name}: card {$label} counts what it lists");
    }

    // Plan Maestro: cards = Σ over the listed rows = expected; Estado per row.
    $m = parseMaster($bodies[$masterPath]);
    $rows = array_intersect_key($fx, array_flip($wanted));
    $want = [
        'cohorts'        => count($wanted),
        'admissions'     => array_sum(array_map(static fn(array $r): int => $r[6] + $r[7], $rows)),
        'target'         => array_sum(array_column($rows, 4)),
        'revenue_actual' => (float) array_sum(array_column($rows, 9)),
        'at_risk'        => count(array_filter($rows, static fn(array $r): bool => $r[4] > 0 && $r[6] + $r[7] < (int) floor($r[4] * 0.7))),
    ];
    check(ordered(array_keys($m['rows'])) === $wanted && count($m['rows']) === count($wanted), "{$name}: Plan Maestro rows " . json_encode(array_keys($m['rows'])) . ' vs ' . json_encode($wanted));
    check($m['cards'] === $want, "{$name}: Plan Maestro cards " . json_encode($m['cards']) . ' vs ' . json_encode($want));
    check($m['cards']['cohorts'] === $total, "{$name}: Cohortes filtradas = Resultados");
    foreach ($m['rows'] as $code => $status) {
        check($status === $statusOf[$code], "{$name}: Plan Maestro Estado of {$code} {$status} vs {$statusOf[$code]}");
        check($f['cohort_status'] === '' || $status === $f['cohort_status'], "{$name}: Plan Maestro row {$code} outside the status filter");
    }
}

// The cases called out by the audit report.
$audit = parseCohortes($bodies[$paths['auditoría | estado=cancelled proyecto=- población=-'][0]]);
check($audit['sections']['cancelled'] === ['CAN_IN', 'CAN_IN_B2C', 'CAN_EDGE'], 'audit range + Cancelado: ' . json_encode($audit['sections']['cancelled']));
check($audit['cards']['cancelled'] === 3 && $audit['cards']['total'] === 3, 'audit range + Cancelado: card Cancelado = Resultados = 3');
foreach (['CAN_BEFORE', 'CAN_AFTER', 'CAN_NODATE'] as $outside) {
    check(!in_array($outside, $audit['sections']['cancelled'], true), "{$outside} must not be in the audit range");
}

// COH-008: filter, column and chip say "Población" (never "sub canal").
[$listB2b, $masterB2b] = $paths['sin rango | estado=- proyecto=- población=b2b'];
foreach (['/cohorts' => $bodies[$listB2b], '/cohorts/master' => $bodies[$masterB2b]] as $path => $html) {
    check(str_contains($html, '<label for="business_model" class="form-label">Población</label>'), "{$path}: Población filter label");
    check(preg_match('/Población:?\s*(<\/strong>)?\s*(<strong>)?\s*B2B/u', strip_tags($html, '<strong>')) === 1, "{$path}: Población chip");
    check(preg_match('/sub[\s_-]*canal/iu', $html) === 0, "{$path}: still says sub canal");
}
check(str_contains($bodies[$listB2b], '<th class="d-none d-lg-table-cell text-center">Población</th>'), '/cohorts: Población column');

echo 'PASS: ' . count($cases) . ' filter combinations (' . $nonEmpty . ' with results): Cohortes cards add up to Resultados and count what they list; Plan Maestro cards and Estado match its rows (synthetic SQLite).' . "\n";

// ═════════════════════════════════════════════════════════════════════════
//  Child process: one GET against a fresh in-memory database, as admin.
// ═════════════════════════════════════════════════════════════════════════
function runPage(array $spec): void
{
    $pdo = new SqlitePdo('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->sqliteCreateFunction('CURDATE', static fn(): string => TODAY, 0);
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
        category TEXT, body TEXT, created_at TEXT);");
    $insert = $pdo->prepare('INSERT INTO cohorts (cohort_code, name, training_status, start_date, end_date, related_project,
        bootcamp_type, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions,
        financial_target_revenue, financial_actual_revenue)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach (fixture() as $code => [$stored, $s, $e, $project, $target, $b2bTarget, $b2b, $b2c, $revTarget, $revActual]) {
        $insert->execute([$code, 'Cohorte ' . strtolower($code), $stored, $s, $e, $project, 'Full Stack',
            $target, $b2bTarget, max(0, $target - $b2bTarget), $b2b, $b2c, $revTarget, $revActual]);
    }

    $reflection = new ReflectionClass(App\Core\Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    (new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
    CohortStatus::freezeNow(NOW_UTC);
    $_SESSION = [
        'user' => ['id' => 1, 'username' => 'probe', 'full_name' => 'COH-007 probe', 'email' => '', 'role' => 'admin'],
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
