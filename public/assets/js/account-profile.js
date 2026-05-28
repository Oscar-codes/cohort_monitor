/**
 * Account profile page interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    function updateClock() {
        const dateEl = document.getElementById('systemDate');
        const timeEl = document.getElementById('systemTime');

        if (!dateEl || !timeEl) {
            return;
        }

        const now = new Date();
        const pad = function (n) { return String(n).padStart(2, '0'); };
        dateEl.textContent = pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear();
        timeEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }

    updateClock();
    setInterval(updateClock, 1000);

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
