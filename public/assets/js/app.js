/**
 * Cohort Monitor - Main JavaScript Module
 * Dashboard shell interactions.
 */

'use strict';

const App = (() => {
    let sidebar, sidebarCollapseBtn;

    function init() {
        sidebar = document.getElementById('sidebar');
        sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');

        initSidebar();
        initDensityMode();
        initHeaderSearch();
        initTooltips();
        initDynamicStyles();
        initConfirmDialogs();
        initFormValidation();
        initTableResponsive();
        initAlertsWorkbench();
    }

    function initDynamicStyles() {
        const selector = [
            '[data-style-width]',
            '[data-style-left]',
            '[data-style-background]',
            '[data-style-color]',
            '[data-style-min-width]',
            '[data-style-height]',
            '[data-style-border-radius]',
            '[data-style-max-width]',
            '[data-style-font-size]',
            '[data-style-status-color]'
        ].join(',');

        document.querySelectorAll(selector).forEach((el) => {
            if (el.dataset.styleWidth) el.style.width = el.dataset.styleWidth;
            if (el.dataset.styleLeft) el.style.left = el.dataset.styleLeft;
            if (el.dataset.styleBackground) el.style.background = el.dataset.styleBackground;
            if (el.dataset.styleColor) el.style.color = el.dataset.styleColor;
            if (el.dataset.styleMinWidth) el.style.minWidth = el.dataset.styleMinWidth;
            if (el.dataset.styleHeight) el.style.height = el.dataset.styleHeight;
            if (el.dataset.styleBorderRadius) el.style.borderRadius = el.dataset.styleBorderRadius;
            if (el.dataset.styleMaxWidth) el.style.maxWidth = el.dataset.styleMaxWidth;
            if (el.dataset.styleFontSize) el.style.fontSize = el.dataset.styleFontSize;
            if (el.dataset.styleStatusColor) el.style.setProperty('--status-color', el.dataset.styleStatusColor);
        });
    }

    function initSidebar() {
        if (sidebarCollapseBtn) {
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

    function initDensityMode() {
        const toggle = document.getElementById('densityToggle');
        const root = document.documentElement;
        const storageKey = 'app-density';
        const announcer = document.getElementById('app-announcer');

        const applyDensity = (mode) => {
            const isCompact = mode === 'compact';
            root.classList.toggle('app-density-compact', isCompact);

            if (toggle) {
                toggle.setAttribute('aria-pressed', String(isCompact));
                toggle.setAttribute('aria-label', isCompact ? 'Desactivar modo compacto' : 'Activar modo compacto');
                toggle.setAttribute('title', isCompact ? 'Modo comodo' : 'Modo compacto');

                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.className = isCompact ? 'bi bi-arrows-expand' : 'bi bi-arrows-collapse';
                }
            }
        };

        const savedMode = localStorage.getItem(storageKey) === 'compact' ? 'compact' : 'comfortable';
        applyDensity(savedMode);

        if (toggle) {
            toggle.addEventListener('click', () => {
                const nextMode = root.classList.contains('app-density-compact') ? 'comfortable' : 'compact';
                localStorage.setItem(storageKey, nextMode);
                applyDensity(nextMode);
                if (announcer) {
                    announcer.textContent = nextMode === 'compact' ? 'Modo compacto activado' : 'Modo comodo activado';
                }

                const tooltip = bootstrap.Tooltip.getInstance(toggle);
                if (tooltip) {
                    tooltip.dispose();
                    new bootstrap.Tooltip(toggle, { trigger: 'hover', container: 'body' });
                }
            });
        }
    }

    function initHeaderSearch() {
        const mobileSearch = document.getElementById('headerMobileSearch');

        if (mobileSearch) {
            mobileSearch.addEventListener('shown.bs.collapse', () => {
                const input = mobileSearch.querySelector('input[type="search"]');
                if (input) {
                    input.focus();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            const isSearchShortcut = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k';
            if (!isSearchShortcut) {
                return;
            }

            const activeElement = document.activeElement;
            const isTyping = activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName);
            if (isTyping) {
                return;
            }

            const desktopInput = document.querySelector('.header-search:not(.header-search--mobile) input[type="search"]');
            const mobileInput = document.querySelector('.header-search--mobile input[type="search"]');
            const targetInput = window.matchMedia('(min-width: 1200px)').matches ? desktopInput : mobileInput;

            if (!targetInput) {
                return;
            }

            event.preventDefault();

            if (mobileSearch && targetInput === mobileInput && !mobileSearch.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(mobileSearch).show();
                return;
            }

            targetInput.focus();
            targetInput.select();
        });
    }

    function initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => {
            new bootstrap.Tooltip(el, {
                trigger: 'hover',
                container: 'body'
            });
        });
    }

    function initConfirmDialogs() {
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                const message = form.dataset.confirm || 'Confirmar accion';

                if (typeof Swal !== 'undefined') {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Confirmar accion',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si, continuar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-outline-secondary'
                        },
                        buttonsStyling: false
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = 'true';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        }
                    });

                    return;
                }

                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

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

    function initTableResponsive() {
        document.querySelectorAll('.table-responsive').forEach(wrapper => {
            const table = wrapper.querySelector('table');
            if (table && table.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('has-scroll');
            }
        });
    }

    function initAlertsWorkbench() {
        const searchInput = document.getElementById('alertsSearch');
        const items = Array.from(document.querySelectorAll('[data-alert-item]'));
        const filters = Array.from(document.querySelectorAll('[data-alert-filter]'));
        const emptyState = document.getElementById('alertsEmptyFilter');

        if (!items.length) {
            return;
        }

        let activeFilter = 'all';

        const applyFilters = () => {
            const query = (searchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            items.forEach(item => {
                const type = item.dataset.alertType || '';
                const searchable = item.dataset.alertSearch || '';
                const typeMatches = activeFilter === 'all' || type === activeFilter;
                const searchMatches = !query || searchable.includes(query);
                const visible = typeMatches && searchMatches;

                item.classList.toggle('d-none', !visible);
                if (visible) visibleCount += 1;
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount > 0);
            }
        };

        filters.forEach(button => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.alertFilter || 'all';
                filters.forEach(filter => filter.classList.toggle('is-active', filter === button));
                applyFilters();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', App.init);
