<!-- Sidebar Navigation -->
<?php use App\Core\Auth; ?>
<?php
    $active = static fn(string $page): string => ($activePage ?? '') === $page ? 'active' : '';
?>
<nav id="sidebar" class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" aria-labelledby="sidebarLabel">
    <div class="offcanvas-header d-lg-none border-bottom border-secondary border-opacity-25 px-3 py-2">
        <a href="/" class="d-flex align-items-center text-decoration-none gap-2">
            <img src="/assets/images/logo/kodigo.jpg" alt="Kodigo Logo" class="brand-logo rounded" width="36" height="36">
            <span class="text-white fw-semibold fs-5" id="sidebarLabel">Cohort Monitor</span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar"></button>
    </div>

    <div class="sidebar-brand d-none d-lg-flex">
        <a href="/" class="d-flex align-items-center text-decoration-none">
            <img src="/assets/images/logo/kodigo.jpg" alt="Kodigo Logo" class="brand-logo rounded" width="40" height="40">
            <span class="brand-text">Cohort Monitor</span>
        </a>
    </div>

    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-section-label"><span>Principal</span></li>
            <li class="nav-item">
                <a href="/" class="nav-link <?= $active('dashboard') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section-label"><span>Operacion</span></li>
            <li class="nav-item">
                <a href="/cohorts" class="nav-link <?= $active('cohorts') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Cohortes">
                    <i class="bi bi-people"></i>
                    <span>Cohortes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/cohorts/master" class="nav-link <?= $active('cohorts-master') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Plan Maestro Cohort">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Plan Maestro</span>
                </a>
            </li>

            <?php if (Auth::isAdmin()): ?>
            <li class="nav-item">
                <a href="/cohorts/import" class="nav-link <?= $active('import') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Importar cohortes">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Importar</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (Auth::hasRole(['admin', 'marketing'])): ?>
            <li class="nav-item">
                <a href="/marketing" class="nav-link <?= $active('marketing') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Marketing">
                    <i class="bi bi-megaphone"></i>
                    <span>Marketing</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="/alerts" class="nav-link <?= $active('alerts') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Alertas">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Alertas</span>
                    <span class="nav-alert-dot" aria-hidden="true"></span>
                </a>
            </li>

            <?php if (Auth::isAdmin()): ?>
            <li class="nav-item">
                <a href="/coaches" class="nav-link <?= $active('coaches') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Coaches">
                    <i class="bi bi-calendar-range"></i>
                    <span>Coaches</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-section-label"><span>Analitica</span></li>
            <li class="nav-item">
                <a href="/reports" class="nav-link <?= $active('reports') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Reportes">
                    <i class="bi bi-bar-chart"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/cohorts/finance" class="nav-link <?= $active('cohorts-finance') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Finanzas Cohort Plan">
                    <i class="bi bi-cash-coin"></i>
                    <span>Finanzas</span>
                </a>
            </li>

            <?php if (Auth::isAdmin()): ?>
            <li class="nav-section-label"><span>Administracion</span></li>
            <li class="nav-item">
                <a href="/users" class="nav-link <?= $active('users') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Usuarios">
                    <i class="bi bi-person-gear"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/audit-log" class="nav-link <?= $active('admin-audit-log') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Bitacora de auditoria">
                    <i class="bi bi-journal-text"></i>
                    <span>Bitacora</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/health" class="nav-link <?= $active('admin-health') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Estado del sistema">
                    <i class="bi bi-heart-pulse"></i>
                    <span>Estado Sistema</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-section-label"><span>Cuenta</span></li>
            <li class="nav-item">
                <a href="/account" class="nav-link <?= $active('account') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Mi cuenta">
                    <i class="bi bi-person-circle"></i>
                    <span>Mi cuenta</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="Proximamente">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Estudiantes</span>
                    <span class="badge-soon">Pronto</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="version-badge">
            <i class="bi bi-shield-check"></i>
            <span>v1.0</span>
        </div>
    </div>

    <button type="button" class="sidebar-collapse-btn d-none d-lg-flex" id="sidebarCollapseBtn" aria-label="Colapsar menu">
        <i class="bi bi-chevron-left"></i>
    </button>
</nav>
