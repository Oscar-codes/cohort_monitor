/**
 * Reports page filters and exports interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    const exportExcel = document.getElementById('btnExportExcel');
    const exportPdf = document.getElementById('btnExportPdf');
    const previewPdf = document.getElementById('btnPreviewPdf');

    if (!filterForm) {
        return;
    }

    function getFilterParams() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData) {
            if (value && value !== 'all') {
                params.set(key, value);
            }
        }

        return params.toString();
    }

    function showDateWarning() {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Rango invalido',
                text: 'La fecha Desde no puede ser mayor que Hasta.',
                confirmButtonText: 'Revisar'
            });
            return;
        }

        alert('La fecha Desde no puede ser mayor que Hasta.');
    }

    filterForm.addEventListener('submit', function (event) {
        const from = document.getElementById('date_from').value;
        const to = document.getElementById('date_to').value;

        if (from && to && from > to) {
            event.preventDefault();
            showDateWarning();
        }
    });

    if (exportExcel) {
        exportExcel.addEventListener('click', function () {
            const params = getFilterParams();
            window.location.href = '/reports/export/excel' + (params ? '?' + params : '');
        });
    }

    if (exportPdf) {
        exportPdf.addEventListener('click', function () {
            const params = getFilterParams();
            const sep = params ? '&' : '';
            window.location.href = '/reports/export/pdf?mode=download' + sep + params;
        });
    }

    if (previewPdf) {
        previewPdf.addEventListener('click', function () {
            const params = getFilterParams();
            const sep = params ? '&' : '';
            window.open('/reports/export/pdf?mode=preview' + sep + params, '_blank');
        });
    }
});
