<!-- Account Profile View -->
<?php
use App\Core\Auth;

/** @var array<string, mixed> $user */
$user = isset($user) && is_array($user) ? $user : [];

$userRole = (string) ($user['role'] ?? 'usuario');
$userFullName = (string) ($user['full_name'] ?? 'Usuario');
$userUsername = (string) ($user['username'] ?? 'usuario');
$userEmail = (string) ($user['email'] ?? '');
$userIsActive = (bool) ($user['is_active'] ?? false);
$userLastLoginAt = (string) ($user['last_login_at'] ?? '');
$userCreatedAt = (string) ($user['created_at'] ?? '');

$roleLabels = [
    'admin' => ['Administrador', 'bg-danger-subtle text-danger', 'bi-shield-lock'],
    'admissions_b2b' => ['Admisiones B2B', 'bg-info-subtle text-info', 'bi-building'],
    'admissions_b2c' => ['Admisiones B2C', 'bg-primary-subtle text-primary', 'bi-people'],
    'finance' => ['Finanzas', 'bg-success-subtle text-success', 'bi-cash-stack'],
    'marketing' => ['Marketing', 'bg-warning-subtle text-warning', 'bi-megaphone'],
];
[$roleLabel, $roleClass, $roleIcon] = $roleLabels[$userRole] ?? [$userRole, 'bg-secondary-subtle text-secondary', 'bi-person-badge'];

$nameParts = preg_split('/\s+/', trim($userFullName));
$initials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
$initials = $initials !== '' ? $initials : 'U';
$lastLogin = $userLastLoginAt !== '' ? date('d/m/Y H:i', strtotime($userLastLoginAt)) : 'Sin registro';
$createdAt = $userCreatedAt !== '' ? date('d/m/Y', strtotime($userCreatedAt)) : 'Sin fecha';
?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<section class="account-hero mb-4">
    <div class="account-hero__identity">
        <span class="account-avatar account-avatar--lg"><?= htmlspecialchars($initials) ?></span>
        <div>
            <span class="dashboard-hero__eyebrow">
                <i class="bi bi-person-circle"></i>
                Mi cuenta
            </span>
            <h1><?= htmlspecialchars($userFullName) ?></h1>
            <p>@<?= htmlspecialchars($userUsername) ?> - <?= htmlspecialchars($userEmail) ?></p>
        </div>
    </div>
    <div class="account-hero__meta">
        <span class="badge badge-status <?= $roleClass ?>">
            <i class="bi <?= $roleIcon ?> me-1"></i><?= htmlspecialchars($roleLabel) ?>
        </span>
        <?php if ($userIsActive): ?>
            <span class="badge badge-status bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
        <?php else: ?>
            <span class="badge badge-status bg-secondary-subtle text-secondary"><i class="bi bi-pause-circle me-1"></i>Inactivo</span>
        <?php endif; ?>
    </div>
</section>

<div class="account-summary mb-4">
    <article class="account-summary-card">
        <span class="account-summary-card__icon is-primary"><i class="bi bi-envelope"></i></span>
        <div>
            <p>Email</p>
            <strong><?= htmlspecialchars($userEmail) ?></strong>
            <small>Contacto principal</small>
        </div>
    </article>
    <article class="account-summary-card">
        <span class="account-summary-card__icon is-success"><i class="bi bi-clock-history"></i></span>
        <div>
            <p>Ultimo acceso</p>
            <strong><?= htmlspecialchars($lastLogin) ?></strong>
            <small>Actividad reciente</small>
        </div>
    </article>
    <article class="account-summary-card">
        <span class="account-summary-card__icon is-info"><i class="bi bi-calendar-check"></i></span>
        <div>
            <p>Miembro desde</p>
            <strong><?= htmlspecialchars($createdAt) ?></strong>
            <small>Registro de cuenta</small>
        </div>
    </article>
    <article class="account-summary-card">
        <span class="account-summary-card__icon is-warning"><i class="bi bi-shield-check"></i></span>
        <div>
            <p>Rol</p>
            <strong><?= htmlspecialchars($roleLabel) ?></strong>
            <small>Permisos activos</small>
        </div>
    </article>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <section class="app-panel account-side-panel mb-4">
            <div class="account-profile-card">
                <span class="account-avatar account-avatar--xl"><?= htmlspecialchars($initials) ?></span>
                <h2><?= htmlspecialchars($userFullName) ?></h2>
                <p>@<?= htmlspecialchars($userUsername) ?></p>
                <span class="badge badge-status <?= $roleClass ?>"><?= htmlspecialchars($roleLabel) ?></span>
            </div>
            <dl class="account-meta-list">
                <div>
                    <dt>Estado</dt>
                    <dd><?= $userIsActive ? 'Activo' : 'Inactivo' ?></dd>
                </div>
                <div>
                    <dt>PHP</dt>
                    <dd><?= htmlspecialchars(PHP_VERSION) ?></dd>
                </div>
                <div>
                    <dt>Fecha</dt>
                    <dd id="systemDate">--/--/----</dd>
                </div>
                <div>
                    <dt>Hora</dt>
                    <dd id="systemTime">--:--:--</dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="col-xl-8">
        <section class="app-panel account-form-panel mb-4">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-person"></i> Informacion del perfil</h2>
                    <p class="app-panel__subtitle">Actualiza tus datos visibles y correo de contacto.</p>
                </div>
            </div>
            <form method="POST" action="/account" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control account-readonly-input" id="username" value="<?= htmlspecialchars($userUsername) ?>" readonly>
                        <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($userEmail) ?>">
                        <div class="invalid-feedback">Ingresa un email valido.</div>
                    </div>
                    <div class="col-12">
                        <label for="full_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?= htmlspecialchars($userFullName) ?>">
                        <div class="invalid-feedback">El nombre completo es requerido.</div>
                    </div>
                </div>
                <div class="account-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </section>

        <section class="app-panel account-form-panel">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-lock"></i> Seguridad</h2>
                    <p class="app-panel__subtitle">Cambia tu contrasena usando una clave de al menos 8 caracteres.</p>
                </div>
            </div>
            <form method="POST" action="/account/password" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="current_password" class="form-label">Contrasena actual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#current_password" aria-label="Mostrar contrasena actual">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">Ingresa tu contrasena actual.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">Nueva contrasena <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#new_password" aria-label="Mostrar nueva contrasena">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">La contrasena debe tener al menos 8 caracteres.</div>
                        </div>
                        <div class="form-text">Minimo 8 caracteres.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirmar contrasena <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#confirm_password" aria-label="Mostrar confirmacion de contrasena">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">Confirma la nueva contrasena.</div>
                        </div>
                    </div>
                </div>
                <div class="account-form-actions">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-lock me-1"></i> Cambiar contrasena
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>


