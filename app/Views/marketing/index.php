<?php
use App\Core\Auth;

/** @var array<int, array<string, mixed>> $cohorts */
$cohorts = isset($cohorts) && is_array($cohorts) ? $cohorts : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$activeFilters = isset($activeFilters) && is_array($activeFilters) ? $activeFilters : [];
$bootcampTypes = isset($bootcampTypes) && is_array($bootcampTypes) ? $bootcampTypes : [];
$projectNames = isset($projectNames) && is_array($projectNames) ? $projectNames : [];

$totalCohorts = (int) ($totalCohorts ?? 0);
$campaignsActive = (int) ($campaignsActive ?? 0);
$campaignsCompleted = (int) ($campaignsCompleted ?? 0);
$stagesOnTrack = (int) ($stagesOnTrack ?? 0);
$stagesTotal = (int) ($stagesTotal ?? 0);
$stagesPct = (int) ($stagesPct ?? 0);

if (!function_exists('marketingStatusLabel')) {
    function marketingStatusLabel(?string $status): string
    {
        $labels = [
            'not_started' => 'No iniciado',
            'in_progress' => 'En progreso',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];
        return $labels[$status ?? ''] ?? (string) ($status ?? '—');
    }
}
?>

<section class="cohorts-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-megaphone"></i>
            Gestión de campañas
        </div>
        <h2 class="cohorts-hero__title">Marketing Cohort Plan</h2>
        <p class="cohorts-hero__copy">Monitorea campañas, etapas de workflow y la información de marketing por cohorte.</p>
    </div>
    <div class="cohorts-hero__actions">
        <a href="/cohorts/master" class="btn btn-outline-secondary">
            <i class="bi bi-grid-1x2 me-1"></i> Plan maestro
        </a>
        <a href="/cohorts/finance" class="btn btn-outline-secondary">
            <i class="bi bi-cash-coin me-1"></i> Finanzas
        </a>
        <?php if (!empty($activeFilters)): ?>
            <a href="/marketing" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i> Limpiar filtros
            </a>
        <?php endif; ?>
    </div>
</section>

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

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--primary h-100">
            <span><i class="bi bi-collection"></i></span>
            <div>
                <strong><?= $totalCohorts ?></strong>
                <small>Cohortes filtradas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--info h-100">
            <span><i class="bi bi-megaphone"></i></span>
            <div>
                <strong><?= $campaignsActive ?></strong>
                <small>Campañas activas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--success h-100">
            <span><i class="bi bi-check2-circle"></i></span>
            <div>
                <strong><?= $campaignsCompleted ?></strong>
                <small>Campañas completadas</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-xl-3">
        <article class="cohort-summary-card cohort-summary-card--warning h-100">
            <span><i class="bi bi-diagram-3"></i></span>
            <div>
                <strong><?= $stagesPct ?>%</strong>
                <small>Etapas completadas (<?= $stagesOnTrack ?>/<?= $stagesTotal ?>)</small>
            </div>
        </article>
    </div>
</div>

<div class="app-panel cohort-filter-panel mb-4">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-funnel text-primary"></i> Filtros de marketing</h3>
            <p class="app-panel__subtitle">Filtra por búsqueda, bootcamp name, proyecto, fechas, población o estado.</p>
        </div>
    </div>
    <form method="GET" action="/marketing" class="row g-3">
        <div class="col-12 col-xl-4">
            <label for="search" class="form-label">Búsqueda</label>
            <input type="search" class="form-control" id="search" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>" placeholder="Código, cohorte, coach, proyecto...">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
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
                <?php foreach (($projectNames ?? []) as $project): ?>
                    <option value="<?= htmlspecialchars($project) ?>" <?= (($filters['related_project'] ?? '') === $project) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($project) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-xl-2">
            <label for="start_date" class="form-label">Desde</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($filters['start_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-xl-2">
            <label for="end_date" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($filters['end_date'] ?? '')) ?>">
        </div>
        <div class="col-6 col-xl-2">
            <label for="business_model" class="form-label">Población o sub canal</label>
            <select class="form-select" id="business_model" name="business_model">
                <option value="">Todos</option>
                <option value="b2b" <?= (($filters['business_model'] ?? '') === 'b2b') ? 'selected' : '' ?>>B2B</option>
                <option value="b2c" <?= (($filters['business_model'] ?? '') === 'b2c') ? 'selected' : '' ?>>B2C</option>
            </select>
        </div>
        <div class="col-6 col-xl-2">
            <label for="cohort_status" class="form-label">Estado</label>
            <select class="form-select" id="cohort_status" name="cohort_status">
                <option value="">Todos</option>
                <option value="not_started" <?= (($filters['cohort_status'] ?? '') === 'not_started') ? 'selected' : '' ?>>No iniciado</option>
                <option value="in_progress" <?= (($filters['cohort_status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>En progreso</option>
                <option value="completed" <?= (($filters['cohort_status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completado</option>
                <option value="cancelled" <?= (($filters['cohort_status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Aplicar filtros
            </button>
        </div>
    </form>
</div>

<section class="app-panel">
    <div class="app-panel__header">
        <div>
            <h3 class="app-panel__title"><i class="bi bi-table text-primary"></i> Matriz de campañas por cohorte</h3>
            <p class="app-panel__subtitle">Estado de la campaña, avance de etapas y últimas actualizaciones por cohorte.</p>
        </div>
    </div>

    <?php if (empty($cohorts)): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-megaphone"></i></div>
            <h5 class="empty-state-title">Sin cohortes disponibles</h5>
            <p class="empty-state-text">No hay cohortes con los filtros actuales o crea una nueva cohorte para empezar.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código / Cohorte</th>
                        <th>Bootcamp name</th>
                        <th>Proyecto</th>
                        <th>Campaña</th>
                        <th>Avance etapas</th>
                        <th>Estado cohorte</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cohorts as $c): ?>
                        <?php
                        $cStages = $c['marketing_stages'] ?? [];
                        $cCompleted = 0;
                        foreach ($cStages as $st) {
                            if (($st['status'] ?? '') === 'completed') {
                                $cCompleted++;
                            }
                        }
                        $cTotalStages = max(1, count($cStages));
                        $cPct = (int) round(($cCompleted / $cTotalStages) * 100);

                        $cInfo = $c['marketing_info'] ?? null;
                        $campaignStatus = $cInfo && ($cInfo['campaign_status'] ?? 'Active') === 'Active' ? 'Active' : 'Completed';
                        $campaignBadge = $campaignStatus === 'Active'
                            ? 'bg-primary-subtle text-primary'
                            : 'bg-success-subtle text-success';
                        $campaignIcon = $campaignStatus === 'Active' ? 'bi-broadcast' : 'bi-check-circle';
                        ?>
                        <tr>
                            <td>
                                <a href="/cohorts/<?= (int) $c['id'] ?>/marketing" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars((string) ($c['cohort_code'] ?? 'N/A')) ?>
                                </a>
                                <div class="small text-muted"><?= htmlspecialchars((string) ($c['name'] ?? '—')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($c['bootcamp_type'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($c['related_project'] ?? '—')) ?></td>
                            <td>
                                <span class="badge <?= $campaignBadge ?>">
                                    <i class="bi <?= $campaignIcon ?> me-1"></i>
                                    <?= $campaignStatus ?>
                                </span>
                            </td>
                            <td style="min-width: 220px;">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= $cCompleted ?> / <?= $cTotalStages ?> etapas</span>
                                    <span><?= $cPct ?>%</span>
                                </div>
                                <div class="dashboard-mini-progress"><span data-style-width="<?= $cPct ?>%"></span></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars(marketingStatusLabel($c['training_status'] ?? null)) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="/cohorts/<?= (int) $c['id'] ?>/marketing" class="btn btn-sm btn-outline-primary" title="Ver marketing">
                                        <i class="bi bi-megaphone"></i>
                                    </a>
                                    <a href="/cohorts/<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
