<?php
declare(strict_types=1);

// Standalone regression/measurement harness. Never loads .env.
// Run: php tests/dashboard_queries.php
// Optional: --mysql-port=PORT, only against a disposable local server with root/no password.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

use App\Core\Database;
use App\Repositories\CohortRepository;
use App\Repositories\CommentRepository;
use App\Repositories\MarketingStageRepository;
use App\Services\DashboardService;

final class MeasuredPDO extends PDO
{
    public int $queries = 0;
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries++;
        return parent::prepare($query, $options);
    }
}

// The unchanged upcoming query uses MySQL DATE_ADD/INTERVAL. Exclude it from
// this SQLite harness rather than pretending to validate MySQL-specific SQL.
final class CohortsWithoutUpcoming extends CohortRepository
{
    public function findUpcoming(int $days = 30, int $limit = 10): array
    {
        return [];
    }
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function fixture(int $size): MeasuredPDO
{
    global $mysqlAdmin, $mysqlPort, $testSchemas;
    $dsn = 'sqlite::memory:';
    if ($mysqlAdmin !== null) {
        $schema = 'cm_perf_test_' . bin2hex(random_bytes(8));
        $mysqlAdmin->exec('CREATE DATABASE `' . $schema . '` CHARACTER SET utf8mb4');
        $testSchemas[] = $schema;
        $dsn = 'mysql:host=127.0.0.1;port=' . $mysqlPort . ';dbname=' . $schema . ';charset=utf8mb4';
    }
    $pdo = new MeasuredPDO($dsn, $mysqlAdmin === null ? null : 'root', null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('CREATE TABLE cohorts (
        id INTEGER PRIMARY KEY, cohort_code TEXT, name TEXT, correlative_number INTEGER,
        total_admission_target INTEGER, b2b_admission_target INTEGER, b2c_admission_target INTEGER,
        b2b_admissions INTEGER, b2c_admissions INTEGER, financial_target_revenue NUMERIC,
        financial_actual_revenue NUMERIC, admission_deadline_date TEXT, start_date TEXT, end_date TEXT,
        related_project TEXT, assigned_coach TEXT, bootcamp_type TEXT, area TEXT,
        assigned_class_schedule TEXT, training_status TEXT, created_at TEXT, updated_at TEXT);
        CREATE TABLE users (id INTEGER PRIMARY KEY, full_name TEXT, role TEXT);
        CREATE TABLE cohort_comments (id INTEGER PRIMARY KEY, cohort_id INTEGER, user_id INTEGER,
            category TEXT, body TEXT, created_at TEXT);
        CREATE TABLE marketing_stages (id INTEGER PRIMARY KEY, cohort_id INTEGER, updated_by INTEGER,
            stage_name TEXT, status TEXT, risk_notes TEXT, updated_at TEXT);
        INSERT INTO users VALUES (1, "Synthetic author", "admin")');
    $cohort = $pdo->prepare('INSERT INTO cohorts
        (id, cohort_code, name, start_date, total_admission_target, b2b_admissions, b2c_admissions,
         training_status, bootcamp_type) VALUES (?, ?, ?, ?, 20, 4, 6, "not_started", "Test")');
    $comment = $pdo->prepare('INSERT INTO cohort_comments VALUES (?, ?, 1, "risk", ?, ?)');
    $stage = $pdo->prepare('INSERT INTO marketing_stages VALUES (?, ?, NULL, "ads", "at_risk", ?, ?)');
    $pdo->beginTransaction();
    for ($i = 1; $i <= $size; $i++) {
        // Ties and NULL start dates exercise stable ordering; NULL authors must remain visible for stages.
        $date = $i % 7 === 0 ? null : '2026-10-' . sprintf('%02d', 1 + $i % 20);
        $cohort->execute([$i, 'TEST-' . $i, 'Synthetic cohort ' . $i, $date]);
        $created = '2026-09-' . sprintf('%02d', 1 + $i % 20);
        $comment->execute([$i, $i, str_repeat('x', 256), $created]);
        $stage->execute([$i, $i, str_repeat('y', 256), $created]);
    }
    $pdo->commit();
    // Rows excluded by the listing joins/predicates must also be excluded from counts.
    $pdo->exec('INSERT INTO cohort_comments VALUES
        (-1, -99, 1, "risk", "orphan cohort", "2099-01-01"),
        (-2, 1, -99, "risk", "orphan author", "2099-01-01"),
        (-3, 1, 1, "general", "not a risk", "2099-01-01");
        INSERT INTO marketing_stages VALUES
        (-1, -99, NULL, "ads", "at_risk", "orphan", "2099-01-01"),
        (-2, 1, NULL, "ads", "completed", "not a risk", "2099-01-01")');

    // Inject only this isolated connection into the existing singleton, without invoking its constructor.
    $reflection = new ReflectionClass(Database::class);
    $db = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('pdo')->setValue($db, $pdo);
    $reflection->getProperty('instance')->setValue(null, $db);
    return $pdo;
}

function measure(MeasuredPDO $pdo, callable $operation): array
{
    gc_collect_cycles();
    memory_reset_peak_usage();
    $startMemory = memory_get_usage();
    $pdo->queries = 0;
    $start = hrtime(true);
    $result = $operation();
    return [
        'result' => $result,
        'queries' => $pdo->queries,
        'peak_bytes' => memory_get_peak_usage() - $startMemory,
        'ms' => round((hrtime(true) - $start) / 1e6, 3),
    ];
}

$mysqlAdmin = null;
$mysqlPort = null;
$testSchemas = [];
if (isset($argv[1])) {
    check(preg_match('/^--mysql-port=([0-9]{1,5})$/', $argv[1], $match) === 1, 'Invalid option');
    $mysqlPort = (int) $match[1];
    check($mysqlPort > 0 && $mysqlPort <= 65535, 'Invalid port');
    $mysqlAdmin = new PDO('mysql:host=127.0.0.1;port=' . $mysqlPort, 'root', null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

try {
foreach ([0, 3, 10000] as $size) {
    $pdo = fixture($size);
    $cohorts = new CohortRepository();
    $comments = new CommentRepository();
    $stages = new MarketingStageRepository();

    $before = measure($pdo, static function () use ($cohorts, $comments, $stages): array {
        $risks = $comments->findAllRisks();
        $marketing = $stages->findAtRisk();
        $all = $cohorts->findAll();
        return [array_slice($all, 0, 5), array_slice($risks, 0, 5),
            array_slice($marketing, 0, 5), count($risks) + count($marketing)];
    });
    $after = measure($pdo, static function () use ($cohorts, $comments, $stages): array {
        return [$cohorts->findFirst(5), $comments->findAllRisks(5), $stages->findAtRisk(5),
            $comments->countAllRisks() + $stages->countAtRisk()];
    });
    check($before['result'] === $after['result'], "Result mismatch at size $size");
    check($after['result'][3] === $size * 2, 'Incorrect full alert count / orphan semantics');
    check($before['queries'] === 3 && $after['queries'] === 5, 'Unexpected query count');
    foreach ([-10, 0, 1, 5, 1000] as $limit) {
        $expected = min($size, max(1, min(100, $limit)));
        check(count($cohorts->findFirst($limit)) === $expected, 'Cohort limit');
        check(count($comments->findAllRisks($limit)) === $expected, 'Comment limit');
        check(count($stages->findAtRisk($limit)) === $expected, 'Stage limit');
    }

    $service = new DashboardService();
    if ($mysqlAdmin === null) {
        (new ReflectionProperty($service, 'cohortRepo'))->setValue($service, new CohortsWithoutUpcoming());
    }
    $pdo->queries = 0;
    $summary = $service->getSummaryStats();
    check($pdo->queries === ($mysqlAdmin === null ? 7 : 8), 'Unexpected dashboard query count');
    check([$summary['recentCohorts'], $summary['riskComments'], $summary['atRiskStages'],
        $summary['totalAlerts']] === $after['result'], 'Dashboard wiring mismatch');
    check($summary['totalCohorts'] === $size && $summary['totalAdmissions'] === $size * 10,
        'Dashboard aggregate regression');
    if ($size === 10000) {
        check($after['peak_bytes'] < $before['peak_bytes'] / 10, 'Expected bounded PHP memory');
        check(array_column($after['result'][1], 'id') === [9999, 9979, 9959, 9939, 9919],
            'Risk tie-breaker must be deterministic');
        check(array_column($after['result'][0], 'id') === [20, 40, 60, 80, 100],
            'Cohort ordering changed');
    }
    unset($before['result'], $after['result']);
    echo json_encode(['rows_per_list' => $size, 'before' => $before, 'after' => $after], JSON_THROW_ON_ERROR) . PHP_EOL;
    $pdo->exec('DROP TABLE cohort_comments');
    $failed = false;
    try {
        $service->getSummaryStats();
    } catch (PDOException) {
        $failed = true;
    }
    check($failed, 'Database failures must propagate instead of returning a successful empty dashboard');
}
echo "PASS: bounded lists, full totals, joins, empty sets, ties, limits and dashboard wiring.\n";
} finally {
    foreach ($testSchemas as $schema) {
        $mysqlAdmin->exec('DROP DATABASE `' . $schema . '`');
    }
}
