<!-- Reports Index -->
<?php
use App\Core\Auth;

$reportData = isset($reportData) && is_array($reportData) ? $reportData : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$rawFilters = isset($rawFilters) && is_array($rawFilters) ? $rawFilters : [];
$areaLabels = isset($areaLabels) && is_array($areaLabels) ? $areaLabels : [];

$cohorts = $reportData['cohorts'] ?? [];
$byArea = $reportData['byArea'] ?? [];
$byStatus = $reportData['byStatus'] ?? [];

$areaIcons = [
    'academic' => 'bi-mortarboard',
    'marketing' => 'bi-megaphone',
    'admissions' => 'bi-person-plus',
];
$areaTone = [
    'academic' => 'primary',
    'marketing' => 'success',
    'admissions' => 'info',
];
$statusCards = [
    ['key' => 'completed', 'label' => 'Completado', 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['key' => 'in_progress', 'label' => 'En ejecucion', 'icon' => 'bi-play-circle', 'tone' => 'primary'],
    ['key' => 'not_started', 'label' => 'Pendiente', 'icon' => 'bi-hourglass-split', 'tone' => 'warning'],
    ['key' => 'cancelled', 'label' => 'Cancelado', 'icon' => 'bi-x-circle', 'tone' => 'danger'],
];
$statusBadge = [
    'completed' => 'bg-success-subtle text-success',
    'in_progress' => 'bg-primary-subtle text-primary',
    'not_started' => 'bg-warning-subtle text-warning',
    'cancelled' => 'bg-danger-subtle text-danger',
];

$totalCohorts = count($cohorts);
$totalRisk = 0;
foreach ($cohorts as $c) {
    if (!empty($c['at_risk'])) {
        $totalRisk++;
    }
}
$completedCount = (int) ($byStatus['completed'] ?? 0);
$completionPct = $totalCohorts > 0 ? min(100, (int) round(($completedCount / $totalCohorts) * 100)) : 0;
$activeFilterCount = count(array_filter([
    $filters['area'] ?? '',
    $filters['date_from'] ?? '',
    $filters['date_to'] ?? '',
]));

if (!function_exists('reportDate')) {
    function reportDate(?string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : 'Sin fecha';
    }
}
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<section class="reports-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-bar-chart-line"></i>
            Reportes ejecutivos
        </span>
        <h1>Analisis de cohortes</h1>
        <p>Filtra, revisa riesgos y exporta datos operativos para seguimiento ejecutivo.</p>
    </div>
    <div class="reports-hero__actions">
        <button type="button" class="btn btn-light btn-sm" id="btnExportExcel" title="Exportar a Excel">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-outline-light btn-sm" id="btnExportPdf" title="Descargar PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
        <button type="button" class="btn btn-outline-light btn-sm" id="btnPreviewPdf" title="Vista previa para imprimir">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>
</section>

<section class="app-panel reports-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-funnel"></i> Filtros</h2>
            <p class="app-panel__subtitle">Usa area y rango de fechas para construir el reporte exportable.</p>
        </div>
        <?php if ($activeFilterCount > 0): ?>
            <span class="reports-filter-count"><?= $activeFilterCount ?> activo<?= $activeFilterCount > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>
    <form method="GET" action="/reports" id="filterForm" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-3">
            <label for="area" class="form-label">Area</label>
            <select class="form-select" name="area" id="area">
                <option value="all" <?= empty($filters['area']) ? 'selected' : '' ?>>Todas las areas</option>
                <?php foreach ($areaLabels as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($filters['area'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="date_from" class="form-label">Desde</label>
            <input type="date" class="form-control" name="date_from" id="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? $rawFilters['date_from'] ?? '') ?>">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="date_to" class="form-label">Hasta</label>
            <input type="date" class="form-control" name="date_to" id="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? $rawFilters['date_to'] ?? '') ?>">
        </div>
        <div class="col-sm-6 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
                <i class="bi bi-search me-1"></i> Filtrar
            </button>
            <a href="/reports" class="btn btn-outline-secondary" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>
    </form>
</section>

<div class="reports-summary mb-4">
    <article class="reports-kpi">
        <span class="reports-kpi__icon is-primary"><i class="bi bi-collection"></i></span>
        <div>
            <p>Cohortes</p>
            <strong><?= $totalCohorts ?></strong>
            <small>Resultados filtrados</small>
        </div>
    </article>
    <article class="reports-kpi">
        <span class="reports-kpi__icon is-success"><i class="bi bi-check-circle"></i></span>
        <div>
            <p>Completadas</p>
            <strong><?= $completedCount ?></strong>
            <small><?= $completionPct ?>% del reporte</small>
        </div>
    </article>
    <article class="reports-kpi">
        <span class="reports-kpi__icon is-danger"><i class="bi bi-exclamation-triangle"></i></span>
        <div>
            <p>En riesgo</p>
            <strong><?= $totalRisk ?></strong>
            <small>Requieren revision</small>
        </div>
    </article>
    <article class="reports-kpi">
        <span class="reports-kpi__icon is-info"><i class="bi bi-download"></i></span>
        <div>
            <p>Exportacion</p>
            <strong>Excel/PDF</strong>
            <small>Con filtros actuales</small>
        </div>
    </article>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="app-panel reports-area-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-grid-3x3-gap"></i> Resumen por area</h2>
                    <p class="app-panel__subtitle">Distribucion de resultados por responsabilidad operativa.</p>
                </div>
            </div>
            <div class="reports-area-grid">
                <?php foreach ($areaLabels as $aKey => $aLabel): ?>
                    <?php $a = $byArea[$aKey] ?? ['total' => 0, 'at_risk' => 0, 'completed' => 0, 'in_progress' => 0]; ?>
                    <article class="reports-area-card">
                        <div class="reports-area-card__head">
                            <span class="reports-area-card__icon is-<?= htmlspecialchars($areaTone[$aKey] ?? 'secondary') ?>">
                                <i class="bi <?= htmlspecialchars($areaIcons[$aKey] ?? 'bi-grid') ?>"></i>
                            </span>
                            <div>
                                <h3><?= htmlspecialchars($aLabel) ?></h3>
                                <small><?= (int) $a['total'] ?> cohortes</small>
                            </div>
                        </div>
                        <div class="reports-area-card__metrics">
                            <div><strong><?= (int) $a['at_risk'] ?></strong><span>Riesgo</span></div>
                            <div><strong><?= (int) $a['completed'] ?></strong><span>Compl.</span></div>
                            <div><strong><?= (int) $a['in_progress'] ?></strong><span>Ejec.</span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="app-panel reports-status-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-activity"></i> Estado de entrenamiento</h2>
                    <p class="app-panel__subtitle">Conteo por estado actual de las cohortes filtradas.</p>
                </div>
            </div>
            <div class="reports-status-list">
                <?php foreach ($statusCards as $sc): ?>
                    <?php
                    $count = (int) ($byStatus[$sc['key']] ?? 0);
                    $pct = $totalCohorts > 0 ? min(100, (int) round(($count / $totalCohorts) * 100)) : 0;
                    ?>
                    <article class="reports-status-item">
                        <span class="reports-status-item__icon is-<?= htmlspecialchars($sc['tone']) ?>"><i class="bi <?= htmlspecialchars($sc['icon']) ?>"></i></span>
                        <div>
                            <div class="reports-status-item__top">
                                <strong><?= htmlspecialchars($sc['label']) ?></strong>
                                <span><?= $count ?></span>
                            </div>
                            <div class="dashboard-mini-progress"><span data-style-width="<?= $pct ?>%"></span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<section class="app-panel reports-table-panel">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-table"></i> Detalle de cohortes</h2>
            <p class="app-panel__subtitle"><?= $totalCohorts ?> resultado<?= $totalCohorts !== 1 ? 's' : '' ?> disponibles para exportacion.</p>
        </div>
    </div>

    <?php if (!empty($cohorts)): ?>
        <div class="table-responsive reports-table">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Codigo</th>
                        <th class="text-center d-none d-md-table-cell">Area</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center d-none d-md-table-cell">Inicio</th>
                        <th class="text-center d-none d-lg-table-cell">Fin</th>
                        <th class="text-center">Riesgo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $c): ?>
                        <tr class="<?= ($c['at_risk'] ?? 0) ? 'reports-row-risk' : '' ?>">
                            <td>
                                <a href="/cohorts/<?= (int) $c['id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($c['name']) ?>
                                </a>
                            </td>
                            <td class="text-center"><code class="small"><?= htmlspecialchars($c['cohort_code'] ?? 'N/A') ?></code></td>
                            <td class="text-center d-none d-md-table-cell">
                                <?php if (!empty($c['area'])): ?>
                                    <span class="badge bg-<?= htmlspecialchars($areaTone[$c['area']] ?? 'secondary') ?>-subtle text-<?= htmlspecialchars($areaTone[$c['area']] ?? 'secondary') ?>">
                                        <?= htmlspecialchars($areaLabels[$c['area']] ?? $c['area']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Sin area</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-status <?= $statusBadge[$c['training_status'] ?? ''] ?? 'bg-secondary-subtle text-secondary' ?>">
                                    <?= htmlspecialchars($statusLabels[$c['training_status'] ?? ''] ?? ($c['training_status'] ?? 'Sin estado')) ?>
                                </span>
                            </td>
                            <td class="text-center d-none d-md-table-cell"><?= htmlspecialchars(reportDate($c['start_date'] ?? null)) ?></td>
                            <td class="text-center d-none d-lg-table-cell"><?= htmlspecialchars(reportDate($c['end_date'] ?? null)) ?></td>
                            <td class="text-center">
                                <?php if ($c['at_risk'] ?? 0): ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Si</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="reports-mobile-list">
            <?php foreach ($cohorts as $c): ?>
                <article class="reports-mobile-card <?= ($c['at_risk'] ?? 0) ? 'is-risk' : '' ?>">
                    <div class="reports-mobile-card__top">
                        <div>
                            <a href="/cohorts/<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['cohort_code'] ?? 'N/A') ?></a>
                            <h3><?= htmlspecialchars($c['name']) ?></h3>
                        </div>
                        <span class="badge badge-status <?= $statusBadge[$c['training_status'] ?? ''] ?? 'bg-secondary-subtle text-secondary' ?>">
                            <?= htmlspecialchars($statusLabels[$c['training_status'] ?? ''] ?? ($c['training_status'] ?? 'Sin estado')) ?>
                        </span>
                    </div>
                    <div class="reports-mobile-card__meta">
                        <span><i class="bi bi-grid"></i><?= htmlspecialchars($areaLabels[$c['area'] ?? ''] ?? 'Sin area') ?></span>
                        <span><i class="bi bi-calendar-event"></i><?= htmlspecialchars(reportDate($c['start_date'] ?? null)) ?> - <?= htmlspecialchars(reportDate($c['end_date'] ?? null)) ?></span>
                        <span><i class="bi bi-exclamation-triangle"></i><?= ($c['at_risk'] ?? 0) ? 'En riesgo' : 'Sin riesgo' ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon">
                <i class="bi bi-bar-chart-line"></i>
            </div>
            <h5 class="empty-state-title">Sin resultados</h5>
            <p class="empty-state-text">No se encontraron cohortes con los filtros seleccionados.</p>
            <a href="/reports" class="btn btn-outline-primary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
            </a>
        </div>
    <?php endif; ?>
</section>


