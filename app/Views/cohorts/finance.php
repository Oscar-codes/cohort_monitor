<?php
use App\Core\Auth;

/** @var array<int, array<string, mixed>> $byMonth */
$byMonth = isset($byMonth) && is_array($byMonth) ? $byMonth : [];
/** @var array<int, array<string, mixed>> $byBootcamp */
$byBootcamp = isset($byBootcamp) && is_array($byBootcamp) ? $byBootcamp : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$activeFilters = isset($activeFilters) && is_array($activeFilters) ? $activeFilters : [];
$financeChartData = isset($financeChartData) && is_array($financeChartData) ? $financeChartData : [];
$chartPrefs = isset($chartPrefs) && is_array($chartPrefs) ? $chartPrefs : [];
$bootcampTypes = isset($bootcampTypes) && is_array($bootcampTypes) ? $bootcampTypes : [];
$projectNames = isset($projectNames) && is_array($projectNames) ? $projectNames : [];
$availableMonths = isset($availableMonths) && is_array($availableMonths) ? $availableMonths : [];
$availableYears = isset($availableYears) && is_array($availableYears) ? $availableYears : [];
$currentYear = (string) ($currentYear ?? date('Y'));
$selectedYear = (string) ($filters['year'] ?? $currentYear);
$monthLabels = [
    '01' => 'Ene', '02' => 'Feb', '03' => 'Mar',
    '04' => 'Abr', '05' => 'May', '06' => 'Jun',
    '07' => 'Jul', '08' => 'Ago', '09' => 'Sep',
    '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
];
$monthlyByKey = [];
foreach ($byMonth as $row) {
    $key = (string) ($row['period_key'] ?? '');
    if ($key !== '') {
        $monthlyByKey[$key] = $row;
    }
}
$trendMonths = [];
for ($m = 1; $m <= 12; $m++) {
    $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    $key = $selectedYear . '-' . $mm;
    $row = $monthlyByKey[$key] ?? null;
    $trendMonths[] = [
        'key'   => $key,
        'label' => ($monthLabels[$mm] ?? $mm) . ' ' . $selectedYear,
        'target' => (float) ($row['target_revenue'] ?? 0),
        'actual' => (float) ($row['actual_revenue'] ?? 0),
    ];
}

$selectedTopN = (int) ($chartPrefs['top_n'] ?? 10);
$selectedForecastHorizon = (int) ($chartPrefs['forecast_horizon'] ?? 3);
$selectedForecastMethod = (string) ($chartPrefs['forecast_method'] ?? 'moving_avg');

$totalTarget = max(0.0, (float) ($totalTarget ?? 0));
$totalActual = max(0.0, (float) ($totalActual ?? 0));
$totalGap = max(0.0, $totalTarget - $totalActual);
$totalPct = $totalTarget > 0 ? min(100, (int) round(($totalActual / $totalTarget) * 100)) : 0;

if (!function_exists('moneyFmt')) {
    function moneyFmt(float $value): string
    {
        return '$' . number_format($value, 2);
    }
}

$spanishMonths = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
    '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
    '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
    '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
];
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-cash-coin"></i>
            Finanzas ejecutivas
        </div>
        <h2 class="cohorts-hero__title">Finanzas Cohort Plan</h2>
        <p class="cohorts-hero__copy">Seguimiento de revenue por periodo y por cohorte para detectar brechas y priorizar acciones.</p>
    </div>
    <div class="cohorts-hero__actions">
        <a href="/cohorts/master<?= !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '' ?>" class="btn btn-outline-secondary">
            <i class="bi bi-grid-1x2 me-1"></i> Plan Maestro
        </a>
    </div>
</section>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="app-panel cohort-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-funnel text-primary"></i> Panel de filtros financieros</h3>
            <p class="app-panel__subtitle">Filtra por mes, bootcamp, meta de revenue (proyecto) o rango de metas para analizar el revenue.</p>
        </div>
        <?php if (!empty($activeFilters)): ?>
            <span class="badge bg-primary-subtle text-primary">
                <i class="bi bi-funnel-fill me-1"></i><?= count($activeFilters) ?> filtro(s) activo(s)
            </span>
        <?php endif; ?>
    </div>
    <form method="GET" action="/cohorts/finance" class="row g-3">
        <div class="col-12 col-lg-3">
            <label for="month" class="form-label"><i class="bi bi-calendar-month me-1"></i>Mes</label>
            <select class="form-select" id="month" name="month">
                <option value="">Todos los meses</option>
                <?php foreach (($availableMonths ?? []) as $monthKey):
                    $monthKey = (string) $monthKey;
                    if ($monthKey === '' || !preg_match('/^\d{4}-\d{2}$/', $monthKey)) continue;
                    $year = substr($monthKey, 0, 4);
                    $mn = substr($monthKey, 5, 2);
                    $label = ($spanishMonths[$mn] ?? $mn) . ' ' . $year;
                ?>
                    <option value="<?= htmlspecialchars($monthKey) ?>" <?= (($filters['month'] ?? '') === $monthKey) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="year" class="form-label"><i class="bi bi-calendar3 me-1"></i>Año</label>
            <select class="form-select" id="year" name="year">
                <?php
                $formYearOptions = $availableYears;
                if (!in_array($currentYear, $formYearOptions, true)) {
                    array_unshift($formYearOptions, $currentYear);
                }
                $formYearOptions = array_values(array_unique(array_filter($formYearOptions, static fn($y) => preg_match('/^\d{4}$/', (string) $y) === 1)));
                usort($formYearOptions, static fn($a, $b) => (int) $b <=> (int) $a);
                ?>
                <?php foreach ($formYearOptions as $yearOpt): ?>
                    <option value="<?= htmlspecialchars((string) $yearOpt) ?>" <?= ((string) $yearOpt === ($filters['year'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars((string) $yearOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label for="bootcamp_type" class="form-label"><i class="bi bi-mortarboard me-1"></i>Bootcamp</label>
            <select class="form-select" id="bootcamp_type" name="bootcamp_type">
                <option value="">Todos</option>
                <?php foreach (($bootcampTypes ?? []) as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label for="related_project" class="form-label"><i class="bi bi-briefcase me-1"></i>Tipo de revenue (Proyecto)</label>
            <select class="form-select" id="related_project" name="related_project">
                <option value="">Todos</option>
                <?php foreach (($projectNames ?? []) as $project): ?>
                    <option value="<?= htmlspecialchars($project) ?>" <?= (($filters['related_project'] ?? '') === $project) ? 'selected' : '' ?>><?= htmlspecialchars($project) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-3">
            <label for="target_min" class="form-label"><i class="bi bi-arrow-down-circle me-1"></i>Meta minima</label>
            <input type="number" min="0" step="1" class="form-control" id="target_min" name="target_min" value="<?= htmlspecialchars((string) ($filters['target_min'] ?? '')) ?>" placeholder="Ej. 10">
        </div>
        <div class="col-6 col-md-3 col-lg-3">
            <label for="target_max" class="form-label"><i class="bi bi-arrow-up-circle me-1"></i>Meta maxima</label>
            <input type="number" min="0" step="1" class="form-control" id="target_max" name="target_max" value="<?= htmlspecialchars((string) ($filters['target_max'] ?? '')) ?>" placeholder="Ej. 50">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="start_date" class="form-label">Desde</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($filters['start_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="end_date" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($filters['end_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="business_model" class="form-label">Poblacion</label>
            <select class="form-select" id="business_model" name="business_model">
                <option value="">Todos</option>
                <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="cohort_status" class="form-label">Estado</label>
            <select class="form-select" id="cohort_status" name="cohort_status">
                <option value="">Todos</option>
                <option value="not_started" <?= (($filters['cohort_status'] ?? '') === 'not_started') ? 'selected' : '' ?>>No iniciado</option>
                <option value="in_progress" <?= (($filters['cohort_status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>En progreso</option>
                <option value="completed" <?= (($filters['cohort_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completado</option>
                <option value="cancelled" <?= (($filters['cohort_status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>
        <div class="col-12 col-lg-4">
            <label for="search" class="form-label">Buscar</label>
            <input type="search" class="form-control" id="search" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>" placeholder="Codigo, cohorte, coach, proyecto...">
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Aplicar filtros
            </button>
            <?php if (!empty($activeFilters)): ?>
                <a href="/cohorts/finance?reset_filters=1" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </a>
            <?php endif; ?>
            <?php if (!empty($activeFilters)): ?>
                <div class="d-flex flex-wrap gap-1 ms-lg-2">
                    <?php foreach ($activeFilters as $key => $value):
                        if (!is_string($value) && !is_numeric($value)) continue;
                        $displayValue = (string) $value;
                        if ($key === 'month' && preg_match('/^\d{4}-\d{2}$/', $displayValue) === 1) {
                            $mn = substr($displayValue, 5, 2);
                            $yr = substr($displayValue, 0, 4);
                            $displayValue = ($spanishMonths[$mn] ?? $mn) . ' ' . $yr;
                        }
                        $filterLabels = [
                            'search'          => 'Busqueda',
                            'bootcamp_type'   => 'Bootcamp',
                            'related_project' => 'Proyecto',
                            'month'           => 'Mes',
                            'year'            => 'Año',
                            'target_min'      => 'Meta min',
                            'target_max'      => 'Meta max',
                            'start_date'      => 'Desde',
                            'end_date'        => 'Hasta',
                            'business_model'  => 'Poblacion',
                            'cohort_status'   => 'Estado',
                        ];
                        $label = $filterLabels[$key] ?? $key;
                    ?>
                        <span class="badge bg-light text-dark border">
                            <strong><?= htmlspecialchars($label) ?>:</strong>
                            <?= htmlspecialchars($displayValue) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--primary h-100">
            <span><i class="bi bi-bullseye"></i></span>
            <div>
                <strong><?= htmlspecialchars(moneyFmt($totalTarget)) ?></strong>
                <small>Meta revenue</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--success h-100">
            <span><i class="bi bi-currency-dollar"></i></span>
            <div>
                <strong><?= htmlspecialchars(moneyFmt($totalActual)) ?></strong>
                <small>Revenue actual</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--warning h-100">
            <span><i class="bi bi-percent"></i></span>
            <div>
                <strong><?= $totalPct ?>%</strong>
                <small>Cumplimiento global</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--danger h-100">
            <span><i class="bi bi-graph-down"></i></span>
            <div>
                <strong><?= htmlspecialchars(moneyFmt($totalGap)) ?></strong>
                <small>Brecha pendiente</small>
            </div>
        </article>
    </div>
</div>

<div class="alert alert-light border d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
    <div class="small">
        <strong class="d-block mb-1">Reglas de ingreso de revenue por fuente</strong>
        <span class="d-inline-block"><span class="badge bg-info-subtle text-info me-1">INCAF</span> Se ingresa el revenue cuando Academia haya emitido el reporte y el valor definitivo a cobrar.</span>
        <span class="d-inline-block ms-md-3"><span class="badge bg-success-subtle text-success me-1">Student Revenue / Other SF</span> Se ingresa al emitir la factura, con actualizaciones semanales.</span>
        <span class="d-block mt-1 text-muted"><i class="bi bi-calendar-event me-1"></i> Período: mes de facturación. Para INCAF se alinea con el compromiso presupuestario.</span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-graph-up-arrow"></i> Tendencia mensual</h3>
                    <p class="app-panel__subtitle">Comparativo visual de revenue meta vs actual por periodo del año calendario seleccionado.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" id="financeTrendPrevYear" class="btn btn-sm btn-outline-secondary" title="Año anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <label for="financeTrendYear" class="form-label mb-0 small text-muted">Año</label>
                    <select id="financeTrendYear" class="form-select form-select-sm" style="min-width: 110px;">
                        <?php
                        $yearOptions = $availableYears;
                        if (!in_array($currentYear, $yearOptions, true)) {
                            array_unshift($yearOptions, $currentYear);
                        }
                        if (!in_array($selectedYear, $yearOptions, true)) {
                            array_unshift($yearOptions, $selectedYear);
                        }
                        $yearOptions = array_values(array_unique(array_filter($yearOptions, static fn($y) => preg_match('/^\d{4}$/', (string) $y) === 1)));
                        usort($yearOptions, static fn($a, $b) => (int) $b <=> (int) $a);
                        foreach ($yearOptions as $yearOpt):
                        ?>
                            <option value="<?= htmlspecialchars((string) $yearOpt) ?>" <?= ((string) $yearOpt === $selectedYear) ? 'selected' : '' ?>><?= htmlspecialchars((string) $yearOpt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="financeTrendNextYear" class="btn btn-sm btn-outline-secondary" title="Año siguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <span class="badge bg-light text-dark border" id="financeTrendYearBadge">12 meses</span>
                    <label for="financeForecastMethod" class="form-label mb-0 small text-muted">Metodo</label>
                    <select id="financeForecastMethod" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="moving_avg" <?= $selectedForecastMethod === 'moving_avg' ? 'selected' : '' ?>>Media movil</option>
                        <option value="linear_trend" <?= $selectedForecastMethod === 'linear_trend' ? 'selected' : '' ?>>Tendencia lineal</option>
                    </select>
                    <label for="financeForecastHorizon" class="form-label mb-0 small text-muted">Proyeccion</label>
                    <select id="financeForecastHorizon" class="form-select form-select-sm" style="min-width: 110px;">
                        <option value="0" <?= $selectedForecastHorizon === 0 ? 'selected' : '' ?>>Sin proyeccion</option>
                        <option value="3" <?= $selectedForecastHorizon === 3 ? 'selected' : '' ?>>+3 periodos</option>
                        <option value="6" <?= $selectedForecastHorizon === 6 ? 'selected' : '' ?>>+6 periodos</option>
                    </select>
                </div>
            </div>
            <div id="financeMonthlyChart" style="min-height: 320px;"></div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-bar-chart-line"></i> Cumplimiento por cohorte</h3>
                    <p class="app-panel__subtitle">Top de revenue actual con referencia de meta.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="financeTopN" class="form-label mb-0 small text-muted">Top</label>
                    <select id="financeTopN" class="form-select form-select-sm" style="min-width: 90px;">
                        <option value="5" <?= $selectedTopN === 5 ? 'selected' : '' ?>>Top 5</option>
                        <option value="10" <?= $selectedTopN === 10 ? 'selected' : '' ?>>Top 10</option>
                        <option value="15" <?= $selectedTopN === 15 ? 'selected' : '' ?>>Top 15</option>
                    </select>
                </div>
            </div>
            <div id="financeBootcampChart" style="min-height: 320px;"></div>
        </section>
    </div>
</div>


<textarea id="cohort-finance-data" class="d-none"><?= htmlspecialchars(json_encode($financeChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></textarea>
<textarea id="cohort-finance-trend" class="d-none" data-year="<?= htmlspecialchars($selectedYear, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(json_encode([
    'year' => $selectedYear,
    'current_year' => $currentYear,
    'available_years' => array_values(array_filter($yearOptions, static fn($y) => preg_match('/^\d{4}$/', (string) $y) === 1)),
    'months' => $trendMonths,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></textarea>

<div class="row g-4">
    <div class="col-xl-6">
        <section class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-calendar3"></i> Revenue por mes</h3>
                    <p class="app-panel__subtitle">Comparativo de meta y real por periodo de inicio.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periodo</th>
                            <th class="text-end">Meta</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Cumplimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trendMonths)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin datos</td></tr>
                        <?php else: ?>
                            <?php foreach ($trendMonths as $monthRow):
                                $target = (float) ($monthRow['target'] ?? 0);
                                $actual = (float) ($monthRow['actual'] ?? 0);
                                $pct = $target > 0 ? min(100, (int) round(($actual / $target) * 100)) : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($monthRow['label'] ?? '—')) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(moneyFmt($target)) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(moneyFmt($actual)) ?></td>
                                    <td class="text-end"><?= $pct ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-layers"></i> Revenue por cohorte</h3>
                    <p class="app-panel__subtitle">Ranking financiero por cohorte.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bootcamp name</th>
                            <th class="text-end">Meta</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Brecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($byBootcamp)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin datos</td></tr>
                        <?php else: ?>
                            <?php foreach ($byBootcamp as $row): ?>
                                <?php
                                $target = max(0.0, (float) ($row['target_revenue'] ?? 0));
                                $actual = max(0.0, (float) ($row['actual_revenue'] ?? 0));
                                $gap = max(0.0, $target - $actual);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($row['bootcamp_name'] ?? '—')) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(moneyFmt($target)) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(moneyFmt($actual)) ?></td>
                                    <td class="text-end"><?= htmlspecialchars(moneyFmt($gap)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
