<!-- Alerts Dashboard (Admin only) -->
<?php use App\Core\Auth; ?>
<?php use App\Services\MarketingService; ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Monitorea todos los riesgos identificados en las cohortes.</p>
    </div>
</div>

<!-- ─── Summary cards ──────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-card-icon bg-danger-subtle text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value"><?= count($riskComments) ?></div>
                        <div class="stat-card-label">Comentarios de Riesgo</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-card-icon bg-warning-subtle text-warning">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value"><?= count($atRiskStages) ?></div>
                        <div class="stat-card-label">Etapas Mkt en Riesgo</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-content">
                    <div class="stat-card-icon bg-info-subtle text-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-card-info">
                        <div class="stat-card-value"><?= count($risksByCohort) ?></div>
                        <div class="stat-card-label">Cohortes con Riesgos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Marketing stages at risk ──────────────────────────── -->
<?php if (!empty($atRiskStages)): ?>
<div class="card table-card mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="bi bi-megaphone text-warning me-2"></i>Etapas de Marketing en Riesgo</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cohorte</th>
                    <th class="text-center">Etapa</th>
                    <th class="d-none d-md-table-cell">Notas de Riesgo</th>
                    <th class="d-none d-lg-table-cell">Actualizado</th>
                    <th class="text-end" style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($atRiskStages as $s): ?>
                <tr>
                    <td>
                        <a href="/cohorts/<?= $s['cohort_id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($s['cohort_code']) ?>
                        </a>
                        <div class="text-muted small"><?= htmlspecialchars($s['cohort_name']) ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-status bg-danger-subtle text-danger">
                            <?= htmlspecialchars(MarketingService::STAGE_LABELS[$s['stage_name']] ?? $s['stage_name']) ?>
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <small><?= htmlspecialchars($s['risk_notes'] ?? '—') ?></small>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <div class="small">
                            <div class="text-muted"><?= htmlspecialchars($s['updated_by_name'] ?? '—') ?></div>
                            <span class="text-muted"><?= date('d/m/Y', strtotime($s['updated_at'])) ?></span>
                        </div>
                    </td>
                    <td class="text-end">
                        <a href="/cohorts/<?= $s['cohort_id'] ?>/marketing" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver marketing">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Risk comments ─────────────────────────────────────── -->
<?php if (!empty($riskComments)): ?>
<div class="card table-card">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="bi bi-chat-left-dots text-danger me-2"></i>Comentarios de Riesgo</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cohorte</th>
                    <th>Comentario</th>
                    <th class="d-none d-md-table-cell">Autor</th>
                    <th class="d-none d-lg-table-cell">Fecha</th>
                    <th class="text-end" style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $roleLabels = [
                'admin'          => ['Administrador', 'bg-danger-subtle text-danger'],
                'admissions_b2b' => ['Admisiones B2B', 'bg-info-subtle text-info'],
                'admissions_b2c' => ['Admisiones B2C', 'bg-primary-subtle text-primary'],
                'marketing'      => ['Marketing', 'bg-warning-subtle text-warning'],
            ];
            ?>
            <?php foreach ($riskComments as $rc): ?>
                <tr>
                    <td>
                        <a href="/cohorts/<?= $rc['cohort_id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($rc['cohort_code']) ?>
                        </a>
                        <div class="text-muted small"><?= htmlspecialchars($rc['cohort_name']) ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($rc['body']) ?></div>
                        <small class="text-muted d-md-none">
                            <?= htmlspecialchars($rc['author_name']) ?> · <?= date('d/m/Y', strtotime($rc['created_at'])) ?>
                        </small>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="small">
                            <div><?= htmlspecialchars($rc['author_name']) ?></div>
                            <?php [$rl, $rc_class] = $roleLabels[$rc['author_role']] ?? [$rc['author_role'], 'bg-secondary-subtle text-secondary']; ?>
                            <span class="badge badge-status <?= $rc_class ?>"><?= htmlspecialchars($rl) ?></span>
                        </div>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($rc['created_at'])) ?></small>
                    </td>
                    <td class="text-end">
                        <a href="/cohorts/<?= $rc['cohort_id'] ?>" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver cohorte">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($riskComments) && empty($atRiskStages)): ?>
<!-- Empty State -->
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon text-success">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5 class="empty-state-title">Todo en orden</h5>
            <p class="empty-state-text">No hay alertas de riesgo activas. ¡Excelente trabajo!</p>
        </div>
    </div>
</div>
<?php endif; ?>
