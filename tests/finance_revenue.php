<?php
declare(strict_types=1);

// Isolated regression: synthetic SQLite data, no .env or operational database.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
set_error_handler(static function (int $severity, string $message): never {
    throw new RuntimeException($message);
});
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->sqliteCreateFunction('DATE_FORMAT', static function (?string $date, string $format): ?string {
    return $date === null ? null : date(strtr($format, ['%Y' => 'Y', '%m' => 'm', '%b' => 'M']), strtotime($date));
}, 2);
$pdo->exec("CREATE TABLE cohorts (
    id INTEGER PRIMARY KEY, start_date TEXT, bootcamp_type TEXT, related_project TEXT,
    b2b_admission_target INTEGER, b2c_admission_target INTEGER, total_admission_target INTEGER,
    b2b_admissions INTEGER, b2c_admissions INTEGER,
    financial_target_revenue NUMERIC, financial_actual_revenue NUMERIC);
    INSERT INTO cohorts VALUES
    (1, '2026-01-01', 'Alpha', 'Test', 10, 20, 30, 2, 3, 1000.25, 800.50),
    (2, '2026-01-15', 'Alpha', 'Test', 0, 20, 20, 0, 4, 2000.50, 1200.25),
    (3, '2026-02-01', 'Beta', 'Test', 10, 0, 10, 1, 0, 500.25, 2500.75),
    (4, '2026-03-01', 'Null', 'Test', 0, 0, 0, 0, 0, NULL, NULL)");
$reflection = new ReflectionClass(App\Core\Database::class);
$db = $reflection->newInstanceWithoutConstructor();
$reflection->getProperty('pdo')->setValue($db, $pdo);
$reflection->getProperty('instance')->setValue(null, $db);
// Set a synthetic authorized session; authentication itself is outside this test.
(new ReflectionProperty(App\Core\Auth::class, 'booted'))->setValue(null, true);
$_SESSION = ['user' => ['id' => 1, 'role' => 'finance']];

class FinanceControllerProbe extends App\Controllers\CohortController {
    public array $result = [];
    protected function view(string $view, array $data = [], ?string $layout = 'layouts.main'): void {
        $this->result = $data;
    }
}
function renderFinance(array $data): string {
    extract($data);
    ob_start();
    require dirname(__DIR__) . '/app/Views/cohorts/finance.php';
    return ob_get_clean();
}
$repo = new App\Repositories\CohortRepository();
check(count($repo->getFinancialByMonth()) === 3, 'Default filters must work');
foreach (['' => [3501.0, 4501.5], 'b2b' => [1500.5, 3301.25], 'b2c' => [3000.75, 2000.75]] as $model => [$target, $actual]) {
    $_REQUEST = $_GET = ['year' => '2026', 'business_model' => $model];
    $controller = new FinanceControllerProbe();
    $controller->finance();
    $data = $controller->result;
    check(abs($data['totalTarget'] - $target) < 0.001, 'Financial target total');
    check(abs($data['totalActual'] - $actual) < 0.001, 'Financial actual total');
    foreach (['monthly', 'bootcamp'] as $chart) {
        check(abs(array_sum($data['financeChartData'][$chart]['target']) - $target) < 0.001, 'Chart target');
        check(abs(array_sum($data['financeChartData'][$chart]['actual']) - $actual) < 0.001, 'Chart actual');
    }
    $html = renderFinance($data);
    $dom = new DOMDocument();
    $previousErrors = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
    $xpath = new DOMXPath($dom);
    $tableColumns = $xpath->query('//*[@id="finance-revenue-tables"]/div');
    check($tableColumns->length === 2, 'Revenue tables must be sibling grid columns');
    foreach ($tableColumns as $column) {
        check($xpath->query('.//table', $column)->length === 1, 'One table per grid column');
    }
    $summaryValues = $xpath->query('//article[contains(@class,"cohort-summary-card")]//strong');
    check($summaryValues->item(0)->textContent === '$' . number_format($target, 2), 'Target card');
    check($summaryValues->item(1)->textContent === '$' . number_format($actual, 2), 'Actual card');
    foreach ($data['byBootcamp'] as $index => $row) {
        $cells = $xpath->query('.//tbody/tr', $tableColumns->item(1))->item($index)->getElementsByTagName('td');
        check($cells->item(1)->textContent === '$' . number_format((float) $row['financial_target_revenue'], 2), 'Table target');
        check($cells->item(2)->textContent === '$' . number_format((float) $row['financial_actual_revenue'], 2), 'Table actual');
    }
    preg_match('/<textarea id="cohort-finance-trend"[^>]*>(.*?)<\/textarea>/s', $html, $match);
    $trend = json_decode(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
    check(count($trend['months']) === 12, 'Twelve-month trend');
    check(abs(array_sum(array_column($trend['months'], 'actual')) - $actual) < 0.001, 'Rendered trend actual');
    check(str_contains($html, '$' . number_format($target, 2)), 'Rendered financial total');
}
$rank = $repo->getFinancialByBootcamp();
check($rank[0]['bootcamp_name'] === 'Beta', 'Rank by revenue, not admissions');
$filtered = $repo->getFinancialByMonth(['target_min' => 1000.25, 'target_max' => 1000.25]);
check(count($filtered) === 1 && (float) $filtered[0]['financial_actual_revenue'] === 800.5, 'Inclusive monetary range');
check((float) $rank[2]['financial_actual_revenue'] === 0.0, 'Null revenue becomes zero');
$_REQUEST = $_GET = ['year' => '1999'];
$controller->finance();
check((float) $controller->result['totalTarget'] === 0.0 && $controller->result['byMonth'] === [], 'Empty results');
check(str_contains(renderFinance($controller->result), 'Sin datos'), 'Empty view');
echo "PASS: financial totals, filters, ranking, chart payloads and rendered view (synthetic SQLite).\n";
