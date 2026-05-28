<!-- User Edit View (Admin) -->
<?php
use App\Core\Auth;

/** @var array<string, mixed> $user */
$user = isset($user) && is_array($user) ? $user : [];

$userId = (int) ($user['id'] ?? 0);
$userUsername = (string) ($user['username'] ?? 'usuario');
$userEmail = (string) ($user['email'] ?? '');
$userFullName = (string) ($user['full_name'] ?? '');
$userRole = (string) ($user['role'] ?? 'marketing');
$userIsActive = (bool) ($user['is_active'] ?? true);

$roleLabels = [
    'admin' => 'Administrador',
    'admissions_b2b' => 'Admisiones B2B',
    'admissions_b2c' => 'Admisiones B2C',
    'finance' => 'Finanzas',
    'marketing' => 'Marketing',
];
?>

<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/users" class="text-decoration-none">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($userUsername) ?></li>
    </ol>
</nav>

<section class="form-page-hero users-form-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-person-gear"></i>
            Administracion
        </span>
        <h1 class="form-page-hero__title">Editar usuario</h1>
        <p class="form-page-hero__copy"><?= htmlspecialchars($userFullName) ?> - <?= htmlspecialchars($roleLabels[$userRole] ?? $userRole) ?></p>
    </div>
    <a href="/users" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</section>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <section class="app-panel form-workbench">
            <div class="form-workbench__header">
                <div>
                    <h3><i class="bi bi-person-gear"></i> Configuracion de usuario</h3>
                    <p>Actualiza identidad, rol, estado y credenciales cuando sea necesario.</p>
                </div>
            </div>
            <div class="form-workbench__body">
                <form method="POST" action="/users/<?= $userId ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-person"></i> Cuenta
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($userUsername) ?>" autocomplete="username">
                                <div class="invalid-feedback">El nombre de usuario es requerido.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($userEmail) ?>" autocomplete="email">
                                <div class="invalid-feedback">Ingresa un email valido.</div>
                            </div>
                            <div class="col-12">
                                <label for="full_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required value="<?= htmlspecialchars($userFullName) ?>" autocomplete="name">
                                <div class="invalid-feedback">El nombre completo es requerido.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-shield"></i> Rol y estado
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" <?= $userRole === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="admissions_b2b" <?= $userRole === 'admissions_b2b' ? 'selected' : '' ?>>Admisiones B2B</option>
                                    <option value="admissions_b2c" <?= $userRole === 'admissions_b2c' ? 'selected' : '' ?>>Admisiones B2C</option>
                                    <option value="finance" <?= $userRole === 'finance' ? 'selected' : '' ?>>Finanzas</option>
                                    <option value="marketing" <?= $userRole === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="is_active" class="form-label">Estado</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" <?= $userIsActive ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= !$userIsActive ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-lock"></i> Seguridad
                        </div>
                        <label for="password" class="form-label">Nueva contrasena</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" minlength="6" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Mostrar nueva contrasena">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Dejar vacio para mantener la contrasena actual.</div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Actualizar usuario
                        </button>
                        <a href="/users" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>


