/**
 * Cohort edit page interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function () {
            const disabledInputs = form.querySelectorAll('input[disabled], select[disabled]');
            disabledInputs.forEach(function (input) {
                input.removeAttribute('name');
            });
        });
    }

    const accessDeniedModal = document.getElementById('accessDeniedModal');
    if (accessDeniedModal && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(accessDeniedModal, {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();
    }
});
