-- =========================================================================
-- Migración 018: Agregar columnas reales para b2c_admission_target,
-- financial_target_revenue y financial_actual_revenue
-- =========================================================================
-- Estas columnas se simulaban con literales "0 AS ..." en
-- CohortRepository::baseSelect() porque nunca existieron en la tabla
-- `cohorts`, lo que impedía persistir los valores capturados en el
-- formulario de edición del Plan Maestro (Meta B2C, Meta de ingresos e
-- Ingreso actual).
-- =========================================================================

ALTER TABLE cohorts
    ADD COLUMN b2c_admission_target INT UNSIGNED NOT NULL DEFAULT 0 AFTER b2b_admission_target,
    ADD COLUMN financial_target_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER b2c_admissions,
    ADD COLUMN financial_actual_revenue DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER financial_target_revenue;
