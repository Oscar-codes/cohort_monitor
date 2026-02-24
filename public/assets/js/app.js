/**
 * ============================================================
 *  Cohort Monitor — Main JavaScript Module
 *  Modern Dashboard Interactions
 * ============================================================
 */

'use strict';

const App = (() => {
    // Cache DOM elements
    let sidebar, sidebarCollapseBtn;

    /**
     * Initialize the application.
     */
    function init() {
        // Cache elements
        sidebar            = document.getElementById('sidebar');
        sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');

        initSidebar();
        initTooltips();
        initConfirmDialogs();
        initFormValidation();
        initTableResponsive();
    }

    /**
     * Initialize sidebar behavior.
     * Mobile: Bootstrap offcanvas handles open/close/backdrop via data attributes.
     * Desktop: Custom collapse toggle with localStorage persistence.
     */
    function initSidebar() {
        // Desktop collapse toggle (shrink/expand sidebar)
        if (sidebarCollapseBtn) {
            // Restore saved state
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

        // Mobile: close offcanvas when clicking a nav link
        if (sidebar) {
            sidebar.querySelectorAll('.nav-link:not(.disabled)').forEach(link => {
                link.addEventListener('click', () => {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebar);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                });
            });
        }
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
    return { init };
})();

// Boot when DOM is ready
document.addEventListener('DOMContentLoaded', App.init);
