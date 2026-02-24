<!-- Cohort Show View -->
<?php
$statusMap = [
    'not_started' => ['bg-secondary-subtle text-secondary', 'Sin iniciar'],
    'in_progress' => ['bg-primary-subtle text-primary',     'En progreso'],
    'completed'   => ['bg-success-subtle text-success',     'Completado'],
    'cancelled'   => ['bg-danger-subtle text-danger',       'Cancelado'],
];
$ts = $cohort['training_status'] ?? 'not_started';
[$badgeClass, $badgeLabel] = $statusMap[$ts] ?? ['bg-info-subtle text-info', ucfirst($ts)];

function showFormatDate(?string $date): string {
    if (!$date) return '<span class="text-muted">—</span>';
    return date('d M Y', strtotime($date));
}
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/cohorts" class="text-decoration-none">Cohortes</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cohort['name']) ?></li>
    </ol>
</nav>

<!-- Header Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h4 class="mb-0"><?= htmlspecialchars($cohort['name']) ?></h4>
                    <span class="badge badge-status <?= $badgeClass ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                </div>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="bi bi-hash"></i> <?= htmlspecialchars($cohort['cohort_code'] ?? 'N/A') ?></span>
                    <?php if (!empty($cohort['bootcamp_type'])): ?>
                        <span><i class="bi bi-mortarboard"></i> <?= htmlspecialchars($cohort['bootcamp_type']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cohort['assigned_coach'])): ?>
                        <span><i class="bi bi-person"></i> <?= htmlspecialchars($cohort['assigned_coach']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ($canEdit ?? false): ?>
                <a href="/cohorts/<?= $cohort['id'] ?>/edit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
                <?php endif; ?>
                <?php if ($canDelete ?? false): ?>
                <form method="POST" action="/cohorts/<?= $cohort['id'] ?>" class="d-inline" data-confirm="¿Estás seguro de que deseas eliminar esta cohorte?">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ─── Main content ──────────────────────────────── -->
    <div class="col-lg-8">
        <!-- Dates Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-calendar-event text-primary me-2"></i>Fechas de Entrenamiento</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Inicio</div>
                        <div class="fw-semibold"><?= showFormatDate($cohort['start_date'] ?? null) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">Fin</div>
                        <div class="fw-semibold"><?= showFormatDate($cohort['end_date'] ?? null) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">50% Entrenamiento</div>
                        <div class="fw-semibold text-info"><?= showFormatDate($cohort['training_date_50'] ?? null) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small mb-1">75% Entrenamiento</div>
                        <div class="fw-semibold text-info"><?= showFormatDate($cohort['training_date_75'] ?? null) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-briefcase text-primary me-2"></i>Asignaciones</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Proyecto Relacionado</div>
                        <div class="fw-semibold"><?= htmlspecialchars($cohort['related_project'] ?? '—') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Coach Asignado</div>
                        <div class="fw-semibold"><?= htmlspecialchars($cohort['assigned_coach'] ?? '—') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Tipo de Bootcamp</div>
                        <div class="fw-semibold"><?= htmlspecialchars($cohort['bootcamp_type'] ?? '—') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Área</div>
                        <div class="fw-semibold">
                            <?php
                            $areaMap = ['academic' => 'Academic', 'marketing' => 'Marketing', 'admissions' => 'Admissions'];
                            echo htmlspecialchars($areaMap[$cohort['area'] ?? ''] ?? '—');
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Horario Asignado</div>
                        <div class="fw-semibold"><?= htmlspecialchars($cohort['assigned_class_schedule'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Sidebar ───────────────────────────────────── -->
    <div class="col-lg-4">
        <!-- Admissions Card -->
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-people text-primary me-2"></i>Admisiones</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-primary"><?= htmlspecialchars($cohort['total_admission_target'] ?? 0) ?></div>
                        <small class="text-muted">Meta Total</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-info"><?= htmlspecialchars($cohort['b2b_admission_target'] ?? 0) ?></div>
                        <small class="text-muted">Meta B2B</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-warning"><?= htmlspecialchars($cohort['b2b_admissions'] ?? 0) ?></div>
                        <small class="text-muted">B2B Actual</small>
                    </div>
                    <div class="col-6">
                        <div class="fs-4 fw-bold text-success"><?= htmlspecialchars($cohort['b2c_admissions'] ?? 0) ?></div>
                        <small class="text-muted">B2C Actual</small>
                    </div>
                    <div class="col-6">
                        <div class="small fw-semibold"><?= showFormatDate($cohort['admission_deadline_date'] ?? null) ?></div>
                        <small class="text-muted">Límite</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metadata Card -->
        <div class="card">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Información</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted small">ID</span>
                        <span class="fw-semibold">#<?= htmlspecialchars($cohort['id']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Correlativo</span>
                        <span class="fw-semibold"><?= htmlspecialchars($cohort['correlative_number'] ?? 0) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Creado</span>
                        <span class="small"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['created_at'] ?? 'now'))) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Actualizado</span>
                        <span class="small"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['updated_at'] ?? 'now'))) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ─── Comments / Risks Section ─────────────────────────── -->
<?php use App\Core\Auth; ?>
<div class="card mt-4">
    <div class="card-header bg-transparent d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="mb-0"><i class="bi bi-chat-left-text text-primary me-2"></i>Comentarios y Riesgos</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#commentForm">
            <i class="bi bi-plus me-1"></i> Nuevo Comentario
        </button>
    </div>

    <!-- New comment form (collapsed) -->
    <div class="collapse" id="commentForm">
        <div class="card-body border-bottom bg-body-secondary">
            <form method="POST" action="/cohorts/<?= $cohort['id'] ?>/comments">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label">Categoría</label>
                        <select name="category" class="form-select" required>
                            <option value="general">General</option>
                            <option value="risk">⚠ Riesgo</option>
                            <option value="admission">Admisión</option>
                            <option value="marketing">Marketing</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-7">
                        <label class="form-label">Comentario</label>
                        <textarea name="body" class="form-control" rows="2" required
                                  placeholder="Escribe tu comentario aquí…"></textarea>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> <span class="d-none d-sm-inline">Enviar</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($comments ?? [])): ?>
            <div class="empty-state py-5">
                <div class="empty-state-icon" style="font-size: 2rem;">
                    <i class="bi bi-chat"></i>
                </div>
                <p class="empty-state-text mb-0">No hay comentarios aún.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Categoría</th>
                            <th>Comentario</th>
                            <th class="d-none d-md-table-cell" style="width: 150px;">Autor</th>
                            <th style="width: 100px;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $catBadges = [
                                'risk'      => 'bg-danger-subtle text-danger',
                                'general'   => 'bg-secondary-subtle text-secondary',
                                'admission' => 'bg-info-subtle text-info',
                                'marketing' => 'bg-warning-subtle text-warning',
                            ];
                            $catLabels = [
                                'risk'      => 'Riesgo',
                                'general'   => 'General',
                                'admission' => 'Admisión',
                                'marketing' => 'Marketing',
                            ];
                        ?>
                        <?php foreach ($comments as $c): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-status <?= $catBadges[$c['category']] ?? 'bg-secondary-subtle text-secondary' ?>">
                                        <?= htmlspecialchars($catLabels[$c['category']] ?? $c['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= nl2br(htmlspecialchars($c['body'])) ?></div>
                                    <small class="text-muted d-md-none"><?= htmlspecialchars($c['author_name'] ?? 'Sistema') ?></small>
                                </td>
                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($c['author_name'] ?? 'Sistema') ?></td>
                                <td><small class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
