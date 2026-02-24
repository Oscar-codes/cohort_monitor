<!-- Reports Index — Filters + Metrics + Table -->
<?php use App\Core\Auth; ?>

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

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line text-primary me-2"></i>Reportes</h4>
        <p class="text-muted mb-0">Genera reportes filtrables y exportables de las cohortes.</p>
    </div>
    <!-- Export buttons: stack full-width on small screens -->
    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto" style="max-width: 320px;">
        <button type="button" class="btn btn-success btn-sm w-100 w-sm-auto" id="btnExportExcel" title="Exportar a Excel">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
        </button>
        <div class="btn-group w-100 w-sm-auto">
            <button type="button" class="btn btn-danger btn-sm" id="btnExportPdf" title="Descargar PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnPreviewPdf" title="Vista previa para imprimir">
                <i class="bi bi-printer me-1"></i> Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/reports" id="filterForm">
            <div class="row g-3 align-items-end">
                <!-- Area Filter -->
                <div class="col-sm-6 col-md-3">
                    <label for="area" class="form-label fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Área
                    </label>
                    <select class="form-select" name="area" id="area">
                        <option value="all" <?= empty($filters['area']) ? 'selected' : '' ?>>Todas las áreas</option>
                        <?php foreach ($areaLabels as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($filters['area'] ?? '') === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-sm-6 col-md-3">
                    <label for="date_from" class="form-label fw-semibold">
                        <i class="bi bi-calendar-event me-1"></i> Desde
                    </label>
                    <input type="date" class="form-control" name="date_from" id="date_from"
                           value="<?= htmlspecialchars($filters['date_from'] ?? $rawFilters['date_from'] ?? '') ?>">
                </div>

                <!-- Date To -->
                <div class="col-sm-6 col-md-3">
                    <label for="date_to" class="form-label fw-semibold">
                        <i class="bi bi-calendar-event me-1"></i> Hasta
                    </label>
                    <input type="date" class="form-control" name="date_to" id="date_to"
                           value="<?= htmlspecialchars($filters['date_to'] ?? $rawFilters['date_to'] ?? '') ?>">
                </div>

                <!-- Buttons -->
                <div class="col-sm-6 col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <a href="/reports" class="btn btn-outline-secondary" title="Limpiar filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
    $cohorts  = $reportData['cohorts']  ?? [];
    $byArea   = $reportData['byArea']   ?? [];
    $byStatus = $reportData['byStatus'] ?? [];
?>

<!-- Area Summary Cards -->
<div class="row g-3 mb-4">
    <?php
    $areaIcons  = ['academic' => 'bi-mortarboard', 'marketing' => 'bi-megaphone', 'admissions' => 'bi-person-plus'];
    $areaColors = ['academic' => 'primary', 'marketing' => 'success', 'admissions' => 'info'];
    ?>
    <?php foreach ($areaLabels as $aKey => $aLabel): ?>
        <?php $a = $byArea[$aKey] ?? ['total' => 0, 'at_risk' => 0, 'completed' => 0, 'in_progress' => 0]; ?>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="card-icon bg-<?= $areaColors[$aKey] ?>-subtle text-<?= $areaColors[$aKey] ?>">
                            <i class="bi <?= $areaIcons[$aKey] ?>"></i>
                        </div>
                        <h6 class="fw-semibold mb-0"><?= htmlspecialchars($aLabel) ?></h6>
                    </div>
                    <div class="row text-center g-2">
                        <div class="col-3">
                            <div class="fs-5 fw-bold text-<?= $areaColors[$aKey] ?>"><?= (int) $a['total'] ?></div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-3">
                            <div class="fs-5 fw-bold text-danger"><?= (int) $a['at_risk'] ?></div>
                            <small class="text-muted">Riesgo</small>
                        </div>
                        <div class="col-3">
                            <div class="fs-5 fw-bold text-success"><?= (int) $a['completed'] ?></div>
                            <small class="text-muted">Compl.</small>
                        </div>
                        <div class="col-3">
                            <div class="fs-5 fw-bold text-warning"><?= (int) $a['in_progress'] ?></div>
                            <small class="text-muted">Ejecuc.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Status Summary Cards -->
<div class="row g-3 mb-4">
    <?php
    $statusCards = [
        ['key' => 'completed',   'label' => 'Completado',  'icon' => 'bi-check-circle',   'color' => 'success'],
        ['key' => 'in_progress', 'label' => 'En Ejecución','icon' => 'bi-play-circle',     'color' => 'primary'],
        ['key' => 'not_started', 'label' => 'Pendiente',   'icon' => 'bi-hourglass-split', 'color' => 'warning'],
        ['key' => 'cancelled',   'label' => 'Cancelado',   'icon' => 'bi-x-circle',        'color' => 'danger'],
    ];
    ?>
    <?php foreach ($statusCards as $sc): ?>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <i class="bi <?= $sc['icon'] ?> text-<?= $sc['color'] ?> fs-4 mb-1"></i>
                    <div class="fs-4 fw-bold text-<?= $sc['color'] ?>"><?= (int) ($byStatus[$sc['key']] ?? 0) ?></div>
                    <small class="text-muted"><?= $sc['label'] ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Cohorts Table -->
<div class="card table-card">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-table me-1"></i> Detalle de Cohortes
            <span class="badge bg-primary-subtle text-primary ms-2"><?= count($cohorts) ?></span>
        </h6>
    </div>
    <?php if (!empty($cohorts)): ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th class="text-center">Código</th>
                    <th class="text-center d-none d-md-table-cell">Área</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center d-none d-md-table-cell">Fecha Inicio</th>
                    <th class="text-center d-none d-lg-table-cell">Fecha Fin</th>
                    <th class="text-center">Riesgo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $statusBadge = [
                    'completed'   => 'bg-success-subtle text-success',
                    'in_progress' => 'bg-primary-subtle text-primary',
                    'not_started' => 'bg-warning-subtle text-warning',
                    'cancelled'   => 'bg-danger-subtle text-danger',
                ];
                ?>
                <?php foreach ($cohorts as $c): ?>
                    <tr class="<?= ($c['at_risk'] ?? 0) ? 'table-warning' : '' ?>">
                        <td>
                            <a href="/cohorts/<?= $c['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($c['name']) ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <code class="small"><?= htmlspecialchars($c['cohort_code'] ?? '—') ?></code>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <?php if (!empty($c['area'])): ?>
                                <span class="badge bg-<?= $areaColors[$c['area']] ?? 'secondary' ?>-subtle text-<?= $areaColors[$c['area']] ?? 'secondary' ?>">
                                    <?= htmlspecialchars($areaLabels[$c['area']] ?? $c['area']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-status <?= $statusBadge[$c['training_status'] ?? ''] ?? 'bg-secondary-subtle text-secondary' ?>">
                                <?= htmlspecialchars($statusLabels[$c['training_status'] ?? ''] ?? ($c['training_status'] ?? '—')) ?>
                            </span>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <?= $c['start_date'] ? date('d/m/Y', strtotime($c['start_date'])) : '—' ?>
                        </td>
                        <td class="text-center d-none d-lg-table-cell">
                            <?= $c['end_date'] ? date('d/m/Y', strtotime($c['end_date'])) : '—' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c['at_risk'] ?? 0): ?>
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Sí
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-bar-chart-line"></i>
            </div>
            <h5 class="empty-state-title">Sin resultados</h5>
            <p class="empty-state-text">No se encontraron cohortes con los filtros seleccionados.</p>
            <a href="/reports" class="btn btn-outline-primary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Export Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');

    // Build query string from current filters
    function getFilterParams() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData) {
            if (value && value !== 'all') {
                params.set(key, value);
            }
        }
        return params.toString();
    }

    // Date validation
    filterForm.addEventListener('submit', function(e) {
        const from = document.getElementById('date_from').value;
        const to   = document.getElementById('date_to').value;
        if (from && to && from > to) {
            e.preventDefault();
            alert('La fecha "Desde" no puede ser mayor que "Hasta".');
        }
    });

    // Excel export
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        const params = getFilterParams();
        window.location.href = '/reports/export/excel' + (params ? '?' + params : '');
    });

    // PDF download
    document.getElementById('btnExportPdf').addEventListener('click', function() {
        const params = getFilterParams();
        const sep = params ? '&' : '';
        window.location.href = '/reports/export/pdf?mode=download' + sep + params;
    });

    // PDF preview / print
    document.getElementById('btnPreviewPdf').addEventListener('click', function() {
        const params = getFilterParams();
        const sep = params ? '&' : '';
        window.open('/reports/export/pdf?mode=preview' + sep + params, '_blank');
    });
});
</script>

<style>
.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* Responsive export buttons */
@media (min-width: 576px) {
    .w-sm-auto { width: auto !important; max-width: none !important; }
}
</style>
