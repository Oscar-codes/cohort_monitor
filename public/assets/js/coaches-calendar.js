/**
 * Coaches calendar view toggle interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const btnTimeline = document.getElementById('btn-view-timeline');
    const btnList = document.getElementById('btn-view-list');
    const viewTimeline = document.getElementById('view-timeline');
    const viewList = document.getElementById('view-list');

    if (!btnTimeline || !btnList || !viewTimeline || !viewList) {
        return;
    }

    const saved = localStorage.getItem('coaches-view') || 'timeline';
    switchTo(saved === 'list' ? 'list' : 'timeline');

    btnTimeline.addEventListener('click', function () {
        switchTo('timeline');
    });

    btnList.addEventListener('click', function () {
        switchTo('list');
    });

    function switchTo(view) {
        const isTimeline = view === 'timeline';
        viewTimeline.classList.toggle('d-none', !isTimeline);
        viewList.classList.toggle('d-none', isTimeline);
        btnTimeline.classList.toggle('active', isTimeline);
        btnTimeline.classList.toggle('btn-light', isTimeline);
        btnTimeline.classList.toggle('btn-outline-light', !isTimeline);
        btnList.classList.toggle('active', !isTimeline);
        btnList.classList.toggle('btn-light', !isTimeline);
        btnList.classList.toggle('btn-outline-light', isTimeline);
        localStorage.setItem('coaches-view', view);

        const target = isTimeline ? viewTimeline : viewList;
        target.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
            }
        });
    }
});
