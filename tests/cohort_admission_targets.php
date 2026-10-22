<?php
declare(strict_types=1);

// COH-004 regression: over-admitted cohorts (B2B target above the total target, as
// delivered by Academia, e.g. AIGSK32/AIGSK33) are valid and can be saved on create,
// edit and import; business-model filters work on those rows without UNSIGNED
// subtraction; and the error-detail policy never exposes details in production.
// Isolated: synthetic SQLite in memory, never loads .env or an operational database.
// Run: php tests/cohort_admission_targets.php
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
// Same contract as bootstrap/app.php env(), without reading .env.
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return match (strtolower($value)) {
        'true' => true, 'false' => false, 'null' => null, 'empty' => '',
        default => $value,
    };
}
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

// Mirrors the operational `cohorts` table: no CHECK constraints on targets/admissions.
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
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
CREATE TABLE audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, entity_type TEXT,
    entity_id INTEGER, old_values TEXT, new_values TEXT, ip_address TEXT, created_at TEXT);");

$reflection = new ReflectionClass(App\Core\Database::class);
$db = $reflection->newInstanceWithoutConstructor();
$reflection->getProperty('pdo')->setValue($db, $pdo);
$reflection->getProperty('instance')->setValue(null, $db);
(new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
$_SESSION = ['user' => ['id' => 1, 'role' => 'admin']];
App\Core\SafeLog::$disabled = true;

$service = new App\Services\CohortService();
$repo = new App\Repositories\CohortRepository();
$base = ['name' => 'Cohorte prueba', 'start_date' => '2026-01-10', 'end_date' => '2026-03-10'];
$targets = static function (int $id) use ($repo): array {
    $row = $repo->findById($id);
    return [(int) $row['total_admission_target'], (int) $row['b2b_admission_target'], (int) $row['b2b_admissions']];
};

// ─── Create: over-admitted cohort is saved ─────────────────
$overId = $service->createCohort($base + [
    'cohort_code' => 'AIGSK32', 'total_admission_target' => 15, 'b2b_admission_target' => 16, 'b2b_admissions' => 18,
]);
check($targets($overId) === [15, 16, 18], 'Create must save b2b target > total target and admissions > target');

$okId = $service->createCohort($base + ['cohort_code' => 'OK1', 'total_admission_target' => 20, 'b2b_admission_target' => 12]);
check($targets($okId) === [20, 12, 0], 'Create must save a regular cohort');

// ─── Edit (partial update, as CohortController::update does) ─
$service->updateCohortPartial($okId, ['total_admission_target' => 20, 'b2b_admission_target' => 21]);
check($targets($okId) === [20, 21, 0], 'Edit must save b2b target > total target');
$service->updateCohortPartial($okId, ['total_admission_target' => 10]);
check($targets($okId) === [10, 21, 0], 'Edit lowering only the total below the b2b target must be saved');
$service->updateCohortPartial($okId, ['b2b_admissions' => 30]);
check($targets($okId) === [10, 21, 30], 'Edit of b2b admissions above the b2b target must be saved');
$service->updateCohortPartial($okId, ['total_admission_target' => 30, 'b2b_admission_target' => 12, 'b2b_admissions' => 5]);
check($targets($okId) === [30, 12, 5], 'Edit back to a regular cohort must be saved');

// ─── Import: over-admitted rows pass validation and keep their values ─
$import = (new ReflectionClass(App\Services\CohortImportService::class))->newInstanceWithoutConstructor();
$validateRow = new ReflectionMethod($import, 'validateRow');
$normalizeRow = new ReflectionMethod($import, 'normalizeRow');
$importRow = ['name' => 'Import', 'start_date' => '2026-01-10', 'end_date' => '2026-03-10',
              'total_admission_target' => '15', 'b2b_admission_target' => '16', 'b2c_admissions' => '20'];
check($validateRow->invoke($import, $importRow, 2) === [], 'Import must accept b2b target > total target');
check($validateRow->invoke($import, ['total_admission_target' => '', 'b2b_admission_target' => '5'] + $importRow, 2) === [], 'Import must accept b2b target with empty total');
$normalized = $normalizeRow->invoke($import, $importRow);
check($normalized['total_admission_target'] === 15 && $normalized['b2b_admission_target'] === 16, 'Import must keep over-admitted targets unchanged');

// ─── Business-model filters with over-admitted rows ────────
// AIGSK32 (15/16) and AIGSK33 (15/16) like production; OK1 (30/12); pure B2C (10/0); pure B2B (8/8).
$pdo->exec("INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, start_date, end_date) VALUES
            ('AIGSK33', 'Over-admitted', 15, 16, '2026-01-10', '2026-03-10'),
            ('PUREB2C', 'Pure B2C', 10, 0, '2026-01-10', '2026-03-10'),
            ('PUREB2B', 'Pure B2B', 8, 8, '2026-01-10', '2026-03-10')");
$codes = static function (array $filters) use ($service): array {
    $values = array_column($service->getFilteredCohorts($filters), 'cohort_code');
    sort($values);
    return $values;
};
check($codes(['business_model' => 'b2c']) === ['OK1', 'PUREB2C'], 'B2C filter: total > b2b target, over-admitted rows excluded without error');
check($codes(['business_model' => 'b2b']) === ['AIGSK32', 'AIGSK33', 'OK1', 'PUREB2B'], 'B2B filter: b2b target > 0, over-admitted rows included');
check(count($codes(['business_model' => ''])) === 5, 'All: no business-model condition');
check(count($service->getFinancialByMonth(['business_model' => 'b2c'])) === 1, 'Finance aggregation with B2C filter must not fail');

$buildFilters = new ReflectionMethod(App\Repositories\CohortRepository::class, 'buildFilters');
[$where] = $buildFilters->invoke($repo, ['business_model' => 'b2c']);
check(!preg_match('/admission_target\s*-\s*c\./', implode(' ', $where)), 'B2C filter must not subtract UNSIGNED columns');

// ─── Error-detail policy (never details in production) ─────
$matrix = [
    ['production', 'true', false],
    ['production', 'false', false],
    ['prod', 'true', false],       // unknown values fail closed
    [null, 'true', false],         // missing APP_ENV fails closed
    ['local', 'true', true],
    ['local', '1', true],
    ['local', 'false', false],
];
foreach ($matrix as [$appEnv, $debug, $expected]) {
    $appEnv === null ? putenv('APP_ENV') : putenv("APP_ENV={$appEnv}");
    putenv("APP_DEBUG={$debug}");
    check(App\Core\ErrorHandler::showDetails() === $expected, sprintf('showDetails(APP_ENV=%s, APP_DEBUG=%s)', $appEnv ?? '(unset)', $debug));
}
putenv('APP_ENV');
putenv('APP_DEBUG');

echo "PASS: over-admitted cohorts saved on create/edit/import, b2b/b2c/all filters without subtraction, error-detail policy (synthetic SQLite).\n";
