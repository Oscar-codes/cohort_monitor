/**
 * Cohort edit page interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');

    function sanitizeDecimalValue(rawValue) {
        let value = String(rawValue || '').trim().replace(',', '.').replace(/[^0-9.]/g, '');
        const firstDot = value.indexOf('.');

        if (firstDot !== -1) {
            value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
            const parts = value.split('.');
            value = parts[0] + '.' + (parts[1] || '').slice(0, 2);
        }

        return value;
    }

    function isValidDecimalValue(value) {
        return /^\d+(\.\d{1,2})?$/.test(value);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            let hasInvalidDecimal = false;
            const decimalInputs = form.querySelectorAll('[data-decimal-input]');

            decimalInputs.forEach(function (input) {
                const sanitizedValue = sanitizeDecimalValue(input.value);
                input.value = sanitizedValue;

                if (sanitizedValue === '' || !isValidDecimalValue(sanitizedValue)) {
                    input.setCustomValidity('Ingresa un monto valido mayor o igual a 0 con hasta 2 decimales.');
                    hasInvalidDecimal = true;
                } else {
                    input.setCustomValidity('');
                }
            });

            if (hasInvalidDecimal) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

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

    const decimalInputs = document.querySelectorAll('[data-decimal-input]');
    decimalInputs.forEach(function (input) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'e' || event.key === 'E' || event.key === '-' || event.key === '+') {
                event.preventDefault();
            }
        });

        input.addEventListener('beforeinput', function (event) {
            if (event.data && /[^0-9.,]/.test(event.data)) {
                event.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            input.value = sanitizeDecimalValue(input.value);
            input.setCustomValidity(input.value === '' || isValidDecimalValue(input.value)
                ? ''
                : 'Ingresa un monto valido mayor o igual a 0 con hasta 2 decimales.');
        });

        input.addEventListener('paste', function (event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const normalized = sanitizeDecimalValue(text);
            if (!/^\d+(\.\d{0,2})?$/.test(normalized)) {
                event.preventDefault();
            }
        });

        input.addEventListener('drop', function (event) {
            const text = event.dataTransfer ? event.dataTransfer.getData('text') : '';
            const normalized = sanitizeDecimalValue(text);
            if (!/^\d+(\.\d{0,2})?$/.test(normalized)) {
                event.preventDefault();
            }
        });
    });
});
