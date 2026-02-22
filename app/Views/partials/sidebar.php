<!-- Sidebar Navigation -->
<nav id="sidebar" class="bg-dark text-white" style="width: 260px; min-height: 100vh;">
    <div class="p-3 border-bottom border-secondary">
        <a href="/" class="text-white text-decoration-none d-flex align-items-center">
            <i class="bi bi-graph-up-arrow fs-4 me-2"></i>
            <span class="fs-5 fw-semibold">Cohort Monitor</span>
        </a>
    </div>

    <ul class="nav flex-column p-3 gap-1">
        <li class="nav-item">
            <a href="/" class="nav-link text-white rounded <?= ($activePage ?? '') === 'dashboard' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="/cohorts" class="nav-link text-white rounded <?= ($activePage ?? '') === 'cohorts' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-people me-2"></i> Cohorts
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link text-white-50 rounded disabled">
                <i class="bi bi-person-lines-fill me-2"></i> Students
                <span class="badge bg-secondary ms-auto">Soon</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link text-white-50 rounded disabled">
                <i class="bi bi-bar-chart me-2"></i> Reports
                <span class="badge bg-secondary ms-auto">Soon</span>
            </a>
        </li>
    </ul>

    <div class="mt-auto p-3 border-top border-secondary">
        <small class="text-white-50">
            <i class="bi bi-info-circle me-1"></i> Cohort Monitor v1.0
        </small>
    </div>
</nav>
