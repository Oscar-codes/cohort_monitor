/**
 * Finance dashboard charts for Cohort Plan.
 */

'use strict';

(function () {
    let data = {};
    let monthlyChart = null;
    let bootcampChart = null;
    let persistTimeout = null;

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
        return buildMovingAverageForecast(actual, horizon);
    }

    function buildMovingAverageForecast(actual, horizon) {
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

    function buildLinearTrendForecast(actual, horizon) {
        const steps = Math.max(0, Number(horizon || 0));
        if (steps <= 0 || !Array.isArray(actual) || actual.length === 0) {
            return [];
        }

        const points = actual
            .map(Number)
            .map((y, index) => ({ x: index + 1, y }))
            .filter(point => Number.isFinite(point.y));

        if (points.length === 0) {
            return [];
        }

        if (points.length === 1) {
            return new Array(steps).fill(points[0].y);
        }

        const n = points.length;
        let sumX = 0;
        let sumY = 0;
        let sumXY = 0;
        let sumX2 = 0;

        points.forEach(point => {
            sumX += point.x;
            sumY += point.y;
            sumXY += point.x * point.y;
            sumX2 += point.x * point.x;
        });

        const denominator = (n * sumX2) - (sumX * sumX);
        const slope = denominator === 0 ? 0 : ((n * sumXY) - (sumX * sumY)) / denominator;
        const intercept = (sumY - (slope * sumX)) / n;

        const forecast = [];
        for (let i = 1; i <= steps; i++) {
            const x = n + i;
            forecast.push(Math.max(0, intercept + (slope * x)));
        }

        return forecast;
    }

    function schedulePreferencePersist() {
        if (persistTimeout) {
            clearTimeout(persistTimeout);
        }

        persistTimeout = setTimeout(() => {
            const topNEl = document.getElementById('financeTopN');
            const horizonEl = document.getElementById('financeForecastHorizon');
            const methodEl = document.getElementById('financeForecastMethod');

            if (!topNEl || !horizonEl || !methodEl) {
                return;
            }

            const body = new URLSearchParams();
            body.set('top_n', String(topNEl.value || '10'));
            body.set('forecast_horizon', String(horizonEl.value || '3'));
            body.set('forecast_method', String(methodEl.value || 'moving_avg'));

            fetch('/cohorts/finance/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString(),
                credentials: 'same-origin'
            }).catch(() => {
                // Ignore persistence errors to keep chart interactions responsive.
            });
        }, 250);
    }

    function renderMonthlyChart() {
        const el = document.getElementById('financeMonthlyChart');
        if (!el || !hasApex()) return;

        const monthly = data.monthly || {};
        const labels = Array.isArray(monthly.labels) ? monthly.labels.slice() : [];
        const target = Array.isArray(monthly.target) ? monthly.target.map(Number) : [];
        const actual = Array.isArray(monthly.actual) ? monthly.actual.map(Number) : [];

        const horizonEl = document.getElementById('financeForecastHorizon');
        const methodEl = document.getElementById('financeForecastMethod');
        const horizon = horizonEl ? Number(horizonEl.value || 0) : 0;
        const method = methodEl ? String(methodEl.value || 'moving_avg') : 'moving_avg';
        const forecast = method === 'linear_trend'
            ? buildLinearTrendForecast(actual, horizon)
            : buildForecast(actual, horizon);

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
        const methodEl = document.getElementById('financeForecastMethod');

        if (topNEl) {
            topNEl.addEventListener('change', () => {
                renderBootcampChart();
                schedulePreferencePersist();
            });
        }
        if (horizonEl) {
            horizonEl.addEventListener('change', () => {
                renderMonthlyChart();
                schedulePreferencePersist();
            });
        }
        if (methodEl) {
            methodEl.addEventListener('change', () => {
                renderMonthlyChart();
                schedulePreferencePersist();
            });
        }

        renderMonthlyChart();
        renderBootcampChart();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
