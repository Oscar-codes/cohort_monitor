<!-- Sidebar Navigation -->
<?php use App\Core\Auth; ?>
<nav id="sidebar" class="sidebar bg-dark">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="/" class="d-flex align-items-center text-decoration-none">
            <div class="brand-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <span class="brand-text">Cohort Monitor</span>
        </a>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <!-- Dashboard — all roles -->
            <li class="nav-item">
                <a href="/" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Cohorts — all roles -->
            <li class="nav-item">
                <a href="/cohorts" class="nav-link <?= ($activePage ?? '') === 'cohorts' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Cohortes">
                    <i class="bi bi-people"></i>
                    <span>Cohortes</span>
                </a>
            </li>

            <!-- Marketing — admin + marketing -->
            <?php if (Auth::hasRole(['admin', 'marketing'])): ?>
            <li class="nav-item">
                <a href="/marketing" class="nav-link <?= ($activePage ?? '') === 'marketing' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Marketing">
                    <i class="bi bi-megaphone"></i>
                    <span>Marketing</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Alerts — admin only -->
            <?php if (Auth::isAdmin()): ?>
            <li class="nav-item">
                <a href="/alerts" class="nav-link <?= ($activePage ?? '') === 'alerts' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Alertas">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Alertas</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Users — admin only -->
            <?php if (Auth::isAdmin()): ?>
            <li class="nav-item">
                <a href="/users" class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Usuarios">
                    <i class="bi bi-person-gear"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <li class="nav-divider"></li>

            <!-- Coming Soon -->
            <li class="nav-item">
                <a href="#" class="nav-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="Próximamente">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Estudiantes</span>
                    <span class="badge-soon">Pronto</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link disabled" data-bs-toggle="tooltip" data-bs-placement="right" title="Próximamente">
                    <i class="bi bi-bar-chart"></i>
                    <span>Reportes</span>
                    <span class="badge-soon">Pronto</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="version-badge">
            <i class="bi bi-shield-check"></i>
            <span>v1.0</span>
        </div>
    </div>

    <!-- Collapse Toggle (Desktop) -->
    <button type="button" class="sidebar-collapse-btn d-none d-lg-flex" id="sidebarCollapseBtn" aria-label="Colapsar menú">
        <i class="bi bi-chevron-left"></i>
    </button>
</nav>
