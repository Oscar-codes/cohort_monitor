<!-- Cohort Show View -->
<div class="mb-3">
    <a href="/cohorts" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Back to Cohorts
    </a>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars($cohort['name']) ?></h5>
                <?php
                $badgeClass = match ($cohort['status'] ?? 'active') {
                    'active'   => 'bg-success',
                    'inactive' => 'bg-secondary',
                    'archived' => 'bg-warning text-dark',
                    default    => 'bg-info',
                };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($cohort['status'])) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted"><?= nl2br(htmlspecialchars($cohort['description'] ?? 'No description provided.')) ?></p>

                <hr>

                <div class="row">
                    <div class="col-sm-6">
                        <small class="text-muted">Start Date</small>
                        <p class="fw-semibold"><?= htmlspecialchars($cohort['start_date'] ?? '—') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">End Date</small>
                        <p class="fw-semibold"><?= htmlspecialchars($cohort['end_date'] ?? '—') ?></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <small class="text-muted">Created</small>
                        <p class="fw-semibold"><?= htmlspecialchars($cohort['created_at'] ?? '—') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Last Updated</small>
                        <p class="fw-semibold"><?= htmlspecialchars($cohort['updated_at'] ?? '—') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="/cohorts/<?= $cohort['id'] ?>/edit" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i> Edit Cohort
                </a>
                <form method="POST" action="/cohorts/<?= $cohort['id'] ?>"
                      onsubmit="return confirm('Are you sure you want to delete this cohort?');">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i> Delete Cohort
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
