<!-- Marketing Index — Cohort selector -->
<?php use App\Core\Auth; ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Selecciona una cohorte para gestionar su workflow de marketing.</p>
    </div>
</div>

<?php if (!empty($cohorts)): ?>
<div class="row g-3">
    <?php foreach ($cohorts as $c): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="card-icon bg-primary-subtle text-primary">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <code class="small text-muted"><?= htmlspecialchars($c['cohort_code']) ?></code>
                    </div>
                    <h6 class="fw-semibold mb-1"><?= htmlspecialchars($c['name']) ?></h6>
                    <?php if (!empty($c['bootcamp_type'])): ?>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($c['bootcamp_type']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent border-top-0 pt-0">
                    <a href="/cohorts/<?= $c['id'] ?>/marketing" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-arrow-right me-1"></i> Ver Marketing
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<!-- Empty State -->
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-megaphone"></i>
            </div>
            <h5 class="empty-state-title">No hay cohortes disponibles</h5>
            <p class="empty-state-text">Crea una cohorte primero para gestionar su workflow de marketing.</p>
            <a href="/cohorts/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Crear Cohorte
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.hover-shadow {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-2px);
}
</style>
