<!-- Cohort Edit View -->
<div class="mb-3">
    <a href="/cohorts/<?= $cohort['id'] ?>" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Back to Cohort
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Cohort</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/cohorts/<?= $cohort['id'] ?>">
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label for="name" class="form-label">Cohort Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= htmlspecialchars($cohort['name']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                        ><?= htmlspecialchars($cohort['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?= htmlspecialchars($cohort['start_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?= htmlspecialchars($cohort['end_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active"   <?= ($cohort['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($cohort['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="archived" <?= ($cohort['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Update Cohort
                        </button>
                        <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
