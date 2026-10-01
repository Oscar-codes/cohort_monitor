-- Generado automaticamente a partir de scripts/data/cohortes_2026.csv
-- Filas que antes violaban cohorts_chk_admissions (constraint eliminado; ver nota abajo).
-- NOTA: cohorts_chk_admissions nunca existio en kodigo.cohorts (277 filas);
-- solo existia en k_pruebas.cohorts (base distinta, 111 filas). No se ejecuto ningun
-- ALTER TABLE sobre kodigo.cohorts porque no habia nada que eliminar ahi.
-- NO EJECUTAR sin revision previa.

START TRANSACTION;

-- fila_excel=6 cohort_code=AIGSK4 fecha=2026-01-12 (DB actual: b2b_target=0, b2b_admissions=7)
UPDATE cohorts SET total_admission_target = 9, b2b_admission_target = 7, b2c_admission_target = 2, b2c_admissions = 0, b2b_admissions = 17, financial_target_revenue = 1557.52, financial_actual_revenue = 2640.00 WHERE id = 4; -- cohort_code = AIGSK4

-- fila_excel=17 cohort_code=AIGSK8 fecha=2026-04-28 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 13, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 30, financial_target_revenue = 1771.68, financial_actual_revenue = 6820.00 WHERE id = 39; -- cohort_code = AIGSK8

-- fila_excel=20 cohort_code=AIGSK11 fecha=2026-06-02 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 36, financial_target_revenue = 8849.50, financial_actual_revenue = 0.00 WHERE id = 33; -- cohort_code = AIGSK11

-- fila_excel=21 cohort_code=AIGSK15 fecha=2026-06-02 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 17, financial_target_revenue = 2920.27, financial_actual_revenue = 0.00 WHERE id = 34; -- cohort_code = AIGSK15

-- fila_excel=22 cohort_code=POWBI1 fecha=2026-06-08 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 20, b2b_admission_target = 20, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 22, financial_target_revenue = 5132.74, financial_actual_revenue = 0.00 WHERE id = 184; -- cohort_code = POWBI1

-- fila_excel=23 cohort_code=BIANL1 fecha=2026-06-10 (DB actual: b2b_target=0, b2b_admissions=30)
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 30, b2c_admission_target = 30, b2c_admissions = 0, b2b_admissions = 31, financial_target_revenue = 73299.95, financial_actual_revenue = 1327.44 WHERE id = 77; -- cohort_code = BIANL1

-- fila_excel=25 cohort_code=AIGTA1 fecha=2026-06-10 (DB actual: b2b_target=0, b2b_admissions=27)
UPDATE cohorts SET total_admission_target = 30, b2b_admission_target = 18, b2c_admission_target = 12, b2c_admissions = 18, b2b_admissions = 35, financial_target_revenue = 7430.00, financial_actual_revenue = 0.00 WHERE id = 330; -- cohort_code = AIGTA1

-- fila_excel=29 cohort_code=AITCH4 fecha=2026-06-11 (DB actual: b2b_target=0, b2b_admissions=39)
UPDATE cohorts SET total_admission_target = 42, b2b_admission_target = 36, b2c_admission_target = 6, b2c_admissions = 8, b2b_admissions = 43, financial_target_revenue = 21091.45, financial_actual_revenue = 0.00 WHERE id = 25; -- cohort_code = AITCH4

-- fila_excel=30 cohort_code=DATTR1 fecha=2026-06-11 (DB actual: b2b_target=0, b2b_admissions=33)
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 27, b2c_admission_target = 33, b2c_admissions = 28, b2b_admissions = 43, financial_target_revenue = 56881.45, financial_actual_revenue = 0.00 WHERE id = 23; -- cohort_code = DATTR1

-- fila_excel=36 cohort_code=AIGSK14 fecha=2026-07-01 (DB actual: b2b_target=13, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 6, b2c_admission_target = 7, b2c_admissions = 0, b2b_admissions = 27, financial_target_revenue = 2024.78, financial_actual_revenue = 0.00 WHERE id = 264; -- cohort_code = AIGSK14

-- fila_excel=38 cohort_code=DATTR2 fecha=2026-07-07 (DB actual: b2b_target=27, b2b_admissions=27)
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 27, b2c_admission_target = 33, b2c_admissions = 27, b2b_admissions = 29, financial_target_revenue = 56881.45, financial_actual_revenue = 0.00 WHERE id = 273; -- cohort_code = DATTR2

-- fila_excel=41 cohort_code=PY5 fecha=2026-07-09 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 44, b2b_admission_target = 0, b2c_admission_target = 44, b2c_admissions = 43, b2b_admissions = 10, financial_target_revenue = 89754.48, financial_actual_revenue = 0.00 WHERE id = 16; -- cohort_code = PY5

-- fila_excel=48 cohort_code=AIGSK12 fecha=2026-07-21 (DB actual: b2b_target=31, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 50, b2b_admission_target = 0, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 15, financial_target_revenue = 8849.50, financial_actual_revenue = 0.00 WHERE id = 257; -- cohort_code = AIGSK12

-- fila_excel=54 cohort_code=PY6 fecha=2026-08-10 (DB actual: b2b_target=43, b2b_admissions=43)
UPDATE cohorts SET total_admission_target = 43, b2b_admission_target = 43, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 46, financial_target_revenue = 89754.48, financial_actual_revenue = 0.00 WHERE id = 284; -- cohort_code = PY6

-- fila_excel=55 cohort_code=DATTR3 fecha=2026-08-12 (DB actual: b2b_target=18, b2b_admissions=18)
UPDATE cohorts SET total_admission_target = 61, b2b_admission_target = 18, b2c_admission_target = 43, b2c_admissions = 37, b2b_admissions = 24, financial_target_revenue = 58303.48, financial_actual_revenue = 0.00 WHERE id = 287; -- cohort_code = DATTR3

-- fila_excel=61 cohort_code=SQL7 fecha=2026-08-24 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 24, b2b_admission_target = 12, b2c_admission_target = 12, b2c_admissions = 11, b2b_admissions = 14, financial_target_revenue = 37113.53, financial_actual_revenue = 0.00 WHERE id = 354; -- cohort_code = SQL7

-- fila_excel=62 cohort_code=AIGSK32 fecha=2026-08-25 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 16, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 37, financial_target_revenue = 2654.85, financial_actual_revenue = 0.00 WHERE id = 356; -- cohort_code = AIGSK32

-- fila_excel=64 cohort_code=AITCH6 fecha=2026-08-26 (DB actual: b2b_target=36, b2b_admissions=36)
UPDATE cohorts SET total_admission_target = 45, b2b_admission_target = 36, b2c_admission_target = 9, b2c_admissions = 9, b2b_admissions = 41, financial_target_revenue = 21091.45, financial_actual_revenue = 0.00 WHERE id = 297; -- cohort_code = AITCH6

-- fila_excel=72 cohort_code=AIESS10 fecha=2026-09-16 (DB actual: b2b_target=13, b2b_admissions=13)
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 13, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 16, financial_target_revenue = 6780.93, financial_actual_revenue = 0.00 WHERE id = 280; -- cohort_code = AIESS10

-- fila_excel=73 cohort_code=AIGSK33 fecha=2026-09-16 (DB actual: b2b_target=0, b2b_admissions=0)
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 16, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 20, financial_target_revenue = 4424.80, financial_actual_revenue = 0.00 WHERE id = 361; -- cohort_code = AIGSK33

COMMIT;
