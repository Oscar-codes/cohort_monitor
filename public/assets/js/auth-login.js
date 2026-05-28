/**
 * Login page interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

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
            button.setAttribute('aria-label', show ? 'Ocultar contrasena' : 'Mostrar contrasena');
        });
    });
});
