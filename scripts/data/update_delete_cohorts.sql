-- Acciones sobre cohorts revisadas manualmente antes de generar este script.
-- NO EJECUTAR sin revision previa.

START TRANSACTION;

-- ============================================================
-- 1) UPDATE AIDJR3 (id=365)
--    Valores actuales en DB antes del UPDATE:
--    total_admission_target=52, b2b_admission_target=17, b2c_admission_target=0,
--    b2b_admissions=17, b2c_admissions=35, financial_target_revenue=0.00, financial_actual_revenue=0.00
-- ============================================================
UPDATE cohorts
SET total_admission_target = 52,
    b2b_admission_target = 25,
    b2c_admission_target = 27,
    b2c_admissions = 0,
    b2b_admissions = 6,
    financial_target_revenue = 18325.96,
    financial_actual_revenue = 0.00
WHERE cohort_code = 'AIDJR3';

-- ============================================================
-- 2) DELETE cohortes sin uso
--    Verificacion previa (FK e columnas de texto sin FK):
--      AIPRO4 (id=384): 0 filas relacionadas en TODAS las tablas revisadas -> seguro
--      AIGTA9 (id=392): 0 filas relacionadas en TODAS las tablas revisadas -> seguro
--      AIAOP1 (id=329): EXCLUIDO por decision del usuario. Tiene ~2800 filas
--        relacionadas por cohort_code (sin FK) en: aca_estudiantes (46),
--        aca_snapshots (1890), pbi_dim_cohort (1), pbi_dim_student (45),
--        pbi_fact_feedback (196), pbi_read_attendance (495), pbi_read_grades (100),
--        pbi_read_progress (50), teacher_attendance (4). Borrarlo las dejaria huerfanas.
-- ============================================================
DELETE FROM cohorts WHERE cohort_code IN ('AIPRO4', 'AIGTA9');

COMMIT;
