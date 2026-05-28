/**
 * Shared interactions for user create/edit forms.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.querySelector(button.getAttribute('data-password-toggle'));
            const icon = button.querySelector('i');
            if (!input || !icon) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
});
