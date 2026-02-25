-- ============================================================
--  Migration 006 — Seed real cohort data (Feb 2026)
-- ============================================================
--  Inserts 27 cohorts from the operations spreadsheet.
--  Maps: Cohort→cohort_code, Familia→name, Bootcamp→bootcamp_type,
--        Proyecto→related_project, Coach→assigned_coach,
--        Fecha Inicio/Fin→start/end_date, Días+Horas→schedule,
--        B2B/B2C→admissions, training_status by date.
--
--  Run: mysql -u root cohort_monitor < database/migrations/006_seed_cohorts_feb2026.sql
-- ============================================================

USE cohort_monitor;

-- Remove old sample/seed data
DELETE FROM cohorts WHERE cohort_code IN ('COH-2026-001', 'COH-2025-002', 'COH-2025-003');

INSERT INTO cohorts (
    cohort_code, name, bootcamp_type, related_project, assigned_coach,
    start_date, end_date, assigned_class_schedule,
    b2b_admissions, b2c_admissions, total_admission_target,
    training_status
) VALUES
-- 1. AIGSK4 — Gen AI Skills (ended 20/02, COMPLETED)
('AIGSK4', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-01-12', '2026-02-20', 'Mar-Jue 18:30-20:30',
 7, 2, 9, 'completed'),

-- 2. AIESS1 — AI Agents Essentials (started 12/01, IN PROGRESS)
('AIESS1', 'AI Agents Essentials', 'AIESS', 'KODIGO', 'Michelle Bonilla',
 '2026-01-12', '2026-03-20', 'Mar-Jue 16:00-18:00',
 3, 1, 4, 'in_progress'),

-- 3. AITCH2 — AI For Teacher MINDE (started 06/02, IN PROGRESS)
('AITCH2', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Kenia Paiz',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 4. AITCH3 — AI For Teacher MINDE (started 06/02, IN PROGRESS)
('AITCH3', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Fernando Aguilar',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 5. AITCH1 — AI For Teacher MINDE (started 06/02, IN PROGRESS)
('AITCH1', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Vic Flores',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 6. DAJ21 — Data Analyst Jr. (started 16/02, IN PROGRESS)
('DAJ21', 'Data Analyst Jr.', 'DAJ', 'KODIGO', 'Numas Salazar',
 '2026-02-16', '2026-06-16', 'Lun-Mar-Mié 18:30-20:30',
 6, 5, 11, 'in_progress'),

-- 7. PY-K1 — Python para análisis de datos (started 19/02, IN PROGRESS)
('PY-K1', 'Python para análisis de datos', 'PY', 'KEY INSTITUTE', 'Joel Orellana',
 '2026-02-19', '2026-05-21', 'Jue 16:00-18:00',
 0, 0, 0, 'in_progress'),

-- 8. AIESS2 — AI Agents Essentials (started 23/02, IN PROGRESS)
('AIESS2', 'AI Agents Essentials', 'AIESS', 'KODIGO', 'Vic Flores',
 '2026-02-23', '2026-04-24', 'Mar-Jue 18:30-20:30',
 0, 0, 45, 'in_progress'),

-- 9. TECHF3 — Tech Fundamentals (starts 25/02, NOT STARTED)
('TECHF3', 'Tech Fundamentals', 'TECHF', 'ALDEA', 'Eduardo Calles',
 '2026-02-25', '2026-04-08', 'Lun-Vie 08:00-12:00',
 0, 0, 0, 'not_started'),

-- 10. TECHF4 — Tech Fundamentals (starts 25/02, NOT STARTED)
('TECHF4', 'Tech Fundamentals', 'TECHF', 'ALDEA', 'Fernando Aguilar',
 '2026-02-25', '2026-04-08', 'Lun-Vie 13:00-17:00',
 0, 0, 0, 'not_started'),

-- 11. FSJ33 — Full Stack Jr. (starts 02/03, NOT STARTED)
('FSJ33', 'Full Stack Jr.', 'FSJ', 'KODIGO', 'Jairo Vega',
 '2026-03-02', '2026-09-02', 'Lun-Mié-Vie 18:00-20:00',
 8, 4, 12, 'not_started'),

-- 12. AIDAT1 — AI For Data (starts 09/03, NOT STARTED)
('AIDAT1', 'AI For Data', 'AIDAT', 'KODIGO', 'Michelle Bonilla',
 '2026-03-09', '2026-05-09', 'Mar-Jue 18:30-20:30',
 4, 4, 8, 'not_started'),

-- 13. PY5 — Python para análisis de datos (starts 16/03, NOT STARTED)
('PY5', 'Python para análisis de datos', 'PY', 'KODIGO', 'Andrés Torres',
 '2026-03-16', '2026-07-16', 'Lun-Mié-Vie 18:00-20:00',
 5, 4, 9, 'not_started'),

-- 14. PY4 — Python para análisis de datos (starts 06/04, NOT STARTED)
('PY4', 'Python para análisis de datos', 'PY', 'INCAF 3.1', 'Andrés Torres',
 '2026-04-06', '2026-08-02', 'Mar-Jue-Sáb 18:00-20:00',
 0, 45, 45, 'not_started'),

-- 15. DAJ22 — BI para Análisis de Datos (starts 06/04, NOT STARTED)
('DAJ22', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.1', 'Andrés Hércules',
 '2026-04-06', '2026-08-02', 'Lun-Mié-Vie 18:30-20:30',
 23, 23, 46, 'not_started'),

-- 16. FSJ34 — Full Stack Jr. (starts 06/04, NOT STARTED)
('FSJ34', 'Full Stack Jr.', 'FSJ', 'INCAF 3.1', 'Gino Miles',
 '2026-04-06', '2026-10-07', 'Lun-Mié-Vie 15:00-17:00',
 45, 0, 45, 'not_started'),

-- 17. TECHF5 — Tech Fundamentals (starts 06/04, NOT STARTED)
('TECHF5', 'Tech Fundamentals', 'TECHF', 'KODIGO', 'Eduardo Calles',
 '2026-04-06', '2026-05-06', 'Jue-Vie 18:30-20:30',
 10, 0, 10, 'not_started'),

-- 18. SQL6 — SQL (starts 06/04, NOT STARTED)
('SQL6', 'SQL', 'SQL', 'INCAF 3.1', 'Johnny de Paz',
 '2026-04-06', '2026-08-02', 'Lun-Mar-Mié 18:00-20:00',
 37, 0, 37, 'not_started'),

-- 19. AIGSK5 — Gen AI Agents (starts 07/04, NOT STARTED)
('AIGSK5', 'Gen AI Agents', 'AIGSK', 'INCAF 3.1', 'Vic Flores',
 '2026-04-07', '2026-05-07', 'Mar-Jue 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 20. DATTR1 — Data Trainee (starts 07/04, NOT STARTED)
('DATTR1', 'Data Trainee', 'DATTR', 'INCAF 3.1', 'Michelle Bonilla',
 '2026-04-07', '2026-07-10', 'Lun-Mié-Vie 18:30-20:30',
 18, 27, 45, 'not_started'),

-- 21. AIDJR1 — AI For Devs Jr. (starts 07/04, NOT STARTED)
('AIDJR1', 'AI For Devs Jr.', 'AIDJR', 'INCAF 3.1', 'Kenia Paiz',
 '2026-04-07', '2026-05-19', 'Mar-Jue 18:30-20:30',
 0, 45, 45, 'not_started'),

-- 22. AITCH4 — AI For Teacher (starts 07/04, NOT STARTED)
('AITCH4', 'AI For Teacher', 'AITCH', 'INCAF 3.1', '(nuevo coach 5)',
 '2026-04-07', '2026-06-02', 'Mar-Jue 18:30-04:30',
 36, 9, 45, 'not_started'),

-- 23. AIGSK6 — Gen AI Skills (starts 09/04, NOT STARTED)
('AIGSK6', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Sergio Hernández',
 '2026-04-09', '2026-05-09', 'Mar-Jue 15:00-17:00',
 14, 4, 18, 'not_started'),

-- 24. WIB2 — Web Infrastructure Basic (starts 27/04, NOT STARTED)
('WIB2', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Kenia Paiz',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 25. WIB3 — Web Infrastructure Basic (starts 27/04, NOT STARTED)
('WIB3', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Fernando Aguilar',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 26. WIB1 — Web Infrastructure Basic (starts 27/04, NOT STARTED)
('WIB1', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Vic Flores',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 27. DAJ-L1 — Data Analyst Jr. (starts 27/04, NOT STARTED)
('DAJ-L1', 'Data Analyst Jr.', 'DAJ', 'LAMAR', 'Michelle Bonilla',
 '2026-04-27', '2026-10-27', 'Lun-Mié-Vie 10:00-12:00',
 12, 0, 12, 'not_started');
