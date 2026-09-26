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
        initKodigoToast();
        initKodigoReveal();
        initAlertToKodigoToast();
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
                const tone = form.dataset.confirmTone || 'warning';
                const title = form.dataset.confirmTitle || 'Confirmar accion';
                const confirmText = form.dataset.confirmButton || 'Si, continuar';
                const icon = tone === 'danger' ? 'warning' : 'question';

                if (typeof Swal !== 'undefined') {
                    e.preventDefault();

                    Swal.fire({
                        title,
                        text: message,
                        icon,
                        showCancelButton: true,
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        focusCancel: tone === 'danger',
                        customClass: {
                            confirmButton: tone === 'danger'
                                ? 'btn btn-danger'
                                : 'btn btn-kodigo',
                            cancelButton: 'btn btn-outline-secondary',
                            popup: 'kodigo-swal-popup',
                            title: 'kodigo-swal-title',
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

    /**
     * Kodigo toast helper (Phase 0).
     * Mounts a fixed stack container and exposes window.kodigoToast({tone,title,message,timeout})
     * which renders a non-blocking notification. Honors prefers-reduced-motion.
     */
    function initKodigoToast() {
        if (!document.querySelector('.kodigo-toast-stack')) {
            const stack = document.createElement('div');
            stack.className = 'kodigo-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'false');
            document.body.appendChild(stack);
        }

        window.kodigoToast = function (options) {
            const opts = options || {};
            const tone = ['success', 'warning', 'danger', 'info'].includes(opts.tone) ? opts.tone : 'info';
            const title = opts.title ? String(opts.title) : '';
            const message = opts.message ? String(opts.message) : '';
            const timeout = Number.isFinite(opts.timeout) ? opts.timeout : 3500;

            const stack = document.querySelector('.kodigo-toast-stack');
            if (!stack) return null;

            const node = document.createElement('div');
            node.className = 'kodigo-toast kodigo-toast--' + tone;
            node.setAttribute('role', 'status');

            const titleEl = document.createElement('strong');
            titleEl.textContent = title;
            node.appendChild(titleEl);

            if (message) {
                const msgEl = document.createElement('p');
                msgEl.textContent = message;
                node.appendChild(msgEl);
            }

            stack.appendChild(node);

            requestAnimationFrame(() => {
                node.classList.add('kodigo-toast--mounted');
            });

            const dismiss = () => {
                node.classList.remove('kodigo-toast--mounted');
                const cleanup = () => {
                    if (node.parentNode) {
                        node.parentNode.removeChild(node);
                    }
                };
                const duration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 240;
                setTimeout(cleanup, duration);
            };

            const timer = setTimeout(dismiss, timeout);
            node.addEventListener('click', () => {
                clearTimeout(timer);
                dismiss();
            });

            return node;
        };
    }

    /**
     * Kodigo reveal (Phase 0). Applies a small staggered fade/translate
     * entrance to elements with [data-kodigo-reveal], capped at 6 staggered
     * groups so power users do not get a long cascade.
     */
    function initKodigoReveal() {
        const targets = document.querySelectorAll('[data-kodigo-reveal]');
        if (!targets.length) return;

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        targets.forEach((el, i) => {
            const stagger = Math.min(i, 6);
            if (prefersReduced) {
                el.style.opacity = '1';
                el.style.transform = 'none';
                return;
            }
            el.style.transition = 'opacity var(--dur-page) var(--ease-out), transform var(--dur-page) var(--ease-out)';
            el.style.transitionDelay = (stagger * 40) + 'ms';
            el.style.opacity = '0';
            el.style.transform = 'translateY(8px)';
        });

        requestAnimationFrame(() => {
            targets.forEach((el) => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });
        });
    }

    /**
     * Convert Bootstrap-flash alerts (alert-success/info/warning/danger) into
     * Kodigo toasts so feedback is consistent across pages without editing each
     * view. Hidden after conversion so the same message is not duplicated.
     */
    function initAlertToKodigoToast() {
        if (typeof window.kodigoToast !== 'function') return;

        const toneMap = {
            'alert-success': 'success',
            'alert-info': 'info',
            'alert-warning': 'warning',
            'alert-danger': 'danger',
            'alert-primary': 'info',
            'alert-secondary': 'info',
        };

        const alerts = Array.from(document.querySelectorAll('.alert'));
        if (!alerts.length) return;

        alerts.forEach((alert) => {
            const tone = Object.keys(toneMap).find(cls => alert.classList.contains(cls));
            if (!tone) return;

            const cloned = alert.cloneNode(true);
            cloned.querySelectorAll('.btn-close, button').forEach(btn => btn.remove());

            const text = cloned.textContent.replace(/\s+/g, ' ').trim();
            if (!text) {
                alert.style.display = 'none';
                return;
            }

            const parts = text.split(/(?<=[.!?])\s+/);
            const title = parts[0] || text;
            const message = parts.length > 1 ? parts.slice(1).join(' ') : '';

            window.kodigoToast({
                tone: toneMap[tone],
                title,
                message,
                timeout: 4500,
            });

            alert.style.display = 'none';
            alert.setAttribute('aria-hidden', 'true');
        });
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', App.init);
