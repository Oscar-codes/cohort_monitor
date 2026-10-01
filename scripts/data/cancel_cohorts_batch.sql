-- ============================================================
-- Cancelación masiva de cohortes por cohort_code
-- Generado: 2026-09-22
--
-- 30 códigos encontrados y actualizados (todos estaban en
-- training_status = 'not_started' antes de este cambio).
--
-- 1 código NO encontrado en la tabla `cohorts` (se ignora,
-- no se incluye ningún UPDATE para él):
--   - WDFRT3
-- ============================================================

USE kodigo;

START TRANSACTION;

UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AICLD4';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIDAT1';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIDSR1';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS12';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS13';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS15';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS19';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS20';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS22';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS5';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIESS8';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK10';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK13';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK17';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK18';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK20';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK21';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK23';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK24';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK25';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK26';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK30';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AIGSK31';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AISCA2';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AISCA4';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AISCA6';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'AISCA7';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'BIANL5';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'JD24';
UPDATE cohorts SET training_status = 'cancelled' WHERE cohort_code = 'TECHF3';

-- Verificación antes de confirmar (debe devolver 30 filas, todas 'cancelled'):
-- SELECT cohort_code, training_status FROM cohorts
-- WHERE cohort_code IN (
--   'AICLD4','AIDAT1','AIDSR1','AIESS12','AIESS13','AIESS15','AIESS19','AIESS20',
--   'AIESS22','AIESS5','AIESS8','AIGSK10','AIGSK13','AIGSK17','AIGSK18','AIGSK20',
--   'AIGSK21','AIGSK23','AIGSK24','AIGSK25','AIGSK26','AIGSK30','AIGSK31','AISCA2',
--   'AISCA4','AISCA6','AISCA7','BIANL5','JD24','TECHF3'
-- ) ORDER BY cohort_code;

COMMIT;
