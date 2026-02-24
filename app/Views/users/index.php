<!-- Users Index View (Admin) -->
<?php use App\Core\Auth; ?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">Administra los usuarios del sistema y sus roles.</p>
    </div>
    <a href="/users/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>
        <span class="d-none d-sm-inline">Nuevo Usuario</span>
        <span class="d-sm-none">Nuevo</span>
    </a>
</div>

<?php
$roleLabels = [
    'admin'           => ['Administrador', 'bg-danger-subtle text-danger'],
    'admissions_b2b'  => ['Admisiones B2B', 'bg-info-subtle text-info'],
    'admissions_b2c'  => ['Admisiones B2C', 'bg-primary-subtle text-primary'],
    'marketing'       => ['Marketing', 'bg-warning-subtle text-warning'],
];
?>

<?php if (!empty($users)): ?>
<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-primary"><?= count($users) ?></div>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-success"><?= count(array_filter($users, fn($u) => $u['is_active'])) ?></div>
                <small class="text-muted">Activos</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-danger"><?= count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin')) ?></div>
                <small class="text-muted">Admins</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-body-secondary border-0">
            <div class="card-body py-3 text-center">
                <div class="fs-4 fw-bold text-secondary"><?= count(array_filter($users, fn($u) => !$u['is_active'])) ?></div>
                <small class="text-muted">Inactivos</small>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Usuario</th>
                    <th class="d-none d-md-table-cell">Email</th>
                    <th class="text-center">Rol</th>
                    <th class="text-center d-none d-sm-table-cell">Estado</th>
                    <th class="text-center d-none d-lg-table-cell">Último Acceso</th>
                    <th class="text-end" style="width: 160px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <!-- ID -->
                        <td>
                            <span class="text-muted">#<?= $u['id'] ?></span>
                        </td>
                        
                        <!-- User Info -->
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold"><?= htmlspecialchars($u['username']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($u['full_name']) ?></small>
                            </div>
                        </td>
                        
                        <!-- Email -->
                        <td class="d-none d-md-table-cell">
                            <small><?= htmlspecialchars($u['email']) ?></small>
                        </td>
                        
                        <!-- Role -->
                        <td class="text-center">
                            <?php [$rl, $rc] = $roleLabels[$u['role']] ?? [$u['role'], 'bg-secondary-subtle text-secondary']; ?>
                            <span class="badge badge-status <?= $rc ?>"><?= htmlspecialchars($rl) ?></span>
                        </td>
                        
                        <!-- Status -->
                        <td class="text-center d-none d-sm-table-cell">
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-status bg-success-subtle text-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-status bg-secondary-subtle text-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <!-- Last Login -->
                        <td class="text-center d-none d-lg-table-cell">
                            <small class="text-muted">
                                <?= !empty($u['last_login_at']) ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : '—' ?>
                            </small>
                        </td>
                        
                        <!-- Actions -->
                        <td class="text-end">
                            <div class="action-buttons justify-content-end">
                                <a href="/users/<?= $u['id'] ?>/edit" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int)$u['id'] !== Auth::id()): ?>
                                <form method="POST" action="/users/<?= $u['id'] ?>/toggle-status" class="d-inline">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" data-bs-toggle="tooltip" title="<?= $u['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" action="/users/<?= $u['id'] ?>/reset-password" class="d-inline" data-confirm="¿Restablecer la contraseña de este usuario?">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Restablecer Contraseña">
                                        <i class="bi bi-key"></i>
                                    </button>
                                </form>
                                <form method="POST" action="/users/<?= $u['id'] ?>" class="d-inline" data-confirm="¿Eliminar este usuario?">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Empty State -->
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-people"></i>
            </div>
            <h5 class="empty-state-title">No hay usuarios aún</h5>
            <p class="empty-state-text">Comienza creando el primer usuario del sistema.</p>
            <a href="/users/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Crear Primer Usuario
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
