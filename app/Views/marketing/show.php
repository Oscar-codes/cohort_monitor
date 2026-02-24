<!-- Marketing Stages for a Cohort -->
<?php use App\Core\Auth; ?>

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

<!-- Header Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="mb-0"><i class="bi bi-megaphone text-primary me-2"></i>Workflow de Marketing</h5>
                </div>
                <p class="text-muted mb-0">
                    <span class="fw-semibold"><?= htmlspecialchars($cohort['name']) ?></span>
                    <code class="ms-2"><?= htmlspecialchars($cohort['cohort_code']) ?></code>
                </p>
            </div>
            <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> Ver Cohorte
            </a>
        </div>
    </div>
</div>

<!-- Stages Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Etapa</th>
                    <th class="text-center">Estado</th>
                    <th class="d-none d-md-table-cell">Notas de Riesgo</th>
                    <th class="d-none d-lg-table-cell">Actualizado por</th>
                    <th class="text-end" style="width: 100px;">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $statusBadge = [
                'completed' => 'bg-success-subtle text-success',
                'pending'   => 'bg-secondary-subtle text-secondary',
                'at_risk'   => 'bg-danger-subtle text-danger',
            ];
            ?>
            <?php foreach ($stages as $stage): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($stageLabels[$stage['stage_name']] ?? $stage['stage_name']) ?></div>
                        <small class="text-muted d-md-none"><?= htmlspecialchars($stage['risk_notes'] ?? '') ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-status <?= $statusBadge[$stage['status']] ?? 'bg-info-subtle text-info' ?>">
                            <?= htmlspecialchars($statusLabels[$stage['status']] ?? $stage['status']) ?>
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <?php if (!empty($stage['risk_notes'])): ?>
                            <small><?= htmlspecialchars($stage['risk_notes']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <div class="small">
                            <div><?= htmlspecialchars($stage['updated_by_name'] ?? '—') ?></div>
                            <span class="text-muted"><?= !empty($stage['updated_at']) ? date('d/m/Y', strtotime($stage['updated_at'])) : '' ?></span>
                        </div>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modal-<?= $stage['stage_name'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>

                <!-- Modal for editing stage -->
                <div class="modal fade" id="modal-<?= $stage['stage_name'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="/cohorts/<?= $cohort['id'] ?>/marketing">
                                <div class="modal-header">
                                    <h6 class="modal-title">
                                        <i class="bi bi-pencil me-2"></i>
                                        <?= htmlspecialchars($stageLabels[$stage['stage_name']] ?? '') ?>
                                    </h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="stage_name" value="<?= $stage['stage_name'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="status" id="status-<?= $stage['stage_name'] ?>"
                                                onchange="toggleRisk(this, '<?= $stage['stage_name'] ?>')">
                                            <option value="completed" <?= $stage['status'] === 'completed' ? 'selected' : '' ?>>Completada</option>
                                            <option value="pending"   <?= $stage['status'] === 'pending'   ? 'selected' : '' ?>>Pendiente a iniciar</option>
                                            <option value="at_risk"   <?= $stage['status'] === 'at_risk'   ? 'selected' : '' ?>>En riesgo</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="risk-notes-<?= $stage['stage_name'] ?>"
                                         style="display: <?= $stage['status'] === 'at_risk' ? 'block' : 'none' ?>;">
                                        <label class="form-label">Documentar condición de riesgo <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="risk_notes" rows="3"
                                                  placeholder="Describa por qué esta etapa está en riesgo..."
                                        ><?= htmlspecialchars($stage['risk_notes'] ?? '') ?></textarea>
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
</div>

<script>
function toggleRisk(select, stageName) {
    const div = document.getElementById('risk-notes-' + stageName);
    div.style.display = select.value === 'at_risk' ? 'block' : 'none';
}
</script>
