<!-- Dashboard Index View -->
<?php use App\Core\Auth; ?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <div>
                <h2 class="h4 fw-bold mb-1">¡Hola, <?= e(explode(' ', Auth::user()['full_name'] ?? 'Usuario')[0]) ?>!</h2>
                <p class="text-muted mb-0">Aquí tienes un resumen de tus cohortes</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/cohorts/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Cohorte
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 g-lg-4 mb-4">
    <!-- Total Cohorts -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="stat-label">Total Cohortes</p>
                        <h3 class="stat-value"><?= $totalCohorts ?? 0 ?></h3>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <a href="/cohorts" class="text-decoration-none small">
                    Ver todas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Cohorts -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="stat-label">Cohortes Activas</p>
                        <h3 class="stat-value"><?= $activeCohorts ?? 0 ?></h3>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <span class="text-success small">
                    <i class="bi bi-graph-up"></i> En progreso
                </span>
            </div>
        </div>
    </div>

    <!-- Total Students -->
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="stat-label">Total Estudiantes</p>
                        <h3 class="stat-value"><?= $totalStudents ?? 0 ?></h3>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <span class="badge bg-secondary-subtle text-secondary">Próximamente</span>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row g-3 g-lg-4">
    <!-- Quick Actions -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-lightning-charge text-warning me-2"></i>
                <span class="fw-semibold">Acciones Rápidas</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4">
                        <a href="/cohorts/create" class="card bg-light border-0 text-decoration-none h-100 quick-action-card">
                            <div class="card-body text-center py-4">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-plus-lg text-primary fs-5"></i>
                                </div>
                                <h6 class="mb-1">Nueva Cohorte</h6>
                                <small class="text-muted">Crear una cohorte</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <a href="/cohorts" class="card bg-light border-0 text-decoration-none h-100 quick-action-card">
                            <div class="card-body text-center py-4">
                                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-list-ul text-success fs-5"></i>
                                </div>
                                <h6 class="mb-1">Ver Cohortes</h6>
                                <small class="text-muted">Listar todas</small>
                            </div>
                        </a>
                    </div>
                    <?php if (Auth::hasRole(['admin', 'marketing'])): ?>
                    <div class="col-sm-6 col-md-4">
                        <a href="/marketing" class="card bg-light border-0 text-decoration-none h-100 quick-action-card">
                            <div class="card-body text-center py-4">
                                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-megaphone text-warning fs-5"></i>
                                </div>
                                <h6 class="mb-1">Marketing</h6>
                                <small class="text-muted">Etapas de marketing</small>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-gear text-secondary me-2"></i>
                <span class="fw-semibold">Sistema</span>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">
                            <i class="bi bi-code-slash me-2"></i>PHP
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary"><?= PHP_VERSION ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">
                            <i class="bi bi-calendar3 me-2"></i>Fecha
                        </span>
                        <span class="fw-medium" id="dash-date">--/--/----</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">
                            <i class="bi bi-clock me-2"></i>Hora
                        </span>
                        <span class="fw-medium" id="dash-time">--:--:--</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                        <span class="text-muted">
                            <i class="bi bi-person me-2"></i>Rol
                        </span>
                        <?php
                            $roles = [
                                'admin' => ['Admin', 'danger'],
                                'admissions_b2b' => ['B2B', 'info'],
                                'admissions_b2c' => ['B2C', 'primary'],
                                'marketing' => ['Marketing', 'warning'],
                            ];
                            $r = Auth::role();
                            [$roleLabel, $roleColor] = $roles[$r] ?? [ucfirst($r ?? ''), 'secondary'];
                        ?>
                        <span class="badge bg-<?= $roleColor ?>-subtle text-<?= $roleColor ?>"><?= $roleLabel ?></span>
                    </li>
                </ul>
            </div>
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