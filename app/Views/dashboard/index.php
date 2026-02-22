<!-- Dashboard Index View -->
<div class="row g-4 mb-4">
    <!-- Total Cohorts Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                    <i class="bi bi-people-fill fs-3 text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Total Cohorts</h6>
                    <h3 class="mb-0 fw-bold"><?= $totalCohorts ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Cohorts Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Active Cohorts</h6>
                    <h3 class="mb-0 fw-bold"><?= $activeCohorts ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Students Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3">
                    <i class="bi bi-mortarboard-fill fs-3 text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Total Students</h6>
                    <h3 class="mb-0 fw-bold"><?= $totalStudents ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="/cohorts/create" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-1"></i> New Cohort
                </a>
                <a href="/cohorts" class="btn btn-outline-secondary">
                    <i class="bi bi-list me-1"></i> View All Cohorts
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Info</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <small class="text-muted">PHP Version:</small><br>
                        <strong><?= PHP_VERSION ?></strong>
                    </li>
                    <li class="mb-2">
                        <small class="text-muted">Server Time:</small><br>
                        <strong><?= date('Y-m-d H:i:s') ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
