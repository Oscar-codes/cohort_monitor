/**
 * Finance dashboard charts for Cohort Plan.
 */

'use strict';

(function () {
    let data = {};

    try {
        const raw = document.getElementById('cohort-finance-data');
        if (raw && raw.value) {
            data = JSON.parse(raw.value);
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
        warning: '#f59e0b',
        neutral: '#64748b'
    };

    function hasApex() {
        return typeof ApexCharts !== 'undefined';
    }

    function currencyFmt(value) {
        return new Intl.NumberFormat('es-SV', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function renderMonthlyChart() {
        const el = document.getElementById('financeMonthlyChart');
        if (!el || !hasApex()) return;

        const monthly = data.monthly || {};
        const labels = Array.isArray(monthly.labels) ? monthly.labels : [];
        const target = Array.isArray(monthly.target) ? monthly.target.map(Number) : [];
        const actual = Array.isArray(monthly.actual) ? monthly.actual.map(Number) : [];

        if (!labels.length) {
            el.innerHTML = '<div class="dashboard-chart-empty">Sin datos financieros mensuales</div>';
            return;
        }

        const chart = new ApexCharts(el, {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series: [
                { name: 'Meta', data: target },
                { name: 'Actual', data: actual }
            ],
            colors: [palette.warning, palette.success],
            stroke: {
                curve: 'smooth',
                width: [2, 3]
            },
            markers: {
                size: 4,
                strokeWidth: 0
            },
            xaxis: {
                categories: labels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: value => currencyFmt(value)
                }
            },
            grid: {
                borderColor: '#e2e8f0',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: value => currencyFmt(value)
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        });

        chart.render();
    }

    function renderBootcampChart() {
        const el = document.getElementById('financeBootcampChart');
        if (!el || !hasApex()) return;

        const bootcamp = data.bootcamp || {};
        const labels = Array.isArray(bootcamp.labels) ? bootcamp.labels.slice(0, 10) : [];
        const target = Array.isArray(bootcamp.target) ? bootcamp.target.map(Number).slice(0, 10) : [];
        const actual = Array.isArray(bootcamp.actual) ? bootcamp.actual.map(Number).slice(0, 10) : [];

        if (!labels.length) {
            el.innerHTML = '<div class="dashboard-chart-empty">Sin datos por bootcamp</div>';
            return;
        }

        const chart = new ApexCharts(el, {
            chart: {
                type: 'bar',
                height: 320,
                stacked: false,
                toolbar: { show: false },
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series: [
                { name: 'Actual', data: actual },
                { name: 'Meta', data: target }
            ],
            colors: [palette.primary, palette.neutral],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '58%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: labels,
                labels: {
                    formatter: value => currencyFmt(value)
                }
            },
            grid: {
                borderColor: '#e2e8f0',
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: value => currencyFmt(value)
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        });

        chart.render();
    }

    function init() {
        renderMonthlyChart();
        renderBootcampChart();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
