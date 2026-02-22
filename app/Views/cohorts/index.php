<!-- Cohorts Index View -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Manage and monitor all your cohorts.</p>
    </div>
    <a href="/cohorts/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Cohort
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($cohorts)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cohorts as $cohort): ?>
                            <tr>
                                <td><?= htmlspecialchars($cohort['id']) ?></td>
                                <td>
                                    <a href="/cohorts/<?= $cohort['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($cohort['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = match ($cohort['status'] ?? 'active') {
                                        'active'   => 'bg-success',
                                        'inactive' => 'bg-secondary',
                                        'archived' => 'bg-warning text-dark',
                                        default    => 'bg-info',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst(htmlspecialchars($cohort['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($cohort['start_date'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($cohort['end_date'] ?? '—') ?></td>
                                <td class="text-end">
                                    <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/cohorts/<?= $cohort['id'] ?>/edit" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="/cohorts/<?= $cohort['id'] ?>" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this cohort?');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">No cohorts found. Create your first one!</p>
                <a href="/cohorts/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Create Cohort
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
