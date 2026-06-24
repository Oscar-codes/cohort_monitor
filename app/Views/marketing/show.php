<?php
use App\Core\Auth;

/** @var array<string, mixed> $cohort */
$cohort = isset($cohort) && is_array($cohort) ? $cohort : [];

/** @var array<int, array<string, mixed>> $stages */
$stages = isset($stages) && is_array($stages) ? $stages : [];

$marketingInfo = isset($marketingInfo) && is_array($marketingInfo) ? $marketingInfo : null;

$statusBadge = [
    'active' => 'bg-primary-subtle text-primary',
    'completed' => 'bg-success-subtle text-success',
    'pending' => 'bg-primary-subtle text-primary',
    'at_risk' => 'bg-primary-subtle text-primary',
];
$statusIcon = [
    'active' => 'bi-broadcast',
    'completed' => 'bi-check-circle',
    'pending' => 'bi-broadcast',
    'at_risk' => 'bi-broadcast',
];

$completedCount = 0;
$pendingCount = 0;
$riskCount = 0;
foreach ($stages as $stage) {
    if (($stage['status'] ?? '') === 'completed') {
        $completedCount++;
    } else {
        $pendingCount++;
    }
}
$totalStages = max(1, count($stages));
$completionPct = (int) round(($completedCount / $totalStages) * 100);

$campaignStatus = ($marketingInfo['campaign_status'] ?? null) === 'Completed' ? 'Completed' : 'Active';
$fieldsFilledCount = 0;
$fieldsTotalCount = 7;
$textFields = ['strategy_notes', 'content_notes', 'ads_notes', 'organic_notes', 'events_notes', 'partnerships_notes', 'analytics_notes'];
foreach ($textFields as $f) {
    $val = trim((string) ($marketingInfo[$f] ?? ''));
    if ($val !== '') {
        $fieldsFilledCount++;
    }
}
$fieldsPct = (int) round(($fieldsFilledCount / $fieldsTotalCount) * 100);

if (!function_exists('marketingStageStatusLabel')) {
    function marketingStageStatusLabel(?string $status): string
    {
        return [
            'active' => 'Active',
            'completed' => 'Completed',
        ][$status ?? ''] ?? ucfirst((string) $status);
    }
}
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/marketing" class="text-decoration-none">Marketing</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cohort['cohort_code']) ?></li>
    </ol>
</nav>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-megaphone"></i>
            <?= htmlspecialchars($cohort['cohort_code']) ?>
        </div>
        <h2 class="cohorts-hero__title"><?= htmlspecialchars($cohort['name']) ?></h2>
        <p class="cohorts-hero__copy">
            <?= htmlspecialchars($cohort['bootcamp_type'] ?? 'Sin tipo') ?>
            <?php if (!empty($cohort['related_project'])): ?>
                 · <?= htmlspecialchars($cohort['related_project']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="cohorts-hero__actions">
        <a href="/cohorts/<?= (int) $cohort['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> Ver cohorte
        </a>
        <a href="/marketing" class="btn btn-outline-secondary">
            <i class="bi bi-grid-1x2 me-1"></i> Plan maestro marketing
        </a>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--primary h-100">
            <span><i class="bi bi-list-check"></i></span>
            <div>
                <strong><?= $completionPct ?>%</strong>
                <small>Avance de etapas (<?= $completedCount ?>/<?= count($stages) ?>)</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--success h-100">
            <span><i class="bi bi-check2-circle"></i></span>
            <div>
                <strong><?= $completedCount ?></strong>
                <small>Etapas completadas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--info h-100">
            <span><i class="bi bi-hourglass-split"></i></span>
            <div>
                <strong><?= $pendingCount ?></strong>
                <small>Etapas activas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--warning h-100">
            <span><i class="bi bi-megaphone"></i></span>
            <div>
                <strong><?= $campaignStatus ?></strong>
                <small>Campaña marketing</small>
            </div>
        </article>
    </div>
</div>

<!-- Campaña -->
<section class="app-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-megaphone-fill text-primary"></i> Campaña marketing</h3>
            <p class="app-panel__subtitle">Estado de la campaña para esta cohorte. Solo Active o Completed.</p>
        </div>
    </div>
    <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/marketing/info" class="row g-3 align-items-end">
        <div class="col-md-6 col-xl-5">
            <label for="campaign_status" class="form-label">Estado de la campaña</label>
            <select id="campaign_status" name="campaign_status" class="form-select" required>
                <option value="Active" <?= $campaignStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Completed" <?= $campaignStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="col-md-6 col-xl-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save me-1"></i> Guardar campaña
            </button>
        </div>
        <div class="col-md-6 col-xl-4">
            <span class="badge <?= $campaignStatus === 'Active' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' ?>">
                <i class="bi <?= $campaignStatus === 'Active' ? 'bi-broadcast' : 'bi-check-circle' ?> me-1"></i>
                <?= $campaignStatus ?>
            </span>
        </div>
        <?php foreach ($textFields as $f): ?>
            <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars((string) ($marketingInfo[$f] ?? '')) ?>">
        <?php endforeach; ?>
    </form>
</section>

<!-- Campos manuales -->
<section class="app-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-pencil-square"></i> Información de marketing</h3>
            <p class="app-panel__subtitle">Campos manuales por cohorte. Completados <?= $fieldsFilledCount ?> de <?= $fieldsTotalCount ?> (<?= $fieldsPct ?>%).</p>
        </div>
        <div class="marketing-progress-pill" style="min-width: 180px;">
            <span><?= $fieldsPct ?>%</span>
            <div class="dashboard-mini-progress"><span data-style-width="<?= $fieldsPct ?>%"></span></div>
        </div>
    </div>
    <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/marketing/info">
        <input type="hidden" name="campaign_status" value="<?= htmlspecialchars($campaignStatus) ?>">
        <div class="row g-3">
            <?php
            $fields = [
                'strategy_notes' => ['Estrategia', 'bi-lightbulb', 'Define tácticas, mensajes clave y diferenciadores de la campaña.'],
                'content_notes' => ['Contenido', 'bi-file-text', 'Entregables de contenido: posts, emails, guiones o landing pages.'],
                'ads_notes' => ['Paid Ads', 'bi-megaphone-fill', 'Inversiones y campañas pagadas: canales, presupuesto, KPIs.'],
                'organic_notes' => ['Orgánico', 'bi-tree', 'Tácticas orgánicas: SEO, redes, community management.'],
                'events_notes' => ['Eventos', 'bi-calendar-event', 'Eventos activados, webinar, ferias o sesiones con prospects.'],
                'partnerships_notes' => ['Partnerships', 'bi-people', 'Alianzas estratégicas y co-marketing activo.'],
                'analytics_notes' => ['Analítica', 'bi-graph-up', 'KPIs, canales de medición, fuentes y reportes clave.'],
            ];

            foreach ($fields as $fieldKey => $fieldMeta):
                [$label, $icon, $placeholder] = $fieldMeta;
            ?>
            <div class="col-md-6 col-xl-4">
                <label for="<?= $fieldKey ?>" class="form-label">
                    <i class="bi <?= $icon ?> me-1 text-primary"></i> <?= $label ?>
                </label>
                <textarea
                    name="<?= $fieldKey ?>"
                    id="<?= $fieldKey ?>"
                    class="form-control"
                    rows="3"
                    placeholder="<?= htmlspecialchars($placeholder) ?>"
                ><?= htmlspecialchars($marketingInfo[$fieldKey] ?? '') ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 mt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Guardar esta sección no actualiza el estado de la campaña.
            </small>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Guardar información de marketing
            </button>
        </div>
    </form>
</section>

<!-- Workflow stages como tabla -->
<section class="app-panel">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-diagram-3 text-primary"></i> Etapas del workflow</h3>
            <p class="app-panel__subtitle">Actualiza el estado de cada etapa y documenta condiciones de riesgo.</p>
        </div>
        <div class="marketing-progress-pill" style="min-width: 180px;">
            <span><?= $completionPct ?>%</span>
            <div class="dashboard-mini-progress"><span data-style-width="<?= $completionPct ?>%"></span></div>
        </div>
    </div>

    <?php if (empty($stages)): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-diagram-3"></i></div>
            <h5 class="empty-state-title">Sin etapas</h5>
            <p class="empty-state-text">Esta cohorte aún no tiene etapas de marketing registradas.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 48px;">#</th>
                        <th>Etapa</th>
                        <th>Estado</th>
                        <th>Notas de riesgo / observaciones</th>
                        <th>Actualizado por</th>
                        <th>Fecha</th>
                        <th style="width: 110px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stages as $index => $stage): ?>
                        <?php
                        $stageName = (string) $stage['stage_name'];
                        $stageStatus = (string) ($stage['status'] ?? 'active');
                        if (in_array($stageStatus, ['pending', 'at_risk'], true)) {
                            $stageStatus = 'active';
                        }
                        $modalId = 'modal-' . preg_replace('/[^a-z0-9_-]/i', '-', $stageName);
                        ?>
                        <tr>
                            <td><span class="marketing-stage-card__step"><?= $index + 1 ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($stageLabels[$stageName] ?? $stageName) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-status <?= $statusBadge[$stageStatus] ?? 'bg-info-subtle text-info' ?>">
                                    <i class="bi <?= $statusIcon[$stageStatus] ?? 'bi-info-circle' ?> me-1"></i>
                                    <?= htmlspecialchars(marketingStageStatusLabel($stageStatus)) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($stage['risk_notes'])
                                    ? '<span class="text-body">' . htmlspecialchars($stage['risk_notes']) . '</span>'
                                    : '<span class="text-muted small">Sin observaciones registradas</span>' ?>
                            </td>
                            <td><?= htmlspecialchars($stage['updated_by_name'] ?? '—') ?></td>
                            <td>
                                <small class="text-muted">
                                    <?= !empty($stage['updated_at']) ? htmlspecialchars(date('d/m/Y', strtotime($stage['updated_at']))) : 'Sin fecha' ?>
                                </small>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" title="Editar etapa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/marketing">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <i class="bi bi-pencil me-2"></i>
                                                <?= htmlspecialchars($stageLabels[$stageName] ?? '') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="stage_name" value="<?= htmlspecialchars($stageName) ?>">

                                            <div class="mb-3">
                                                <label class="form-label">Estado</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?= $stageStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="completed" <?= $stageStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notas / condiciones</label>
                                                <textarea class="form-control" name="risk_notes" rows="3" placeholder="Registra observaciones, bloqueos o riesgos de la etapa"><?= htmlspecialchars((string) ($stage['risk_notes'] ?? '')) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-lg me-1"></i> Guardar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
