<!-- Account Profile View (Self-Service) -->
<?php use App\Core\Auth; ?>

<?php
$roleLabels = [
    'admin'           => ['Administrador', 'bg-danger-subtle text-danger'],
    'admissions_b2b'  => ['Admisiones B2B', 'bg-info-subtle text-info'],
    'admissions_b2c'  => ['Admisiones B2C', 'bg-primary-subtle text-primary'],
    'marketing'       => ['Marketing', 'bg-warning-subtle text-warning'],
];
[$roleLabel, $roleClass] = $roleLabels[$user['role']] ?? [$user['role'], 'bg-secondary-subtle text-secondary'];
?>

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

<div class="row g-4">
    <!-- Left Column: Profile Summary Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <!-- Avatar -->
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(mb_substr($user['full_name'], 0, 1)) ?><?= strtoupper(mb_substr(explode(' ', $user['full_name'])[1] ?? '', 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
                <p class="text-muted mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                <span class="badge <?= $roleClass ?> fs-6"><?= $roleLabel ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-envelope me-2"></i>Email</span>
                    <span class="text-truncate ms-2" style="max-width: 180px;"><?= htmlspecialchars($user['email']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-check-circle me-2"></i>Estado</span>
                    <?php if ($user['is_active']): ?>
                        <span class="badge bg-success-subtle text-success">Activo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                    <?php endif; ?>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-clock me-2"></i>Último acceso</span>
                    <span><?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : '—' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-calendar me-2"></i>Miembro desde</span>
                    <span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </li>
            </ul>
        </div>

        <!-- System Info Card -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Sistema</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-filetype-php me-2"></i>PHP</span>
                    <span class="badge bg-primary-subtle text-primary"><?= PHP_VERSION ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-calendar-date me-2"></i>Fecha</span>
                    <span id="systemDate">--/--/----</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-clock-history me-2"></i>Hora</span>
                    <span id="systemTime">--:--:--</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted"><i class="bi bi-shield me-2"></i>Rol</span>
                    <span class="badge <?= $roleClass ?>"><?= $roleLabel ?></span>
                </li>
            </ul>
        </div>
        <script>
        // Live system clock — runs immediately and updates every second
        (function updateClock() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            document.getElementById('systemDate').textContent =
                pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear();
            document.getElementById('systemTime').textContent =
                pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
            setTimeout(updateClock, 1000);
        })();
        </script>
    </div>

    <!-- Right Column: Edit Forms -->
    <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Información del Perfil</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/account" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label text-muted">Nombre de usuario</label>
                            <input type="text" class="form-control-plaintext fw-semibold" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                            <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($user['email']) ?>">
                            <div class="invalid-feedback">Ingresa un email válido.</div>
                        </div>
                        <div class="col-12">
                            <label for="full_name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                   value="<?= htmlspecialchars($user['full_name']) ?>">
                            <div class="invalid-feedback">El nombre completo es requerido.</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/account/password" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="current_password" class="form-label">Contraseña Actual <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <div class="invalid-feedback">Ingresa tu contraseña actual.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <div class="form-text">Mínimo 8 caracteres.</div>
                            <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            <div class="invalid-feedback">Confirma la nueva contraseña.</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-shield-lock me-1"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
