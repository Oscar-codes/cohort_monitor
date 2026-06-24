<?php
/**
 * Coach Calendar View
 *
 * Timeline and list views for active coaches with cohorts in progress.
 */

if (!function_exists('coachPhaseBadge')) {
    function coachPhaseBadge(string $phase): string
    {
        $map = [
            'early' => ['bg-info-subtle text-info', 'Inicio'],
            'mid' => ['bg-primary-subtle text-primary', 'Medio'],
            'advanced' => ['bg-warning-subtle text-warning', 'Avanzado'],
            'finishing' => ['bg-danger-subtle text-danger', 'Finalizando'],
        ];
        [$class, $label] = $map[$phase] ?? ['bg-secondary-subtle text-secondary', ucfirst($phase)];

        return '<span class="badge badge-status ' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('coachProgressColor')) {
    function coachProgressColor(int $pct): string
    {
        if ($pct <= 25) {
            return 'bg-info';
        }
        if ($pct <= 50) {
            return 'bg-primary';
        }
        if ($pct <= 75) {
            return 'bg-warning';
        }
        return 'bg-danger';
    }
}

if (!function_exists('coachCalendarDate')) {
    function coachCalendarDate(?string $date): string
    {
        return $date ? date('d M Y', strtotime($date)) : 'Sin fecha';
    }
}

if (!function_exists('coachInitial')) {
    function coachInitial(string $name): string
    {
        $name = trim($name);
        return strtoupper(substr($name !== '' ? $name : 'C', 0, 1));
    }
}

$filters = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$querySuffix = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '';
$stats = $stats ?? ['total_coaches' => 0, 'total_cohorts' => 0, 'avg_completion' => 0, 'finishing_soon' => 0];
$entries = $entries ?? [];
$groupedByCoach = $groupedByCoach ?? [];

$todayStr = date('Y-m-d');
$todayTs = strtotime($todayStr);

$timelineMin = null;
$timelineMax = null;
foreach ($entries as $e) {
    $s = $e['start_date'];
    $d = $e['end_date'];
    if (!$timelineMin || $s < $timelineMin) {
        $timelineMin = $s;
    }
    if (!$timelineMax || $d > $timelineMax) {
        $timelineMax = $d;
    }
}

if (!$timelineMin) {
    $timelineMin = date('Y-m-d', strtotime('-30 days'));
}
if (!$timelineMax) {
    $timelineMax = date('Y-m-d', strtotime('+30 days'));
}

$tlStartTs = strtotime($timelineMin);
$tlEndTs = strtotime($timelineMax);
$tlSpanDays = max(1, (int) round(($tlEndTs - $tlStartTs) / 86400));

$months = [];
$cursor = strtotime(date('Y-m-01', $tlStartTs));
while ($cursor <= $tlEndTs) {
    $mLabel = ucfirst(date('M Y', $cursor));
    $mStart = max($tlStartTs, $cursor);
    $mEnd = min($tlEndTs, strtotime(date('Y-m-t', $cursor)));
    $mLeft = (($mStart - $tlStartTs) / 86400) / $tlSpanDays * 100;
    $mWidth = max(0, (($mEnd - $mStart) / 86400 + 1) / $tlSpanDays * 100);
    $months[] = ['label' => $mLabel, 'left' => $mLeft, 'width' => $mWidth];
    $cursor = strtotime('+1 month', strtotime(date('Y-m-01', $cursor)));
}

$todayOffset = min(100, max(0, ($todayTs - $tlStartTs) / 86400) / $tlSpanDays * 100);

$phaseBarColors = [
    'early' => 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
    'mid' => 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
    'advanced' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    'finishing' => 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
];
?>

<?php if (!empty($loadError)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($loadError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<section class="coach-calendar-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-calendar-range"></i>
            Calendario operativo
        </span>
        <h1>Coaches activos</h1>
        <p>Seguimiento de carga, avance y cohortes proximas a cierre.</p>
    </div>
    <div class="coach-calendar-hero__actions">
        <div class="btn-group" role="group" aria-label="Cambiar vista">
            <button type="button" class="btn btn-light btn-sm active" id="btn-view-timeline" data-view="timeline">
                <i class="bi bi-bar-chart-steps me-1"></i><span>Timeline</span>
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="btn-view-list" data-view="list">
                <i class="bi bi-list-ul me-1"></i><span>Lista</span>
            </button>
        </div>
        <?php if (!empty($activeFilters)): ?>
            <a href="/coaches" class="btn btn-outline-light btn-sm">
                <i class="bi bi-x-circle me-1"></i> Limpiar filtros
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="app-panel coach-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-funnel"></i> Filtros</h2>
            <p class="app-panel__subtitle">Segmenta por coach o tipo de cohorte sin perder el modo de vista.</p>
        </div>
    </div>
    <form method="GET" action="/coaches" class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
            <label for="coach" class="form-label">Coach</label>
            <select class="form-select" id="coach" name="coach">
                <option value="">Todos los coaches</option>
                <?php foreach (($coachNames ?? []) as $name): ?>
                    <option value="<?= htmlspecialchars($name) ?>" <?= (($filters['coach'] ?? '') === $name) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label for="bootcamp_type" class="form-label">Cohorte</label>
            <select class="form-select" id="bootcamp_type" name="bootcamp_type">
                <option value="">Todos</option>
                <?php foreach (($bootcampTypes ?? []) as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
                <i class="bi bi-search me-1"></i> Filtrar
            </button>
            <a href="/coaches" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</section>

<div class="coach-calendar-summary mb-4">
    <article class="coach-calendar-kpi">
        <span class="coach-calendar-kpi__icon is-primary"><i class="bi bi-person-video3"></i></span>
        <div>
            <p>Coaches activos</p>
            <strong><?= htmlspecialchars((string) $stats['total_coaches']) ?></strong>
            <small>Con cohortes en progreso</small>
        </div>
    </article>
    <article class="coach-calendar-kpi">
        <span class="coach-calendar-kpi__icon is-success"><i class="bi bi-collection"></i></span>
        <div>
            <p>Cohortes</p>
            <strong><?= htmlspecialchars((string) $stats['total_cohorts']) ?></strong>
            <small>Entre 1% y 99%</small>
        </div>
    </article>
    <article class="coach-calendar-kpi">
        <span class="coach-calendar-kpi__icon is-info"><i class="bi bi-speedometer2"></i></span>
        <div>
            <p>Avance promedio</p>
            <strong><?= htmlspecialchars((string) $stats['avg_completion']) ?>%</strong>
            <small>Progreso calendario</small>
        </div>
    </article>
    <article class="coach-calendar-kpi">
        <span class="coach-calendar-kpi__icon is-danger"><i class="bi bi-hourglass-bottom"></i></span>
        <div>
            <p>Finalizando</p>
            <strong><?= htmlspecialchars((string) $stats['finishing_soon']) ?></strong>
            <small>En fase avanzada final</small>
        </div>
    </article>
</div>

<?php if (empty($entries)): ?>
    <section class="app-panel">
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
            <h5 class="empty-state-title">Sin coaches activos</h5>
            <p class="empty-state-text">No hay coaches con cohortes en progreso activo en este momento.</p>
            <?php if (!empty($activeFilters)): ?>
                <a href="/coaches" class="btn btn-outline-secondary btn-sm">Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>

<section id="view-timeline" class="app-panel coach-calendar-board">
    <div class="app-panel__header">
        <div>
            <h2 class="app-panel__title"><i class="bi bi-bar-chart-steps"></i> Timeline de carga</h2>
            <p class="app-panel__subtitle"><?= (int) $stats['total_coaches'] ?> coaches - <?= (int) $stats['total_cohorts'] ?> cohortes activas</p>
        </div>
        <div class="coach-calendar-range">
            <?= htmlspecialchars(coachCalendarDate($timelineMin)) ?> - <?= htmlspecialchars(coachCalendarDate($timelineMax)) ?>
        </div>
    </div>

    <div class="coach-gantt-shell">
        <div class="gantt-wrapper coach-gantt-modern">
            <div class="gantt-header">
                <div class="gantt-label-col coach-gantt-label"><small>Coach / Cohorte</small></div>
                <div class="gantt-timeline-col position-relative">
                    <?php foreach ($months as $m): ?>
                        <div class="gantt-month" data-style-left="<?= $m['left'] ?>%" data-style-width="<?= $m['width'] ?>%"><?= htmlspecialchars($m['label']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($groupedByCoach as $coachName => $coachEntries): ?>
                <?php
                $coachAvg = (int) round(array_sum(array_column($coachEntries, 'pct_completion')) / max(1, count($coachEntries)));
                ?>
                <div class="coach-gantt-group-header">
                    <div class="gantt-label-col coach-gantt-label">
                        <div class="coach-row-person">
                            <span class="coach-avatar-sm"><?= htmlspecialchars(coachInitial($coachName)) ?></span>
                            <div>
                                <strong><?= htmlspecialchars($coachName) ?></strong>
                                <small><?= count($coachEntries) ?> cohorte<?= count($coachEntries) > 1 ? 's' : '' ?> - <?= $coachAvg ?>% promedio</small>
                            </div>
                        </div>
                    </div>
                    <div class="gantt-timeline-col position-relative">
                        <div class="gantt-today-line" data-style-left="<?= $todayOffset ?>%"></div>
                    </div>
                </div>

                <?php foreach ($coachEntries as $ce): ?>
                    <?php
                    $barStart = strtotime($ce['start_date']);
                    $barEnd = strtotime($ce['end_date']);
                    $barLeft = max(0, ($barStart - $tlStartTs) / 86400) / $tlSpanDays * 100;
                    $barWidth = max(1, ($barEnd - $barStart) / 86400) / $tlSpanDays * 100;
                    $barWidth = min($barWidth, 100 - $barLeft);
                    $barBg = $phaseBarColors[$ce['phase_status']] ?? $phaseBarColors['mid'];
                    $fillPct = (int) $ce['pct_completion'];
                    ?>
                    <div class="gantt-row">
                        <div class="gantt-label-col coach-gantt-label">
                            <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>" class="coach-gantt-code">
                                <?= htmlspecialchars($ce['cohort_code']) ?>
                            </a>
                            <div class="coach-gantt-meta">
                                <?= coachPhaseBadge($ce['phase_status']) ?>
                                <span><?= $fillPct ?>%</span>
                            </div>
                        </div>
                        <div class="gantt-timeline-col position-relative">
                            <div class="gantt-today-line" data-style-left="<?= $todayOffset ?>%"></div>
                            <a class="coach-gantt-bar"
                               href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>"
                               data-style-left="<?= $barLeft ?>%"
                               data-style-width="<?= $barWidth ?>%"
                               data-style-background="<?= $barBg ?>"
                               data-bs-toggle="tooltip"
                               data-bs-html="true"
                               title="<strong><?= htmlspecialchars($ce['cohort_code']) ?></strong><br><?= htmlspecialchars($ce['name']) ?><br><?= htmlspecialchars(coachCalendarDate($ce['start_date'])) ?> - <?= htmlspecialchars(coachCalendarDate($ce['end_date'])) ?><br>Progreso: <?= $fillPct ?>% - <?= (int) $ce['days_remaining'] ?> dias restantes">
                                <span class="coach-gantt-bar-fill" data-style-width="<?= $fillPct ?>%"></span>
                                <span class="gantt-bar-label"><?= htmlspecialchars($ce['cohort_code']) ?> - <?= $fillPct ?>%</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="coach-calendar-legend">
        <small><span class="coach-legend-dot is-early"></span> Inicio</small>
        <small><span class="coach-legend-dot is-mid"></span> Medio</small>
        <small><span class="coach-legend-dot is-advanced"></span> Avanzado</small>
        <small><span class="coach-legend-dot is-finishing"></span> Finalizando</small>
        <small><span class="coach-legend-line"></span> Hoy</small>
    </div>
</section>

<section id="view-list" class="d-none">
    <?php foreach ($groupedByCoach as $coachName => $coachEntries): ?>
        <?php
        $coachAvg = (int) round(array_sum(array_column($coachEntries, 'pct_completion')) / max(1, count($coachEntries)));
        ?>
        <article class="app-panel coach-list-panel mb-3">
            <div class="coach-list-panel__header">
                <div class="coach-row-person">
                    <span class="coach-avatar-sm"><?= htmlspecialchars(coachInitial($coachName)) ?></span>
                    <div>
                        <strong><?= htmlspecialchars($coachName) ?></strong>
                        <small><?= count($coachEntries) ?> cohorte<?= count($coachEntries) > 1 ? 's' : '' ?> activa<?= count($coachEntries) > 1 ? 's' : '' ?></small>
                    </div>
                </div>
                <div class="coach-list-panel__avg">
                    <span><?= $coachAvg ?>%</span>
                    <div class="dashboard-mini-progress"><span data-style-width="<?= $coachAvg ?>%"></span></div>
                </div>
            </div>

            <div class="table-responsive coach-list-table">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cohorte</th>
                            <th class="d-none d-md-table-cell">Bootcamp</th>
                            <th>Progreso</th>
                            <th class="d-none d-lg-table-cell">Inicio</th>
                            <th class="d-none d-lg-table-cell">Fin</th>
                            <th class="d-none d-md-table-cell text-center">Restante</th>
                            <th>Fase</th>
                            <th class="d-none d-xl-table-cell">Horario</th>
                            <th class="text-end">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coachEntries as $ce): ?>
                            <tr>
                                <td>
                                    <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($ce['cohort_code']) ?>
                                    </a>
                                    <div class="text-muted small"><?= htmlspecialchars($ce['name']) ?></div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php if (!empty($ce['bootcamp_type'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($ce['bootcamp_type']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin tipo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="coach-progress-col">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 coach-progress-track">
                                            <div class="progress-bar <?= coachProgressColor((int) $ce['pct_completion']) ?>"
                                                 role="progressbar"
                                                 data-style-width="<?= (int) $ce['pct_completion'] ?>%"
                                                 aria-valuenow="<?= (int) $ce['pct_completion'] ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <small class="fw-semibold text-nowrap"><?= (int) $ce['pct_completion'] ?>%</small>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= htmlspecialchars(coachCalendarDate($ce['start_date'])) ?></small></td>
                                <td class="d-none d-lg-table-cell"><small><?= htmlspecialchars(coachCalendarDate($ce['end_date'])) ?></small></td>
                                <td class="d-none d-md-table-cell text-center">
                                    <?php if ((int) $ce['days_remaining'] <= 7): ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= (int) $ce['days_remaining'] ?>d</span>
                                    <?php elseif ((int) $ce['days_remaining'] <= 30): ?>
                                        <span class="badge bg-warning-subtle text-warning"><?= (int) $ce['days_remaining'] ?>d</span>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= (int) $ce['days_remaining'] ?>d</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= coachPhaseBadge($ce['phase_status']) ?></td>
                                <td class="d-none d-xl-table-cell">
                                    <small class="text-muted"><?= htmlspecialchars($ce['assigned_class_schedule'] ?? 'Sin horario') ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver cohorte">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="coach-mobile-list">
                <?php foreach ($coachEntries as $ce): ?>
                    <article class="coach-mobile-card">
                        <div class="coach-mobile-card__top">
                            <div>
                                <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>"><?= htmlspecialchars($ce['cohort_code']) ?></a>
                                <h3><?= htmlspecialchars($ce['name']) ?></h3>
                            </div>
                            <?= coachPhaseBadge($ce['phase_status']) ?>
                        </div>
                        <div class="coach-mobile-card__meta">
                            <span><i class="bi bi-calendar-event"></i><?= htmlspecialchars(coachCalendarDate($ce['start_date'])) ?> - <?= htmlspecialchars(coachCalendarDate($ce['end_date'])) ?></span>
                            <span><i class="bi bi-clock"></i><?= htmlspecialchars($ce['assigned_class_schedule'] ?? 'Sin horario') ?></span>
                            <span><i class="bi bi-hourglass-split"></i><?= (int) $ce['days_remaining'] ?> dias restantes</span>
                        </div>
                        <div class="coach-mobile-card__progress">
                            <strong><?= (int) $ce['pct_completion'] ?>%</strong>
                            <div class="dashboard-mini-progress"><span data-style-width="<?= (int) $ce['pct_completion'] ?>%"></span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php endif; ?>


