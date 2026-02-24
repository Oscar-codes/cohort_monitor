<!-- Top Header Bar -->
<?php use App\Core\Auth; ?>
<header class="header bg-white border-bottom sticky-top">
    <div class="header-content">
        <!-- Left: Mobile Toggle + Page Title -->
        <div class="header-left">
            <!-- Mobile Menu Toggle -->
            <button type="button" class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggle" aria-label="Abrir menú">
                <i class="bi bi-list fs-4"></i>
            </button>

            <!-- Page Title & Breadcrumb -->
            <div class="header-title">
                <h1 class="h5 mb-0 fw-semibold"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                <nav aria-label="breadcrumb" class="d-none d-sm-block">
                    <ol class="breadcrumb breadcrumb-sm mb-0">
                        <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Inicio</a></li>
                        <?php foreach ($breadcrumb as $item): ?>
                            <?php if (isset($item['active']) && $item['active']): ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['label']) ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($item['url'] ?? '#') ?>" class="text-decoration-none"><?= htmlspecialchars($item['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: User Menu -->
        <div class="header-right">
            <?php
                $roleBadges = [
                    'admin'           => ['danger', 'bi-shield-check'],
                    'admissions_b2b'  => ['info', 'bi-building'],
                    'admissions_b2c'  => ['primary', 'bi-person-check'],
                    'marketing'       => ['warning', 'bi-megaphone'],
                ];
                $roleLabels = [
                    'admin'           => 'Admin',
                    'admissions_b2b'  => 'B2B',
                    'admissions_b2c'  => 'B2C',
                    'marketing'       => 'Marketing',
                ];
                $role  = Auth::role();
                $badge = $roleBadges[$role][0] ?? 'secondary';
                $icon  = $roleBadges[$role][1] ?? 'bi-person';
                $label = $roleLabels[$role] ?? ucfirst($role ?? 'Usuario');
            ?>

            <!-- Role Badge (Desktop) -->
            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> d-none d-md-inline-flex align-items-center gap-1">
                <i class="bi <?= $icon ?>"></i>
                <?= e($label) ?>
            </span>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="user-name d-none d-sm-inline"><?= e(Auth::user()['full_name'] ?? 'Usuario') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li class="dropdown-header">
                        <div class="fw-semibold"><?= e(Auth::user()['full_name'] ?? 'Usuario') ?></div>
                        <small class="text-muted"><?= e(Auth::user()['email'] ?? '') ?></small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center text-danger" href="/logout">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
