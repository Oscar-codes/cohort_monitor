<?php
/**
 * Cohorts Index View
 *
 * Two views: Accordion (grouped by status) and Gantt Timeline (upcoming 60 days).
 * Filters are URL-driven (GET), so state persists with browser navigation.
 */

/** Helper: format date safely */
function formatDateLabel(?string $date): string
{
    if (!$date) {
        return '—';
    }

    return date('d M Y', strtotime($date));
}

/** Helper: derive lifecycle status based on dates */
function cohortLifecycleStatus(array $cohort): string
{
    $today = date('Y-m-d');
    $startDate = $cohort['start_date'] ?? null;
    $endDate = $cohort['end_date'] ?? null;

    if ($startDate && $startDate > $today) {
        return 'upcoming';
    }

    if ($startDate && $startDate <= $today && (!$endDate || $endDate >= $today)) {
        return 'in_progress';
    }

    if ($endDate && $endDate < $today) {
        return 'completed';
    }

    return 'upcoming';
}

/** Helper: render lifecycle badge */
function lifecycleBadge(array $cohort): string
{
    $status = cohortLifecycleStatus($cohort);

    $map = [
        'upcoming' => ['bg-secondary-subtle text-secondary', 'Upcoming'],
        'in_progress' => ['bg-primary-subtle text-primary', 'In progress'],
        'completed' => ['bg-success-subtle text-success', 'Completed'],
    ];

    [$class, $label] = $map[$status] ?? ['bg-light text-dark', ucfirst($status)];

    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/** Helper: detect business model */
function businessModelBadge(array $cohort): string
{
    $hasB2B = ((int) ($cohort['b2b_admission_target'] ?? 0) > 0) || ((int) ($cohort['b2b_admissions'] ?? 0) > 0);
    $hasB2C = (int) ($cohort['b2c_admissions'] ?? 0) > 0;

    if ($hasB2B && $hasB2C) {
        return '<span class="badge bg-warning-subtle text-warning">B2B + B2C</span>';
    }

    if ($hasB2B) {
        return '<span class="badge bg-info-subtle text-info">B2B</span>';
    }

    if ($hasB2C) {
        return '<span class="badge bg-primary-subtle text-primary">B2C</span>';
    }

    return '<span class="text-muted">—</span>';
}

/** Helper: render a single cohort row */
function renderCohortRow(array $cohort, string $querySuffix, bool $canDelete): string
{
    $id = (int) $cohort['id'];
    $name = htmlspecialchars($cohort['name']);
    $code = htmlspecialchars($cohort['cohort_code'] ?? 'N/A');
    $type = htmlspecialchars($cohort['bootcamp_type'] ?? '');
    $startLabel = htmlspecialchars(formatDateLabel($cohort['start_date'] ?? null));
    $endLabel = htmlspecialchars(formatDateLabel($cohort['end_date'] ?? null));
    $bModel = businessModelBadge($cohort);
    $badge = lifecycleBadge($cohort);
    $coach = htmlspecialchars($cohort['assigned_coach'] ?? '—');
    $schedule = htmlspecialchars($cohort['assigned_class_schedule'] ?? '—');
    $project = htmlspecialchars($cohort['related_project'] ?? '—');
    $b2b = (int) ($cohort['b2b_admissions'] ?? 0);
    $b2c = (int) ($cohort['b2c_admissions'] ?? 0);

    $typeCell = $type ? '<span class="badge bg-light text-dark border">' . $type . '</span>' : '<span class="text-muted">—</span>';

    $deleteBtn = '';
    if ($canDelete) {
        $deleteBtn = '<form method="POST" action="/cohorts/' . $id . '" class="d-inline" data-confirm="¿Estás seguro de que deseas eliminar esta cohorte?">'
            . '<input type="hidden" name="_method" value="DELETE">'
            . '<button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar"><i class="bi bi-trash"></i></button>'
            . '</form>';
    }

    return '<tr>'
        . '<td><span class="text-muted">#' . $id . '</span></td>'
        . '<td><div class="d-flex flex-column">'
            . '<a href="/cohorts/' . $id . $querySuffix . '" class="text-decoration-none fw-semibold text-dark">' . $name . '</a>'
            . '<div class="small text-muted mt-1">' . $code . '</div>'
        . '</div></td>'
        . '<td class="d-none d-md-table-cell">' . $typeCell . '</td>'
        . '<td class="d-none d-lg-table-cell"><div class="small">'
            . '<div><i class="bi bi-calendar-event text-muted me-1"></i>' . $startLabel . '</div>'
            . '<div class="text-muted"><i class="bi bi-calendar-check me-1"></i>' . $endLabel . '</div>'
        . '</div></td>'
        . '<td class="d-none d-xl-table-cell"><small>' . $coach . '</small></td>'
        . '<td class="d-none d-xl-table-cell"><small>' . $schedule . '</small></td>'
        . '<td class="d-none d-xl-table-cell text-center"><small>' . $b2b . ' / ' . $b2c . '</small></td>'
        . '<td class="d-none d-lg-table-cell text-center">' . $bModel . '</td>'
        . '<td class="text-end"><div class="action-buttons justify-content-end">'
            . '<a href="/cohorts/' . $id . $querySuffix . '" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles"><i class="bi bi-eye"></i></a>'
            . '<a href="/cohorts/' . $id . '/edit' . $querySuffix . '" class="btn btn-icon btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil"></i></a>'
            . $deleteBtn
        . '</div></td>'
        . '</tr>';
}

$filters = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$querySuffix = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '';
$canDeleteVal = $canDelete ?? false;

// Group cohorts by lifecycle status
$grouped = ['upcoming' => [], 'in_progress' => [], 'completed' => []];
foreach ($cohorts as $c) {
    $grouped[cohortLifecycleStatus($c)][] = $c;
}
$upcomingCount   = count($grouped['upcoming']);
$inProgressCount = count($grouped['in_progress']);
$completedCount  = count($grouped['completed']);

// Upcoming cohorts starting within 60 days (for Gantt view)
$today60 = date('Y-m-d', strtotime('+60 days'));
$todayStr = date('Y-m-d');
$ganttCohorts = array_filter($grouped['upcoming'], function (array $c) use ($todayStr, $today60) {
    $sd = $c['start_date'] ?? null;
    return $sd && $sd >= $todayStr && $sd <= $today60;
});
$ganttCohorts = array_values($ganttCohorts);

// Gantt timeline range
$ganttMin = $todayStr;
$ganttMax = $today60;
if (!empty($ganttCohorts)) {
    $ends = array_map(fn($c) => $c['end_date'] ?? $c['start_date'], $ganttCohorts);
    $maxEnd = max($ends);
    if ($maxEnd > $ganttMax) {
        $ganttMax = $maxEnd;
    }
}
$ganttStartTs = strtotime($ganttMin);
$ganttEndTs   = strtotime($ganttMax);
$ganttSpanDays = max(1, (int) round(($ganttEndTs - $ganttStartTs) / 86400));

// Status config for accordion
$statusConfig = [
    'upcoming'    => ['label' => 'Upcoming',    'color' => '#6c757d', 'icon' => 'bi-clock',          'defaultOpen' => true],
    'in_progress' => ['label' => 'In Progress', 'color' => '#0d6efd', 'icon' => 'bi-play-circle',    'defaultOpen' => false],
    'completed'   => ['label' => 'Completed',   'color' => '#198754', 'icon' => 'bi-check-circle',   'defaultOpen' => false],
];
?>

<!-- ── Toolbar: filtros + toggle vista ──────────────────── -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Gestiona cohortes con filtros combinables por tipo, fechas, modelo de negocio y estado.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <!-- View toggle -->
        <div class="btn-group" role="group" aria-label="Cambiar vista">
            <button type="button" class="btn btn-outline-primary active" id="btn-view-list" data-view="list">
                <i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">Lista</span>
            </button>
            <button type="button" class="btn btn-outline-primary" id="btn-view-gantt" data-view="gantt">
                <i class="bi bi-bar-chart-steps me-1"></i><span class="d-none d-sm-inline">Timeline</span>
            </button>
        </div>

        <?php if (!empty($activeFilters)): ?>
            <a href="/cohorts" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Limpiar filtros
            </a>
        <?php endif; ?>

        <?php if ($canCreate ?? false): ?>
            <a href="/cohorts/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">Nueva Cohorte</span>
                <span class="d-sm-none">Nueva</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Filtros ──────────────────────────────────────────── -->
<div class="card mb-4" id="cohort-filters">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel me-1"></i>Filtros</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="/cohorts" class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <label for="bootcamp_type" class="form-label">Bootcamp Type</label>
                <select class="form-select" id="bootcamp_type" name="bootcamp_type">
                    <option value="">Todos</option>
                    <?php foreach (($bootcampTypes ?? []) as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= (($filters['bootcamp_type'] ?? '') === $type) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="business_model" class="form-label">Business Model</label>
                <select class="form-select" id="business_model" name="business_model">
                    <option value="">Todos</option>
                    <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                    <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label for="cohort_status" class="form-label">Cohort Status</label>
                <select class="form-select" id="cohort_status" name="cohort_status">
                    <option value="">Todos</option>
                    <option value="upcoming" <?= (($filters['cohort_status'] ?? '') === 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                    <option value="in_progress" <?= (($filters['cohort_status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In progress</option>
                    <option value="completed" <?= (($filters['cohort_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Aplicar filtros
                </button>
                <a href="/cohorts" class="btn btn-outline-secondary">Restablecer</a>
            </div>
        </form>
    </div>
</div>

<!-- ── Summary cards ───────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary"><?= count($cohorts) ?></div>
                <small class="text-muted">Resultados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-secondary"><?= $upcomingCount ?></div>
                <small class="text-muted">Upcoming</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-info"><?= $inProgressCount ?></div>
                <small class="text-muted">In progress</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success"><?= $completedCount ?></div>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW 1: Accordion List (grouped by status)
     ════════════════════════════════════════════════════════ -->
<div id="view-list">
<?php if (!empty($cohorts)): ?>

    <?php foreach (['upcoming' => $upcomingCount, 'in_progress' => $inProgressCount, 'completed' => $completedCount] as $status => $count): ?>
        <?php if ($count === 0) continue; ?>
        <?php
            $cfg = $statusConfig[$status];
            $isOpen = $cfg['defaultOpen'];
            $collapseId = 'accordion-' . $status;
        ?>
        <div class="status-accordion mb-3">
            <!-- Accordion header -->
            <div class="status-accordion-header" role="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>" style="--status-color: <?= $cfg['color'] ?>;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-chevron-right status-accordion-arrow"></i>
                    <span class="status-dot" style="background: <?= $cfg['color'] ?>;"></span>
                    <i class="bi <?= $cfg['icon'] ?>" style="color: <?= $cfg['color'] ?>;"></i>
                    <span class="fw-semibold"><?= $cfg['label'] ?></span>
                    <span class="badge rounded-pill text-bg-dark ms-1"><?= $count ?></span>
                </div>
            </div>

            <!-- Accordion body -->
            <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <div class="card table-card border-top-0" style="border-top-left-radius:0;border-top-right-radius:0;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:56px;">ID</th>
                                    <th>Cohorte</th>
                                    <th class="d-none d-md-table-cell">Bootcamp</th>
                                    <th class="d-none d-lg-table-cell">Fechas</th>
                                    <th class="d-none d-xl-table-cell">Coach</th>
                                    <th class="d-none d-xl-table-cell">Horario</th>
                                    <th class="d-none d-xl-table-cell text-center">B2B/B2C</th>
                                    <th class="d-none d-lg-table-cell text-center">Business Model</th>
                                    <th class="text-end" style="width:120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped[$status] as $cohort): ?>
                                    <?= renderCohortRow($cohort, $querySuffix, $canDeleteVal) ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-funnel"></i></div>
                <h5 class="empty-state-title">No hay resultados para los filtros aplicados</h5>
                <p class="empty-state-text">Ajusta o limpia los filtros para ver más cohortes.</p>
                <a href="/cohorts" class="btn btn-outline-secondary">Limpiar filtros</a>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW 2: Gantt Timeline (Upcoming — next 60 days)
     ════════════════════════════════════════════════════════ -->
<div id="view-gantt" class="d-none">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Upcoming Timeline — próximos 60 días</h6>
            <small class="text-muted"><?= count($ganttCohorts) ?> bootcamps</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ganttCohorts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                    <h5 class="empty-state-title">Sin bootcamps próximos</h5>
                    <p class="empty-state-text">No hay bootcamps que inicien en los próximos 60 días.</p>
                </div>
            <?php else: ?>
                <div class="gantt-wrapper" id="gantt-wrapper">
                    <!-- Month headers -->
                    <?php
                        $months = [];
                        $cursor = strtotime(date('Y-m-01', $ganttStartTs));
                        while ($cursor <= $ganttEndTs) {
                            $mKey = date('Y-m', $cursor);
                            $mLabel = ucfirst((new IntlDateFormatter('es', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMM yyyy'))->format($cursor));
                            $mStart = max($ganttStartTs, $cursor);
                            $mEnd   = min($ganttEndTs, strtotime(date('Y-m-t', $cursor)));
                            $mLeft  = (($mStart - $ganttStartTs) / 86400) / $ganttSpanDays * 100;
                            $mWidth = max(0, (($mEnd - $mStart) / 86400 + 1) / $ganttSpanDays * 100);
                            $months[] = ['label' => $mLabel, 'left' => $mLeft, 'width' => $mWidth];
                            $cursor = strtotime('+1 month', strtotime(date('Y-m-01', $cursor)));
                        }
                    ?>
                    <div class="gantt-header">
                        <div class="gantt-label-col"><small class="fw-semibold text-muted">Cohorte</small></div>
                        <div class="gantt-timeline-col position-relative">
                            <?php foreach ($months as $m): ?>
                                <div class="gantt-month" style="left:<?= $m['left'] ?>%;width:<?= $m['width'] ?>%;"><?= htmlspecialchars($m['label']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Today marker -->
                    <?php
                        $todayOffset = max(0, (strtotime($todayStr) - $ganttStartTs) / 86400) / $ganttSpanDays * 100;
                    ?>

                    <!-- Rows -->
                    <?php foreach ($ganttCohorts as $gc): ?>
                        <?php
                            $gcStart = strtotime($gc['start_date']);
                            $gcEnd   = strtotime($gc['end_date'] ?? $gc['start_date']);
                            $barLeft = max(0, ($gcStart - $ganttStartTs) / 86400) / $ganttSpanDays * 100;
                            $barWidth = max(1, ($gcEnd - $gcStart) / 86400) / $ganttSpanDays * 100;
                            $barWidth = min($barWidth, 100 - $barLeft);
                            $gcId = (int) $gc['id'];
                        ?>
                        <div class="gantt-row">
                            <div class="gantt-label-col">
                                <a href="/cohorts/<?= $gcId ?><?= $querySuffix ?>" class="text-decoration-none text-dark small fw-semibold">
                                    <?= htmlspecialchars($gc['cohort_code']) ?>
                                </a>
                                <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($gc['name']) ?></div>
                            </div>
                            <div class="gantt-timeline-col position-relative">
                                <div class="gantt-today-line" style="left:<?= $todayOffset ?>%;"></div>
                                <div class="gantt-bar" style="left:<?= $barLeft ?>%;width:<?= $barWidth ?>%;"
                                     data-bs-toggle="tooltip" data-bs-html="true"
                                     title="<strong><?= htmlspecialchars($gc['cohort_code']) ?></strong><br><?= htmlspecialchars(formatDateLabel($gc['start_date'])) ?> → <?= htmlspecialchars(formatDateLabel($gc['end_date'] ?? null)) ?><br>Coach: <?= htmlspecialchars($gc['assigned_coach'] ?? '—') ?>">
                                    <span class="gantt-bar-label"><?= htmlspecialchars($gc['cohort_code']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── View toggle script ──────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnList  = document.getElementById('btn-view-list');
    const btnGantt = document.getElementById('btn-view-gantt');
    const viewList = document.getElementById('view-list');
    const viewGantt = document.getElementById('view-gantt');

    if (!btnList || !btnGantt || !viewList || !viewGantt) return;

    const saved = localStorage.getItem('cohorts-view') || 'list';
    if (saved === 'gantt') switchTo('gantt');

    btnList.addEventListener('click', function () { switchTo('list'); });
    btnGantt.addEventListener('click', function () { switchTo('gantt'); });

    function switchTo(view) {
        const isList = view === 'list';
        viewList.classList.toggle('d-none', !isList);
        viewGantt.classList.toggle('d-none', isList);
        btnList.classList.toggle('active', isList);
        btnGantt.classList.toggle('active', !isList);
        localStorage.setItem('cohorts-view', view);

        // Reinitialize tooltips for the visible view
        const target = isList ? viewList : viewGantt;
        target.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
            }
        });
    }
});
</script>
