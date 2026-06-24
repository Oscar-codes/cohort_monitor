<!-- Cohort Edit View with Role-Based Field Permissions -->
<?php
use App\Core\Auth;

// ── Access control: marketing role cannot edit cohorts ─────────────────────
$currentRole = Auth::role() ?? '';
$isMarketingBlocked = ($currentRole === 'marketing');

/** @var array<string, mixed> $cohort */
$cohort = isset($cohort) && is_array($cohort) ? $cohort : [];

/** @var string[] $editableFields */
$editableFields = isset($editableFields) && is_array($editableFields) ? $editableFields : [];

$canEdit = static fn(string $field): bool => false;
$disabled = static fn(string $field): string => 'disabled readonly';
$fieldClass = static fn(string $field): string => 'form-control bg-light text-muted';

// Helper functions (only needed when user has access)
if (!$isMarketingBlocked) {
    $canEdit    = fn(string $field): bool   => in_array($field, $editableFields, true);
    $disabled   = fn(string $field): string => $canEdit($field) ? '' : 'disabled readonly';
    $fieldClass = fn(string $field): string => $canEdit($field) ? 'form-control' : 'form-control bg-light text-muted';
}
?>

<?php if ($isMarketingBlocked): ?>
<!-- ── 403 Modal (marketing role) ───────────────────────────────────────── -->
<div class="modal fade" id="accessDeniedModal" tabindex="-1"
     aria-labelledby="accessDeniedModalLabel" aria-modal="true" role="dialog"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="accessDeniedModalLabel">
                    <i class="bi bi-shield-x me-2"></i>403 – Acceso Denegado
                </h5>
            </div>

            <div class="modal-body py-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-circle-fill text-danger fs-2 flex-shrink-0 mt-1"></i>
                    <div>
                        <p class="mb-1 fw-semibold text-dark">No tienes los permisos necesarios para acceder a esta sección.</p>
                        <p class="mb-0 text-muted small">Contacta al administrador si crees que deberías tener acceso.</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 gap-2 justify-content-end">
                <a href="/cohorts" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
                <a href="/" class="btn btn-primary">
                    <i class="bi bi-house me-1"></i>Ir al Inicio
                </a>
            </div>

        </div>
    </div>
</div>



<?php else: ?>

<section class="form-page-hero mb-4">
    <div>
        <div class="dashboard-eyebrow">
            <i class="bi bi-pencil-square"></i>
            Edicion controlada
        </div>
        <h2 class="form-page-hero__title"><?= htmlspecialchars($cohort['cohort_code'] ?? 'Cohorte') ?></h2>
        <p class="form-page-hero__copy"><?= htmlspecialchars($cohort['name']) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-outline-light">
            <i class="bi bi-eye me-1"></i> Ver detalle
        </a>
        <a href="/cohorts" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Cohortes
        </a>
    </div>
</section>

<?php if (!($isAdmin ?? false)): ?>
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <div>
        <strong>Permisos limitados:</strong> Solo puedes editar los campos resaltados. Los demás campos están bloqueados para tu rol.
    </div>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="app-panel form-workbench">
            <div class="form-workbench__header">
                <div>
                    <h3><i class="bi bi-sliders text-primary"></i> Campos editables</h3>
                    <p>La disponibilidad de campos depende del rol activo.</p>
                </div>
            </div>
            <div class="form-workbench__body">
                <form method="POST" action="/cohorts/<?= $cohort['id'] ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="_method" value="PUT">

                    <!-- ─── Identificación ─────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-tag"></i> Identificación
                            <?php if (!$canEdit('cohort_code') && !$canEdit('name')): ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="cohort_code" class="form-label">
                                    Código de Cohorte
                                    <?php if ($canEdit('cohort_code')): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="text" class="<?= $fieldClass('cohort_code') ?>" id="cohort_code" name="cohort_code"
                                       <?= $canEdit('cohort_code') ? 'required' : '' ?>
                                       <?= $disabled('cohort_code') ?>
                                       value="<?= htmlspecialchars($cohort['cohort_code'] ?? '') ?>">
                                <?php if ($canEdit('cohort_code')): ?>
                                <div class="invalid-feedback">El código es requerido.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label for="name" class="form-label">
                                    Nombre de Cohorte
                                    <?php if ($canEdit('name')): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="text" class="<?= $fieldClass('name') ?>" id="name" name="name"
                                       <?= $canEdit('name') ? 'required' : '' ?>
                                       <?= $disabled('name') ?>
                                       value="<?= htmlspecialchars($cohort['name']) ?>">
                                <?php if ($canEdit('name')): ?>
                                <div class="invalid-feedback">El nombre es requerido.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label for="correlative_number" class="form-label">Número Correlativo</label>
                                <input type="number" class="<?= $fieldClass('correlative_number') ?>" id="correlative_number" name="correlative_number"
                                       min="0" <?= $disabled('correlative_number') ?>
                                       value="<?= htmlspecialchars($cohort['correlative_number'] ?? 0) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Admisiones ─────────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-people"></i> Admisiones
                            <?php 
                            $admissionFields = ['total_admission_target', 'b2b_admission_target', 'b2c_admission_target', 'b2b_admissions', 'b2c_admissions'];
                            $canEditAny = count(array_intersect($admissionFields, $editableFields ?? [])) > 0;
                            if (!$canEditAny): 
                            ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <label for="total_admission_target" class="form-label">Meta a inscribir</label>
                                <input type="number" class="<?= $fieldClass('total_admission_target') ?>" id="total_admission_target" name="total_admission_target"
                                       min="0" <?= $disabled('total_admission_target') ?>
                                       value="<?= htmlspecialchars($cohort['total_admission_target'] ?? 0) ?>">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2b_admission_target" class="form-label">Meta B2B</label>
                                <input type="number" class="<?= $fieldClass('b2b_admission_target') ?>" id="b2b_admission_target" name="b2b_admission_target"
                                       min="0" <?= $disabled('b2b_admission_target') ?>
                                       value="<?= htmlspecialchars($cohort['b2b_admission_target'] ?? 0) ?>">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2c_admission_target" class="form-label">Meta B2C</label>
                                <input type="number" class="<?= $fieldClass('b2c_admission_target') ?>" id="b2c_admission_target" name="b2c_admission_target"
                                       min="0" <?= $disabled('b2c_admission_target') ?>
                                       value="<?= htmlspecialchars($cohort['b2c_admission_target'] ?? 0) ?>">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2b_admissions" class="form-label">
                                    Inscritos B2B
                                    <?php if ($canEdit('b2b_admissions')): ?>
                                    <i class="bi bi-pencil-fill text-success small" title="Campo editable"></i>
                                    <?php endif; ?>
                                </label>
                                <input type="number" class="<?= $fieldClass('b2b_admissions') ?> <?= $canEdit('b2b_admissions') ? 'border-success' : '' ?>" 
                                       id="b2b_admissions" name="b2b_admissions"
                                       min="0" <?= $disabled('b2b_admissions') ?>
                                       value="<?= htmlspecialchars($cohort['b2b_admissions'] ?? 0) ?>">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2c_admissions" class="form-label">
                                    Inscritos B2C
                                    <?php if ($canEdit('b2c_admissions')): ?>
                                    <i class="bi bi-pencil-fill text-success small" title="Campo editable"></i>
                                    <?php endif; ?>
                                </label>
                                <input type="number" class="<?= $fieldClass('b2c_admissions') ?> <?= $canEdit('b2c_admissions') ? 'border-success' : '' ?>" 
                                       id="b2c_admissions" name="b2c_admissions"
                                       min="0" <?= $disabled('b2c_admissions') ?>
                                       value="<?= htmlspecialchars($cohort['b2c_admissions'] ?? 0) ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-sm-6 col-md-4">
                                <label for="admission_deadline_date" class="form-label">Límite Admisión</label>
                                <input type="date" class="<?= $fieldClass('admission_deadline_date') ?>" id="admission_deadline_date" name="admission_deadline_date"
                                       <?= $disabled('admission_deadline_date') ?>
                                       value="<?= htmlspecialchars($cohort['admission_deadline_date'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Finanzas ─────────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-cash-stack"></i> Finanzas
                            <?php
                            $financeFields = ['financial_target_revenue', 'financial_actual_revenue'];
                            $canEditFinance = count(array_intersect($financeFields, $editableFields ?? [])) > 0;
                            if (!$canEditFinance):
                            ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                        <div class="alert alert-light border small mb-3">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <strong>Reglas de ingreso por fuente:</strong>
                            <ul class="mb-0 mt-1">
                                <li><strong>INCAF:</strong> Se ingresa el revenue cuando se tiene el reporte emitido por Academia y el valor definitivo a cobrar.</li>
                                <li><strong>Student Revenue / Other SF:</strong> Se ingresa al emitir la factura, con actualizaciones semanales.</li>
                                <li>El período de revenue corresponde al mes de facturación.</li>
                            </ul>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="financial_target_revenue" class="form-label">
                                    Meta de ingresos (USD)
                                    <i class="bi bi-question-circle text-muted small" data-bs-toggle="tooltip" title="Valor esperado de revenue para esta cohorte."></i>
                                    <?php if ($canEdit('financial_target_revenue')): ?>
                                    <i class="bi bi-pencil-fill text-success small" title="Campo editable"></i>
                                    <?php endif; ?>
                                </label>
                                <input type="text" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$"
                                       data-decimal-input
                                       class="<?= $fieldClass('financial_target_revenue') ?> <?= $canEdit('financial_target_revenue') ? 'border-success' : '' ?>"
                                       id="financial_target_revenue" name="financial_target_revenue"
                                       <?= $disabled('financial_target_revenue') ?>
                                       value="<?= htmlspecialchars((string) ($cohort['financial_target_revenue'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="financial_actual_revenue" class="form-label">
                                    Ingreso actual (USD)
                                    <i class="bi bi-question-circle text-muted small" data-bs-toggle="tooltip" title="Revenue facturado o confirmado al mes en curso."></i>
                                    <?php if ($canEdit('financial_actual_revenue')): ?>
                                    <i class="bi bi-pencil-fill text-success small" title="Campo editable"></i>
                                    <?php endif; ?>
                                </label>
                                <input type="text" inputmode="decimal" pattern="^\d+(\.\d{1,2})?$"
                                       data-decimal-input
                                       class="<?= $fieldClass('financial_actual_revenue') ?> <?= $canEdit('financial_actual_revenue') ? 'border-success' : '' ?>"
                                       id="financial_actual_revenue" name="financial_actual_revenue"
                                       <?= $disabled('financial_actual_revenue') ?>
                                       value="<?= htmlspecialchars((string) ($cohort['financial_actual_revenue'] ?? 0)) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Fechas de Entrenamiento ────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-calendar-event"></i> Fechas de Entrenamiento
                            <?php if (!$canEdit('start_date') && !$canEdit('end_date')): ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="start_date" class="form-label">Fecha de Inicio</label>
                                <input type="date" class="<?= $fieldClass('start_date') ?>" id="start_date" name="start_date"
                                       <?= $disabled('start_date') ?>
                                       value="<?= htmlspecialchars($cohort['start_date'] ?? '') ?>">
                            </div>
                            <div class="col-sm-6">
                                <label for="end_date" class="form-label">Fecha de Fin</label>
                                <input type="date" class="<?= $fieldClass('end_date') ?>" id="end_date" name="end_date"
                                       <?= $disabled('end_date') ?>
                                       value="<?= htmlspecialchars($cohort['end_date'] ?? '') ?>">
                            </div>
                        </div>
                        <?php if (!empty($cohort['training_date_50']) || !empty($cohort['training_date_75'])): ?>
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-calculator text-primary me-2"></i>
                            <strong>Fechas calculadas:</strong>
                            50% → <?= htmlspecialchars($cohort['training_date_50'] ?? '—') ?> |
                            75% → <?= htmlspecialchars($cohort['training_date_75'] ?? '—') ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ─── Asignaciones ───────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-briefcase"></i> Asignaciones
                            <?php 
                            $assignFields = ['related_project', 'assigned_coach', 'bootcamp_type', 'assigned_class_schedule'];
                            $canEditAssign = count(array_intersect($assignFields, $editableFields ?? [])) > 0;
                            if (!$canEditAssign): 
                            ?>
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="related_project" class="form-label">Proyecto Relacionado</label>
                                <input type="text" class="<?= $fieldClass('related_project') ?>" id="related_project" name="related_project"
                                       <?= $disabled('related_project') ?>
                                       value="<?= htmlspecialchars($cohort['related_project'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_coach" class="form-label">Coach Asignado</label>
                                <input type="text" class="<?= $fieldClass('assigned_coach') ?>" id="assigned_coach" name="assigned_coach"
                                       <?= $disabled('assigned_coach') ?>
                                       value="<?= htmlspecialchars($cohort['assigned_coach'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="bootcamp_type" class="form-label">Bootcamp name</label>
                                <input type="text" class="<?= $fieldClass('bootcamp_type') ?>" id="bootcamp_type" name="bootcamp_type"
                                       <?= $disabled('bootcamp_type') ?>
                                       value="<?= htmlspecialchars($cohort['bootcamp_type'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="area" class="form-label">Área</label>
                                <select class="<?= $fieldClass('area') ?>" id="area" name="area" <?= $disabled('area') ?>>
                                    <option value="">Seleccionar área...</option>
                                    <option value="academic" <?= ($cohort['area'] ?? '') === 'academic' ? 'selected' : '' ?>>Academic</option>
                                    <option value="marketing" <?= ($cohort['area'] ?? '') === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                                    <option value="admissions" <?= ($cohort['area'] ?? '') === 'admissions' ? 'selected' : '' ?>>Admissions</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_class_schedule" class="form-label">Horario Asignado</label>
                                <input type="text" class="<?= $fieldClass('assigned_class_schedule') ?>" id="assigned_class_schedule" name="assigned_class_schedule"
                                       <?= $disabled('assigned_class_schedule') ?>
                                       value="<?= htmlspecialchars($cohort['assigned_class_schedule'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dias de clase</label>
                                <input type="text" class="form-control bg-light text-muted" disabled readonly
                                       value="<?= htmlspecialchars($cohort['class_days'] ?? '—') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Horario puntual</label>
                                <input type="text" class="form-control bg-light text-muted" disabled readonly
                                       value="<?= htmlspecialchars($cohort['class_time'] ?? '—') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Estado ─────────────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-flag"></i> Estado
                            <span class="badge bg-secondary-subtle text-secondary ms-2">Solo lectura</span>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="training_status" class="form-label">Estado de la cohorte</label>
                                <select class="form-select bg-light text-muted" 
                                        id="training_status" name="training_status"
                                        disabled readonly>
                                    <option value="planned" <?= ($cohort['training_status'] ?? '') === 'planned' ? 'selected' : '' ?>>Planificado</option>
                                    <option value="in_progress" <?= ($cohort['training_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>En progreso</option>
                                    <option value="completed"   <?= ($cohort['training_status'] ?? '') === 'completed'   ? 'selected' : '' ?>>Completado</option>
                                    <option value="cancelled"   <?= ($cohort['training_status'] ?? '') === 'cancelled'   ? 'selected' : '' ?>>Cancelado</option>
                                    <option value="pending_reschedule"   <?= ($cohort['training_status'] ?? '') === 'pending_reschedule'   ? 'selected' : '' ?>>Pendiente de reprogramar</option>
                                </select>
                                <div class="form-text">El estado se actualiza por reglas de ciclo de vida o acciones controladas.</div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Botones ────────────────────────────── -->
                    <div class="d-flex flex-column flex-sm-row gap-2 pt-3 border-top">
                        <?php if (count($editableFields ?? []) > 0): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Actualizar Cohorte
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="bi bi-lock me-1"></i> Sin permisos para editar
                        </button>
                        <?php endif; ?>
                        <a href="/cohorts/<?= $cohort['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php endif; // end marketing access block ?>
