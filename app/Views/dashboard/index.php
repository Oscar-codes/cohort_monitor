<?php use App\Core\Auth; ?>
<?php use App\Services\MarketingService; ?>
<?php use App\Services\CohortStatus; ?>

<?php
    // DATA-001: labels come from CohortStatus; only the chart colors live here.
    $statusColors = [
        CohortStatus::NOT_STARTED => ['secondary', '#64748b'],
        CohortStatus::IN_PROGRESS => ['success', '#16a34a'],
        CohortStatus::COMPLETED   => ['primary', '#2563eb'],
        CohortStatus::CANCELLED   => ['danger', '#dc2626'],
        CohortStatus::NO_DATES    => ['warning', '#d97706'],
    ];
    $statusLabels = [];
    foreach (CohortStatus::LABELS as $statusKey => $statusLabel) {
        $statusLabels[$statusKey] = [$statusLabel, ...$statusColors[$statusKey]];
    }

    $roleLabels = [
        'admin'          => ['Admin', 'danger', 'bi-shield-check'],
        'admissions_b2b' => ['Admisiones B2B', 'info', 'bi-building'],
        'admissions_b2c' => ['Admisiones B2C', 'primary', 'bi-person-check'],
        'finance'        => ['Finanzas', 'success', 'bi-cash-stack'],
        'marketing'      => ['Marketing', 'warning', 'bi-megaphone'],
    ];

    [$roleLabel, $roleColor, $roleIcon] = $roleLabels[Auth::role()] ?? [ucfirst(Auth::role() ?? 'Usuario'), 'secondary', 'bi-person'];

    $totalCohorts       = (int) ($totalCohorts ?? 0);
    $activeCohorts      = (int) ($activeCohorts ?? 0);
    $completedCohorts   = (int) ($completedCohorts ?? 0);
    $plannedCohorts     = (int) ($plannedCohorts ?? 0);
    $totalAlerts        = (int) ($totalAlerts ?? 0);
    $totalTarget        = (int) ($totalTarget ?? 0);
    $totalAdmissions    = (int) ($totalAdmissions ?? 0);
    $totalB2bAdmissions = (int) ($totalB2bAdmissions ?? 0);
    $totalB2cAdmissions = (int) ($totalB2cAdmissions ?? 0);
    $admissionPct       = min(100, (float) ($admissionPct ?? 0));
    // DATA-003: cut-off date = El Salvador business date (same "today" as CohortStatus).
    $cutoffLabel        = date('d/m/Y', strtotime((string) ($cutoffDate ?? CohortStatus::today())));
    // DASH-004: "Completado" card = completed with end_date in the current year.
    $closedYear         = (string) ($closedYear ?? substr(CohortStatus::today(), 0, 4));
    $closedThisYear     = (int) ($closedThisYear ?? 0);
    // DASH-012: starts in (today, today + N]; the link uses the same filter.
    $upcomingDays       = (int) ($upcomingDays ?? 30);
    $upcomingCount      = (int) ($upcomingCount ?? 0);
    $upcomingUntilLabel = date('d/m/Y', strtotime((string) ($upcomingUntil ?? CohortStatus::todayPlus($upcomingDays))));
    $monthsEs           = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $remainingTarget    = max(0, $totalTarget - $totalAdmissions);
    // DASH-007: admissions block by calendar year of start_date (default: current year).
    $admissionsYear     = (string) ($admissionsYear ?? substr(CohortStatus::today(), 0, 4));
    $admissionsYears    = $admissionsYears ?? [$admissionsYear];
    $admissionsCohorts  = (int) ($admissionsCohorts ?? 0);
    $withoutStartDate   = (int) ($withoutStartDate ?? 0);
    $admissionShares    = $admissionShares ?? \App\Services\CohortService::admissionShares($totalB2bAdmissions, $totalB2cAdmissions, $totalTarget);
    $admissionsEmpty    = $admissionShares['base'] === 0;
    $admissionsOverTarget = (int) ($admissionsOverTarget ?? 0);
    $admissionBars      = [];
    foreach (['b2b' => 'B2B', 'b2c' => 'B2C', 'pending' => 'Pendiente'] as $barKey => $barLabel) {
        $barPct = (float) $admissionShares['pct'][$barKey];
        $barCount = (int) $admissionShares['counts'][$barKey];
        $admissionBars[] = [
            'key'   => $barKey,
            'label' => $barLabel,
            'pct'   => $barPct,
            'count' => $barCount,
            'text'  => number_format($barPct, 1) . ' % (' . number_format($barCount) . ')',
        ];
    }
    $riskCommentCount   = count($riskComments ?? []);
    $riskStageCount     = count($atRiskStages ?? []);

    $statusBreakdown = $statusBreakdown ?? [];
    $statusChart = ['labels' => [], 'series' => [], 'colors' => []];
    foreach ($statusBreakdown as $key => $count) {
        [$label, , $hex] = $statusLabels[$key] ?? [CohortStatus::label((string) $key), 'secondary', '#64748b'];
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
            'year'      => $admissionsYear,
            'empty'     => $admissionsEmpty,
            'bars'      => $admissionBars,
        ],
        'status' => $statusChart,
        'types' => $typeChart,
        'sparklines' => [
            'total'     => [$plannedCohorts, $activeCohorts, $completedCohorts],
            'active'    => [$plannedCohorts, $activeCohorts],
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
                Resumen operativo de cohortes, admisiones y riesgos para <?= $cutoffLabel ?>.
            </p>
            <p class="dashboard-hero__copy small mb-0" data-dashboard-scope>
                <i class="bi bi-funnel me-1" aria-hidden="true"></i>Totales y estados: todas las cohortes (incluye canceladas y sin fechas), corte <?= $cutoffLabel ?> &middot; Admisiones: cohortes que inician en <?= htmlspecialchars($admissionsYear) ?>
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
        <article class="metric-card metric-card--primary" data-kodigo-reveal>
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-people-fill"></i></span>
                <div>
                    <p class="metric-card__label">Total cohortes</p>
                    <h3 class="metric-card__value"><?= number_format($totalCohorts) ?></h3>
                </div>
            </div>
            <div id="kpiTotalSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span>No iniciado: <?= number_format($plannedCohorts) ?></span>
                <span>En progreso: <?= number_format($activeCohorts) ?></span>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--success" data-kodigo-reveal>
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-play-circle-fill"></i></span>
                <div>
                    <p class="metric-card__label">En progreso</p>
                    <h3 class="metric-card__value" data-dashboard-card="in_progress"><?= number_format($activeCohorts) ?></h3>
                </div>
            </div>
            <div id="kpiActiveSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span>Corte <?= $cutoffLabel ?></span>
                <a href="/cohorts?cohort_status=in_progress">Ver cohortes</a>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--info" data-kodigo-reveal>
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-check-circle-fill"></i></span>
                <div>
                    <p class="metric-card__label">Completado</p>
                    <h3 class="metric-card__value" data-dashboard-card="closed_year"><?= number_format($closedThisYear) ?></h3>
                </div>
            </div>
            <div id="kpiCompletedSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="metric-card__footer">
                <span>Cerradas en <?= htmlspecialchars($closedYear) ?></span>
                <a href="/cohorts?closed_year=<?= urlencode($closedYear) ?>">Ver historial</a>
            </div>
        </article>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <article class="metric-card metric-card--danger" data-kodigo-reveal>
            <div class="metric-card__body">
                <span class="metric-card__icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
                <div>
                    <p class="metric-card__label">Alertas activas</p>
                    <h3 class="metric-card__value"><?= number_format($totalAlerts) ?></h3>
                </div>
            </div>
            <div id="kpiAlertsSparkline" class="metric-card__sparkline" aria-hidden="true"></div>
            <div class="visually-hidden">Resumen: No iniciado <?= number_format($plannedCohorts) ?>, En progreso <?= number_format($activeCohorts) ?>, Completado <?= number_format($completedCohorts) ?> y <?= number_format($totalAlerts) ?> alertas activas.</div>
            <div class="metric-card__footer">
                <span><?= $riskStageCount ?> marketing</span>
                <a href="/alerts">Revisar</a>
            </div>
        </article>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-lightning-charge text-warning"></i> Acciones rapidas</h3>
                    <p class="kodigo-card__subtitle">Atajos principales segun permisos del usuario.</p>
                </div>
            </div>
            <div class="kodigo-card__body">
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
                <?php /* Oculto temporalmente: MarketingStageRepository roto, pendiente de fix.
                if (Auth::hasRole(['admin', 'marketing'])): ?>
                <a href="/marketing" class="dashboard-action">
                    <span class="dashboard-action__icon text-warning bg-warning-subtle"><i class="bi bi-megaphone"></i></span>
                    <span>Marketing</span>
                </a>
                <?php endif; */ ?>
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
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-graph-up-arrow text-primary"></i> Progreso de admisiones</h3>
                    <p class="kodigo-card__subtitle">Avance contra la meta de las cohortes que inician en <?= htmlspecialchars($admissionsYear) ?>.</p>
                </div>
                <div class="dashboard-year-filter">
                    <form method="get" action="/" class="dashboard-year-filter__form" data-dashboard-year-form>
                        <label for="admissionsYear" class="visually-hidden">Año de inicio</label>
                        <select class="form-select form-select-sm" id="admissionsYear" name="year" aria-label="Año de inicio de las cohortes">
                            <?php foreach ($admissionsYears as $yearOption): ?>
                                <option value="<?= htmlspecialchars((string) $yearOption) ?>" <?= (string) $yearOption === $admissionsYear ? 'selected' : '' ?>><?= htmlspecialchars((string) $yearOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary" data-dashboard-year-submit>Ver</button>
                    </form>
                    <span class="kodigo-pill" data-tone="info" data-dashboard-admissions-pct><span class="kodigo-pill__dot" aria-hidden="true"></span><?= number_format($admissionPct, 1) ?>%<?= $admissionsOverTarget > 0 ? ' &middot; +' . number_format($admissionsOverTarget) . ' sobre la meta' : '' ?></span>
                </div>
            </div>
            <div class="kodigo-card__body">
                <?php if ($admissionsEmpty): ?>
                <div class="kodigo-empty py-4" data-dashboard-admissions-empty>
                    <i class="bi bi-bar-chart kodigo-empty__icon" aria-hidden="true"></i>
                    <?php if ($admissionsCohorts === 0): ?>
                        <h4 class="kodigo-empty__title">Sin cohortes en <?= htmlspecialchars($admissionsYear) ?></h4>
                        <p class="kodigo-empty__text">Ninguna cohorte inicia en <?= htmlspecialchars($admissionsYear) ?>. Elige otro año.</p>
                    <?php else: ?>
                        <h4 class="kodigo-empty__title">Sin meta de admisiones en <?= htmlspecialchars($admissionsYear) ?></h4>
                        <p class="kodigo-empty__text"><?= number_format($admissionsCohorts) ?> cohortes inician en <?= htmlspecialchars($admissionsYear) ?> sin meta de admisiones (<?= number_format($totalAdmissions) ?> inscritos); no hay porcentajes que calcular.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
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
                        <?php $barIcons = ['b2b' => 'text-primary', 'b2c' => 'text-info', 'pending' => 'text-secondary']; ?>
                        <?php foreach ($admissionBars as $bar): ?>
                            <span data-admissions-bar="<?= $bar['key'] ?>"><i class="bi bi-square-fill <?= $barIcons[$bar['key']] ?>"></i> <?= $bar['label'] ?> <?= htmlspecialchars($bar['text']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($admissionShares['over_admitted']): ?>
                        <p class="small text-muted mt-2 mb-0" data-dashboard-over-admitted>Los inscritos superan la meta (<?= number_format($totalAdmissions) ?> de <?= number_format($totalTarget) ?>): Pendiente es 0 y los porcentajes se calculan sobre los inscritos.</p>
                    <?php endif; ?>
                </div>
                <div id="dashboardAdmissionsChart" class="dashboard-chart dashboard-chart--admissions" role="img" aria-label="Grafica de admisiones <?= htmlspecialchars($admissionsYear) ?>: <?= htmlspecialchars(implode(', ', array_map(static fn(array $b): string => $b['label'] . ' ' . $b['text'], $admissionBars))) ?>"></div>
                </div>
                <?php endif; ?>
                <?php if ($withoutStartDate > 0): ?>
                    <p class="small text-muted mt-3 mb-0" data-dashboard-no-start><i class="bi bi-info-circle me-1" aria-hidden="true"></i><?= number_format($withoutStartDate) ?> <?= $withoutStartDate === 1 ? 'cohorte sin fecha de inicio no se incluye' : 'cohortes sin fecha de inicio no se incluyen' ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-pie-chart text-info"></i> Estado de cohortes</h3>
                    <p class="kodigo-card__subtitle">Distribucion actual por estado.</p>
                </div>
            </div>
            <div class="kodigo-card__body">
                <div id="dashboardStatusChart" class="dashboard-chart dashboard-chart--donut" role="img" aria-label="Grafica de dona con distribucion de cohortes por estado"></div>
            <div class="dashboard-status-list">
                <?php $statusTotal = array_sum($statusBreakdown) ?: 1; ?>
                <?php foreach ($statusBreakdown as $key => $count): ?>
                    <?php [$label, $color] = $statusLabels[$key] ?? [CohortStatus::label((string) $key), 'secondary', '#64748b']; ?>
                    <div class="dashboard-status-row">
                        <span><i class="bi bi-circle-fill text-<?= $color ?>"></i><?= htmlspecialchars($label) ?></span>
                        <strong><?= (int) $count ?> <small><?= round(((int) $count / $statusTotal) * 100) ?>%</small></strong>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-5">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-layers text-secondary"></i> Cohortes por tipo</h3>
                    <p class="kodigo-card__subtitle">Top <?= count($typeRows) ?> categorias con mas cohortes.</p>
                </div>
            </div>
            <div class="kodigo-card__body">
                <?php if (!empty($typeRows)): ?>
                    <div id="dashboardBootcampChart" class="dashboard-chart dashboard-chart--bar" role="img" aria-label="Grafica de barras con cohortes agrupadas por tipo de bootcamp"></div>
                <?php else: ?>
                    <div class="kodigo-empty py-4">
                        <i class="bi bi-bar-chart kodigo-empty__icon" aria-hidden="true"></i>
                        <h4 class="kodigo-empty__title">Sin datos por tipo</h4>
                        <p class="kodigo-empty__text">No hay cohortes suficientes para graficar la distribucion por tipo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-calendar-event text-primary"></i> Proximos inicios</h3>
                    <p class="kodigo-card__subtitle"><span data-dashboard-card="upcoming"><?= number_format($upcomingCount) ?></span> cohortes inician despues de hoy y hasta el <?= $upcomingUntilLabel ?>.</p>
                </div>
                <a href="/cohorts?upcoming_days=<?= $upcomingDays ?>" class="btn btn-sm btn-outline-primary">Ver agenda</a>
            </div>
            <div class="kodigo-card__body">
                <?php if (!empty($upcomingCohorts)): ?>
                <div class="dashboard-upcoming-list">
                    <?php foreach (array_slice($upcomingCohorts, 0, 5) as $uc): ?>
                    <?php
                        // Days left against the El Salvador business date (same "today" as the rule).
                        $startTs = strtotime($uc['start_date']);
                        $daysLeft = $startTs ? (new DateTimeImmutable(CohortStatus::today()))->diff(new DateTimeImmutable(substr((string) $uc['start_date'], 0, 10)))->days : null;
                    ?>
                    <a href="/cohorts/<?= (int) $uc['id'] ?>" class="dashboard-upcoming-item" data-upcoming-code="<?= htmlspecialchars($uc['cohort_code']) ?>">
                        <span class="dashboard-date-chip">
                            <strong><?= $startTs ? date('d', $startTs) : '--' ?></strong>
                            <small><?= $startTs ? $monthsEs[(int) date('n', $startTs)] : '--' ?></small>
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
                <div class="kodigo-empty py-4">
                    <i class="bi bi-calendar-check text-success kodigo-empty__icon" aria-hidden="true"></i>
                    <p class="kodigo-empty__text mb-0">No hay inicios programados en los proximos 30 dias.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-exclamation-triangle text-danger"></i> Alertas recientes</h3>
                    <p class="kodigo-card__subtitle">Riesgos de marketing y comentarios marcados.</p>
                </div>
                <a href="/alerts" class="btn btn-sm btn-outline-danger">Ver todas</a>
            </div>
            <div class="kodigo-card__body">
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
                <div class="kodigo-empty py-4">
                    <i class="bi bi-shield-check text-success kodigo-empty__icon" aria-hidden="true"></i>
                    <p class="kodigo-empty__text mb-0">Sin alertas activas.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="kodigo-card h-100" data-elevation="1">
            <div class="kodigo-card__header">
                <div>
                    <h3 class="kodigo-card__title"><i class="bi bi-clock-history text-primary"></i> Cohortes recientes</h3>
                    <p class="kodigo-card__subtitle">Ultimos registros y avance de admisiones.</p>
                </div>
                <a href="/cohorts" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="kodigo-card__body">
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
                            $sStatus = (string) ($c['effective_status'] ?? CohortStatus::forRow($c));
                            [$sLabel, $sColor] = $statusLabels[$sStatus] ?? [CohortStatus::label($sStatus), 'secondary', '#64748b'];
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
                                <span class="kodigo-pill" data-tone="<?= htmlspecialchars(CohortStatus::tone($sStatus)) ?>"><?= htmlspecialchars($sLabel) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="kodigo-empty py-4">
                    <i class="bi bi-inbox text-muted kodigo-empty__icon" aria-hidden="true"></i>
                    <p class="kodigo-empty__text mb-0">No hay cohortes registradas.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="dashboard-system-note">
    <i class="bi bi-calendar3"></i> <span id="dash-date">--/--/----</span>
    <span>&bull;</span>
    <i class="bi bi-clock"></i> <span id="dash-time">--:--:--</span>
</div>

<textarea id="cohort-dashboard-data" class="d-none" aria-hidden="true"><?= htmlspecialchars(json_encode($dashboardChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></textarea>

<details class="visually-hidden">
    <summary>Resumen ejecutivo de indicadores</summary>
    <ul>
        <li>Total cohortes: <?= number_format($totalCohorts) ?></li>
        <li>En progreso: <?= number_format($activeCohorts) ?></li>
        <li>Completado (cerradas en <?= htmlspecialchars($closedYear) ?>): <?= number_format($closedThisYear) ?></li>
        <li>Alertas activas: <?= number_format($totalAlerts) ?></li>
        <?php foreach ($admissionBars as $bar): ?>
            <li><?= $bar['label'] ?> (inicio en <?= htmlspecialchars($admissionsYear) ?>): <?= htmlspecialchars($admissionsEmpty ? number_format($bar['count']) : $bar['text']) ?></li>
        <?php endforeach; ?>
        <li>Cumplimiento <?= htmlspecialchars($admissionsYear) ?>: <?= number_format($admissionPct, 1) ?>%</li>
    </ul>
</details>
<details class="visually-hidden">
    <summary>Distribucion por estado de cohortes</summary>
    <table>
        <caption>Cohortes por estado</caption>
        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($statusBreakdown as $key => $count): ?>
                <?php [$label] = $statusLabels[$key] ?? [CohortStatus::label((string) $key), 'secondary']; ?>
                <tr><td><?= htmlspecialchars($label) ?></td><td><?= (int) $count ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</details>
<details class="visually-hidden">
    <summary>Cohortes por tipo de bootcamp</summary>
    <table>
        <caption>Top cohortes por tipo</caption>
        <thead><tr><th>Tipo</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($typeRows as $typeName => $typeCount): ?>
                <tr><td><?= htmlspecialchars((string) ($typeName ?: 'Sin tipo')) ?></td><td><?= (int) $typeCount ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</details>
