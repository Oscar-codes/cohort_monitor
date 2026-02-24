<!-- Cohorts Import View — Upload Form + Results -->
<?php use App\Core\Auth; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($msg = Auth::getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($msg = Auth::getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-cloud-arrow-up text-primary me-2"></i>Importar Cohortes
        </h4>
        <p class="text-muted mb-0">Carga masiva de cohortes desde un archivo Excel o CSV.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/cohorts" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="/cohorts/import/template" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Descargar Plantilla
        </a>
    </div>
</div>

<?php if (empty($summary)): ?>
<!-- ─── UPLOAD FORM ─────────────────────────────────────────── -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Instructions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-info-circle text-primary me-1"></i> Instrucciones
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-primary mb-2">
                            <i class="bi bi-1-circle me-1"></i> Formato del Archivo
                        </h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Formatos: <strong>.xlsx</strong>, <strong>.xls</strong>, <strong>.csv</strong></li>
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> Tamaño máximo: <strong>5 MB</strong></li>
                            <li class="mb-1"><i class="bi bi-check text-success me-1"></i> La primera fila debe ser el encabezado</li>
                            <li><i class="bi bi-check text-success me-1"></i> Fechas en formato <strong>YYYY-MM-DD</strong></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-primary mb-2">
                            <i class="bi bi-2-circle me-1"></i> Columnas Requeridas
                        </h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><code>name</code> — Nombre del cohort <span class="text-danger">*</span></li>
                            <li class="mb-1"><code>area</code> — Academic | Marketing | Admissions</li>
                            <li class="mb-1"><code>type</code> — Tipo de bootcamp</li>
                            <li class="mb-1"><code>project</code> — Proyecto relacionado</li>
                            <li class="mb-1"><code>start_date</code> — Fecha inicio <span class="text-danger">*</span></li>
                            <li class="mb-1"><code>end_date</code> — Fecha fin <span class="text-danger">*</span></li>
                            <li class="mb-1"><code>meta_total</code>, <code>meta_b2b</code>, <code>admissions_b2c</code></li>
                            <li class="mb-1"><code>status</code> — Completado | En ejecución | Pendiente | Cancelado</li>
                            <li><code>at_risk</code> — Sí / No</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-upload text-primary me-1"></i> Subir Archivo
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/cohorts/import" enctype="multipart/form-data" id="importForm">
                    <!-- Drag & Drop Zone -->
                    <div class="upload-zone text-center p-5 rounded-3 mb-3" id="dropZone">
                        <div class="upload-zone-icon mb-3">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <h6 class="fw-semibold mb-1">Arrastra tu archivo aquí</h6>
                        <p class="text-muted small mb-3">o haz clic para seleccionar</p>

                        <input type="file" class="d-none" id="importFile" name="import_file"
                               accept=".xlsx,.xls,.csv">

                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSelectFile">
                            <i class="bi bi-folder2-open me-1"></i> Seleccionar archivo
                        </button>

                        <!-- File info (shown after selection) -->
                        <div class="mt-3 d-none" id="fileInfo">
                            <div class="d-inline-flex align-items-center gap-2 bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                <span id="fileName" class="fw-semibold small"></span>
                                <span id="fileSize" class="small opacity-75"></span>
                                <button type="button" class="btn-close btn-close-sm" id="btnClearFile" aria-label="Quitar"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                            <i class="bi bi-cloud-arrow-up me-1"></i> Importar Cohortes
                            <span class="spinner-border spinner-border-sm ms-1 d-none" id="spinner" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ─── IMPORT RESULTS ──────────────────────────────────────── -->
<?php $s = $summary; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <i class="bi bi-list-check text-primary fs-4 mb-1"></i>
                <div class="fs-4 fw-bold text-primary"><?= (int) $s['total_processed'] ?></div>
                <small class="text-muted">Total Procesados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <i class="bi bi-check-circle text-success fs-4 mb-1"></i>
                <div class="fs-4 fw-bold text-success"><?= (int) $s['inserted_ok'] ?></div>
                <small class="text-muted">Insertados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <i class="bi bi-x-circle text-danger fs-4 mb-1"></i>
                <div class="fs-4 fw-bold text-danger"><?= (int) $s['failed'] ?></div>
                <small class="text-muted">Fallidos</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <i class="bi bi-files text-warning fs-4 mb-1"></i>
                <div class="fs-4 fw-bold text-warning"><?= (int) $s['duplicates_skipped'] ?></div>
                <small class="text-muted">Duplicados</small>
            </div>
        </div>
    </div>
</div>

<!-- Result Alert -->
<?php if ($s['success']): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>
            <strong>Importación completada.</strong>
            Se insertaron <strong><?= $s['inserted_ok'] ?></strong> cohorte(s) correctamente.
            <?php if ($s['failed'] > 0): ?>
                <span class="text-danger">(<?= $s['failed'] ?> fila(s) con errores)</span>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>No se insertaron registros.</strong>
            Revisa los errores en la tabla de abajo.
        </div>
    </div>
<?php endif; ?>

<!-- Error Details Table -->
<?php if ($s['has_errors']): ?>
<div class="card table-card mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-bug text-danger me-1"></i> Detalle de Errores
            <span class="badge bg-danger-subtle text-danger ms-2"><?= count($s['errors']) ?></span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Fila</th>
                    <th style="width: 150px;">Campo</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($s['errors'] as $err): ?>
                    <tr>
                        <td>
                            <?php if ((int) $err['row'] > 0): ?>
                                <span class="badge bg-secondary-subtle text-secondary"># <?= (int) $err['row'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">General</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="small"><?= htmlspecialchars($err['field']) ?></code>
                        </td>
                        <td class="small text-danger">
                            <?= htmlspecialchars($err['message']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="d-flex flex-column flex-sm-row gap-2">
    <a href="/cohorts/import" class="btn btn-primary">
        <i class="bi bi-cloud-arrow-up me-1"></i> Importar Otro Archivo
    </a>
    <a href="/cohorts" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a Cohortes
    </a>
</div>

<?php endif; ?>

<!-- Inline Styles for Upload Zone -->
<style>
.upload-zone {
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
    transition: all 0.2s ease;
    cursor: pointer;
}
.upload-zone:hover,
.upload-zone.drag-over {
    border-color: #0d6efd;
    background: rgba(13, 110, 253, 0.04);
}
.upload-zone-icon {
    font-size: 3rem;
    color: #adb5bd;
}
.upload-zone:hover .upload-zone-icon,
.upload-zone.drag-over .upload-zone-icon {
    color: #0d6efd;
}
.btn-close-sm {
    font-size: 0.6rem;
    padding: 0.35em;
}
</style>

<!-- Import Form Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone     = document.getElementById('dropZone');
    const fileInput    = document.getElementById('importFile');
    const fileInfo     = document.getElementById('fileInfo');
    const fileName     = document.getElementById('fileName');
    const fileSize     = document.getElementById('fileSize');
    const btnSelect    = document.getElementById('btnSelectFile');
    const btnClear     = document.getElementById('btnClearFile');
    const btnSubmit    = document.getElementById('btnSubmit');
    const spinner      = document.getElementById('spinner');
    const form         = document.getElementById('importForm');

    if (!dropZone) return; // Only run on form view

    // Click to select
    btnSelect.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('click', (e) => {
        if (e.target === dropZone || e.target.closest('.upload-zone-icon') || e.target.tagName === 'H6' || e.target.tagName === 'P') {
            fileInput.click();
        }
    });

    // File selected via input
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            showFileInfo(fileInput.files[0]);
        }
    });

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    // Clear file
    btnClear.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        btnSubmit.disabled = true;
    });

    // Submit with spinner
    form.addEventListener('submit', function() {
        btnSubmit.disabled = true;
        spinner.classList.remove('d-none');
        btnSubmit.querySelector('i')?.classList.add('d-none');
    });

    function showFileInfo(file) {
        const allowedExts = ['xlsx', 'xls', 'csv'];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowedExts.includes(ext)) {
            alert('Formato no permitido. Solo se aceptan archivos .xlsx, .xls o .csv');
            fileInput.value = '';
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('El archivo excede el tamaño máximo de 5 MB.');
            fileInput.value = '';
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = '(' + formatBytes(file.size) + ')';
        fileInfo.classList.remove('d-none');
        btnSubmit.disabled = false;
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
});
</script>
