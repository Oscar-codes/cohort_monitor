-- Generado automaticamente a partir de scripts/data/cohortes_2026.csv
-- Excluye: cohort_code sin match en DB, filas que violarian cohorts_chk_admissions,
-- y cohort_code duplicados en el CSV con valores conflictivos (AIDJR3, AIAOP1) -- revisar aparte.
-- NO EJECUTAR sin revision previa.

START TRANSACTION;

-- fila_excel=5 cohort_code=FSJ33 fecha=2026-01-06
UPDATE cohorts SET total_admission_target = 7, b2b_admission_target = 4, b2c_admission_target = 3, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 7320.80, financial_actual_revenue = 7117.74 WHERE id = 14; -- cohort_code = FSJ33

-- fila_excel=7 cohort_code=AIESS1 fecha=2026-01-12
UPDATE cohorts SET total_admission_target = 16, b2b_admission_target = 16, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5488.00, financial_actual_revenue = 5488.00 WHERE id = 5; -- cohort_code = AIESS1

-- fila_excel=9 cohort_code=DAJ21 fecha=2026-02-16
UPDATE cohorts SET total_admission_target = 11, b2b_admission_target = 6, b2c_admission_target = 5, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 10312.69, financial_actual_revenue = 7157.11 WHERE id = 9; -- cohort_code = DAJ21

-- fila_excel=10 cohort_code=AIESS2 fecha=2026-02-23
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5855.55, financial_actual_revenue = 5855.55 WHERE id = 11; -- cohort_code = AIESS2

-- fila_excel=11 cohort_code=AIESS3 fecha=2026-03-23
UPDATE cohorts SET total_admission_target = 10, b2b_admission_target = 10, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5855.55, financial_actual_revenue = 0.00 WHERE id = 31; -- cohort_code = AIESS3

-- fila_excel=13 cohort_code=AISCA1 fecha=2026-04-15
UPDATE cohorts SET total_admission_target = 6, b2b_admission_target = 6, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 2628.32, financial_actual_revenue = 5501.46 WHERE id = 45; -- cohort_code = AISCA1

-- fila_excel=14 cohort_code=AIGSK6 fecha=2026-04-20
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 10, financial_target_revenue = 2920.27, financial_actual_revenue = 1201.32 WHERE id = 26; -- cohort_code = AIGSK6

-- fila_excel=15 cohort_code=AIGSK7 fecha=2026-04-20
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 15, financial_target_revenue = 2920.27, financial_actual_revenue = 2920.35 WHERE id = 38; -- cohort_code = AIGSK7

-- fila_excel=19 cohort_code=AIGSK9 fecha=2026-05-20
UPDATE cohorts SET total_admission_target = 20, b2b_admission_target = 20, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 11, financial_target_revenue = 4121.57, financial_actual_revenue = 2141.59 WHERE id = 40; -- cohort_code = AIGSK9

-- fila_excel=24 cohort_code=FSJ34 fecha=2026-06-10
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 0, b2c_admission_target = 60, b2c_admissions = 60, b2b_admissions = 0, financial_target_revenue = 111038.35, financial_actual_revenue = 1356.00 WHERE id = 19; -- cohort_code = FSJ34

-- fila_excel=26 cohort_code=SQL5 fecha=2026-06-10
UPDATE cohorts SET total_admission_target = 52, b2b_admission_target = 52, b2c_admission_target = 0, b2c_admissions = 6, b2b_admissions = 44, financial_target_revenue = 108247.79, financial_actual_revenue = 0.00 WHERE id = 194; -- cohort_code = SQL5

-- fila_excel=28 cohort_code=AIDJR1 fecha=2026-06-11
UPDATE cohorts SET total_admission_target = 45, b2b_admission_target = 0, b2c_admission_target = 45, b2c_admissions = 40, b2b_admissions = 0, financial_target_revenue = 15707.96, financial_actual_revenue = 0.00 WHERE id = 24; -- cohort_code = AIDJR1

-- fila_excel=31 cohort_code=PY4 fecha=2026-06-11
UPDATE cohorts SET total_admission_target = 54, b2b_admission_target = 5, b2c_admission_target = 49, b2c_admissions = 43, b2b_admissions = 0, financial_target_revenue = 92849.46, financial_actual_revenue = 1623.89 WHERE id = 17; -- cohort_code = PY4

-- fila_excel=32 cohort_code=FSJ35 fecha=2026-06-15
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 60, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 51, financial_target_revenue = 111038.35, financial_actual_revenue = 0.00 WHERE id = 138; -- cohort_code = FSJ35

-- fila_excel=33 cohort_code=FSJ36 fecha=2026-06-23
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 30, b2c_admission_target = 30, b2c_admissions = 34, b2b_admissions = 18, financial_target_revenue = 111038.35, financial_actual_revenue = 0.00 WHERE id = 139; -- cohort_code = FSJ36

-- fila_excel=34 cohort_code=AIGSK10 fecha=2026-06-30
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 2044.25, financial_actual_revenue = 0.00 WHERE id = 259; -- cohort_code = AIGSK10

-- fila_excel=35 cohort_code=AIESS4 fecha=2026-06-30
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 6, b2c_admission_target = 7, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 4509.73, financial_actual_revenue = 0.00 WHERE id = 265; -- cohort_code = AIESS4

-- fila_excel=37 cohort_code=SQL6 fecha=2026-07-06
UPDATE cohorts SET total_admission_target = 52, b2b_admission_target = 0, b2c_admission_target = 52, b2c_admissions = 49, b2b_admissions = 0, financial_target_revenue = 108247.79, financial_actual_revenue = 0.00 WHERE id = 21; -- cohort_code = SQL6

-- fila_excel=40 cohort_code=AIDSR1 fecha=2026-07-07
UPDATE cohorts SET total_admission_target = 11, b2b_admission_target = 3, b2c_admission_target = 8, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5548.67, financial_actual_revenue = 0.00 WHERE id = 274; -- cohort_code = AIDSR1

-- fila_excel=47 cohort_code=AIGSK18 fecha=2026-07-20
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 13, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 1771.68, financial_actual_revenue = 0.00 WHERE id = 277; -- cohort_code = AIGSK18

-- fila_excel=51 cohort_code=AIMLF1 fecha=2026-08-10
UPDATE cohorts SET total_admission_target = 43, b2b_admission_target = 30, b2c_admission_target = 13, b2c_admissions = 13, b2b_admissions = 29, financial_target_revenue = 39946.23, financial_actual_revenue = 0.00 WHERE id = 268; -- cohort_code = AIMLF1

-- fila_excel=52 cohort_code=BIANL2 fecha=2026-08-10
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 29, b2c_admission_target = 31, b2c_admissions = 32, b2b_admissions = 29, financial_target_revenue = 75045.18, financial_actual_revenue = 0.00 WHERE id = 333; -- cohort_code = BIANL2

-- fila_excel=56 cohort_code=AITCH5 fecha=2026-08-13
UPDATE cohorts SET total_admission_target = 45, b2b_admission_target = 36, b2c_admission_target = 9, b2c_admissions = 6, b2b_admissions = 35, financial_target_revenue = 21091.45, financial_actual_revenue = 0.00 WHERE id = 288; -- cohort_code = AITCH5

-- fila_excel=57 cohort_code=AIMLF2 fecha=2026-08-18
UPDATE cohorts SET total_admission_target = 45, b2b_admission_target = 31, b2c_admission_target = 14, b2c_admissions = 13, b2b_admissions = 31, financial_target_revenue = 41323.68, financial_actual_revenue = 0.00 WHERE id = 317; -- cohort_code = AIMLF2

-- fila_excel=58 cohort_code=AICLD1 fecha=2026-08-18
UPDATE cohorts SET total_admission_target = 2, b2b_admission_target = 2, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 2, financial_target_revenue = 0.00, financial_actual_revenue = 0.00 WHERE id = 372; -- cohort_code = AICLD1

-- fila_excel=63 cohort_code=AICLD2 fecha=2026-08-25
UPDATE cohorts SET total_admission_target = 25, b2b_admission_target = 25, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 25, financial_target_revenue = 278.76, financial_actual_revenue = 0.00 WHERE id = 355; -- cohort_code = AICLD2

-- fila_excel=65 cohort_code=AICLD3 fecha=2026-08-27
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 10, financial_target_revenue = 4181.40, financial_actual_revenue = 0.00 WHERE id = 371; -- cohort_code = AICLD3

-- fila_excel=69 cohort_code=AIDJR2 fecha=2026-09-09
UPDATE cohorts SET total_admission_target = 45, b2b_admission_target = 0, b2c_admission_target = 45, b2c_admissions = 45, b2b_admissions = 0, financial_target_revenue = 15707.96, financial_actual_revenue = 0.00 WHERE id = 263; -- cohort_code = AIDJR2

-- fila_excel=70 cohort_code=WDFRT1 fecha=2026-09-10
UPDATE cohorts SET total_admission_target = 46, b2b_admission_target = 23, b2c_admission_target = 23, b2c_admissions = 23, b2b_admissions = 20, financial_target_revenue = 42327.43, financial_actual_revenue = 0.00 WHERE id = 304; -- cohort_code = WDFRT1

-- fila_excel=74 cohort_code=TECHF3 fecha=2026-09-18
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 6, financial_target_revenue = 7566.37, financial_actual_revenue = 0.00 WHERE id = 363; -- cohort_code = TECHF3

-- fila_excel=76 cohort_code=WDFRT2 fecha=2026-09-23
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 30, b2c_admission_target = 30, b2c_admissions = 25, b2b_admissions = 5, financial_target_revenue = 56436.58, financial_actual_revenue = 0.00 WHERE id = 360; -- cohort_code = WDFRT2

-- fila_excel=77 cohort_code=DATTR4 fecha=2026-09-23
UPDATE cohorts SET total_admission_target = 60, b2b_admission_target = 30, b2c_admission_target = 30, b2c_admissions = 21, b2b_admissions = 22, financial_target_revenue = 39817.01, financial_actual_revenue = 0.00 WHERE id = 358; -- cohort_code = DATTR4

-- fila_excel=82 cohort_code=AIGSK28 fecha=2026-10-05
UPDATE cohorts SET total_admission_target = 31, b2b_admission_target = 15, b2c_admission_target = 16, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 2654.85, financial_actual_revenue = 0.00 WHERE id = 311; -- cohort_code = AIGSK28

-- fila_excel=86 cohort_code=TECHF4 fecha=2026-10-12
UPDATE cohorts SET total_admission_target = 18, b2b_admission_target = 18, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 8512.17, financial_actual_revenue = 0.00 WHERE id = 366; -- cohort_code = TECHF4

-- fila_excel=87 cohort_code=AIDJR4 fecha=2026-10-15
UPDATE cohorts SET total_admission_target = 52, b2b_admission_target = 25, b2c_admission_target = 27, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 18325.96, financial_actual_revenue = 0.00 WHERE id = 367; -- cohort_code = AIDJR4

-- fila_excel=88 cohort_code=AIDJR5 fecha=2026-10-15
UPDATE cohorts SET total_admission_target = 52, b2b_admission_target = 17, b2c_admission_target = 35, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 18325.96, financial_actual_revenue = 0.00 WHERE id = 368; -- cohort_code = AIDJR5

-- fila_excel=92 cohort_code=AIESS16 fecha=2026-10-29
UPDATE cohorts SET total_admission_target = 15, b2b_admission_target = 15, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 10, financial_target_revenue = 5913.15, financial_actual_revenue = 0.00 WHERE id = 309; -- cohort_code = AIESS16

-- fila_excel=93 cohort_code=AIESS21 fecha=2026-11-03
UPDATE cohorts SET total_admission_target = 13, b2b_admission_target = 13, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5124.70, financial_actual_revenue = 0.00 WHERE id = 322; -- cohort_code = AIESS21

-- fila_excel=94 cohort_code=AIGSK29 fecha=2026-11-09
UPDATE cohorts SET total_admission_target = 25, b2b_admission_target = 20, b2c_admission_target = 5, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 5530.97, financial_actual_revenue = 0.00 WHERE id = 312; -- cohort_code = AIGSK29

-- fila_excel=95 cohort_code=TECHF5 fecha=2026-11-09
UPDATE cohorts SET total_admission_target = 18, b2b_admission_target = 18, b2c_admission_target = 0, b2c_admissions = 0, b2b_admissions = 0, financial_target_revenue = 8512.17, financial_actual_revenue = 0.00 WHERE id = 370; -- cohort_code = TECHF5

COMMIT;
