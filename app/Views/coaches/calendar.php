<?php
/**
 * Coach Calendar View
 *
 * Two views: Timeline (Gantt per coach) and List (table grouped by coach).
 * Shows only coaches with in-progress cohorts (1-99% completion).
 * Filters: coach name, bootcamp type. State via GET params.
 */

/** Phase badge helper */
function phaseBadge(string $phase): string
{
    $map = [
        'early'     => ['bg-info-subtle text-info',       'Inicio'],
        'mid'       => ['bg-primary-subtle text-primary', 'Medio'],
        'advanced'  => ['bg-warning-subtle text-warning', 'Avanzado'],
        'finishing' => ['bg-danger-subtle text-danger',   'Finalizando'],
    ];
    [$class, $label] = $map[$phase] ?? ['bg-secondary-subtle text-secondary', ucfirst($phase)];
    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/** Progress bar color based on percentage */
function progressColor(int $pct): string
{
    if ($pct <= 25) return 'bg-info';
    if ($pct <= 50) return 'bg-primary';
    if ($pct <= 75) return 'bg-warning';
    return 'bg-danger';
}

/** Format date for display */
function calFormatDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '—';
}

$filters       = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$querySuffix   = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '';
$stats         = $stats ?? ['total_coaches' => 0, 'total_cohorts' => 0, 'avg_completion' => 0, 'finishing_soon' => 0];
$entries       = $entries ?? [];
$groupedByCoach = $groupedByCoach ?? [];

// ── Timeline calculations ────────────────────────────────
$todayStr = date('Y-m-d');
$todayTs  = strtotime($todayStr);

// Find global min/max for the timeline
$timelineMin = null;
$timelineMax = null;
foreach ($entries as $e) {
    $s = $e['start_date'];
    $d = $e['end_date'];
    if (!$timelineMin || $s < $timelineMin) $timelineMin = $s;
    if (!$timelineMax || $d > $timelineMax) $timelineMax = $d;
}
// Fallback to today ± 60 days if no data
if (!$timelineMin) $timelineMin = date('Y-m-d', strtotime('-30 days'));
if (!$timelineMax) $timelineMax = date('Y-m-d', strtotime('+30 days'));

$tlStartTs  = strtotime($timelineMin);
$tlEndTs    = strtotime($timelineMax);
$tlSpanDays = max(1, (int) round(($tlEndTs - $tlStartTs) / 86400));

// Month headers
$months = [];
$cursor = strtotime(date('Y-m-01', $tlStartTs));
while ($cursor <= $tlEndTs) {
    $mLabel = ucfirst(date('M Y', $cursor));
    $mStart = max($tlStartTs, $cursor);
    $mEnd   = min($tlEndTs, strtotime(date('Y-m-t', $cursor)));
    $mLeft  = (($mStart - $tlStartTs) / 86400) / $tlSpanDays * 100;
    $mWidth = max(0, (($mEnd - $mStart) / 86400 + 1) / $tlSpanDays * 100);
    $months[] = ['label' => $mLabel, 'left' => $mLeft, 'width' => $mWidth];
    $cursor = strtotime('+1 month', strtotime(date('Y-m-01', $cursor)));
}

$todayOffset = max(0, ($todayTs - $tlStartTs) / 86400) / $tlSpanDays * 100;

// Phase colors for Gantt bars
$phaseBarColors = [
    'early'     => 'linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%)',
    'mid'       => 'linear-gradient(135deg, #4f8cff 0%, #2563eb 100%)',
    'advanced'  => 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)',
    'finishing' => 'linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%)',
];
?>

<!-- ── Toolbar ──────────────────────────────────────────── -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-calendar-range text-primary me-2"></i>Calendario de Coaches</h5>
        <p class="text-muted mb-0 small">Coaches activos con cohorts en progreso (1-99% completado). Se actualiza automáticamente.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <!-- View toggle -->
        <div class="btn-group" role="group" aria-label="Cambiar vista">
            <button type="button" class="btn btn-outline-primary active btn-sm" id="btn-view-timeline" data-view="timeline">
                <i class="bi bi-bar-chart-steps me-1"></i><span class="d-none d-sm-inline">Timeline</span>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-view-list" data-view="list">
                <i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">Lista</span>
            </button>
        </div>
        <?php if (!empty($activeFilters)): ?>
            <a href="/coaches" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle me-1"></i>Limpiar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Filters ──────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header py-2">
        <h6 class="mb-0 small"><i class="bi bi-funnel me-1"></i>Filtros</h6>
    </div>
    <div class="card-body py-2">
        <form method="GET" action="/coaches" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="coach" class="form-label small mb-1">Coach</label>
                <select class="form-select form-select-sm" id="coach" name="coach">
                    <option value="">Todos los coaches</option>
                    <?php foreach (($coachNames ?? []) as $name): ?>
                        <option value="<?= htmlspecialchars($name) ?>" <?= (($filters['coach'] ?? '') === $name) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="bootcamp_type" class="form-label small mb-1">Bootcamp Type</label>
                <select class="form-select form-select-sm" id="bootcamp_type" name="bootcamp_type">
                    <option value="">Todos</option>
                    <?php foreach (($bootcampTypes ?? []) as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="/coaches" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ── KPI Cards ───────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary"><?= $stats['total_coaches'] ?></div>
                <small class="text-muted">Coaches Activos</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success"><?= $stats['total_cohorts'] ?></div>
                <small class="text-muted">Cohorts en Progreso</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-info"><?= $stats['avg_completion'] ?>%</div>
                <small class="text-muted">Completado Promedio</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-danger"><?= $stats['finishing_soon'] ?></div>
                <small class="text-muted">Finalizando Pronto</small>
            </div>
        </div>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                <h5 class="empty-state-title">Sin coaches activos</h5>
                <p class="empty-state-text">No hay coaches con cohorts en progreso activo en este momento.</p>
                <?php if (!empty($activeFilters)): ?>
                    <a href="/coaches" class="btn btn-outline-secondary btn-sm">Limpiar filtros</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>

<!-- ════════════════════════════════════════════════════════
     VIEW 1: Timeline (Gantt grouped by coach)
     ════════════════════════════════════════════════════════ -->
<div id="view-timeline">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Timeline de Coaches Activos</h6>
            <small class="text-muted"><?= $stats['total_coaches'] ?> coaches · <?= $stats['total_cohorts'] ?> cohorts</small>
        </div>
        <div class="card-body p-0">
            <div class="gantt-wrapper">
                <!-- Month headers -->
                <div class="gantt-header">
                    <div class="gantt-label-col coach-gantt-label"><small class="fw-semibold text-muted">Coach / Cohort</small></div>
                    <div class="gantt-timeline-col position-relative">
                        <?php foreach ($months as $m): ?>
                            <div class="gantt-month" style="left:<?= $m['left'] ?>%;width:<?= $m['width'] ?>%;"><?= htmlspecialchars($m['label']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Coach groups -->
                <?php foreach ($groupedByCoach as $coachName => $coachEntries): ?>
                    <!-- Coach separator -->
                    <div class="coach-gantt-group-header">
                        <div class="gantt-label-col coach-gantt-label">
                            <div class="d-flex align-items-center gap-2">
                                <span class="coach-avatar-sm"><?= htmlspecialchars(mb_substr($coachName, 0, 1)) ?></span>
                                <div>
                                    <div class="fw-semibold small"><?= htmlspecialchars($coachName) ?></div>
                                    <div class="text-muted" style="font-size:.65rem;"><?= count($coachEntries) ?> cohort<?= count($coachEntries) > 1 ? 's' : '' ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="gantt-timeline-col position-relative">
                            <div class="gantt-today-line" style="left:<?= $todayOffset ?>%;"></div>
                        </div>
                    </div>

                    <!-- Cohort bars for this coach -->
                    <?php foreach ($coachEntries as $ce): ?>
                        <?php
                            $barStart = strtotime($ce['start_date']);
                            $barEnd   = strtotime($ce['end_date']);
                            $barLeft  = max(0, ($barStart - $tlStartTs) / 86400) / $tlSpanDays * 100;
                            $barWidth = max(1, ($barEnd - $barStart) / 86400) / $tlSpanDays * 100;
                            $barWidth = min($barWidth, 100 - $barLeft);
                            $barBg    = $phaseBarColors[$ce['phase_status']] ?? $phaseBarColors['mid'];

                            // Progress fill inside the bar
                            $fillPct = $ce['pct_completion'];
                        ?>
                        <div class="gantt-row">
                            <div class="gantt-label-col coach-gantt-label">
                                <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>" class="text-decoration-none text-dark small fw-semibold">
                                    <?= htmlspecialchars($ce['cohort_code']) ?>
                                </a>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <?= phaseBadge($ce['phase_status']) ?>
                                    <span class="text-muted" style="font-size:.6rem;"><?= $ce['pct_completion'] ?>%</span>
                                </div>
                            </div>
                            <div class="gantt-timeline-col position-relative">
                                <div class="gantt-today-line" style="left:<?= $todayOffset ?>%;"></div>
                                <div class="coach-gantt-bar" style="left:<?= $barLeft ?>%;width:<?= $barWidth ?>%;background:<?= $barBg ?>;"
                                     data-bs-toggle="tooltip" data-bs-html="true"
                                     title="<strong><?= htmlspecialchars($ce['cohort_code']) ?></strong><br>
                                            <?= htmlspecialchars($ce['name']) ?><br>
                                            <?= calFormatDate($ce['start_date']) ?> → <?= calFormatDate($ce['end_date']) ?><br>
                                            Progreso: <?= $ce['pct_completion'] ?>% · <?= $ce['days_remaining'] ?> días restantes">
                                    <!-- Progress fill overlay -->
                                    <div class="coach-gantt-bar-fill" style="width:<?= $fillPct ?>%;"></div>
                                    <span class="gantt-bar-label"><?= htmlspecialchars($ce['cohort_code']) ?> (<?= $fillPct ?>%)</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex flex-wrap gap-3 mt-3 px-1">
        <small class="text-muted"><span class="coach-legend-dot" style="background:#0dcaf0;"></span> Inicio (1-25%)</small>
        <small class="text-muted"><span class="coach-legend-dot" style="background:#2563eb;"></span> Medio (26-50%)</small>
        <small class="text-muted"><span class="coach-legend-dot" style="background:#ffc107;"></span> Avanzado (51-75%)</small>
        <small class="text-muted"><span class="coach-legend-dot" style="background:#dc3545;"></span> Finalizando (76-99%)</small>
        <small class="text-muted"><span class="coach-legend-line"></span> Hoy</small>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW 2: List (table grouped by coach)
     ════════════════════════════════════════════════════════ -->
<div id="view-list" class="d-none">
    <?php foreach ($groupedByCoach as $coachName => $coachEntries): ?>
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="coach-avatar-sm"><?= htmlspecialchars(mb_substr($coachName, 0, 1)) ?></span>
                    <div>
                        <span class="fw-semibold"><?= htmlspecialchars($coachName) ?></span>
                        <span class="badge rounded-pill text-bg-dark ms-2"><?= count($coachEntries) ?></span>
                    </div>
                </div>
                <?php
                    // Coach-level average completion
                    $coachAvg = (int) round(array_sum(array_column($coachEntries, 'pct_completion')) / max(1, count($coachEntries)));
                ?>
                <span class="text-muted small">Promedio: <?= $coachAvg ?>%</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cohort</th>
                            <th class="d-none d-md-table-cell">Bootcamp</th>
                            <th>Progreso</th>
                            <th class="d-none d-lg-table-cell">Inicio</th>
                            <th class="d-none d-lg-table-cell">Fin</th>
                            <th class="d-none d-md-table-cell text-center">Días Rest.</th>
                            <th class="d-none d-xl-table-cell text-center">Duración</th>
                            <th>Fase</th>
                            <th class="d-none d-xl-table-cell">Horario</th>
                            <th class="text-end">Acciones</th>
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
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width:120px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px; border-radius:4px;">
                                            <div class="progress-bar <?= progressColor($ce['pct_completion']) ?>"
                                                 role="progressbar" style="width:<?= $ce['pct_completion'] ?>%;"
                                                 aria-valuenow="<?= $ce['pct_completion'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="fw-semibold text-nowrap"><?= $ce['pct_completion'] ?>%</small>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= calFormatDate($ce['start_date']) ?></small></td>
                                <td class="d-none d-lg-table-cell"><small><?= calFormatDate($ce['end_date']) ?></small></td>
                                <td class="d-none d-md-table-cell text-center">
                                    <?php if ($ce['days_remaining'] <= 7): ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= $ce['days_remaining'] ?>d</span>
                                    <?php elseif ($ce['days_remaining'] <= 30): ?>
                                        <span class="badge bg-warning-subtle text-warning"><?= $ce['days_remaining'] ?>d</span>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= $ce['days_remaining'] ?>d</span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-xl-table-cell text-center">
                                    <small class="text-muted"><?= $ce['duration_days'] ?>d</small>
                                </td>
                                <td><?= phaseBadge($ce['phase_status']) ?></td>
                                <td class="d-none d-xl-table-cell">
                                    <small class="text-muted"><?= htmlspecialchars($ce['assigned_class_schedule'] ?? '—') ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="/cohorts/<?= (int) $ce['id'] ?><?= $querySuffix ?>" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver cohort">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<!-- ── View toggle + tooltip init ──────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnTimeline = document.getElementById('btn-view-timeline');
    const btnList     = document.getElementById('btn-view-list');
    const viewTimeline = document.getElementById('view-timeline');
    const viewList     = document.getElementById('view-list');

    if (!btnTimeline || !btnList || !viewTimeline || !viewList) return;

    const saved = localStorage.getItem('coaches-view') || 'timeline';
    if (saved === 'list') switchTo('list');

    btnTimeline.addEventListener('click', function () { switchTo('timeline'); });
    btnList.addEventListener('click', function () { switchTo('list'); });

    function switchTo(view) {
        const isTimeline = view === 'timeline';
        viewTimeline.classList.toggle('d-none', !isTimeline);
        viewList.classList.toggle('d-none', isTimeline);
        btnTimeline.classList.toggle('active', isTimeline);
        btnList.classList.toggle('active', !isTimeline);
        localStorage.setItem('coaches-view', view);

        const target = isTimeline ? viewTimeline : viewList;
        target.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
            }
        });
    }
});
</script>
