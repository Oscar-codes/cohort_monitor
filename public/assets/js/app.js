/**
 * ============================================================
 *  Cohort Monitor — Main JavaScript Module
 * ============================================================
 */

'use strict';

const App = (() => {

    /**
     * Initialize the application.
     */
    function init() {
        console.log('[Cohort Monitor] Application initialized.');
        initTooltips();
        initSidebarToggle();
        initConfirmDialogs();
    }

    /**
     * Initialize Bootstrap tooltips.
     */
    function initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
    }

    /**
     * Toggle sidebar on mobile.
     */
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebar-toggle');
        const sidebar   = document.getElementById('sidebar');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    }

    /**
     * Auto-attach confirm dialogs to delete forms.
     */
    function initConfirmDialogs() {
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.dataset.confirm || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    // Public API
    return { init };
})();

// Boot
document.addEventListener('DOMContentLoaded', App.init);
