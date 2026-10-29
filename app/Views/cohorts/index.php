<?php
use App\Core\Auth;
use App\Services\CohortStatus;

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

/** Helper: format month label with intl fallback */
function formatMonthLabel(int $timestamp): string
{
    if (class_exists('IntlDateFormatter')) {
        $fmt = new IntlDateFormatter('es', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMM yyyy');
        $formatted = $fmt->format($timestamp);
        if (is_string($formatted) && $formatted !== '') {
            return ucfirst($formatted);
        }
    }

    return ucfirst(strtolower(date('M Y', $timestamp)));
}

/** Helper: status of a cohort, from the single rule (DATA-001). */
function cohortLifecycleStatus(array $cohort): string
{
    return (string) ($cohort['effective_status'] ?? CohortStatus::forRow($cohort));
}

function cohortStatusLabel(string $status): string
{
    return CohortStatus::label($status);
}

function cohortCanDelete(array $cohort): bool
{
    return in_array(cohortLifecycleStatus($cohort), CohortStatus::DELETABLE, true);
}

/**
 * data-* attributes for the delete form: app.js asks the user to type the
 * cohort_code in a SweetAlert2 prompt (COH-009). The server re-checks it.
 */
function cohortDeleteConfirmAttrs(array $cohort): string
{
    $code = htmlspecialchars((string) ($cohort['cohort_code'] ?? ''), ENT_QUOTES, 'UTF-8');

    return ' data-confirm="Esta acción elimina la cohorte de forma permanente."'
        . ' data-confirm-title="Eliminar cohorte"'
        . ' data-confirm-tone="danger"'
        . ' data-confirm-button="Eliminar"'
        . ' data-confirm-code="' . $code . '"';
}

/** Helper: render lifecycle pill (Kodigo) */
function lifecycleBadge(array $cohort): string
{
    $status = cohortLifecycleStatus($cohort);

    return '<span class="kodigo-pill" data-tone="' . CohortStatus::tone($status) . '">'
         . '<span class="kodigo-pill__dot" aria-hidden="true"></span>'
         . htmlspecialchars(CohortStatus::label($status))
         . '</span>';
}

/** Helper: render project pill with color coding (Kodigo) */
function projectBadge(string $project): string
{
    if ($project === '—' || $project === '') {
        return '<span class="text-muted">—</span>';
    }

    $tones = [
        'kodigo' => 'info',
        'lamar'  => 'success',
        'incaf'  => 'warning',
        'aldea'  => 'info',
    ];

    $key = strtolower(trim($project));
    $tone = $tones[$key] ?? 'neutral';

    return '<span class="kodigo-pill" data-tone="' . $tone . '">'
         . '<span class="kodigo-pill__dot" aria-hidden="true"></span>'
         . htmlspecialchars($project)
         . '</span>';
}

/** Helper: detect business model pill (Kodigo) */
function businessModelBadge(array $cohort): string
{
    $hasB2B = ((int) ($cohort['b2b_admission_target'] ?? 0) > 0) || ((int) ($cohort['b2b_admissions'] ?? 0) > 0);
    $hasB2C = ((int) ($cohort['b2c_admission_target'] ?? 0) > 0) || ((int) ($cohort['b2c_admissions'] ?? 0) > 0);

    if ($hasB2B && $hasB2C) {
        return '<span class="kodigo-pill" data-tone="warning"><span class="kodigo-pill__dot" aria-hidden="true"></span>B2B + B2C</span>';
    }

    if ($hasB2B) {
        return '<span class="kodigo-pill" data-tone="info"><span class="kodigo-pill__dot" aria-hidden="true"></span>B2B</span>';
    }

    if ($hasB2C) {
        return '<span class="kodigo-pill" data-tone="info"><span class="kodigo-pill__dot" aria-hidden="true"></span>B2C</span>';
    }

    return '<span class="text-muted">—</span>';
}

/** Helper: render a single cohort row */
function renderCohortRow(array $cohort, string $querySuffix, bool $canEdit, bool $canDelete): string
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
    $classDays = htmlspecialchars($cohort['class_days'] ?? '—');
    $project = htmlspecialchars($cohort['related_project'] ?? '—');
    $b2b = (int) ($cohort['b2b_admissions'] ?? 0);
    $b2c = (int) ($cohort['b2c_admissions'] ?? 0);

    $typeCell = $type ? '<span class="kodigo-pill" data-tone="neutral">' . $type . '</span>' : '<span class="text-muted">—</span>';

    $deleteBtn = '';
    if ($canDelete && cohortCanDelete($cohort)) {
        $deleteBtn = '<form method="POST" action="/cohorts/' . $id . '" class="d-inline"' . cohortDeleteConfirmAttrs($cohort) . '>'
            . csrf_field()
            . '<input type="hidden" name="_method" value="DELETE">'
            . '<input type="hidden" name="confirm_code" value="">'
            . '<button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar" aria-label="Eliminar cohorte"><i class="bi bi-trash" aria-hidden="true"></i></button>'
            . '</form>';
    }

    $editBtn = '';
    if ($canEdit) {
        $editBtn = '<a href="/cohorts/' . $id . '/edit' . $querySuffix . '" class="btn btn-icon btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Editar" aria-label="Editar cohorte"><i class="bi bi-pencil" aria-hidden="true"></i></a>';
    }

    return '<tr>'
        . '<td><span class="text-muted">#' . $id . '</span></td>'
        . '<td><div class="d-flex flex-column">'
            . '<a href="/cohorts/' . $id . $querySuffix . '" class="text-decoration-none fw-semibold text-dark">' . $name . '</a>'
            . '<div class="small text-muted mt-1">' . $code . '</div>'
        . '</div></td>'
        . '<td class="d-none d-md-table-cell">' . $typeCell . '</td>'
        . '<td class="d-none d-md-table-cell">' . projectBadge($project) . '</td>'
        . '<td class="d-none d-lg-table-cell"><div class="small">'
            . '<div><i class="bi bi-calendar-event text-muted me-1"></i>' . $startLabel . '</div>'
            . '<div class="text-muted"><i class="bi bi-calendar-check me-1"></i>' . $endLabel . '</div>'
        . '</div></td>'
        . '<td class="d-none d-xl-table-cell"><small>' . $coach . '</small></td>'
        . '<td class="d-none d-xl-table-cell"><small>' . $schedule . '</small></td>'
        . '<td class="d-none d-xl-table-cell"><small>' . $classDays . '</small></td>'
        . '<td class="d-none d-xl-table-cell text-center"><small>' . $b2b . ' / ' . $b2c . '</small></td>'
        . '<td class="d-none d-lg-table-cell text-center">' . $bModel . '</td>'
        . '<td class="text-end"><div class="action-buttons justify-content-end">'
            . '<a href="/cohorts/' . $id . $querySuffix . '" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles de cohorte"><i class="bi bi-eye" aria-hidden="true"></i></a>'
            . $editBtn
            . $deleteBtn
        . '</div></td>'
        . '</tr>';
}

/** Helper: render a mobile-first cohort card */
function renderCohortMobileCard(array $cohort, string $querySuffix, bool $canEdit, bool $canDelete): string
{
    $id = (int) $cohort['id'];
    $name = htmlspecialchars($cohort['name']);
    $code = htmlspecialchars($cohort['cohort_code'] ?? 'N/A');
    $type = htmlspecialchars($cohort['bootcamp_type'] ?? 'Sin tipo');
    $coach = htmlspecialchars($cohort['assigned_coach'] ?? 'Sin coach');
    $project = htmlspecialchars($cohort['related_project'] ?? 'Sin proyecto');
    $classDays = htmlspecialchars($cohort['class_days'] ?? '—');
    $startLabel = htmlspecialchars(formatDateLabel($cohort['start_date'] ?? null));
    $endLabel = htmlspecialchars(formatDateLabel($cohort['end_date'] ?? null));
    $b2b = (int) ($cohort['b2b_admissions'] ?? 0);
    $b2c = (int) ($cohort['b2c_admissions'] ?? 0);
    $target = (int) ($cohort['total_admission_target'] ?? 0);
    $actual = $b2b + $b2c;
    $pct = $target > 0 ? min(100, (int) round(($actual / $target) * 100)) : 0;

    $deleteBtn = '';
    if ($canDelete && cohortCanDelete($cohort)) {
        $deleteBtn = '<form method="POST" action="/cohorts/' . $id . '" class="d-inline"' . cohortDeleteConfirmAttrs($cohort) . '>'
            . csrf_field()
            . '<input type="hidden" name="_method" value="DELETE">'
            . '<input type="hidden" name="confirm_code" value="">'
            . '<button type="submit" class="btn btn-icon btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>'
            . '</form>';
    }

    $editBtn = '';
    if ($canEdit) {
        $editBtn = '<a href="/cohorts/' . $id . '/edit' . $querySuffix . '" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Editar</a>';
    }

    return '<article class="cohort-mobile-card">'
        . '<div class="cohort-mobile-card__top">'
            . '<div>'
                . '<a href="/cohorts/' . $id . $querySuffix . '" class="cohort-mobile-card__code">' . $code . '</a>'
                . '<h4>' . $name . '</h4>'
            . '</div>'
            . lifecycleBadge($cohort)
        . '</div>'
        . '<div class="cohort-mobile-card__meta">'
            . '<span><i class="bi bi-layers"></i>' . $type . '</span>'
            . '<span><i class="bi bi-building"></i>' . $project . '</span>'
            . '<span><i class="bi bi-person"></i>' . $coach . '</span>'
            . '<span><i class="bi bi-calendar-week"></i>' . $classDays . '</span>'
            . '<span><i class="bi bi-calendar-event"></i>' . $startLabel . ' - ' . $endLabel . '</span>'
        . '</div>'
        . '<div class="cohort-mobile-card__progress">'
            . '<div><strong>' . $actual . '/' . $target . '</strong><small>Inscritos</small></div>'
            . '<div class="dashboard-mini-progress"><span data-style-width="' . $pct . '%"></span></div>'
        . '</div>'
        . '<div class="cohort-mobile-card__actions">'
            . '<a href="/cohorts/' . $id . $querySuffix . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Ver</a>'
            . $editBtn
            . $deleteBtn
        . '</div>'
    . '</article>';
}

$filters = $filters ?? [];
$activeFilters = $activeFilters ?? [];
$cohorts = isset($cohorts) && is_array($cohorts) ? $cohorts : [];
$querySuffix = !empty($activeFilters) ? ('?' . http_build_query($activeFilters)) : '';
$canEditVal = $canEdit ?? false;
$canDeleteVal = $canDelete ?? false;

// Group cohorts by lifecycle status
$grouped = array_fill_keys(array_keys(CohortStatus::LABELS), []);
foreach ($cohorts as $c) {
    $grouped[cohortLifecycleStatus($c)][] = $c;
}
$statusCounts    = array_map('count', $grouped);
$upcomingCount   = $statusCounts[CohortStatus::NOT_STARTED];
$inProgressCount = $statusCounts[CohortStatus::IN_PROGRESS];
$completedCount  = $statusCounts[CohortStatus::COMPLETED];
$cancelledCount  = $statusCounts[CohortStatus::CANCELLED];
$noDatesCount    = $statusCounts[CohortStatus::NO_DATES];

// Upcoming cohorts starting within 60 days (for Gantt view)
$today60 = date('Y-m-d', strtotime('+60 days'));
$todayStr = date('Y-m-d');
$ganttCohorts = array_filter($grouped['not_started'], function (array $c) use ($todayStr, $today60) {
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
    CohortStatus::NOT_STARTED => ['color' => '#6c757d', 'defaultOpen' => true],
    CohortStatus::IN_PROGRESS => ['color' => '#0d6efd', 'defaultOpen' => false],
    CohortStatus::COMPLETED   => ['color' => '#198754', 'defaultOpen' => false],
    CohortStatus::CANCELLED   => ['color' => '#dc3545', 'defaultOpen' => false],
    CohortStatus::NO_DATES    => ['color' => '#d97706', 'defaultOpen' => false],
];
foreach ($statusConfig as $statusKey => $cfg) {
    $statusConfig[$statusKey] += ['label' => CohortStatus::label($statusKey), 'icon' => CohortStatus::icon($statusKey)];
}
?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Toolbar: filtros + toggle vista ──────────────────── -->
<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-people"></i>
            Operacion academica
        </div>
        <h2 class="cohorts-hero__title">Cohortes</h2>
        <p class="cohorts-hero__copy">Gestiona cohortes con filtros combinables, vista timeline y acciones por rol.</p>
    </div>
    <div class="cohorts-hero__actions">
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
<div class="kodigo-card cohort-filter-panel mb-4" id="cohort-filters" data-elevation="1">
    <div class="kodigo-card__header">
        <div>
            <h3 class="kodigo-card__title"><i class="bi bi-funnel text-primary"></i> Filtros</h3>
            <p class="kodigo-card__subtitle">Combina busqueda, fechas, tipo, proyecto, población y estado.</p>
        </div>
    </div>
    <div class="kodigo-card__body">
        <form method="GET" action="/cohorts" class="row g-3">
            <?php foreach (['closed_year', 'upcoming_days'] as $windowKey): ?>
                <?php if (($filters[$windowKey] ?? '') !== ''): ?>
                <input type="hidden" name="<?= $windowKey ?>" value="<?= htmlspecialchars((string) $filters[$windowKey]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="col-12 col-xl-4">
                <label for="search" class="form-label">Busqueda</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="search" name="search"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           placeholder="Codigo, nombre, coach o proyecto">
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label for="bootcamp_type" class="form-label">Bootcamp name</label>
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
                <label for="related_project" class="form-label">Proyecto</label>
                <select class="form-select" id="related_project" name="related_project">
                    <option value="">Todos</option>
                    <?php foreach (($projectNames ?? []) as $pName): ?>
                        <option value="<?= htmlspecialchars($pName) ?>" <?= (($filters['related_project'] ?? '') === $pName) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="start_date" class="form-label">Inicio desde</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="end_date" class="form-label">Inicio hasta</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="business_model" class="form-label">Población</label>
                <select class="form-select" id="business_model" name="business_model">
                    <option value="">Todos</option>
                    <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                    <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label for="cohort_status" class="form-label">Estado</label>
                <select class="form-select" id="cohort_status" name="cohort_status">
                    <option value="">Todos</option>
                    <?php foreach (CohortStatus::LABELS as $statusKey => $statusLabel): ?>
                    <option value="<?= $statusKey ?>" <?= (($filters['cohort_status'] ?? '') === $statusKey) ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Aplicar filtros
                </button>
                <a href="/cohorts" class="btn btn-outline-secondary">Restablecer</a>
            </div>
        </form>

        <?php require APP_ROOT . '/app/Views/partials/active-filters.php'; ?>
    </div>
</section>

<!-- ── Summary cards ───────────────────────────────────── -->
<?php
// DATA-001: one card per status (same rule as the filter); they add up to "Resultados".
$statusCardTone = [
    CohortStatus::NOT_STARTED => 'secondary',
    CohortStatus::IN_PROGRESS => 'info',
    CohortStatus::COMPLETED   => 'success',
    CohortStatus::CANCELLED   => 'danger',
    CohortStatus::NO_DATES    => 'warning',
];
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="cohort-summary-card cohort-summary-card--primary" data-kodigo-reveal>
            <span><i class="bi bi-search"></i></span>
            <div>
                <strong data-status-card="total"><?= count($cohorts) ?></strong>
                <small>Resultados</small>
            </div>
        </div>
    </div>
    <?php foreach ($statusCounts as $statusKey => $statusCount): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="cohort-summary-card cohort-summary-card--<?= $statusCardTone[$statusKey] ?>" data-kodigo-reveal>
            <span><i class="bi <?= CohortStatus::icon($statusKey) ?>"></i></span>
            <div>
                <strong data-status-card="<?= $statusKey ?>"><?= $statusCount ?></strong>
                <small><?= htmlspecialchars(CohortStatus::label($statusKey)) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW 1: Accordion List (grouped by status)
     ════════════════════════════════════════════════════════ -->
<div id="view-list">
<?php if (!empty($cohorts)): ?>

    <?php foreach ($statusCounts as $status => $count): ?>
        <?php if ($count === 0) continue; ?>
        <?php
            $cfg = $statusConfig[$status];
            $isOpen = $cfg['defaultOpen'];
            $collapseId = 'accordion-' . $status;
        ?>
        <div class="status-accordion mb-3">
            <!-- Accordion header -->
            <div class="status-accordion-header" role="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>" data-style-status-color="<?= $cfg['color'] ?>">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-chevron-right status-accordion-arrow"></i>
                    <span class="status-dot" data-style-background="<?= $cfg['color'] ?>"></span>
                    <i class="bi <?= $cfg['icon'] ?>" data-style-color="<?= $cfg['color'] ?>"></i>
                    <span class="fw-semibold"><?= $cfg['label'] ?></span>
                    <span class="badge rounded-pill text-bg-dark ms-1"><?= $count ?></span>
                </div>
            </div>

            <!-- Accordion body -->
            <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <div class="card table-card border-top-0 rounded-top-0">
                    <div class="cohort-mobile-list d-lg-none">
                        <?php foreach ($grouped[$status] as $cohort): ?>
                            <?= renderCohortMobileCard($cohort, $querySuffix, $canEditVal, $canDeleteVal) ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="cohort-col-id">ID</th>
                                    <th>Cohorte</th>
                                    <th class="d-none d-md-table-cell">Bootcamp name</th>
                                    <th class="d-none d-md-table-cell">Proyecto</th>
                                    <th class="d-none d-lg-table-cell">Fechas</th>
                                    <th class="d-none d-xl-table-cell">Coach</th>
                                    <th class="d-none d-xl-table-cell">Horario</th>
                                    <th class="d-none d-xl-table-cell">Dias</th>
                                    <th class="d-none d-xl-table-cell text-center">Inscritos B2B/B2C</th>
                                    <th class="d-none d-lg-table-cell text-center">Población</th>
                                    <th class="text-end cohort-col-actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped[$status] as $cohort): ?>
                                    <?= renderCohortRow($cohort, $querySuffix, $canEditVal, $canDeleteVal) ?>
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
            <div class="kodigo-empty">
                <i class="bi bi-funnel kodigo-empty__icon" aria-hidden="true"></i>
                <h5 class="kodigo-empty__title">No hay resultados para los filtros aplicados</h5>
                <p class="kodigo-empty__text">Ajusta o limpia los filtros para ver más cohortes.</p>
                <a href="/cohorts" class="btn btn-outline-secondary mt-2">Limpiar filtros</a>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW 2: Gantt Timeline (No iniciado — next 60 days)
     ════════════════════════════════════════════════════════ -->
<div id="view-gantt" class="d-none">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Timeline de cohortes No iniciado — proximos 60 dias</h6>
            <small class="text-muted"><?= count($ganttCohorts) ?> cohortes</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ganttCohorts)): ?>
                <div class="kodigo-empty">
                    <i class="bi bi-calendar-x kodigo-empty__icon" aria-hidden="true"></i>
                    <h5 class="kodigo-empty__title">Sin cohortes proximas</h5>
                    <p class="kodigo-empty__text">No hay cohortes que inicien en los proximos 60 dias.</p>
                </div>
            <?php else: ?>
                <div class="gantt-wrapper" id="gantt-wrapper">
                    <!-- Month headers -->
                    <?php
                        $months = [];
                        $cursor = strtotime(date('Y-m-01', $ganttStartTs));
                        while ($cursor <= $ganttEndTs) {
                            $mKey = date('Y-m', $cursor);
                            $mLabel = formatMonthLabel($cursor);
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
                                <div class="gantt-month" data-style-left="<?= $m['left'] ?>%" data-style-width="<?= $m['width'] ?>%"><?= htmlspecialchars($m['label']) ?></div>
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
                                <div class="text-muted cohort-gantt-name"><?= htmlspecialchars($gc['name']) ?></div>
                            </div>
                            <div class="gantt-timeline-col position-relative">
                                  <div class="gantt-today-line" data-style-left="<?= $todayOffset ?>%"></div>
                                  <div class="gantt-bar" data-style-left="<?= $barLeft ?>%" data-style-width="<?= $barWidth ?>%"
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


