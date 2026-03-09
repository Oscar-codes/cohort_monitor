<?php
/**
 * Cohorts Index View
 *
 * Filters are URL-driven (GET), so state persists naturally with browser
 * navigation and when users open/edit cohorts then return.
 */

/** Helper: format date safely */
function formatDateLabel(?string $date): string
{
    if (!$date) {
        return '—';
    }

    return date('d M Y', strtotime($date));
}

/** Helper: derive lifecycle status based on dates */
function cohortLifecycleStatus(array $cohort): string
{
    $today = date('Y-m-d');
    $startDate = $cohort['start_date'] ?? null;
    $endDate = $cohort['end_date'] ?? null;

    if ($startDate && $startDate > $today) {
        return 'upcoming';
    }

    if ($startDate && $startDate <= $today && (!$endDate || $endDate >= $today)) {
        return 'in_progress';
    }

    if ($endDate && $endDate < $today) {
        return 'completed';
    }

    return 'upcoming';
}

/** Helper: render lifecycle badge */
function lifecycleBadge(array $cohort): string
{
    $status = cohortLifecycleStatus($cohort);

    $map = [
        'upcoming' => ['bg-secondary-subtle text-secondary', 'Upcoming'],
        'in_progress' => ['bg-primary-subtle text-primary', 'In progress'],
        'completed' => ['bg-success-subtle text-success', 'Completed'],
    ];

    [$class, $label] = $map[$status] ?? ['bg-light text-dark', ucfirst($status)];

    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/** Helper: detect business model */
function businessModelBadge(array $cohort): string
{
    $hasB2B = ((int) ($cohort['b2b_admission_target'] ?? 0) > 0) || ((int) ($cohort['b2b_admissions'] ?? 0) > 0);
    $hasB2C = (int) ($cohort['b2c_admissions'] ?? 0) > 0;

    if ($hasB2B && $hasB2C) {
        return '<span class="badge bg-warning-subtle text-warning">B2B + B2C</span>';
    }

    if ($hasB2B) {
        return '<span class="badge bg-info-subtle text-info">B2B</span>';
    }

    if ($hasB2C) {
        return '<span class="badge bg-primary-subtle text-primary">B2C</span>';
    }

    return '<span class="text-muted">—</span>';
}

$filters = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$querySuffix = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '';

$upcomingCount = count(array_filter($cohorts, static fn(array $c): bool => cohortLifecycleStatus($c) === 'upcoming'));
$inProgressCount = count(array_filter($cohorts, static fn(array $c): bool => cohortLifecycleStatus($c) === 'in_progress'));
$completedCount = count(array_filter($cohorts, static fn(array $c): bool => cohortLifecycleStatus($c) === 'completed'));
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Gestiona cohortes con filtros combinables por tipo, fechas, modelo de negocio y estado.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($activeFilters)): ?>
            <a href="/cohorts" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Limpiar filtros
            </a>
        <?php endif; ?>

        <?php if ($canCreate ?? false): ?>
            <a href="/cohorts/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">Nueva Cohorte</span>
                <span class="d-sm-none">Nueva</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4" id="cohort-filters">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel me-1"></i>Filtros</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="/cohorts" class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <label for="bootcamp_type" class="form-label">Bootcamp Type</label>
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
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="business_model" class="form-label">Business Model</label>
                <select class="form-select" id="business_model" name="business_model">
                    <option value="">Todos</option>
                    <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                    <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label for="cohort_status" class="form-label">Cohort Status</label>
                <select class="form-select" id="cohort_status" name="cohort_status">
                    <option value="">Todos</option>
                    <option value="upcoming" <?= (($filters['cohort_status'] ?? '') === 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                    <option value="in_progress" <?= (($filters['cohort_status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In progress</option>
                    <option value="completed" <?= (($filters['cohort_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Aplicar filtros
                </button>
                <a href="/cohorts" class="btn btn-outline-secondary">Restablecer</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary"><?= count($cohorts) ?></div>
                <small class="text-muted">Resultados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-secondary"><?= $upcomingCount ?></div>
                <small class="text-muted">Upcoming</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-info"><?= $inProgressCount ?></div>
                <small class="text-muted">In progress</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success"><?= $completedCount ?></div>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($cohorts)): ?>
    <div class="card table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Listado de Cohortes</h6>
            <small class="text-muted">Orden por Start Date ascendente</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 56px;">ID</th>
                        <th>Cohorte</th>
                        <th class="d-none d-md-table-cell">Bootcamp</th>
                        <th class="d-none d-lg-table-cell">Fechas</th>
                        <th class="d-none d-lg-table-cell text-center">Business Model</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 130px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $cohort): ?>
                        <tr>
                            <td><span class="text-muted">#<?= (int) $cohort['id'] ?></span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="/cohorts/<?= (int) $cohort['id'] ?><?= $querySuffix ?>" class="text-decoration-none fw-semibold text-dark">
                                        <?= htmlspecialchars($cohort['name']) ?>
                                    </a>
                                    <div class="small text-muted mt-1">
                                        <?= htmlspecialchars($cohort['cohort_code'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php if (!empty($cohort['bootcamp_type'])): ?>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($cohort['bootcamp_type']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <div class="small">
                                    <div><i class="bi bi-calendar-event text-muted me-1"></i><?= htmlspecialchars(formatDateLabel($cohort['start_date'] ?? null)) ?></div>
                                    <div class="text-muted"><i class="bi bi-calendar-check me-1"></i><?= htmlspecialchars(formatDateLabel($cohort['end_date'] ?? null)) ?></div>
                                </div>
                            </td>
                            <td class="d-none d-lg-table-cell text-center">
                                <?= businessModelBadge($cohort) ?>
                            </td>
                            <td class="text-center">
                                <?= lifecycleBadge($cohort) ?>
                            </td>
                            <td class="text-end">
                                <div class="action-buttons justify-content-end">
                                    <a href="/cohorts/<?= (int) $cohort['id'] ?><?= $querySuffix ?>" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/cohorts/<?= (int) $cohort['id'] ?>/edit<?= $querySuffix ?>" class="btn btn-icon btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($canDelete ?? false): ?>
                                        <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>" class="d-inline" data-confirm="¿Estás seguro de que deseas eliminar esta cohorte?">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-funnel"></i>
                </div>
                <h5 class="empty-state-title">No hay resultados para los filtros aplicados</h5>
                <p class="empty-state-text">Ajusta o limpia los filtros para ver más cohortes.</p>
                <a href="/cohorts" class="btn btn-outline-secondary">Limpiar filtros</a>
            </div>
        </div>
    </div>
<?php endif; ?>
