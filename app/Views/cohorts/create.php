<!-- Cohort Create View -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/cohorts" class="text-decoration-none">Cohortes</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nueva Cohorte</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="/cohorts" class="needs-validation" novalidate>

                    <!-- ─── Identificación ─────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-tag"></i> Identificación
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="cohort_code" class="form-label">Código de Cohorte <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cohort_code" name="cohort_code" required
                                       placeholder="ej. COH-2026-01">
                                <div class="invalid-feedback">El código es requerido.</div>
                            </div>
                            <div class="col-md-5">
                                <label for="name" class="form-label">Nombre de Cohorte <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="ej. Primavera 2026 — Full Stack Web Dev">
                                <div class="invalid-feedback">El nombre es requerido.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="correlative_number" class="form-label">Número Correlativo</label>
                                <input type="number" class="form-control" id="correlative_number" name="correlative_number"
                                       min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Admisiones ─────────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-people"></i> Admisiones
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <label for="total_admission_target" class="form-label">Meta Total</label>
                                <input type="number" class="form-control" id="total_admission_target" name="total_admission_target"
                                       min="0" value="0">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2b_admission_target" class="form-label">Meta B2B</label>
                                <input type="number" class="form-control" id="b2b_admission_target" name="b2b_admission_target"
                                       min="0" value="0">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2b_admissions" class="form-label">Admisiones B2B</label>
                                <input type="number" class="form-control" id="b2b_admissions" name="b2b_admissions"
                                       min="0" value="0">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="b2c_admissions" class="form-label">Admisiones B2C</label>
                                <input type="number" class="form-control" id="b2c_admissions" name="b2c_admissions"
                                       min="0" value="0">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-sm-6 col-md-4">
                                <label for="admission_deadline_date" class="form-label">Límite Admisión</label>
                                <input type="date" class="form-control" id="admission_deadline_date" name="admission_deadline_date">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Fechas de Entrenamiento ────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-calendar-event"></i> Fechas de Entrenamiento
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="start_date" class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            <div class="col-sm-6">
                                <label for="end_date" class="form-label">Fecha de Fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Las fechas de <strong>50%</strong> y <strong>75%</strong> del entrenamiento se calculan automáticamente.
                        </div>
                    </div>

                    <!-- ─── Asignaciones ───────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-briefcase"></i> Asignaciones
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="related_project" class="form-label">Proyecto Relacionado</label>
                                <input type="text" class="form-control" id="related_project" name="related_project"
                                       placeholder="ej. Proyecto Formación Tech 2026">
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_coach" class="form-label">Coach Asignado</label>
                                <input type="text" class="form-control" id="assigned_coach" name="assigned_coach"
                                       placeholder="ej. María González">
                            </div>
                            <div class="col-md-6">
                                <label for="bootcamp_type" class="form-label">Tipo de Bootcamp</label>
                                <input type="text" class="form-control" id="bootcamp_type" name="bootcamp_type"
                                       placeholder="ej. Full Stack, Data Science, UX/UI">
                            </div>
                            <div class="col-md-6">
                                <label for="area" class="form-label">Área</label>
                                <select class="form-select" id="area" name="area">
                                    <option value="">Seleccionar área...</option>
                                    <option value="academic">Academic</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="admissions">Admissions</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_class_schedule" class="form-label">Horario Asignado</label>
                                <input type="text" class="form-control" id="assigned_class_schedule" name="assigned_class_schedule"
                                       placeholder="ej. Lunes a Viernes 9:00-13:00">
                            </div>
                        </div>
                    </div>

                    <!-- ─── Estado ─────────────────────────────── -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-flag"></i> Estado
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="training_status" class="form-label">Estado del Entrenamiento</label>
                                <select class="form-select" id="training_status" name="training_status">
                                    <option value="not_started" selected>Sin iniciar</option>
                                    <option value="in_progress">En progreso</option>
                                    <option value="completed">Completado</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Botones ────────────────────────────── -->
                    <div class="d-flex flex-column flex-sm-row gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Crear Cohorte
                        </button>
                        <a href="/cohorts" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
