<?php
/** @var array<int, array<string, mixed>> $cohorts */
$cohorts = isset($cohorts) && is_array($cohorts) ? $cohorts : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$activeFilters = isset($activeFilters) && is_array($activeFilters) ? $activeFilters : [];

$totalRows = (int) ($totalRows ?? 0);
$totalAdmissionsTarget = max(0, (int) ($totalAdmissionsTarget ?? 0));
$totalAdmissionsActual = max(0, (int) ($totalAdmissionsActual ?? 0));
$totalRevenueTarget = max(0.0, (float) ($totalRevenueTarget ?? 0));
$totalRevenueActual = max(0.0, (float) ($totalRevenueActual ?? 0));
$atRiskAdmissionsCount = max(0, (int) ($atRiskAdmissionsCount ?? 0));

$admissionsPct = $totalAdmissionsTarget > 0 ? min(100, (int) round(($totalAdmissionsActual / $totalAdmissionsTarget) * 100)) : 0;
$revenuePct = $totalRevenueTarget > 0 ? min(100, (int) round(($totalRevenueActual / $totalRevenueTarget) * 100)) : 0;

if (!function_exists('masterDate')) {
    function masterDate(?string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '—';
    }
}

if (!function_exists('masterCurrency')) {
    function masterCurrency(float $value): string
    {
        return '$' . number_format($value, 2);
    }
}
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-grid-1x2"></i>
            Vision integral
        </div>
        <h2 class="cohorts-hero__title">Plan Maestro Cohort</h2>
        <p class="cohorts-hero__copy">Tablero unificado para monitorear admisiones, finanzas, calendario y responsables por cohorte.</p>
    </div>
    <div class="cohorts-hero__actions">
        <?php $querySuffix = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : ''; ?>
        <a href="/cohorts/master/export/csv<?= htmlspecialchars($querySuffix) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i> CSV
        </a>
        <a href="/cohorts/master/export/xlsx<?= htmlspecialchars($querySuffix) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> XLSX
        </a>
        <a href="/cohorts/finance<?= htmlspecialchars($querySuffix) ?>" class="btn btn-light">
            <i class="bi bi-cash-coin me-1"></i> Ver finanzas
        </a>
        <a href="/cohorts" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i> Vista Cohortes
        </a>
        <?php if (!empty($activeFilters)): ?>
            <a href="/cohorts/master" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i> Limpiar filtros
            </a>
        <?php endif; ?>
        <?php if ($canCreate ?? false): ?>
            <a href="/cohorts/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva cohorte
            </a>
        <?php endif; ?>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--primary h-100">
            <span><i class="bi bi-table"></i></span>
            <div>
                <strong><?= $totalRows ?></strong>
                <small>Cohortes filtradas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--info h-100">
            <span><i class="bi bi-people"></i></span>
            <div>
                <strong><?= $totalAdmissionsActual ?> / <?= $totalAdmissionsTarget ?></strong>
                <small>Admisiones (<?= $admissionsPct ?>%)</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--success h-100">
            <span><i class="bi bi-cash-stack"></i></span>
            <div>
                <strong><?= htmlspecialchars(masterCurrency($totalRevenueActual)) ?></strong>
                <small>Revenue (<?= $revenuePct ?>%)</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--warning h-100">
            <span><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <strong><?= $atRiskAdmissionsCount ?></strong>
                <small>Cohortes con riesgo de admision</small>
            </div>
        </article>
    </div>
</div>

<div class="app-panel cohort-filter-panel mb-4" id="master-filters">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-funnel text-primary"></i> Filtros del plan maestro</h3>
            <p class="app-panel__subtitle">Filtra por busqueda, bootcamp, proyecto, fechas, modelo y estado.</p>
        </div>
    </div>
    <form method="GET" action="/cohorts/master" class="row g-3">
        <div class="col-12 col-xl-4">
            <label for="search" class="form-label">Busqueda</label>
            <input type="search" class="form-control" id="search" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>" placeholder="Codigo, cohorte, coach, proyecto...">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <label for="bootcamp_type" class="form-label">Bootcamp</label>
            <select class="form-select" id="bootcamp_type" name="bootcamp_type">
                <option value="">Todos</option>
                <?php foreach (($bootcampTypes ?? []) as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <label for="related_project" class="form-label">Proyecto</label>
            <select class="form-select" id="related_project" name="related_project">
                <option value="">Todos</option>
                <?php foreach (($projectNames ?? []) as $project): ?>
                    <option value="<?= htmlspecialchars($project) ?>" <?= (($filters['related_project'] ?? '') === $project) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($project) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-xl-2">
            <label for="start_date" class="form-label">Desde</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($filters['start_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-xl-2">
            <label for="end_date" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($filters['end_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-xl-2">
            <label for="business_model" class="form-label">Modelo</label>
            <select class="form-select" id="business_model" name="business_model">
                <option value="">Todos</option>
                <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
            </select>
        </div>
        <div class="col-6 col-xl-2">
            <label for="cohort_status" class="form-label">Estado</label>
            <select class="form-select" id="cohort_status" name="cohort_status">
                <option value="">Todos</option>
                <option value="upcoming" <?= (($filters['cohort_status'] ?? '') === 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                <option value="in_progress" <?= (($filters['cohort_status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In progress</option>
                <option value="completed" <?= (($filters['cohort_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Aplicar filtros
            </button>
        </div>
    </form>
</div>

<section class="app-panel">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-table text-primary"></i> Matriz Cohort Plan</h3>
            <p class="app-panel__subtitle">Incluye codigo, admisiones, revenue, dias de clase, horario, coach y progreso.</p>
        </div>
    </div>

    <?php if (empty($cohorts)): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-funnel"></i></div>
            <h5 class="empty-state-title">Sin resultados</h5>
            <p class="empty-state-text">No hay cohortes con los filtros actuales.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Codigo</th>
                        <th>Bootcamp</th>
                        <th>Proyecto</th>
                        <th>Coach</th>
                        <th>Dias</th>
                        <th>Horario</th>
                        <th>Admisiones</th>
                        <th>Revenue</th>
                        <th>Semaforo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $cohort): ?>
                        <?php
                        $b2bTarget = max(0, (int) ($cohort['b2b_admission_target'] ?? 0));
                        $b2cTarget = max(0, (int) ($cohort['b2c_admission_target'] ?? 0));
                        $totalTarget = max(0, (int) ($cohort['total_admission_target'] ?? 0));
                        $b2bActual = max(0, (int) ($cohort['b2b_admissions'] ?? 0));
                        $b2cActual = max(0, (int) ($cohort['b2c_admissions'] ?? 0));
                        $totalActual = $b2bActual + $b2cActual;
                        $admissionProgress = $totalTarget > 0 ? min(100, (int) round(($totalActual / $totalTarget) * 100)) : 0;

                        $targetRevenue = max(0.0, (float) ($cohort['financial_target_revenue'] ?? 0));
                        $actualRevenue = max(0.0, (float) ($cohort['financial_actual_revenue'] ?? 0));
                        $revenueProgress = $targetRevenue > 0 ? min(100, (int) round(($actualRevenue / $targetRevenue) * 100)) : 0;

                        $semaphore = 'Alto riesgo';
                        $semaphoreClass = 'bg-danger-subtle text-danger';
                        if ($admissionProgress >= 90 && $revenueProgress >= 90) {
                            $semaphore = 'Saludable';
                            $semaphoreClass = 'bg-success-subtle text-success';
                        } elseif ($admissionProgress >= 70 || $revenueProgress >= 70) {
                            $semaphore = 'Atencion';
                            $semaphoreClass = 'bg-warning-subtle text-warning';
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="/cohorts/<?= (int) $cohort['id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars((string) ($cohort['cohort_code'] ?? 'N/A')) ?>
                                </a>
                                <div class="small text-muted"><?= htmlspecialchars((string) ($cohort['name'] ?? '—')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($cohort['bootcamp_type'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($cohort['related_project'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($cohort['assigned_coach'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($cohort['class_days'] ?? '—')) ?></td>
                            <td>
                                <div><?= htmlspecialchars((string) ($cohort['class_time'] ?? '—')) ?></div>
                                <small class="text-muted"><?= htmlspecialchars((string) ($cohort['assigned_class_schedule'] ?? '—')) ?></small>
                            </td>
                            <td style="min-width: 220px;">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= $totalActual ?> / <?= $totalTarget ?></span>
                                    <span><?= $admissionProgress ?>%</span>
                                </div>
                                <div class="dashboard-mini-progress mb-1"><span data-style-width="<?= $admissionProgress ?>%"></span></div>
                                <small class="text-muted">B2B <?= $b2bActual ?>/<?= $b2bTarget ?> | B2C <?= $b2cActual ?>/<?= $b2cTarget ?></small>
                            </td>
                            <td style="min-width: 220px;">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= htmlspecialchars(masterCurrency($actualRevenue)) ?> / <?= htmlspecialchars(masterCurrency($targetRevenue)) ?></span>
                                    <span><?= $revenueProgress ?>%</span>
                                </div>
                                <div class="dashboard-mini-progress"><span data-style-width="<?= $revenueProgress ?>%"></span></div>
                            </td>
                            <td>
                                <span class="badge <?= $semaphoreClass ?>"><?= htmlspecialchars($semaphore) ?></span>
                                <div class="small text-muted mt-1">Adm <?= $admissionProgress ?>% | Rev <?= $revenueProgress ?>%</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($cohort['training_status'] ?? '—')) ?></span>
                                <div class="small text-muted mt-1"><?= htmlspecialchars(masterDate($cohort['start_date'] ?? null)) ?> - <?= htmlspecialchars(masterDate($cohort['end_date'] ?? null)) ?></div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="/cohorts/<?= (int) $cohort['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($canEdit ?? false): ?>
                                        <a href="/cohorts/<?= (int) $cohort['id'] ?>/edit" class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
