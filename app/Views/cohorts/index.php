<!-- Cohorts Index View -->
<?php
/** Helper: render training-status badge */
function statusBadge(string $status): string {
    $map = [
        'not_started' => ['bg-secondary-subtle text-secondary', 'Sin iniciar'],
        'in_progress' => ['bg-primary-subtle text-primary',     'En progreso'],
        'completed'   => ['bg-success-subtle text-success',     'Completado'],
        'cancelled'   => ['bg-danger-subtle text-danger',       'Cancelado'],
    ];
    [$class, $label] = $map[$status] ?? ['bg-info-subtle text-info', ucfirst($status)];
    return '<span class="badge badge-status ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/** Helper: format date nicely */
function formatDate(?string $date): string {
    if (!$date) return '<span class="text-muted">—</span>';
    return date('d M Y', strtotime($date));
}
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Gestiona y monitorea todas las cohortes del sistema.</p>
    </div>
    <a href="/cohorts/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>
        <span class="d-none d-sm-inline">Nueva Cohorte</span>
        <span class="d-sm-none">Nueva</span>
    </a>
</div>

<?php if (!empty($cohorts)): ?>
<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary"><?= count($cohorts) ?></div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success"><?= count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'in_progress')) ?></div>
                <small class="text-muted">En Progreso</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-secondary"><?= count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'not_started')) ?></div>
                <small class="text-muted">Sin Iniciar</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-info"><?= count(array_filter($cohorts, fn($c) => ($c['training_status'] ?? '') === 'completed')) ?></div>
                <small class="text-muted">Completados</small>
            </div>
        </div>
    </div>
</div>

<!-- Cohorts Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Cohorte</th>
                    <th class="d-none d-md-table-cell">Tipo</th>
                    <th class="d-none d-lg-table-cell">Fechas</th>
                    <th class="d-none d-xl-table-cell text-center">Admisiones</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end" style="width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cohorts as $cohort): ?>
                    <tr>
                        <!-- ID -->
                        <td>
                            <span class="text-muted">#<?= htmlspecialchars($cohort['id']) ?></span>
                        </td>
                        
                        <!-- Cohort Info -->
                        <td>
                            <div class="d-flex flex-column">
                                <a href="/cohorts/<?= $cohort['id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                    <?= htmlspecialchars($cohort['name']) ?>
                                </a>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <code class="small text-muted"><?= htmlspecialchars($cohort['cohort_code'] ?? 'N/A') ?></code>
                                    <?php if (!empty($cohort['assigned_coach'])): ?>
                                        <span class="text-muted small d-none d-sm-inline">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($cohort['assigned_coach']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Bootcamp Type -->
                        <td class="d-none d-md-table-cell">
                            <?php if (!empty($cohort['bootcamp_type'])): ?>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($cohort['bootcamp_type']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Dates -->
                        <td class="d-none d-lg-table-cell">
                            <div class="small">
                                <div><i class="bi bi-calendar-event text-muted me-1"></i><?= formatDate($cohort['start_date'] ?? null) ?></div>
                                <div class="text-muted"><i class="bi bi-calendar-check me-1"></i><?= formatDate($cohort['end_date'] ?? null) ?></div>
                            </div>
                        </td>
                        
                        <!-- Admissions -->
                        <td class="d-none d-xl-table-cell text-center">
                            <div class="d-flex justify-content-center gap-3">
                                <div class="text-center">
                                    <div class="fw-semibold"><?= $cohort['total_admission_target'] ?? 0 ?></div>
                                    <small class="text-muted">Meta</small>
                                </div>
                                <div class="text-center">
                                    <div class="fw-semibold text-info"><?= $cohort['b2b_admission_target'] ?? 0 ?></div>
                                    <small class="text-muted">B2B</small>
                                </div>
                                <div class="text-center">
                                    <div class="fw-semibold text-primary"><?= $cohort['b2c_admissions'] ?? 0 ?></div>
                                    <small class="text-muted">B2C</small>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Status -->
                        <td class="text-center">
                            <?= statusBadge($cohort['training_status'] ?? 'not_started') ?>
                        </td>
                        
                        <!-- Actions -->
                        <td class="text-end">
                            <div class="action-buttons justify-content-end">
                                <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="/cohorts/<?= $cohort['id'] ?>/edit" class="btn btn-icon btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/cohorts/<?= $cohort['id'] ?>" class="d-inline" data-confirm="¿Estás seguro de que deseas eliminar esta cohorte?">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Empty State -->
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h5 class="empty-state-title">No hay cohortes aún</h5>
            <p class="empty-state-text">Comienza creando tu primera cohorte para gestionar estudiantes y programas.</p>
            <a href="/cohorts/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Crear Primera Cohorte
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
