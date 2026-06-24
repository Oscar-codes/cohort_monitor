<?php use App\Core\Auth; ?>
<?php use App\Services\MarketingService; ?>

<?php
    $statusLabels = [
        'in_progress' => ['En progreso', 'success', '#16a34a'],
        'completed'   => ['Completadas', 'primary', '#2563eb'],
        'planned' => ['Planificadas', 'secondary', '#64748b'],
        'not_started' => ['Planificadas', 'secondary', '#64748b'],
    ];

    $roleLabels = [
        'admin'          => ['Admin', 'danger', 'bi-shield-check'],
        'admissions_b2b' => ['B2B', 'info', 'bi-building'],
        'admissions_b2c' => ['B2C', 'primary', 'bi-person-check'],
        'finance'        => ['Finanzas', 'success', 'bi-cash-stack'],
        'marketing'      => ['Marketing', 'warning', 'bi-megaphone'],
    ];

    [$roleLabel, $roleColor, $roleIcon] = $roleLabels[Auth::role()] ?? [ucfirst(Auth::role() ?? 'Usuario'), 'secondary', 'bi-person'];

    $totalCohorts       = (int) ($totalCohorts ?? 0);
    $activeCohorts      = (int) ($activeCohorts ?? 0);
    $completedCohorts   = (int) ($completedCohorts ?? 0);
    $notStartedCohorts  = (int) ($notStartedCohorts ?? 0);
    $totalAlerts        = (int) ($totalAlerts ?? 0);
    $totalTarget        = (int) ($totalTarget ?? 0);
    $totalAdmissions    = (int) ($totalAdmissions ?? 0);
    $totalB2bAdmissions = (int) ($totalB2bAdmissions ?? 0);
    $totalB2cAdmissions = (int) ($totalB2cAdmissions ?? 0);
    $admissionPct       = min(100, (float) ($admissionPct ?? 0));
    $remainingTarget    = max(0, $totalTarget - $totalAdmissions);
    $riskCommentCount   = count($riskComments ?? []);
    $riskStageCount     = count($atRiskStages ?? []);

    $statusBreakdown = $statusBreakdown ?? [];
    $statusChart = ['labels' => [], 'series' => [], 'colors' => []];
    foreach ($statusBreakdown as $key => $count) {
        [$label, , $hex] = $statusLabels[$key] ?? [ucfirst((string) $key), 'secondary', '#64748b'];
        $statusChart['labels'][] = $label;
        $statusChart['series'][] = (int) $count;
        $statusChart['colors'][] = $hex;
    }

    $typeRows = array_slice($byType ?? [], 0, 8, true);
    $typeChart = [
        'labels' => array_map(static fn($value) => (string) ($value ?: 'Sin tipo'), array_keys($typeRows)),
        'series' => array_map(static fn($value) => (int) $value, array_values($typeRows)),
    ];

    $dashboardChartData = [
        'admissions' => [
            'pct'       => $admissionPct,
            'target'    => $totalTarget,
            'current'   => $totalAdmissions,
            'b2b'       => $totalB2bAdmissions,
            'b2c'       => $totalB2cAdmissions,
            'remaining' => $remainingTarget,
        ],
        'status' => $statusChart,
        'types' => $typeChart,
        'sparklines' => [
            'total'     => [$notStartedCohorts, $activeCohorts, $completedCohorts],
            'active'    => [$notStartedCohorts, $activeCohorts],
            'completed' => [$activeCohorts, $completedCohorts],
            'alerts'    => [$riskStageCount, $riskCommentCount, $totalAlerts],
        ],
    ];
?>

<?php if (!empty($loadError)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-octagon"></i>
    <span><?= htmlspecialchars($loadError) ?></span>
</div>
<?php endif; ?>

<section class="dashboard-hero mb-4">
    <div class="dashboard-hero__content">
        <div>
            <div class="dashboard-eyebrow">
                <i class="bi bi-activity"></i>
                Vista ejecutiva
            </div>
            <h2 class="dashboard-hero__title">&iexcl;Hola, <?= e(explode(' ', Auth::user()['full_name'] ?? 'Usuario')[0]) ?>!</h2>
            <p class="dashboard-hero__copy">
                Resumen operativo de cohortes, admisiones y riesgos para <?= date('d/m/Y') ?>.
            </p>
        </div>
        <div class="dashboard-hero__meta">
            <span class="dashboard-role-pill text-<?= $roleColor ?>">
                <i class="bi <?= $roleIcon ?>"></i>
                <?= e($roleLabel) ?>
            </span>
            <span class="dashboard-health-pill <?= $totalAlerts > 0 ? 'is-warning' : 'is-ok' ?>">
                <i class="bi <?= $totalAlerts > 0 ? 'bi-exclamation-triangle' : 'bi-shield-check' ?>"></i>
                <?= $totalAlerts > 0 ? $totalAlerts . ' alertas activas' : 'Sin alertas activas' ?>
            </span>
        </div>
    </div>
</section>

<section class="row g-3 mb-4" aria-label="Indicadores principales">
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--primary">
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-people-fill"></i></span>
                <div>
                    <p class="metric-card__label">Total cohortes</p>
                    <h3 class="metric-card__value"><?= number_format($totalCohorts) ?></h3>
                </div>
            </div>
            <div id="kpiTotalSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span><?= number_format($notStartedCohorts) ?> sin iniciar</span>
                <span><?= number_format($activeCohorts) ?> activas</span>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--success">
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-play-circle-fill"></i></span>
                <div>
                    <p class="metric-card__label">En progreso</p>
                    <h3 class="metric-card__value"><?= number_format($activeCohorts) ?></h3>
                </div>
            </div>
            <div id="kpiActiveSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span>Operacion activa</span>
                <a href="/cohorts?cohort_status=in_progress">Ver cohortes</a>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--info">
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-check-circle-fill"></i></span>
                <div>
                    <p class="metric-card__label">Completadas</p>
                    <h3 class="metric-card__value"><?= number_format($completedCohorts) ?></h3>
                </div>
            </div>
            <div id="kpiCompletedSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span>Ciclos cerrados</span>
                <a href="/cohorts?cohort_status=completed">Ver historial</a>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--danger">
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
                <div>
                    <p class="metric-card__label">Alertas activas</p>
                    <h3 class="metric-card__value"><?= number_format($totalAlerts) ?></h3>
                </div>
            </div>
            <div id="kpiAlertsSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span><?= $riskStageCount ?> marketing</span>
                <a href="/alerts">Revisar</a>
            </div>
        </article>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12">
        <div class="app-panel">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-lightning-charge text-warning"></i> Acciones rapidas</h3>
                    <p class="app-panel__subtitle">Atajos principales segun permisos del usuario.</p>
                </div>
            </div>
            <div class="dashboard-actions">
                <?php if (Auth::canCreateCohort()): ?>
                <a href="/cohorts/create" class="dashboard-action">
                    <span class="dashboard-action__icon text-primary bg-primary-subtle"><i class="bi bi-plus-lg"></i></span>
                    <span>Nueva cohorte</span>
                </a>
                <?php endif; ?>
                <a href="/cohorts" class="dashboard-action">
                    <span class="dashboard-action__icon text-success bg-success-subtle"><i class="bi bi-list-ul"></i></span>
                    <span>Ver cohortes</span>
                </a>
                <a href="/alerts" class="dashboard-action">
                    <span class="dashboard-action__icon text-danger bg-danger-subtle"><i class="bi bi-exclamation-triangle"></i></span>
                    <span>Alertas</span>
                </a>
                <?php if (Auth::hasRole(['admin', 'marketing'])): ?>
                <a href="/marketing" class="dashboard-action">
                    <span class="dashboard-action__icon text-warning bg-warning-subtle"><i class="bi bi-megaphone"></i></span>
                    <span>Marketing</span>
                </a>
                <?php endif; ?>
                <a href="/reports" class="dashboard-action">
                    <span class="dashboard-action__icon text-info bg-info-subtle"><i class="bi bi-bar-chart"></i></span>
                    <span>Reportes</span>
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="/cohorts/import" class="dashboard-action">
                    <span class="dashboard-action__icon text-secondary bg-secondary-subtle"><i class="bi bi-cloud-arrow-up"></i></span>
                    <span>Importar</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-graph-up-arrow text-primary"></i> Progreso de admisiones</h3>
                    <p class="app-panel__subtitle">Avance global contra la meta total.</p>
                </div>
                <span class="status-pill status-pill--primary"><?= number_format($admissionPct, 1) ?>%</span>
            </div>
            <div class="dashboard-admissions-grid">
                <div>
                    <div class="dashboard-number-row">
                        <div>
                            <span class="dashboard-stat-label">Actuales</span>
                            <strong><?= number_format($totalAdmissions) ?></strong>
                        </div>
                        <div>
                            <span class="dashboard-stat-label">Meta</span>
                            <strong><?= number_format($totalTarget) ?></strong>
                        </div>
                        <div>
                            <span class="dashboard-stat-label">Pendiente</span>
                            <strong><?= number_format($remainingTarget) ?></strong>
                        </div>
                    </div>
                    <div class="dashboard-progress mt-3" role="progressbar" aria-valuenow="<?= $admissionPct ?>" aria-valuemin="0" aria-valuemax="100">
                        <span data-style-width="<?= $admissionPct ?>%"></span>
                    </div>
                    <div class="dashboard-segments mt-3">
                        <span><i class="bi bi-square-fill text-primary"></i> B2B <?= number_format($totalB2bAdmissions) ?></span>
                        <span><i class="bi bi-square-fill text-info"></i> B2C <?= number_format($totalB2cAdmissions) ?></span>
                    </div>
                </div>
                <div id="dashboardAdmissionsChart" class="dashboard-chart dashboard-chart--admissions"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-pie-chart text-info"></i> Estado de cohortes</h3>
                    <p class="app-panel__subtitle">Distribucion actual por estado.</p>
                </div>
            </div>
            <div id="dashboardStatusChart" class="dashboard-chart dashboard-chart--donut"></div>
            <div class="dashboard-status-list">
                <?php $statusTotal = array_sum($statusBreakdown) ?: 1; ?>
                <?php foreach ($statusBreakdown as $key => $count): ?>
                    <?php [$label, $color] = $statusLabels[$key] ?? [ucfirst($key), 'secondary', '#64748b']; ?>
                    <div class="dashboard-status-row">
                        <span><i class="bi bi-circle-fill text-<?= $color ?>"></i><?= htmlspecialchars($label) ?></span>
                        <strong><?= (int) $count ?> <small><?= round(((int) $count / $statusTotal) * 100) ?>%</small></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-5">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-layers text-secondary"></i> Bootcamps por tipo</h3>
                    <p class="app-panel__subtitle">Top <?= count($typeRows) ?> categorias con mas cohortes.</p>
                </div>
            </div>
            <?php if (!empty($typeRows)): ?>
                <div id="dashboardBootcampChart" class="dashboard-chart dashboard-chart--bar"></div>
            <?php else: ?>
                <div class="empty-state py-4">
                    <i class="bi bi-bar-chart empty-state-icon"></i>
                    <p class="empty-state-text mb-0">No hay tipos de bootcamp para graficar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-calendar-event text-primary"></i> Proximos inicios</h3>
                    <p class="app-panel__subtitle">Cohortes que inician en los proximos 30 dias.</p>
                </div>
                <a href="/cohorts?cohort_status=planned" class="btn btn-sm btn-outline-primary">Ver agenda</a>
            </div>
            <?php if (!empty($upcomingCohorts)): ?>
                <div class="dashboard-upcoming-list">
                    <?php foreach (array_slice($upcomingCohorts, 0, 5) as $uc): ?>
                    <?php
                        $startTs = strtotime($uc['start_date']);
                        $daysLeft = $startTs ? max(0, (int) ceil(($startTs - strtotime('today')) / 86400)) : null;
                    ?>
                    <a href="/cohorts/<?= (int) $uc['id'] ?>" class="dashboard-upcoming-item">
                        <span class="dashboard-date-chip">
                            <strong><?= $startTs ? date('d', $startTs) : '--' ?></strong>
                            <small><?= $startTs ? date('M', $startTs) : '--' ?></small>
                        </span>
                        <span class="dashboard-upcoming-main">
                            <strong><?= htmlspecialchars($uc['cohort_code']) ?></strong>
                            <small><?= htmlspecialchars($uc['name'] ?? '') ?></small>
                        </span>
                        <span class="dashboard-upcoming-meta">
                            <?= $daysLeft !== null ? 'En ' . $daysLeft . ' dias' : 'Sin fecha' ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state py-4">
                    <i class="bi bi-calendar-check text-success empty-state-icon"></i>
                    <p class="empty-state-text mb-0">No hay inicios programados en los proximos 30 dias.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-exclamation-triangle text-danger"></i> Alertas recientes</h3>
                    <p class="app-panel__subtitle">Riesgos de marketing y comentarios marcados.</p>
                </div>
                <a href="/alerts" class="btn btn-sm btn-outline-danger">Ver todas</a>
            </div>
            <?php $hasAlerts = !empty($riskComments) || !empty($atRiskStages); ?>
            <?php if ($hasAlerts): ?>
            <div class="dashboard-alert-list">
                <?php foreach (($atRiskStages ?? []) as $s): ?>
                <a href="/cohorts/<?= (int) $s['cohort_id'] ?>/marketing" class="dashboard-alert-item">
                    <span class="dashboard-alert-dot is-warning"></span>
                    <span>
                        <strong><?= htmlspecialchars($s['cohort_code']) ?></strong>
                        <small>Mkt: <?= htmlspecialchars(MarketingService::STAGE_LABELS[$s['stage_name']] ?? $s['stage_name']) ?> en riesgo</small>
                    </span>
                    <time><?= date('d/m', strtotime($s['updated_at'])) ?></time>
                </a>
                <?php endforeach; ?>
                <?php foreach (($riskComments ?? []) as $rc): ?>
                <a href="/cohorts/<?= (int) $rc['cohort_id'] ?>" class="dashboard-alert-item">
                    <span class="dashboard-alert-dot is-danger"></span>
                    <span>
                        <strong><?= htmlspecialchars($rc['cohort_code']) ?></strong>
                        <small><?= htmlspecialchars($rc['body']) ?></small>
                    </span>
                    <time><?= date('d/m', strtotime($rc['created_at'])) ?></time>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state py-4">
                <i class="bi bi-shield-check text-success empty-state-icon"></i>
                <p class="empty-state-text mb-0">Sin alertas activas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="app-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h3 class="app-panel__title"><i class="bi bi-clock-history text-primary"></i> Cohortes recientes</h3>
                    <p class="app-panel__subtitle">Ultimos registros y avance de admisiones.</p>
                </div>
                <a href="/cohorts" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <?php if (!empty($recentCohorts)): ?>
            <div class="table-responsive dashboard-table-wrap">
                <table class="table table-hover align-middle mb-0 dashboard-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th class="d-none d-md-table-cell">Tipo</th>
                            <th class="text-center">Inscritos</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentCohorts as $c): ?>
                        <?php
                            $target = (int) ($c['total_admission_target'] ?? 0);
                            $actual = (int) ($c['b2b_admissions'] ?? 0) + (int) ($c['b2c_admissions'] ?? 0);
                            $cPct   = $target > 0 ? min(100, round(($actual / $target) * 100)) : 0;
                            [$sLabel, $sColor] = $statusLabels[$c['training_status'] ?? ''] ?? ['Sin estado', 'secondary', '#64748b'];
                        ?>
                        <tr>
                            <td>
                                <a href="/cohorts/<?= (int) $c['id'] ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($c['cohort_code']) ?></a>
                                <div class="text-muted text-truncate dashboard-recent-name"><?= htmlspecialchars($c['name']) ?></div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="small"><?= htmlspecialchars($c['bootcamp_type'] ?? 'Sin tipo') ?></span>
                            </td>
                            <td class="text-center">
                                <div class="small fw-medium"><?= $actual ?>/<?= $target ?></div>
                                <div class="dashboard-mini-progress mx-auto mt-1"><span data-style-width="<?= $cPct ?>%"></span></div>
                            </td>
                            <td class="text-center">
                                <span class="status-pill status-pill--<?= $sColor ?>"><?= htmlspecialchars($sLabel) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state py-4">
                <i class="bi bi-inbox text-muted empty-state-icon"></i>
                <p class="empty-state-text mb-0">No hay cohortes registradas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="dashboard-system-note">
    <i class="bi bi-code-slash"></i> PHP <?= PHP_VERSION ?>
    <span>&bull;</span>
    <i class="bi bi-calendar3"></i> <span id="dash-date">--/--/----</span>
    <span>&bull;</span>
    <i class="bi bi-clock"></i> <span id="dash-time">--:--:--</span>
</div>

<textarea id="cohort-dashboard-data" class="d-none"><?= htmlspecialchars(json_encode($dashboardChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></textarea>
