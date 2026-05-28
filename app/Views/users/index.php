<!-- Users Index View (Admin) -->
<?php
use App\Core\Auth;

$roleMeta = [
    'admin' => ['Administrador', 'bg-danger-subtle text-danger', 'bi-shield-lock', 'danger'],
    'admissions_b2b' => ['Admisiones B2B', 'bg-info-subtle text-info', 'bi-building', 'info'],
    'admissions_b2c' => ['Admisiones B2C', 'bg-primary-subtle text-primary', 'bi-people', 'primary'],
    'finance' => ['Finanzas', 'bg-success-subtle text-success', 'bi-cash-stack', 'success'],
    'marketing' => ['Marketing', 'bg-warning-subtle text-warning', 'bi-megaphone', 'warning'],
];

$totalUsers = count($users ?? []);
$activeUsers = count(array_filter($users ?? [], fn($u) => !empty($u['is_active'])));
$inactiveUsers = $totalUsers - $activeUsers;
$adminUsers = count(array_filter($users ?? [], fn($u) => ($u['role'] ?? '') === 'admin'));
$roleCounts = [];
foreach (($users ?? []) as $u) {
    $roleCounts[$u['role'] ?? 'unknown'] = ($roleCounts[$u['role'] ?? 'unknown'] ?? 0) + 1;
}

if (!function_exists('userInitials')) {
    function userInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
        return $initials !== '' ? $initials : 'U';
    }
}

if (!function_exists('userLastLogin')) {
    function userLastLogin(?string $date): string
    {
        return $date ? date('d/m/Y H:i', strtotime($date)) : 'Sin acceso';
    }
}
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

<section class="users-hero mb-4">
    <div>
        <span class="dashboard-hero__eyebrow">
            <i class="bi bi-people"></i>
            Administracion
        </span>
        <h1>Usuarios y permisos</h1>
        <p>Gestiona accesos, roles, estado de cuenta y acciones de seguridad.</p>
    </div>
    <div class="users-hero__actions">
        <a href="/users/create" class="btn btn-light btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Nuevo usuario
        </a>
    </div>
</section>

<div class="users-summary mb-4">
    <article class="users-kpi">
        <span class="users-kpi__icon is-primary"><i class="bi bi-people"></i></span>
        <div>
            <p>Total</p>
            <strong><?= $totalUsers ?></strong>
            <small>Usuarios registrados</small>
        </div>
    </article>
    <article class="users-kpi">
        <span class="users-kpi__icon is-success"><i class="bi bi-check-circle"></i></span>
        <div>
            <p>Activos</p>
            <strong><?= $activeUsers ?></strong>
            <small>Con acceso permitido</small>
        </div>
    </article>
    <article class="users-kpi">
        <span class="users-kpi__icon is-danger"><i class="bi bi-shield-lock"></i></span>
        <div>
            <p>Admins</p>
            <strong><?= $adminUsers ?></strong>
            <small>Privilegios completos</small>
        </div>
    </article>
    <article class="users-kpi">
        <span class="users-kpi__icon is-muted"><i class="bi bi-pause-circle"></i></span>
        <div>
            <p>Inactivos</p>
            <strong><?= $inactiveUsers ?></strong>
            <small>Sin acceso actual</small>
        </div>
    </article>
</div>

<?php if (!empty($users)): ?>
<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <section class="app-panel users-role-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-person-badge"></i> Roles</h2>
                    <p class="app-panel__subtitle">Distribucion actual de permisos por area.</p>
                </div>
            </div>
            <div class="users-role-list">
                <?php foreach ($roleMeta as $roleKey => $meta): ?>
                    <?php [$label, $badgeClass, $icon, $tone] = $meta; $count = (int) ($roleCounts[$roleKey] ?? 0); ?>
                    <article class="users-role-item">
                        <span class="users-role-item__icon is-<?= htmlspecialchars($tone) ?>"><i class="bi <?= htmlspecialchars($icon) ?>"></i></span>
                        <div>
                            <div class="users-role-item__top">
                                <strong><?= htmlspecialchars($label) ?></strong>
                                <span><?= $count ?></span>
                            </div>
                            <div class="dashboard-mini-progress"><span data-style-width="<?= $totalUsers > 0 ? min(100, (int) round(($count / $totalUsers) * 100)) : 0 ?>%"></span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-8">
        <section class="app-panel users-directory-panel h-100">
            <div class="app-panel__header">
                <div>
                    <h2 class="app-panel__title"><i class="bi bi-table"></i> Directorio</h2>
                    <p class="app-panel__subtitle">Usuarios ordenados por fecha de creacion reciente.</p>
                </div>
            </div>
            <div class="table-responsive users-table">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="text-center">Rol</th>
                            <th class="text-center d-none d-sm-table-cell">Estado</th>
                            <th class="text-center d-none d-lg-table-cell">Ultimo acceso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php
                            [$roleLabel, $roleClass] = $roleMeta[$u['role']] ?? [$u['role'], 'bg-secondary-subtle text-secondary'];
                            $isSelf = (int) $u['id'] === Auth::id();
                            ?>
                            <tr>
                                <td>
                                    <div class="users-person">
                                        <span class="users-avatar"><?= htmlspecialchars(userInitials($u['full_name'] ?? $u['username'])) ?></span>
                                        <div>
                                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                                            <small><?= htmlspecialchars($u['full_name']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><small><?= htmlspecialchars($u['email']) ?></small></td>
                                <td class="text-center"><span class="badge badge-status <?= $roleClass ?>"><?= htmlspecialchars($roleLabel) ?></span></td>
                                <td class="text-center d-none d-sm-table-cell">
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge badge-status bg-success-subtle text-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-status bg-secondary-subtle text-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center d-none d-lg-table-cell"><small class="text-muted"><?= htmlspecialchars(userLastLogin($u['last_login_at'] ?? null)) ?></small></td>
                                <td class="text-end">
                                    <div class="action-buttons justify-content-end">
                                        <a href="/users/<?= (int) $u['id'] ?>/edit" class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (!$isSelf): ?>
                                            <form method="POST" action="/users/<?= (int) $u['id'] ?>/toggle-status" class="d-inline" data-confirm="<?= $u['is_active'] ? 'Desactivar este usuario?' : 'Activar este usuario?' ?>">
                                                <button type="submit" class="btn btn-icon btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" data-bs-toggle="tooltip" title="<?= $u['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                                    <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="/users/<?= (int) $u['id'] ?>/reset-password" class="d-inline" data-confirm="Restablecer la contrasena de este usuario?">
                                                <button type="submit" class="btn btn-icon btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Restablecer contrasena">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="/users/<?= (int) $u['id'] ?>" class="d-inline" data-confirm="Eliminar este usuario?">
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

            <div class="users-mobile-list">
                <?php foreach ($users as $u): ?>
                    <?php
                    [$roleLabel, $roleClass] = $roleMeta[$u['role']] ?? [$u['role'], 'bg-secondary-subtle text-secondary'];
                    $isSelf = (int) $u['id'] === Auth::id();
                    ?>
                    <article class="users-mobile-card">
                        <div class="users-mobile-card__top">
                            <div class="users-person">
                                <span class="users-avatar"><?= htmlspecialchars(userInitials($u['full_name'] ?? $u['username'])) ?></span>
                                <div>
                                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                                    <small><?= htmlspecialchars($u['full_name']) ?></small>
                                </div>
                            </div>
                            <span class="badge badge-status <?= $roleClass ?>"><?= htmlspecialchars($roleLabel) ?></span>
                        </div>
                        <div class="users-mobile-card__meta">
                            <span><i class="bi bi-envelope"></i><?= htmlspecialchars($u['email']) ?></span>
                            <span><i class="bi bi-clock-history"></i><?= htmlspecialchars(userLastLogin($u['last_login_at'] ?? null)) ?></span>
                            <span><i class="bi bi-circle-fill"></i><?= $u['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                        </div>
                        <div class="users-mobile-card__actions">
                            <a href="/users/<?= (int) $u['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </a>
                            <?php if (!$isSelf): ?>
                                <form method="POST" action="/users/<?= (int) $u['id'] ?>/toggle-status" data-confirm="<?= $u['is_active'] ? 'Desactivar este usuario?' : 'Activar este usuario?' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>">
                                        <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?> me-1"></i><?= $u['is_active'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php else: ?>
<section class="app-panel">
    <div class="empty-state py-5">
        <div class="empty-state-icon">
            <i class="bi bi-people"></i>
        </div>
        <h5 class="empty-state-title">No hay usuarios aun</h5>
        <p class="empty-state-text">Comienza creando el primer usuario del sistema.</p>
        <a href="/users/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Crear primer usuario
        </a>
    </div>
</section>
<?php endif; ?>
