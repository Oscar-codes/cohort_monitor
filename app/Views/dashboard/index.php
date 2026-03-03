<!-- Dashboard Index View -->
<?php use App\Core\Auth; ?>
<?php use App\Services\MarketingService; ?>

<?php
    $statusLabels = [
        'in_progress' => ['En Progreso', 'success'],
        'completed'   => ['Completado', 'primary'],
        'not_started' => ['No Iniciado', 'secondary'],
    ];
    $roleColors = [
        'admin'          => ['Admin', 'danger'],
        'admissions_b2b' => ['B2B', 'info'],
        'admissions_b2c' => ['B2C', 'primary'],
        'marketing'      => ['Marketing', 'warning'],
    ];
    [$roleLabel, $roleColor] = $roleColors[Auth::role()] ?? [ucfirst(Auth::role() ?? ''), 'secondary'];
?>

<!-- Welcome Banner -->
<div class="welcome-banner mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h2 class="h4 fw-bold mb-1 text-white">¡Hola, <?= e(explode(' ', Auth::user()['full_name'] ?? 'Usuario')[0]) ?>!</h2>
            <p class="mb-0 text-white-50">Aquí tienes el resumen general de Cohort Monitor &mdash; <?= date('d \d\e F, Y') ?></p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 fs-6">
                <i class="bi bi-person-badge me-1"></i><?= $roleLabel ?>
            </span>
        </div>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <!-- Total Cohorts -->
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-primary h-100">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                <div class="kpi-value"><?= $totalCohorts ?? 0 ?></div>
                <div class="kpi-label">Total Cohortes</div>
            </div>
        </div>
    </div>
    <!-- Active Cohorts -->
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-success h-100">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-play-circle-fill"></i></div>
                <div class="kpi-value"><?= $activeCohorts ?? 0 ?></div>
                <div class="kpi-label">En Progreso</div>
            </div>
        </div>
    </div>
    <!-- Completed Cohorts -->
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-info h-100">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="kpi-value"><?= $completedCohorts ?? 0 ?></div>
                <div class="kpi-label">Completadas</div>
            </div>
        </div>
    </div>
    <!-- Active Alerts -->
    <div class="col-6 col-lg-3">
        <div class="card kpi-card kpi-danger h-100">
            <div class="card-body">
                <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="kpi-value"><?= $totalAlerts ?? 0 ?></div>
                <div class="kpi-label">Alertas Activas</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-transparent">
                <span class="fw-semibold"><i class="bi bi-lightning-charge text-warning me-2"></i>Acciones Rápidas</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (Auth::canCreateCohort()): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/cohorts/create" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                            <span class="qa-text">Nueva Cohorte</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/cohorts" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-list-ul"></i>
                            </div>
                            <span class="qa-text">Ver Cohortes</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/alerts" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <span class="qa-text">Alertas</span>
                        </a>
                    </div>
                    <?php if (Auth::hasRole(['admin', 'marketing'])): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/marketing" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <span class="qa-text">Marketing</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/reports" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                            <span class="qa-text">Reportes</span>
                        </a>
                    </div>
                    <?php if (Auth::isAdmin()): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <a href="/cohorts/import" class="quick-action-card text-decoration-none">
                            <div class="qa-icon bg-secondary bg-opacity-10 text-secondary">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <span class="qa-text">Importar</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admission Progress + Status Breakdown -->
<div class="row g-3 mb-4">
    <!-- Admission Progress -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Progreso de Admisiones Global</span>
                <span class="badge bg-primary-subtle text-primary"><?= $admissionPct ?? 0 ?>%</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <div class="text-muted small">Admisiones actuales</div>
                        <h3 class="fw-bold mb-0"><?= number_format($totalAdmissions ?? 0) ?></h3>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Meta total</div>
                        <h3 class="fw-bold mb-0 text-muted"><?= number_format($totalTarget ?? 0) ?></h3>
                    </div>
                </div>
                <?php $pct = min(100, $admissionPct ?? 0); ?>
                <div class="progress progress-lg mb-3" style="height: 18px; border-radius: 10px;">
                    <div class="progress-bar bg-gradient <?= $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-primary' : 'bg-warning') ?>"
                         role="progressbar" style="width: <?= $pct ?>%; border-radius: 10px;"
                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= $pct ?>%
                    </div>
                </div>
                <?php if (!empty($upcomingCohorts)): ?>
                <div class="mt-3">
                    <h6 class="text-muted small text-uppercase mb-2">
                        <i class="bi bi-calendar-event me-1"></i>Próximos inicios (30 días)
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (array_slice($upcomingCohorts, 0, 4) as $uc): ?>
                        <a href="/cohorts/<?= $uc['id'] ?>" class="badge bg-light text-dark text-decoration-none border px-3 py-2">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>
                            <?= htmlspecialchars($uc['cohort_code']) ?>
                            <span class="text-muted ms-1"><?= date('d/m', strtotime($uc['start_date'])) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-transparent">
                <span class="fw-semibold"><i class="bi bi-pie-chart text-info me-2"></i>Estado de Cohortes</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php
                    $breakdown = $statusBreakdown ?? [];
                    $total     = array_sum($breakdown) ?: 1;
                ?>
                <?php foreach ($breakdown as $key => $count): ?>
                <?php [$label, $color] = $statusLabels[$key] ?? [ucfirst($key), 'secondary']; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-medium"><?= $label ?></span>
                        <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>"><?= $count ?></span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 6px;">
                        <div class="progress-bar bg-<?= $color ?>" role="progressbar"
                             style="width: <?= round(($count / $total) * 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!empty($byType)): ?>
                <hr class="my-3">
                <h6 class="text-muted small text-uppercase mb-2">Por tipo de Bootcamp</h6>
                <?php foreach ($byType as $type => $cnt): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small"><?= htmlspecialchars($type ?: 'Sin tipo') ?></span>
                    <span class="badge bg-secondary-subtle text-secondary"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Alerts + Recent Cohorts -->
<div class="row g-3 mb-4">
    <!-- Recent Alerts -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Alertas Recientes</span>
                <a href="/alerts" class="btn btn-sm btn-outline-danger">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php $hasAlerts = !empty($riskComments) || !empty($atRiskStages); ?>
                <?php if ($hasAlerts): ?>
                <div class="list-group list-group-flush">
                    <?php foreach (($atRiskStages ?? []) as $s): ?>
                    <a href="/cohorts/<?= $s['cohort_id'] ?>/marketing" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center gap-3">
                            <div class="alert-dot bg-warning"></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small"><?= htmlspecialchars($s['cohort_code']) ?></div>
                                <div class="text-muted" style="font-size: .8rem;">
                                    Mkt: <?= htmlspecialchars(MarketingService::STAGE_LABELS[$s['stage_name']] ?? $s['stage_name']) ?> en riesgo
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d/m', strtotime($s['updated_at'])) ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php foreach (($riskComments ?? []) as $rc): ?>
                    <a href="/cohorts/<?= $rc['cohort_id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center gap-3">
                            <div class="alert-dot bg-danger"></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small"><?= htmlspecialchars($rc['cohort_code']) ?></div>
                                <div class="text-muted text-truncate" style="font-size: .8rem; max-width: 260px;">
                                    <?= htmlspecialchars($rc['body']) ?>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d/m', strtotime($rc['created_at'])) ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-shield-check text-success fs-1 d-block mb-2"></i>
                    <span class="text-muted">Sin alertas activas</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Cohorts -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-clock-history text-primary me-2"></i>Cohortes Recientes</span>
                <a href="/cohorts" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentCohorts)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th class="d-none d-md-table-cell">Tipo</th>
                                <th class="text-center">Admisiones</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentCohorts as $c): ?>
                            <?php
                                $target = (int) ($c['total_admission_target'] ?? 0);
                                $actual = (int) ($c['b2b_admissions'] ?? 0) + (int) ($c['b2c_admissions'] ?? 0);
                                $cPct   = $target > 0 ? round(($actual / $target) * 100) : 0;
                                [$sLabel, $sColor] = $statusLabels[$c['training_status'] ?? ''] ?? ['—', 'secondary'];
                            ?>
                            <tr>
                                <td>
                                    <a href="/cohorts/<?= $c['id'] ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($c['cohort_code']) ?></a>
                                    <div class="text-muted" style="font-size: .75rem;"><?= htmlspecialchars($c['name']) ?></div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="small"><?= htmlspecialchars($c['bootcamp_type'] ?? '—') ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-medium"><?= $actual ?>/<?= $target ?></div>
                                    <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto; border-radius: 3px;">
                                        <div class="progress-bar bg-<?= $cPct >= 80 ? 'success' : ($cPct >= 50 ? 'primary' : 'warning') ?>"
                                             style="width: <?= min(100, $cPct) ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $sColor ?>-subtle text-<?= $sColor ?> badge-status"><?= $sLabel ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox text-muted fs-1 d-block mb-2"></i>
                    <span class="text-muted">No hay cohortes registradas</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Info (collapsed) -->
<div class="row mt-3">
    <div class="col-12">
        <div class="text-center">
            <small class="text-muted">
                <i class="bi bi-code-slash me-1"></i>PHP <?= PHP_VERSION ?> &bull;
                <i class="bi bi-calendar3 me-1"></i><span id="dash-date">--/--/----</span> &bull;
                <i class="bi bi-clock me-1"></i><span id="dash-time">--:--:--</span>
            </small>
        </div>
    </div>
</div>

<script>
(function updateDashClock() {
    var now  = new Date();
    var pad  = function(n) { return String(n).padStart(2, '0'); };
    var day  = pad(now.getDate());
    var mon  = pad(now.getMonth() + 1);
    var year = now.getFullYear();
    var h    = pad(now.getHours());
    var m    = pad(now.getMinutes());
    var s    = pad(now.getSeconds());

    var dateEl = document.getElementById('dash-date');
    var timeEl = document.getElementById('dash-time');
    if (dateEl) dateEl.textContent = day + '/' + mon + '/' + year;
    if (timeEl) timeEl.textContent = h + ':' + m + ':' + s;

    setTimeout(updateDashClock, 1000);
})();
</script>