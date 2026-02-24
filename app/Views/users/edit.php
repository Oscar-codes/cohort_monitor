<!-- User Edit View (Admin) -->
<?php use App\Core\Auth; ?>

<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/users" class="text-decoration-none">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($user['username']) ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="/users/<?= $user['id'] ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="_method" value="PUT">

                    <!-- Account Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-person"></i> Cuenta
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required
                                       value="<?= htmlspecialchars($user['username']) ?>">
                                <div class="invalid-feedback">El nombre de usuario es requerido.</div>
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
                    </div>

                    <!-- Role & Status Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-shield"></i> Rol y Estado
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin"          <?= $user['role'] === 'admin'          ? 'selected' : '' ?>>Administrador</option>
                                    <option value="admissions_b2b" <?= $user['role'] === 'admissions_b2b' ? 'selected' : '' ?>>Admisiones B2B</option>
                                    <option value="admissions_b2c" <?= $user['role'] === 'admissions_b2c' ? 'selected' : '' ?>>Admisiones B2C</option>
                                    <option value="marketing"      <?= $user['role'] === 'marketing'      ? 'selected' : '' ?>>Marketing</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="is_active" class="form-label">Estado</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-lock"></i> Seguridad
                        </div>
                        <div>
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6">
                            <div class="form-text">Dejar vacío para mantener la contraseña actual.</div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Actualizar Usuario
                        </button>
                        <a href="/users" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
