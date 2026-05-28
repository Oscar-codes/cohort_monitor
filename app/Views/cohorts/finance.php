<?php
/** @var array<int, array<string, mixed>> $byMonth */
$byMonth = isset($byMonth) && is_array($byMonth) ? $byMonth : [];
/** @var array<int, array<string, mixed>> $byBootcamp */
$byBootcamp = isset($byBootcamp) && is_array($byBootcamp) ? $byBootcamp : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$activeFilters = isset($activeFilters) && is_array($activeFilters) ? $activeFilters : [];
$financeChartData = isset($financeChartData) && is_array($financeChartData) ? $financeChartData : [];
$chartPrefs = isset($chartPrefs) && is_array($chartPrefs) ? $chartPrefs : [];

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
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-cash-coin"></i>
            Finanzas ejecutivas
        </div>
        <h2 class="cohorts-hero__title">Finanzas Cohort Plan</h2>
        <p class="cohorts-hero__copy">Seguimiento de revenue por periodo y por bootcamp para detectar brechas y priorizar acciones.</p>
    </div>
    <div class="cohorts-hero__actions">
        <a href="/cohorts/master<?= !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '' ?>" class="btn btn-outline-secondary">
            <i class="bi bi-grid-1x2 me-1"></i> Plan Maestro
        </a>
    </div>
</section>

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

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-graph-up-arrow"></i> Tendencia mensual</h3>
                    <p class="app-panel__subtitle">Comparativo visual de revenue meta vs actual por periodo.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
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
                    <h3 class="app-panel__title"><i class="bi bi-bar-chart-line"></i> Cumplimiento por bootcamp</h3>
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

<div class="app-panel cohort-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-funnel text-primary"></i> Filtros financieros</h3>
            <p class="app-panel__subtitle">Filtra por bootcamp, proyecto y fechas para análisis de revenue.</p>
        </div>
    </div>
    <form method="GET" action="/cohorts/finance" class="row g-3">
        <div class="col-12 col-md-4">
            <label for="bootcamp_type" class="form-label">Bootcamp</label>
            <select class="form-select" id="bootcamp_type" name="bootcamp_type">
                <option value="">Todos</option>
                <?php foreach (($bootcampTypes ?? []) as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label for="related_project" class="form-label">Proyecto</label>
            <select class="form-select" id="related_project" name="related_project">
                <option value="">Todos</option>
                <?php foreach (($projectNames ?? []) as $project): ?>
                    <option value="<?= htmlspecialchars($project) ?>" <?= (($filters['related_project'] ?? '') === $project) ? 'selected' : '' ?>><?= htmlspecialchars($project) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="start_date" class="form-label">Desde</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($filters['start_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label for="end_date" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($filters['end_date'] ?? '')) ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Aplicar filtros
            </button>
            <?php if (!empty($activeFilters)): ?>
                <a href="/cohorts/finance" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<textarea id="cohort-finance-data" class="d-none"><?= htmlspecialchars(json_encode($financeChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></textarea>

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
                        <?php if (empty($byMonth)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin datos</td></tr>
                        <?php else: ?>
                            <?php foreach ($byMonth as $row): ?>
                                <?php
                                $target = max(0.0, (float) ($row['target_revenue'] ?? 0));
                                $actual = max(0.0, (float) ($row['actual_revenue'] ?? 0));
                                $pct = $target > 0 ? min(100, (int) round(($actual / $target) * 100)) : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($row['period_label'] ?? '—')) ?></td>
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
                    <h3 class="app-panel__title"><i class="bi bi-layers"></i> Revenue por bootcamp</h3>
                    <p class="app-panel__subtitle">Ranking financiero por tipo de bootcamp.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bootcamp</th>
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
