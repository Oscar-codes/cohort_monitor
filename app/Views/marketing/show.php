<!-- Marketing Stages for a Cohort -->
<?php
use App\Core\Auth;

/** @var array<string, mixed> $cohort */
$cohort = isset($cohort) && is_array($cohort) ? $cohort : [];

/** @var array<int, array<string, mixed>> $stages */
$stages = isset($stages) && is_array($stages) ? $stages : [];

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
?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/marketing" class="text-decoration-none">Marketing</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cohort['cohort_code']) ?></li>
    </ol>
</nav>

<section class="marketing-workflow-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-megaphone"></i>
            Workflow de marketing
        </span>
        <h1><?= htmlspecialchars($cohort['name']) ?></h1>
        <p><?= htmlspecialchars($cohort['cohort_code']) ?> · <?= htmlspecialchars($cohort['bootcamp_type'] ?? 'Sin tipo') ?></p>
    </div>
    <div class="marketing-workflow-hero__actions">
        <a href="/cohorts/<?= (int) $cohort['id'] ?>" class="btn btn-light btn-sm">
            <i class="bi bi-eye me-1"></i> Ver cohorte
        </a>
    </div>
</section>

<!-- ─── Marketing Info (Manual Fields) ─────────────────── -->
<section class="app-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-pencil-square"></i> Sección Marketing</h2>
            <p class="app-panel__subtitle">Todos los campos son de input manual excepto Campaña marketing.</p>
        </div>
    </div>

    <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/marketing/info">
        <div class="p-4">
            <!-- Campaña marketing (selector) -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Campaña marketing</h5>
                </div>
                <div class="card-body">
                    <select name="campaign_status" class="form-select" required>
                        <option value="Active" <?= ($marketingInfo['campaign_status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Completed" <?= ($marketingInfo['campaign_status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
            </div>

            <!-- Campos manuales -->
            <h6 class="text-muted mb-3"><i class="bi bi-input-cursor-text me-2"></i>Campos de información (input manual)</h6>
            
            <div class="row g-3">
                <?php
                $fields = [
                    'strategy_notes' => ['Estrategia', 'bi-lightbulb'],
                    'content_notes' => ['Contenido', 'bi-file-text'],
                    'ads_notes' => ['Paid Ads', 'bi-megaphone-fill'],
                    'organic_notes' => ['Orgánico', 'bi-tree'],
                    'events_notes' => ['Eventos', 'bi-calendar-event'],
                    'partnerships_notes' => ['Partnerships', 'bi-people'],
                    'analytics_notes' => ['Analítica', 'bi-graph-up'],
                ];
                
                foreach ($fields as $fieldKey => $fieldMeta):
                    [$label, $icon] = $fieldMeta;
                ?>
                <div class="col-md-6">
                    <label for="<?= $fieldKey ?>" class="form-label">
                        <i class="bi <?= $icon ?> me-1"></i> <?= $label ?>
                    </label>
                    <textarea 
                        name="<?= $fieldKey ?>" 
                        id="<?= $fieldKey ?>" 
                        class="form-control" 
                        rows="3"
                        placeholder="Ingrese información sobre <?= strtolower($label) ?>..."
                    ><?= htmlspecialchars($marketingInfo[$fieldKey] ?? '') ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar información de marketing
                </button>
            </div>
        </div>
    </form>
</section>

<div class="marketing-summary mb-4">
    <article class="marketing-summary-card">
        <span class="marketing-summary-card__icon is-primary"><i class="bi bi-list-check"></i></span>
        <div>
            <p>Avance</p>
            <strong><?= $completionPct ?>%</strong>
            <small><?= $completedCount ?> de <?= count($stages) ?> etapas</small>
        </div>
    </article>
    <article class="marketing-summary-card">
        <span class="marketing-summary-card__icon is-success"><i class="bi bi-check2-circle"></i></span>
        <div>
            <p>Completadas</p>
            <strong><?= $completedCount ?></strong>
            <small>Sin bloqueo activo</small>
        </div>
    </article>
    <article class="marketing-summary-card">
        <span class="marketing-summary-card__icon is-warning"><i class="bi bi-hourglass-split"></i></span>
        <div>
            <p>Active</p>
            <strong><?= $pendingCount ?></strong>
            <small>Campos manuales activos</small>
        </div>
    </article>
    <article class="marketing-summary-card">
        <span class="marketing-summary-card__icon is-danger"><i class="bi bi-exclamation-triangle"></i></span>
        <div>
            <p>Solicitud de cambio</p>
            <strong><?= $riskCount ?></strong>
            <small>Gestionar desde comentarios</small>
        </div>
    </article>
</div>

<section class="app-panel marketing-workflow-board">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-diagram-3"></i> Etapas del workflow</h2>
            <p class="app-panel__subtitle">Actualiza el estado de cada etapa y documenta condiciones de riesgo.</p>
        </div>
        <div class="marketing-progress-pill">
            <span><?= $completionPct ?>%</span>
            <div class="dashboard-mini-progress"><span data-style-width="<?= $completionPct ?>%"></span></div>
        </div>
    </div>

    <div class="marketing-stage-grid">
        <?php foreach ($stages as $index => $stage): ?>
            <?php
            $stageName = (string) $stage['stage_name'];
            $stageStatus = (string) ($stage['status'] ?? 'active');
            if (in_array($stageStatus, ['pending', 'at_risk'], true)) {
                $stageStatus = 'active';
            }
            $modalId = 'modal-' . preg_replace('/[^a-z0-9_-]/i', '-', $stageName);
            ?>
            <article class="marketing-stage-card">
                <div class="marketing-stage-card__top">
                    <span class="marketing-stage-card__step"><?= $index + 1 ?></span>
                    <span class="badge badge-status <?= $statusBadge[$stageStatus] ?? 'bg-info-subtle text-info' ?>">
                        <i class="bi <?= $statusIcon[$stageStatus] ?? 'bi-info-circle' ?> me-1"></i>
                        <?= htmlspecialchars($statusLabels[$stageStatus] ?? $stageStatus) ?>
                    </span>
                </div>
                <h3><?= htmlspecialchars($stageLabels[$stageName] ?? $stageName) ?></h3>
                <?php if (!empty($stage['risk_notes'])): ?>
                    <p class="marketing-stage-card__risk"><?= htmlspecialchars($stage['risk_notes']) ?></p>
                <?php else: ?>
                    <p class="marketing-stage-card__muted">Campo manual.</p>
                <?php endif; ?>
                <div class="marketing-stage-card__footer">
                    <div>
                        <span>Actualizado por</span>
                        <strong><?= htmlspecialchars($stage['updated_by_name'] ?? 'Sistema') ?></strong>
                        <small><?= !empty($stage['updated_at']) ? htmlspecialchars(date('d/m/Y', strtotime($stage['updated_at']))) : 'Sin fecha' ?></small>
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </article>

            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="/cohorts/<?= (int) $cohort['id'] ?>/marketing">
                            <div class="modal-header">
                                <h6 class="modal-title">
                                    <i class="bi bi-pencil me-2"></i>
                                    <?= htmlspecialchars($stageLabels[$stageName] ?? '') ?>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="stage_name" value="<?= htmlspecialchars($stageName) ?>">

                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="status" id="status-<?= htmlspecialchars($stageName) ?>">
                                        <option value="active" <?= $stageStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="completed" <?= $stageStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
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
    </div>
</section>
