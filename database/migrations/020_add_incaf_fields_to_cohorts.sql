-- =========================================================================
-- Migración 020: Agregar campos INCAF a cohorts
-- =========================================================================
-- Datos adicionales de admisiones INCAF (inscritos totales, B2B y B2C).
-- No reemplazan ninguna columna existente. NULL significa "sin dato",
-- distinto de 0 inscritos.
-- =========================================================================

ALTER TABLE cohorts
    ADD COLUMN incaf_enrolled INT NULL DEFAULT NULL AFTER b2c_admissions,
    ADD COLUMN incaf_b2b      INT NULL DEFAULT NULL AFTER incaf_enrolled,
    ADD COLUMN incaf_b2c      INT NULL DEFAULT NULL AFTER incaf_b2b;
