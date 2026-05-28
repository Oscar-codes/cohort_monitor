/**
 * Cohorts index interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const btnList = document.getElementById('btn-view-list');
    const btnGantt = document.getElementById('btn-view-gantt');
    const viewList = document.getElementById('view-list');
    const viewGantt = document.getElementById('view-gantt');

    if (!btnList || !btnGantt || !viewList || !viewGantt) {
        return;
    }

    const saved = localStorage.getItem('cohorts-view') || 'list';
    if (saved === 'gantt') {
        switchTo('gantt');
    }

    btnList.addEventListener('click', function () {
        switchTo('list');
    });

    btnGantt.addEventListener('click', function () {
        switchTo('gantt');
    });

    function switchTo(view) {
        const isList = view === 'list';
        viewList.classList.toggle('d-none', !isList);
        viewGantt.classList.toggle('d-none', isList);
        btnList.classList.toggle('active', isList);
        btnGantt.classList.toggle('active', !isList);
        localStorage.setItem('cohorts-view', view);

        const target = isList ? viewList : viewGantt;
        target.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
            }
        });
    }
});
