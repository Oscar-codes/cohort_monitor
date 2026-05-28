<!-- User Create View (Admin) -->
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
        <li class="breadcrumb-item active" aria-current="page">Nuevo usuario</li>
    </ol>
</nav>

<section class="form-page-hero users-form-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-person-plus"></i>
            Administracion
        </span>
        <h1 class="form-page-hero__title">Nuevo usuario</h1>
        <p class="form-page-hero__copy">Crea una cuenta con rol, estado inicial y contrasena temporal.</p>
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
                    <h3><i class="bi bi-person-gear"></i> Datos de acceso</h3>
                    <p>Completa la informacion requerida para habilitar la cuenta.</p>
                </div>
            </div>
            <div class="form-workbench__body">
                <form method="POST" action="/users" class="needs-validation" novalidate>
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-person"></i> Cuenta
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                                <div class="invalid-feedback">El nombre de usuario es requerido.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                                <div class="invalid-feedback">Ingresa un email valido.</div>
                            </div>
                            <div class="col-12">
                                <label for="full_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required autocomplete="name">
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
                                    <option value="admin">Administrador</option>
                                    <option value="admissions_b2b">Admisiones B2B</option>
                                    <option value="admissions_b2c">Admisiones B2C</option>
                                    <option value="marketing">Marketing</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="is_active" class="form-label">Estado</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" selected>Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-lock"></i> Seguridad
                        </div>
                        <label for="password" class="form-label">Contrasena <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Mostrar contrasena">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">La contrasena debe tener al menos 6 caracteres.</div>
                        </div>
                        <div class="form-text">Minimo 6 caracteres.</div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Crear usuario
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


