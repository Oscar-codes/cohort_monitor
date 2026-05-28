/**
 * Dashboard-specific charts and lightweight interactions.
 */

'use strict';

(function () {
    let data = {};

    try {
        const rawData = document.getElementById('cohort-dashboard-data');
        if (rawData && rawData.value) {
            data = JSON.parse(rawData.value);
        }
    } catch (e) {
        data = {};
    }

    if (!data || typeof data !== 'object') {
        data = {};
    }
    const palette = {
        primary: '#2563eb',
        success: '#16a34a',
        info: '#0891b2',
        warning: '#f59e0b',
        danger: '#dc2626',
        neutral: '#64748b'
    };

    function formatNumber(value) {
        return new Intl.NumberFormat('es-SV').format(Number(value || 0));
    }

    function hasApex() {
        return typeof ApexCharts !== 'undefined';
    }

    function renderSparkline(id, series, color) {
        const el = document.getElementById(id);
        if (!el || !hasApex()) return;

        const values = Array.isArray(series) && series.length ? series : [0, 0, 0];
        const chart = new ApexCharts(el, {
            chart: {
                type: 'area',
                height: 58,
                sparkline: { enabled: true },
                animations: { enabled: true, speed: 450 }
            },
            series: [{ data: values }],
            colors: [color],
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.2,
                    opacityFrom: 0.25,
                    opacityTo: 0.02,
                    stops: [0, 90, 100]
                }
            },
            tooltip: { enabled: false }
        });

        chart.render();
    }

    function renderAdmissionsChart() {
        const el = document.getElementById('dashboardAdmissionsChart');
        const admissions = data.admissions || {};
        if (!el || !hasApex()) return;

        const chart = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 220,
                toolbar: { show: false },
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series: [{
                name: 'Estudiantes',
                data: [
                    Number(admissions.b2b || 0),
                    Number(admissions.b2c || 0),
                    Number(admissions.remaining || 0)
                ]
            }],
            colors: [palette.primary, palette.info, '#cbd5e1'],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    distributed: true,
                    columnWidth: '48%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: ['B2B', 'B2C', 'Pendiente'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: value => formatNumber(value)
                }
            },
            grid: {
                borderColor: '#e2e8f0',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: value => `${formatNumber(value)} estudiantes`
                }
            },
            legend: { show: false }
        });

        chart.render();
    }

    function renderStatusChart() {
        const el = document.getElementById('dashboardStatusChart');
        const status = data.status || {};
        if (!el || !hasApex()) return;

        const series = Array.isArray(status.series) ? status.series.map(Number) : [];
        if (!series.length || series.every(value => value === 0)) {
            el.innerHTML = '<div class="dashboard-chart-empty">Sin datos de estado</div>';
            return;
        }

        const chart = new ApexCharts(el, {
            chart: {
                type: 'donut',
                height: 245,
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series,
            labels: status.labels || [],
            colors: status.colors || [palette.success, palette.primary, palette.neutral],
            stroke: { width: 0 },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: w => formatNumber(w.globals.seriesTotals.reduce((a, b) => a + b, 0))
                            }
                        }
                    }
                }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: value => `${formatNumber(value)} cohortes`
                }
            }
        });

        chart.render();
    }

    function renderBootcampChart() {
        const el = document.getElementById('dashboardBootcampChart');
        const types = data.types || {};
        if (!el || !hasApex()) return;

        const series = Array.isArray(types.series) ? types.series.map(Number) : [];
        if (!series.length) return;

        const chart = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 310,
                toolbar: { show: false },
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series: [{ name: 'Cohortes', data: series }],
            colors: [palette.primary],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '62%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: types.labels || [],
                labels: {
                    formatter: value => formatNumber(value)
                }
            },
            grid: {
                borderColor: '#e2e8f0',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: value => `${formatNumber(value)} cohortes`
                }
            }
        });

        chart.render();
    }

    function updateClock() {
        const now = new Date();
        const pad = value => String(value).padStart(2, '0');
        const dateEl = document.getElementById('dash-date');
        const timeEl = document.getElementById('dash-time');

        if (dateEl) {
            dateEl.textContent = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()}`;
        }

        if (timeEl) {
            timeEl.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        }

        window.setTimeout(updateClock, 1000);
    }

    function init() {
        const sparklines = data.sparklines || {};

        renderSparkline('kpiTotalSparkline', sparklines.total, palette.primary);
        renderSparkline('kpiActiveSparkline', sparklines.active, palette.success);
        renderSparkline('kpiCompletedSparkline', sparklines.completed, palette.info);
        renderSparkline('kpiAlertsSparkline', sparklines.alerts, palette.danger);
        renderAdmissionsChart();
        renderStatusChart();
        renderBootcampChart();
        updateClock();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
