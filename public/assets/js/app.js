/**
 * ============================================================
 *  Cohort Monitor — Main JavaScript Module
 *  Modern Dashboard Interactions
 * ============================================================
 */

'use strict';

const App = (() => {
    // Cache DOM elements
    let sidebar, sidebarToggle, sidebarCollapseBtn, sidebarOverlay;

    /**
     * Initialize the application.
     */
    function init() {
        // Cache elements
        sidebar           = document.getElementById('sidebar');
        sidebarToggle     = document.getElementById('sidebarToggle');
        sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
        sidebarOverlay    = document.getElementById('sidebarOverlay');

        initSidebar();
        initTooltips();
        initConfirmDialogs();
        initFormValidation();
        initTableResponsive();
    }

    /**
     * Initialize sidebar behavior (mobile toggle + desktop collapse).
     */
    function initSidebar() {
        // Mobile toggle
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.add('show');
                sidebarOverlay?.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }

        // Close on overlay click
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeMobileSidebar);
        }

        // Desktop collapse toggle
        if (sidebarCollapseBtn) {
            // Load saved state
            const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            }

            sidebarCollapseBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const collapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', collapsed);
            });
        }

        // Close sidebar on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar?.classList.contains('show')) {
                closeMobileSidebar();
            }
        });

        // Close sidebar when clicking a link (mobile)
        sidebar?.querySelectorAll('.nav-link:not(.disabled)').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeMobileSidebar();
                }
            });
        });
    }

    /**
     * Close mobile sidebar.
     */
    function closeMobileSidebar() {
        sidebar?.classList.remove('show');
        sidebarOverlay?.classList.remove('show');
        document.body.style.overflow = '';
    }

    /**
     * Initialize Bootstrap tooltips.
     */
    function initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => {
            // Only enable tooltips when sidebar is collapsed on desktop
            new bootstrap.Tooltip(el, {
                trigger: 'hover',
                container: 'body'
            });
        });
    }

    /**
     * Auto-attach confirm dialogs to delete forms.
     */
    function initConfirmDialogs() {
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.dataset.confirm || '¿Estás seguro?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Initialize Bootstrap form validation.
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    /**
     * Make tables responsive with horizontal scroll indicator.
     */
    function initTableResponsive() {
        document.querySelectorAll('.table-responsive').forEach(wrapper => {
            const table = wrapper.querySelector('table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('has-scroll');
            }
        });
    }

    // Public API
    return { init, closeMobileSidebar };
})();

// Boot when DOM is ready
document.addEventListener('DOMContentLoaded', App.init);
