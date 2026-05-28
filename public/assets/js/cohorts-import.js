/**
 * Cohorts import form interactions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('importFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const btnSelect = document.getElementById('btnSelectFile');
    const btnClear = document.getElementById('btnClearFile');
    const btnSubmit = document.getElementById('btnSubmit');
    const spinner = document.getElementById('spinner');
    const form = document.getElementById('importForm');

    if (!dropZone || !fileInput || !fileInfo || !btnSubmit || !form) {
        return;
    }

    if (btnSelect) {
        btnSelect.addEventListener('click', function () { fileInput.click(); });
    }

    dropZone.addEventListener('click', function (e) {
        if (
            e.target === dropZone ||
            e.target.closest('.upload-zone-icon') ||
            e.target.tagName === 'H6' ||
            e.target.tagName === 'P'
        ) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            showFileInfo(fileInput.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', function (e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    if (btnClear) {
        btnClear.addEventListener('click', function (e) {
            e.stopPropagation();
            fileInput.value = '';
            fileInfo.classList.add('d-none');
            btnSubmit.disabled = true;
        });
    }

    form.addEventListener('submit', function () {
        btnSubmit.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        const icon = btnSubmit.querySelector('i');
        if (icon) {
            icon.classList.add('d-none');
        }
    });

    function showFileInfo(file) {
        const allowedExts = ['xlsx', 'xls', 'csv'];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowedExts.includes(ext)) {
            showImportWarning('Formato no permitido', 'Solo se aceptan archivos .xlsx, .xls o .csv.');
            fileInput.value = '';
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showImportWarning('Archivo demasiado grande', 'El archivo excede el tamano maximo de 5 MB.');
            fileInput.value = '';
            return;
        }

        if (fileName) {
            fileName.textContent = file.name;
        }
        if (fileSize) {
            fileSize.textContent = '(' + formatBytes(file.size) + ')';
        }

        fileInfo.classList.remove('d-none');
        btnSubmit.disabled = false;
    }

    function showImportWarning(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
            return;
        }

        alert(message);
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
});
