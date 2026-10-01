-- Generado automaticamente a partir de scripts/data/cohortes_2026.csv
-- INSERT de 24 cohortes nuevas (sin match previo en kodigo.cohorts).
-- Columnas NOT NULL sin DEFAULT en cohorts: cohort_code, name.
-- name usa el nombre real del bootcamp segun mapeo cohort_code -> nombre provisto por el usuario.
-- training_status se fija explicitamente en 'not_started' (coincide con el DEFAULT).
-- Resto de columnas (fechas, area, coach, etc.) se dejan sin especificar -> NULL o su DEFAULT.
--
-- AIPRO4 y AIGTA9 estaban duplicados en el CSV con valores distintos; se uso solo la
-- primera ocurrencia (fila_excel mas baja) de cada uno. Filas excluidas por duplicado:
--   fila_excel=67 cohort_code=AIPRO4 fecha=2026-08-28 total_target=42 b2b_target=24 b2c_target=18 b2c_adm=0 b2b_adm=26 rev_target=16661.504412000002 rev_actual=0
--   fila_excel=80 cohort_code=AIPRO4 fecha=2026-10-05 total_target=42 b2b_target=24 b2c_target=18 b2c_adm=0 b2b_adm=0 rev_target=8330.7501 rev_actual=0
--   fila_excel=84 cohort_code=AIGTA9 fecha=2026-10-06 total_target=45 b2b_target=27 b2c_target=18 b2c_adm=0 b2b_adm=35 rev_target=17598.6 rev_actual=0
-- NO EJECUTAR sin revision previa.

START TRANSACTION;

-- fila_excel=8 cohort_code=FSJ39 fecha=2026-02-06
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('FSJ39', 'Full Stack Junior', 13, 7, 6, 0, 0, 15995.56, 0.00, 'not_started');

-- fila_excel=12 cohort_code=AIGSK5 fecha=2026-04-06
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIGSK5', 'GEN AI SKILLS', 13, 13, 0, 12, 0, 2102.65, 2336.28, 'not_started');

-- fila_excel=16 cohort_code=MCLASS fecha=2026-04-20
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('MCLASS', 'Master class', 20, 20, 0, 20, 0, 3893.81, 3893.80, 'not_started');

-- fila_excel=18 cohort_code=DAJ-L1 fecha=2026-05-11
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('DAJ-L1', 'Data Analyst', 33, 0, 33, 0, 36, 70800.00, 11949.16, 'not_started');

-- fila_excel=39 cohort_code=AIGSK19 fecha=2026-07-07
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIGSK19', 'GEN AI SKILLS', 46, 27, 19, 33, 21, 8713.55, 0.00, 'not_started');

-- fila_excel=42 cohort_code=AITCH1B fecha=2026-07-09
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AITCH1B', 'AI for Teachers', 55, 0, 55, 0, 0, 38790.56, 38790.56, 'not_started');

-- fila_excel=43 cohort_code=AITCH1C fecha=2026-07-09
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AITCH1C', 'AI for Teachers', 55, 0, 55, 0, 0, 38790.56, 38790.56, 'not_started');

-- fila_excel=44 cohort_code=TECHF1A fecha=2026-07-09
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('TECHF1A', 'Tech Fundamentals', 20, 0, 20, 0, 0, 5433.60, 0.00, 'not_started');

-- fila_excel=45 cohort_code=TECHF1B fecha=2026-07-09
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('TECHF1B', 'Tech Fundamentals', 20, 0, 20, 0, 0, 5433.60, 0.00, 'not_started');

-- fila_excel=46 cohort_code=PYB1 fecha=2026-07-09
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('PYB1', 'Python Science', 35, 35, 0, 0, 0, 29074.36, 14537.18, 'not_started');

-- fila_excel=49 cohort_code=AIESS9 fecha=2026-07-25
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIESS9', 'AI AGENT', 46, 27, 19, 33, 0, 17427.10, 0.00, 'not_started');

-- fila_excel=53 cohort_code=AIPRO4 fecha=2026-08-10
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIPRO4', 'GEN AI SKILLS', 42, 24, 18, 27, 19, 8330.75, 0.00, 'not_started');

-- fila_excel=59 cohort_code=BIANL3 fecha=2026-08-19
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('BIANL3', 'Analista de BI', 60, 30, 30, 0, 0, 69809.47, 0.00, 'not_started');

-- fila_excel=60 cohort_code=BIANL4 fecha=2026-08-19
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('BIANL4', 'Analista de BI', 60, 30, 30, 0, 0, 69809.47, 0.00, 'not_started');

-- fila_excel=66 cohort_code=AICLD4 fecha=2026-08-27
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AICLD4', 'AI Claude', 25, 25, 0, 0, 0, 6969.00, 0.00, 'not_started');

-- fila_excel=68 cohort_code=AITCH1A fecha=2026-08-31
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AITCH1A', 'AI for Teachers', 55, 0, 55, 0, 0, 38790.56, 38790.56, 'not_started');

-- fila_excel=75 cohort_code=AIGTA8 fecha=2026-09-21
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIGTA8', 'AI AGENT', 15, 15, 0, 0, 0, 7858.40, 0.00, 'not_started');

-- fila_excel=78 cohort_code=AIGTA3 fecha=2026-09-24
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIGTA3', 'GEN AI SKILLS', 46, 27, 19, 20, 14, 38788.99, 0.00, 'not_started');

-- fila_excel=79 cohort_code=AILMT-K2 fecha=2026-09-24
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AILMT-K2', 'AI Machine Learning', 0, 0, 0, 0, 0, 0.00, 0.00, 'not_started');

-- fila_excel=81 cohort_code=AIGTA9 fecha=2026-10-05
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIGTA9', 'GEN AI SKILLS', 16, 16, 0, 0, 0, 7858.40, 0.00, 'not_started');

-- fila_excel=83 cohort_code=AIPRO5 fecha=2026-10-05
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIPRO5', 'GEN AI SKILLS', 15, 15, 0, 0, 0, 12294.69, 0.00, 'not_started');

-- fila_excel=89 cohort_code=AICLD5 fecha=2026-10-20
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AICLD5', 'AI Claude', 15, 15, 0, 0, 0, 4181.42, 0.00, 'not_started');

-- fila_excel=90 cohort_code=POWBI2 fecha=2026-10-27
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('POWBI2', 'Power BI', 23, 15, 8, 0, 0, 4599.56, 0.00, 'not_started');

-- fila_excel=91 cohort_code=AIPRO3 fecha=2026-10-27
INSERT INTO cohorts (cohort_code, name, total_admission_target, b2b_admission_target, b2c_admission_target, b2b_admissions, b2c_admissions, financial_target_revenue, financial_actual_revenue, training_status) VALUES ('AIPRO3', 'AI For Professional', 13, 13, 0, 0, 0, 3986.28, 0.00, 'not_started');

COMMIT;
