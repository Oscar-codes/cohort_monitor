/**
 * Finance dashboard charts for Cohort Plan.
 */

'use strict';

(function () {
    let data = {};
    let monthlyChart = null;
    let bootcampChart = null;

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

    function movingAverage(values, windowSize) {
        const clean = values.filter(v => Number.isFinite(v));
        if (!clean.length) return 0;

        const n = Math.max(1, Math.min(windowSize, clean.length));
        const slice = clean.slice(clean.length - n);
        const sum = slice.reduce((acc, value) => acc + value, 0);
        return sum / n;
    }

    function buildForecast(actual, horizon) {
        const steps = Math.max(0, Number(horizon || 0));
        if (steps <= 0 || !Array.isArray(actual) || actual.length === 0) {
            return [];
        }

        const forecast = [];
        const temp = actual.slice();

        for (let i = 0; i < steps; i++) {
            const value = movingAverage(temp, 3);
            forecast.push(value);
            temp.push(value);
        }

        return forecast;
    }

    function renderMonthlyChart() {
        const el = document.getElementById('financeMonthlyChart');
        if (!el || !hasApex()) return;

        const monthly = data.monthly || {};
        const labels = Array.isArray(monthly.labels) ? monthly.labels.slice() : [];
        const target = Array.isArray(monthly.target) ? monthly.target.map(Number) : [];
        const actual = Array.isArray(monthly.actual) ? monthly.actual.map(Number) : [];

        const horizonEl = document.getElementById('financeForecastHorizon');
        const horizon = horizonEl ? Number(horizonEl.value || 0) : 0;
        const forecast = buildForecast(actual, horizon);

        const extendedLabels = labels.slice();
        for (let i = 0; i < forecast.length; i++) {
            extendedLabels.push('Proy ' + (i + 1));
        }

        const targetExtended = target.concat(new Array(forecast.length).fill(null));
        const actualExtended = actual.concat(new Array(forecast.length).fill(null));
        const forecastSeries = new Array(actual.length).fill(null).concat(forecast);

        if (!labels.length) {
            el.innerHTML = '<div class="dashboard-chart-empty">Sin datos financieros mensuales</div>';
            return;
        }

        if (monthlyChart) {
            monthlyChart.destroy();
            monthlyChart = null;
        }

        monthlyChart = new ApexCharts(el, {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Inter, Segoe UI, sans-serif'
            },
            series: [
                { name: 'Meta', data: targetExtended },
                { name: 'Actual', data: actualExtended },
                { name: 'Proyeccion', data: forecastSeries }
            ],
            colors: [palette.warning, palette.success, palette.primary],
            stroke: {
                curve: 'smooth',
                width: [2, 3, 2],
                dashArray: [0, 0, 6]
            },
            markers: {
                size: [3, 4, 3],
                strokeWidth: 0
            },
            xaxis: {
                categories: extendedLabels,
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

        monthlyChart.render();
    }

    function renderBootcampChart() {
        const el = document.getElementById('financeBootcampChart');
        if (!el || !hasApex()) return;

        const bootcamp = data.bootcamp || {};
        const topNEl = document.getElementById('financeTopN');
        const topN = topNEl ? Math.max(1, Number(topNEl.value || 10)) : 10;

        const labels = Array.isArray(bootcamp.labels) ? bootcamp.labels.slice(0, topN) : [];
        const target = Array.isArray(bootcamp.target) ? bootcamp.target.map(Number).slice(0, topN) : [];
        const actual = Array.isArray(bootcamp.actual) ? bootcamp.actual.map(Number).slice(0, topN) : [];

        if (!labels.length) {
            el.innerHTML = '<div class="dashboard-chart-empty">Sin datos por bootcamp</div>';
            return;
        }

        if (bootcampChart) {
            bootcampChart.destroy();
            bootcampChart = null;
        }

        bootcampChart = new ApexCharts(el, {
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

        bootcampChart.render();
    }

    function init() {
        const topNEl = document.getElementById('financeTopN');
        const horizonEl = document.getElementById('financeForecastHorizon');

        if (topNEl) {
            topNEl.addEventListener('change', renderBootcampChart);
        }
        if (horizonEl) {
            horizonEl.addEventListener('change', renderMonthlyChart);
        }

        renderMonthlyChart();
        renderBootcampChart();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
