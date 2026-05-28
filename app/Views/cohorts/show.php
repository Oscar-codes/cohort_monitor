<!-- Cohort Show View -->
<?php
/** @var array<string, mixed> $cohort */
$cohort = isset($cohort) && is_array($cohort) ? $cohort : [];

/** @var array<int, array<string, mixed>> $comments */
$comments = isset($comments) && is_array($comments) ? $comments : [];

$statusMap = [
    'not_started' => ['bg-secondary-subtle text-secondary', 'Sin iniciar', 'bi-hourglass-split'],
    'in_progress' => ['bg-primary-subtle text-primary', 'En progreso', 'bi-play-circle'],
    'completed' => ['bg-success-subtle text-success', 'Completado', 'bi-check-circle'],
    'cancelled' => ['bg-danger-subtle text-danger', 'Cancelado', 'bi-x-circle'],
];

$ts = $cohort['training_status'] ?? 'not_started';
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
$b2bAdmissions = max(0, (int) ($cohort['b2b_admissions'] ?? 0));
$b2cAdmissions = max(0, (int) ($cohort['b2c_admissions'] ?? 0));
$actualAdmissions = $b2bAdmissions + $b2cAdmissions;
$admissionPct = $totalTarget > 0 ? min(100, (int) round(($actualAdmissions / $totalTarget) * 100)) : 0;
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
    'admission' => 'bg-info-subtle text-info',
    'marketing' => 'bg-warning-subtle text-warning',
];
$catLabels = [
    'risk' => 'Riesgo',
    'general' => 'General',
    'admission' => 'Admision',
    'marketing' => 'Marketing',
];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/cohorts" class="text-decoration-none">Cohortes</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cohort['name']) ?></li>
    </ol>
</nav>

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
            <?php if ($canDelete ?? false): ?>
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
            <p>B2B</p>
            <strong><?= $b2bAdmissions ?> / <?= $b2bTarget ?></strong>
            <small>Actual vs meta</small>
        </div>
    </article>
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-success"><i class="bi bi-calendar-check"></i></span>
        <div>
            <p>Fecha limite</p>
            <strong><?= htmlspecialchars(cohortDetailDate($cohort['admission_deadline_date'] ?? null)) ?></strong>
            <small>Admision</small>
        </div>
    </article>
    <article class="cohort-detail-kpi">
        <span class="cohort-detail-kpi__icon is-danger"><i class="bi bi-exclamation-triangle"></i></span>
        <div>
            <p>Riesgos</p>
            <strong><?= $riskCount ?></strong>
            <small><?= $commentCount ?> comentarios totales</small>
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
                    <span>Tipo de bootcamp</span>
                    <strong><?= htmlspecialchars(cohortDetailValue($cohort['bootcamp_type'] ?? null)) ?></strong>
                </div>
                <div>
                    <span>Area</span>
                    <strong><?= htmlspecialchars($areaLabel) ?></strong>
                </div>
                <div class="cohort-info-grid__wide">
                    <span>Horario asignado</span>
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
                    <span>B2B actual</span>
                    <strong><?= $b2bAdmissions ?></strong>
                </div>
                <div>
                    <span>B2C actual</span>
                    <strong><?= $b2cAdmissions ?></strong>
                </div>
                <div>
                    <span>Meta B2B</span>
                    <strong><?= $b2bTarget ?></strong>
                </div>
                <div>
                    <span>Meta total</span>
                    <strong><?= $totalTarget ?></strong>
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
                    <dt>Creado</dt>
                    <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['created_at'] ?? 'now'))) ?></dd>
                </div>
                <div>
                    <dt>Actualizado</dt>
                    <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cohort['updated_at'] ?? 'now'))) ?></dd>
                </div>
            </dl>
        </section>
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
                            <option value="admission">Admision</option>
                            <option value="marketing">Marketing</option>
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
