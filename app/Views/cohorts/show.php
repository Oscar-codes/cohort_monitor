<!-- Cohort Show View -->
<?php
use App\Core\Auth;

/** @var array<string, mixed> $cohort */
$cohort = isset($cohort) && is_array($cohort) ? $cohort : [];

/** @var array<int, array<string, mixed>> $comments */
$comments = isset($comments) && is_array($comments) ? $comments : [];

$workflowTransitions = isset($workflowTransitions) && is_array($workflowTransitions) ? $workflowTransitions : [];
$isAdmin = (bool) ($isAdmin ?? false);
$canManageStatus = (bool) ($canManageStatus ?? false);

$statusMap = [
    'planned' => ['bg-secondary-subtle text-secondary', 'Planificado', 'bi-hourglass-split'],
    'in_progress' => ['bg-primary-subtle text-primary', 'En progreso', 'bi-play-circle'],
    'completed' => ['bg-success-subtle text-success', 'Completado', 'bi-check-circle'],
    'cancelled' => ['bg-danger-subtle text-danger', 'Cancelado', 'bi-x-circle'],
    'pending_reschedule' => ['bg-warning-subtle text-warning', 'Pendiente de reprogramar', 'bi-calendar2-week'],
];

if (!function_exists('cohortDetailStatus')) {
    function cohortDetailStatus(array $cohort): string
    {
        $status = $cohort['training_status'] ?? 'planned';
        if (in_array($status, ['cancelled', 'pending_reschedule', 'completed'], true)) {
            return $status;
        }

        $today = date('Y-m-d');
        $startDate = $cohort['start_date'] ?? null;
        $endDate = $cohort['end_date'] ?? null;

        if ($endDate && $endDate < $today) {
            return 'completed';
        }
        if ($startDate && $startDate <= $today && (!$endDate || $endDate >= $today)) {
            return 'in_progress';
        }

        return 'planned';
    }
}

$ts = cohortDetailStatus($cohort);
[$badgeClass, $badgeLabel, $badgeIcon] = $statusMap[$ts] ?? ['bg-info-subtle text-info', ucfirst($ts), 'bi-info-circle'];

if (!function_exists('cohortDetailDate')) {
    function cohortDetailDate(?string $date): string
    {
        return $date ? date('d M Y', strtotime($date)) : 'Sin fecha';
    }
}

if (!function_exists('cohortDetailValue')) {
    function cohortDetailValue(?string $value): string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : 'Sin dato';
    }
}

$totalTarget = max(0, (int) ($cohort['total_admission_target'] ?? 0));
$b2bTarget = max(0, (int) ($cohort['b2b_admission_target'] ?? 0));
$b2cTarget = max(0, (int) ($cohort['b2c_admission_target'] ?? 0));
$b2bAdmissions = max(0, (int) ($cohort['b2b_admissions'] ?? 0));
$b2cAdmissions = max(0, (int) ($cohort['b2c_admissions'] ?? 0));
$actualAdmissions = $b2bAdmissions + $b2cAdmissions;
$admissionPct = $totalTarget > 0 ? min(100, (int) round(($actualAdmissions / $totalTarget) * 100)) : 0;
$targetRevenue = max(0.0, (float) ($cohort['financial_target_revenue'] ?? 0));
$actualRevenue = max(0.0, (float) ($cohort['financial_actual_revenue'] ?? 0));
$revenuePct = $targetRevenue > 0 ? min(100, (int) round(($actualRevenue / $targetRevenue) * 100)) : 0;

$trainingProgressPct = 0;
if (!empty($cohort['start_date']) && !empty($cohort['end_date'])) {
    $startTs = strtotime((string) $cohort['start_date']);
    $endTs = strtotime((string) $cohort['end_date']);
    $todayTs = strtotime(date('Y-m-d'));
    $duration = max(1, (int) floor(($endTs - $startTs) / 86400));
    $elapsed = max(0, min($duration, (int) floor(($todayTs - $startTs) / 86400)));
    $trainingProgressPct = (int) round(($elapsed / $duration) * 100);
}

$classDaysLabel = trim((string) ($cohort['class_days'] ?? ''));
$classDaysLabel = $classDaysLabel !== '' ? $classDaysLabel : 'Sin definir';
$classTimeLabel = trim((string) ($cohort['class_time'] ?? ''));
$classTimeLabel = $classTimeLabel !== '' ? $classTimeLabel : 'Sin definir';
$commentCount = count($comments ?? []);
$riskCount = 0;
foreach (($comments ?? []) as $comment) {
    if (($comment['category'] ?? '') === 'risk') {
        $riskCount++;
    }
}

$areaMap = ['academic' => 'Academic', 'marketing' => 'Marketing', 'admissions' => 'Admissions'];
$areaLabel = $areaMap[$cohort['area'] ?? ''] ?? 'Sin area';

$timeline = [
    ['label' => 'Inicio', 'date' => $cohort['start_date'] ?? null, 'icon' => 'bi-flag'],
    ['label' => '50%', 'date' => $cohort['training_date_50'] ?? null, 'icon' => 'bi-signpost-2'],
    ['label' => '75%', 'date' => $cohort['training_date_75'] ?? null, 'icon' => 'bi-signpost-split'],
    ['label' => 'Fin', 'date' => $cohort['end_date'] ?? null, 'icon' => 'bi-trophy'],
];
$today = date('Y-m-d');

$catBadges = [
    'risk' => 'bg-danger-subtle text-danger',
    'general' => 'bg-secondary-subtle text-secondary',
    'change_request' => 'bg-info-subtle text-info',
    'admission' => 'bg-info-subtle text-info',
    'marketing' => 'bg-info-subtle text-info',
];
$catLabels = [
    'risk' => 'Riesgo',
    'general' => 'General',
    'change_request' => 'Solicitud de cambio',
    'admission' => 'Solicitud de cambio',
    'marketing' => 'Solicitud de cambio',
];
$canDeleteThisCohort = in_array($ts, ['planned', 'cancelled', 'pending_reschedule'], true);
$workflowActionMap = [
    'completed' => [
        'title' => 'Marcar como completada',
        'description' => 'Cierra la cohorte como finalizada para seguimiento operativo y reportes.',
        'buttonClass' => 'btn-success',
        'icon' => 'bi-check2-circle',
        'requiresReason' => false,
        'reasonLabel' => '',
        'placeholder' => '',
    ],
    'cancelled' => [
        'title' => 'Cancelar cohorte',
        'description' => 'Usa esta acción cuando la cohorte no se ejecutará y debe quedar fuera del flujo activo.',
        'buttonClass' => 'btn-danger',
        'icon' => 'bi-x-circle',
        'requiresReason' => true,
        'reasonLabel' => 'Motivo de cancelación',
        'placeholder' => 'Ej. No alcanzó estudiantes mínimos o se pausó el proyecto.',
    ],
    'pending_reschedule' => [
        'title' => 'Enviar a pendiente de reprogramar',
        'description' => 'Mantiene la cohorte disponible para venta, pero fuera del calendario activo actual.',
        'buttonClass' => 'btn-warning',
        'icon' => 'bi-calendar2-week',
        'requiresReason' => true,
        'reasonLabel' => 'Motivo de reprogramación',
        'placeholder' => 'Ej. Se moverá la fecha por baja demanda o ajuste operativo.',
    ],
    'planned' => [
        'title' => 'Volver a planificado',
        'description' => 'Usa esta acción después de actualizar fecha, coach y horario para reactivar la cohorte.',
        'buttonClass' => 'btn-secondary',
        'icon' => 'bi-arrow-counterclockwise',
        'requiresReason' => false,
        'reasonLabel' => '',
        'placeholder' => '',
    ],
];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/cohorts" class="text-decoration-none">Cohortes</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cohort['name']) ?></li>
    </ol>
</nav>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<section class="cohort-detail-hero mb-4">
    <div class="cohort-detail-hero__content">
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-collection"></i>
            <?= htmlspecialchars($cohort['cohort_code'] ?? 'N/A') ?>
        </span>
        <h1><?= htmlspecialchars($cohort['name']) ?></h1>
        <div class="cohort-detail-hero__meta">
            <span><i class="bi bi-mortarboard"></i><?= htmlspecialchars(cohortDetailValue($cohort['bootcamp_type'] ?? null)) ?></span>
            <span><i class="bi bi-briefcase"></i><?= htmlspecialchars(cohortDetailValue($cohort['related_project'] ?? null)) ?></span>
            <span><i class="bi bi-person-workspace"></i><?= htmlspecialchars(cohortDetailValue($cohort['assigned_coach'] ?? null)) ?></span>
        </div>
    </div>
    <div class="cohort-detail-hero__actions">
        <span class="badge badge-status <?= $badgeClass ?>">
            <i class="bi <?= $badgeIcon ?> me-1"></i><?= htmlspecialchars($badgeLabel) ?>
        </span>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a href="/cohorts/<?= (int) $cohort['id'] ?>/marketing" class="btn btn-light btn-sm">
                <i class="bi bi-megaphone me-1"></i> Marketing
            </a>
            <?php if ($canEdit ?? false): ?>
                <a href="/cohorts/<?= (int) $cohort['id'] ?>/edit" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            <?php endif; ?>
            <?php if (($canDelete ?? false) && $canDeleteThisCohort): ?>
                <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>" data-confirm="Estas seguro de que deseas eliminar esta cohorte?">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="cohort-detail-summary mb-4">
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-primary"><i class="bi bi-people"></i></span>
        <div>
            <p>Admisiones</p>
            <strong><?= $actualAdmissions ?> / <?= $totalTarget ?></strong>
            <small><?= $admissionPct ?>% de avance</small>
        </div>
    </article>
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-info"><i class="bi bi-building"></i></span>
        <div>
            <p>Inscritos B2B/B2C</p>
            <strong><?= $b2bAdmissions ?> / <?= $b2cAdmissions ?></strong>
            <small>Meta <?= $b2bTarget ?> / <?= $b2cTarget ?></small>
        </div>
    </article>
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-success"><i class="bi bi-calendar-check"></i></span>
        <div>
            <p>Progreso training</p>
            <strong><?= $trainingProgressPct ?>%</strong>
            <small><?= htmlspecialchars($classDaysLabel) ?> | <?= htmlspecialchars($classTimeLabel) ?></small>
        </div>
    </article>
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-danger"><i class="bi bi-exclamation-triangle"></i></span>
        <div>
            <p>Revenue</p>
            <strong>$<?= number_format($actualRevenue, 2) ?> / $<?= number_format($targetRevenue, 2) ?></strong>
            <small><?= $revenuePct ?>% de cumplimiento</small>
        </div>
    </article>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <section class="app-panel cohort-detail-panel mb-4">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-calendar-range"></i> Timeline de entrenamiento</h2>
                    <p class="app-panel__subtitle">Hitos principales para seguimiento academico y operativo.</p>
                </div>
            </div>
            <div class="cohort-timeline">
                <?php foreach ($timeline as $item): ?>
                    <?php
                    $dateValue = $item['date'];
                    $stateClass = 'is-pending';
                    if ($dateValue && $dateValue <= $today) {
                        $stateClass = 'is-complete';
                    } elseif ($dateValue) {
                        $stateClass = 'is-upcoming';
                    }
                    ?>
                    <article class="cohort-timeline__item <?= $stateClass ?>">
                        <span class="cohort-timeline__icon"><i class="bi <?= $item['icon'] ?>"></i></span>
                        <div>
                            <strong><?= htmlspecialchars($item['label']) ?></strong>
                            <span><?= htmlspecialchars(cohortDetailDate($dateValue)) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="app-panel cohort-detail-panel mb-4">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-kanban"></i> Asignaciones operativas</h2>
                    <p class="app-panel__subtitle">Responsables, horario y contexto para coordinar la cohorte.</p>
                </div>
            </div>
            <div class="cohort-info-grid">
                <div>
                    <span>Proyecto relacionado</span>
                    <strong><?= htmlspecialchars(cohortDetailValue($cohort['related_project'] ?? null)) ?></strong>
                </div>
                <div>
                    <span>Coach asignado</span>
                    <strong><?= htmlspecialchars(cohortDetailValue($cohort['assigned_coach'] ?? null)) ?></strong>
                </div>
                <div>
                    <span>Dias de clase</span>
                    <strong><?= htmlspecialchars($classDaysLabel) ?></strong>
                </div>
                <div>
                    <span>Horario puntual</span>
                    <strong><?= htmlspecialchars($classTimeLabel) ?></strong>
                </div>
                <div>
                    <span>Bootcamp name</span>
                    <strong><?= htmlspecialchars(cohortDetailValue($cohort['bootcamp_type'] ?? null)) ?></strong>
                </div>
                <div>
                    <span>Area</span>
                    <strong><?= htmlspecialchars($areaLabel) ?></strong>
                </div>
                <div class="cohort-info-grid__wide">
                    <span>Patron de horario</span>
                    <strong><?= htmlspecialchars(cohortDetailValue($cohort['assigned_class_schedule'] ?? null)) ?></strong>
                </div>
            </div>
        </section>
    </div>

    <div class="col-xl-4">
        <section class="app-panel cohort-detail-panel mb-4">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-graph-up-arrow"></i> Admisiones</h2>
                    <p class="app-panel__subtitle">Progreso acumulado B2B y B2C.</p>
                </div>
            </div>
            <div class="cohort-admission-meter">
                <div class="cohort-admission-meter__top">
                    <strong><?= $admissionPct ?>%</strong>
                    <span><?= $actualAdmissions ?> de <?= $totalTarget ?></span>
                </div>
                <div class="dashboard-progress">
                    <span data-style-width="<?= $admissionPct ?>%"></span>
                </div>
            </div>
            <div class="cohort-admission-breakdown">
                <div>
                    <span>Inscritos B2B</span>
                    <strong><?= $b2bAdmissions ?></strong>
                </div>
                <div>
                    <span>Inscritos B2C</span>
                    <strong><?= $b2cAdmissions ?></strong>
                </div>
                <div>
                    <span>Meta B2B</span>
                    <strong><?= $b2bTarget ?></strong>
                </div>
                <div>
                    <span>Meta B2C</span>
                    <strong><?= $b2cTarget ?></strong>
                </div>
                <div>
                    <span>Meta total</span>
                    <strong><?= $totalTarget ?></strong>
                </div>
            </div>
        </section>

        <section class="app-panel cohort-detail-panel mb-4">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-currency-dollar"></i> Finanzas</h2>
                    <p class="app-panel__subtitle">Seguimiento de ingresos vs meta por cohorte.</p>
                </div>
            </div>
            <div class="cohort-admission-meter">
                <div class="cohort-admission-meter__top">
                    <strong><?= $revenuePct ?>%</strong>
                    <span>$<?= number_format($actualRevenue, 2) ?> de $<?= number_format($targetRevenue, 2) ?></span>
                </div>
                <div class="dashboard-progress">
                    <span data-style-width="<?= $revenuePct ?>%"></span>
                </div>
            </div>
            <div class="cohort-admission-breakdown">
                <div>
                    <span>Meta ingresos</span>
                    <strong>$<?= number_format($targetRevenue, 2) ?></strong>
                </div>
                <div>
                    <span>Ingresos actuales</span>
                    <strong>$<?= number_format($actualRevenue, 2) ?></strong>
                </div>
                <div>
                    <span>Brecha</span>
                    <strong>$<?= number_format(max(0, $targetRevenue - $actualRevenue), 2) ?></strong>
                </div>
                <div>
                    <span>Cumplimiento</span>
                    <strong><?= $revenuePct ?>%</strong>
                </div>
            </div>
        </section>

        <section class="app-panel cohort-detail-panel">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-info-circle"></i> Informacion</h2>
                    <p class="app-panel__subtitle">Datos de auditoria y trazabilidad.</p>
                </div>
            </div>
            <dl class="cohort-meta-list">
                <div>
                    <dt>ID</dt>
                    <dd>#<?= htmlspecialchars((string) $cohort['id']) ?></dd>
                </div>
                <div>
                    <dt>Correlativo</dt>
                    <dd><?= htmlspecialchars((string) ($cohort['correlative_number'] ?? 0)) ?></dd>
                </div>
                <div>
                    <dt>Riesgos</dt>
                    <dd><?= $riskCount ?> (<?= $commentCount ?> comentarios)</dd>
                </div>
                <div>
                    <dt>Creado</dt>
                    <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['created_at'] ?? 'now'))) ?></dd>
                </div>
                <div>
                    <dt>Actualizado</dt>
                    <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['updated_at'] ?? 'now'))) ?></dd>
                </div>
            </dl>
        </section>

        <?php if ($canManageStatus): ?>
            <section class="app-panel cohort-detail-panel mt-4">
                <div class="app-panel__header">
                    <div>
                        <h2 class="app-panel__title"><i class="bi bi-arrow-repeat"></i> Workflow de estado</h2>
                        <p class="app-panel__subtitle">Acciones controladas para mover la cohorte entre estados permitidos.</p>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded border bg-light-subtle">
                    <div class="small text-uppercase text-muted fw-semibold mb-1">Estado actual</div>
                    <span class="badge badge-status <?= $badgeClass ?>">
                        <i class="bi <?= $badgeIcon ?> me-1"></i><?= htmlspecialchars($badgeLabel) ?>
                    </span>
                </div>

                <?php if ($workflowTransitions === []): ?>
                    <div class="alert alert-secondary mb-0" role="alert">
                        No hay acciones de workflow disponibles para esta cohorte en su estado actual.
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($workflowTransitions as $targetStatus): ?>
                            <?php $action = $workflowActionMap[$targetStatus] ?? null; ?>
                            <?php if (!$action): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/status" class="border rounded p-3 bg-body-tertiary">
                                <input type="hidden" name="target_status" value="<?= htmlspecialchars($targetStatus) ?>">
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($action['title']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($action['description']) ?></div>
                                    </div>
                                    <?php if ($action['requiresReason']): ?>
                                        <div>
                                            <label class="form-label"><?= htmlspecialchars($action['reasonLabel']) ?></label>
                                            <textarea name="status_reason" class="form-control" rows="2" required placeholder="<?= htmlspecialchars($action['placeholder']) ?>"></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <button type="submit" class="btn <?= htmlspecialchars($action['buttonClass']) ?> btn-sm">
                                            <i class="bi <?= htmlspecialchars($action['icon']) ?> me-1"></i><?= htmlspecialchars($action['title']) ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>

<section class="app-panel cohort-comments mt-4">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-chat-left-text"></i> Comentarios y riesgos</h2>
            <p class="app-panel__subtitle">Registro compartido para decisiones, alertas y seguimiento.</p>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#commentForm">
            <i class="bi bi-plus-lg me-1"></i> Nuevo comentario
        </button>
    </div>

    <div class="collapse" id="commentForm">
        <div class="cohort-comment-form">
            <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/comments">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label">Categoria</label>
                        <select name="category" class="form-select" required>
                            <option value="general">General</option>
                            <option value="risk">Riesgo</option>
                            <option value="change_request">Solicitud de cambio</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-7">
                        <label class="form-label">Comentario</label>
                        <textarea name="body" class="form-control" rows="2" required placeholder="Escribe el comentario operativo..."></textarea>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Enviar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($comments ?? [])): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon">
                <i class="bi bi-chat"></i>
            </div>
            <h5 class="empty-state-title">Sin comentarios</h5>
            <p class="empty-state-text">Todavia no hay notas registradas para esta cohorte.</p>
        </div>
    <?php else: ?>
        <div class="cohort-comment-list">
            <?php foreach ($comments as $c): ?>
                <article class="cohort-comment-item <?= (($c['category'] ?? '') === 'risk') ? 'is-risk' : '' ?>">
                    <div class="cohort-comment-item__body">
                        <span class="badge badge-status <?= $catBadges[$c['category']] ?? 'bg-secondary-subtle text-secondary' ?>">
                            <?= htmlspecialchars($catLabels[$c['category']] ?? $c['category']) ?>
                        </span>
                        <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
                    </div>
                    <div class="cohort-comment-item__meta">
                        <strong><?= htmlspecialchars($c['author_name'] ?? 'Sistema') ?></strong>
                        <span><?= htmlspecialchars(date('d/m/Y', strtotime($c['created_at']))) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
