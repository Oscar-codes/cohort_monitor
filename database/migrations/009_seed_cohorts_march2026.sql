-- ============================================================
--  Migration 009 — Full cohort data reload (March 2026)
-- ============================================================
--  Replaces ALL existing cohort data with the updated
--  operations spreadsheet (112 cohorts, Jan 2026 – Dec 2026).
--
--  Run: mysql -u root -p cohort_monitor < database/migrations/009_seed_cohorts_march2026.sql
-- ============================================================

USE cohort_monitor;

-- Wipe existing cohort data (disable FK checks for students table)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE cohorts;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO cohorts (
    cohort_code, name, bootcamp_type, related_project, assigned_coach,
    start_date, end_date, assigned_class_schedule,
    b2b_admissions, b2c_admissions, total_admission_target,
    training_status
) VALUES

-- ── Enero 2026 ──────────────────────────────────────────────

-- 1. AIGSK4
('AIGSK4', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-01-12', '2026-02-20', 'Mar-Jue 18:30-20:30',
 7, 2, 9, 'completed'),

-- 2. AIESS1
('AIESS1', 'AI Agents Essentials', 'AIESS', 'KODIGO', 'Michelle Bonilla',
 '2026-01-12', '2026-03-20', 'Mar-Jue 16:00-18:00',
 3, 1, 4, 'in_progress'),

-- ── Febrero 2026 ────────────────────────────────────────────

-- 3. AITCH2
('AITCH2', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Kenia Paiz',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 4. AITCH3
('AITCH3', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Fernando Aguilar',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 5. AITCH1
('AITCH1', 'AI For Teacher MINDE', 'AITCH', 'MINEDUCYT', 'Vic Flores',
 '2026-02-06', '2026-04-26', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'in_progress'),

-- 6. DAJ21
('DAJ21', 'Data Analyst Jr.', 'DAJ', 'KODIGO', 'Numas Salazar',
 '2026-02-16', '2026-06-16', 'Lun-Mar-Mié 18:30-20:30',
 6, 5, 11, 'in_progress'),

-- 7. PY-K1
('PY-K1', 'Python para análisis de datos', 'PY', 'KEY INSTITUTE', 'Joel Orellana',
 '2026-02-19', '2026-05-21', 'Jue 16:00-18:00',
 0, 0, 0, 'in_progress'),

-- 8. AIESS2
('AIESS2', 'AI Agents Essentials', 'AIESS', 'KODIGO', 'Vic Flores',
 '2026-02-23', '2026-04-24', 'Mar-Jue 18:30-20:30',
 0, 0, 0, 'in_progress'),

-- ── Marzo 2026 ──────────────────────────────────────────────

-- 9. TECHF1
('TECHF1', 'Tech Fundamentals', 'TECHF', 'ALDEA', 'Fernando Aguilar',
 '2026-03-02', '2026-04-15', 'Lun-Vie 08:00-12:00',
 0, 0, 0, 'in_progress'),

-- 10. TECHF2
('TECHF2', 'Tech Fundamentals', 'TECHF', 'ALDEA', 'Eduardo Calles',
 '2026-03-02', '2026-04-15', 'Lun-Vie 13:00-17:00',
 0, 0, 0, 'in_progress'),

-- 11. FSJ33
('FSJ33', 'Full Stack Jr.', 'FSJ', 'KODIGO', 'Jairo Vega',
 '2026-03-02', '2026-09-02', 'Lun-Mar-Mié 18:00-20:00',
 8, 4, 12, 'in_progress'),

-- 12. AIGSK5
('AIGSK5', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-03-23', '2026-04-30', 'Mar-Jue 15:00-17:00',
 14, 4, 18, 'not_started'),

-- ── Abril 2026 ──────────────────────────────────────────────

-- 13. AIDAT1
('AIDAT1', 'AI For Data', 'AIDAT', 'KODIGO', 'Michelle Bonilla',
 '2026-04-06', '2026-06-01', 'Jue-Vie 18:30-20:30',
 4, 4, 8, 'not_started'),

-- 14. PY4
('PY4', 'Python para análisis de datos', 'PY', 'INCAF 3.1', 'Andrés Torres',
 '2026-04-06', '2026-08-02', 'Mar-Jue-Sáb 18:00-20:00',
 0, 45, 45, 'not_started'),

-- 15. DAJ22
('DAJ22', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.1', 'Andrés Hércules',
 '2026-04-06', '2026-08-02', 'Lun-Mié-Vie 18:30-20:30',
 23, 23, 46, 'not_started'),

-- 16. FSJ34
('FSJ34', 'Full Stack Jr.', 'FSJ', 'INCAF 3.1', 'Gino Miles',
 '2026-04-06', '2026-10-07', 'Lun-Mié-Vie 15:00-17:00',
 45, 0, 45, 'not_started'),

-- 17. TECHF3
('TECHF3', 'Tech Fundamentals', 'TECHF', 'KODIGO', 'Eduardo Calles',
 '2026-04-06', '2026-05-06', 'Jue-Vie 18:30-20:30',
 10, 0, 10, 'not_started'),

-- 18. SQL5
('SQL5', 'SQL', 'SQL', 'INCAF 3.1', 'Johnny de Paz',
 '2026-04-06', '2026-08-02', 'Lun-Mié-Vie 18:00-20:00',
 37, 0, 37, 'not_started'),

-- 19. DAJ23
('DAJ23', 'Data Analyst Jr.', 'DAJ', 'KODIGO', 'Carlos Dubon',
 '2026-04-06', '2027-08-10', 'Lun-Mié-Vie 18:30-20:30',
 7, 6, 13, 'not_started'),

-- 20. AIGSK6
('AIGSK6', 'Gen AI Agents', 'AIGSK', 'INCAF 3.1', 'Carlos Vargas',
 '2026-04-07', '2026-05-07', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 21. DATTR1
('DATTR1', 'Data Trainee', 'DATTR', 'INCAF 3.1', 'Michelle Bonilla',
 '2026-04-07', '2026-07-10', 'Lun-Mar-Mié 18:30-20:30',
 18, 27, 45, 'not_started'),

-- 22. AIDJR1
('AIDJR1', 'AI For Devs Jr.', 'AIDJR', 'INCAF 3.1', 'Hugo Barrientos',
 '2026-04-07', '2026-05-19', 'Mar-Jue 18:30-20:30',
 0, 45, 45, 'not_started'),

-- 23. AITCH4
('AITCH4', 'AI For Teacher', 'AITCH', 'INCAF 3.1', 'Gino Miles',
 '2026-04-07', '2026-06-02', 'Mar-Jue 18:30-20:30',
 36, 9, 45, 'not_started'),

-- 24. WIB2
('WIB2', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Kenia Paiz',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 25. WIB3
('WIB3', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Fernando Aguilar',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 26. WIB1
('WIB1', 'Web Infrastructure Basic', 'WIB', 'MINEDUCYT', 'Vic Flores',
 '2026-04-27', '2026-07-12', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 27. DAJ-L1
('DAJ-L1', 'Data Analyst Jr.', 'DAJ', 'LAMAR', 'Michelle Bonilla',
 '2026-04-27', '2026-10-27', 'Lun-Mié-Vie 10:00-12:00',
 12, 0, 12, 'not_started'),

-- ── Mayo 2026 ───────────────────────────────────────────────

-- 28. FSJ35
('FSJ35', 'Full Stack Jr.', 'FSJ', 'INCAF 3.1', 'Ricardo Arroyo',
 '2026-05-04', '2026-10-30', 'Lun-Mié-Vie 16:00-18:00',
 0, 45, 45, 'not_started'),

-- 29. AIMLF1
('AIMLF1', 'AI Machine Learning Foundation', 'AIMLF', 'INCAF 3.1', 'Nelson Zepeda',
 '2026-05-04', '2026-07-31', 'Lun-Mar-Mié 18:00-20:00',
 62, 0, 62, 'not_started'),

-- 30. DATTR2
('DATTR2', 'Data Trainee', 'DATTR', 'INCAF 3.1', 'Andrés Hércules',
 '2026-05-04', '2026-07-31', 'Mar-Jue-Sáb 18:30-20:30',
 18, 27, 45, 'not_started'),

-- 31. SQL6
('SQL6', 'SQL', 'SQL', 'INCAF 3.1', 'Carlos Dubon',
 '2026-05-04', '2026-09-07', 'Lun-Mar-Mié 18:00-20:00',
 0, 37, 37, 'not_started'),

-- 32. AIGSK7
('AIGSK7', 'Gen AI Agents', 'AIGSK', 'INCAF 3.1', 'Carlos Vargas',
 '2026-05-05', '2026-06-05', 'Mar-Jue 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 33. PY5
('PY5', 'Python para análisis de datos', 'PY', 'INCAF 3.1', 'Camille Le Roy',
 '2026-05-05', '2026-09-02', 'Lun-Mar-Mié 18:00-20:00',
 0, 45, 45, 'not_started'),

-- 34. DAJ24
('DAJ24', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.1', 'Numas Salazar',
 '2026-05-05', '2026-09-02', 'Jue-Vie-Sáb 18:30-20:30',
 23, 23, 46, 'not_started'),

-- 35. AIESS3
('AIESS3', 'Gen AI Agents', 'AIESS', 'INCAF 3.1', 'Carlos Vargas',
 '2026-05-08', '2026-07-03', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 36. AISCA1
('AISCA1', 'AI Scaling & Optimization', 'AISCA', 'KODIGO', '(nuevo coach 7)',
 '2026-05-11', '2026-06-11', 'Lun-Mié 18:30-20:30',
 6, 0, 6, 'not_started'),

-- 37. AIGSK8
('AIGSK8', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 3)',
 '2026-05-11', '2026-06-10', 'Mié-Vie 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 38. AILMT-K1
('AILMT-K1', 'AI Machine Learning', 'AIMLT', 'KEY INSTITUTE', 'Joel Orellana',
 '2026-05-28', '2026-09-17', 'Jue 16:00-18:00',
 0, 0, 0, 'not_started'),

-- ── Junio 2026 ──────────────────────────────────────────────

-- 39. AIESS4
('AIESS4', 'AI Agents & Optimization', 'AIESS', 'INCAF 3.1', 'Sergio Hernández',
 '2026-06-01', '2026-08-01', 'Lun-Mié 15:00-17:00',
 27, 18, 45, 'not_started'),

-- 40. FSJ36
('FSJ36', 'Full Stack Jr.', 'FSJ', 'INCAF 3.1', 'Hugo Barrientos',
 '2026-06-01', '2026-11-28', 'Lun-Mié-Vie 18:00-20:00',
 21, 24, 45, 'not_started'),

-- 41. AIMLF2
('AIMLF2', 'AI Machine Learning Foundation', 'AIMLF', 'INCAF 3.1', 'Joel Orellana',
 '2026-06-01', '2026-09-04', 'Lun-Mar-Mié 18:00-20:00',
 0, 27, 27, 'not_started'),

-- 42. AITCH5
('AITCH5', 'AI For Teacher', 'AITCH', 'INCAF 3.1', 'Sergio Hernández',
 '2026-06-01', '2026-07-31', 'Jue-Vie 18:30-20:30',
 36, 9, 45, 'not_started'),

-- 43. PY6
('PY6', 'Python para análisis de datos', 'PY', 'INCAF 3.1', '(nuevo coach 9)',
 '2026-06-02', '2026-09-25', 'Lun-Mié-Vie 18:00-20:00',
 45, 0, 45, 'not_started'),

-- 44. DATTR3
('DATTR3', 'Data Trainee', 'DATTR', 'INCAF 3.1', '(Backup coach 2)',
 '2026-06-02', '2026-09-04', 'Lun-Mié-Vie 18:30-20:30',
 18, 27, 45, 'not_started'),

-- 45. SQL7
('SQL7', 'SQL', 'SQL', 'INCAF 3.1', '(nuevo coach 8)',
 '2026-06-02', '2026-09-25', 'Lun-Mié-Vie 18:00-20:00',
 37, 0, 37, 'not_started'),

-- 46. DAJ25
('DAJ25', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.1', '(nuevo coach 8)',
 '2026-06-02', '2026-09-25', 'Mar-Jue-Sáb 18:30-20:30',
 23, 23, 46, 'not_started'),

-- 47. AIESS5
('AIESS5', 'Gen AI Agents', 'AIESS', 'INCAF 3.1', 'Carlos Vargas',
 '2026-06-06', '2026-07-29', 'Mar-Jue 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 48. AIGSK9
('AIGSK9', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-06-09', '2026-07-09', 'Mié-Vie 18:30-20:30',
 14, 4, 18, 'not_started'),

-- 49. AIESS6
('AIESS6', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 3)',
 '2026-06-11', '2026-08-11', 'Lun-Mié 18:30-20:30',
 6, 2, 8, 'not_started'),

-- 50. AIESS7
('AIESS7', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 2)',
 '2026-06-13', '2026-08-14', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 51. AIGSK10
('AIGSK10', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 1)',
 '2026-06-15', '2026-07-15', 'Mié-Vie 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 52. AIDAT2
('AIDAT2', 'AI For Data', 'AIDAT', 'KODIGO', '(Backup coach 2)',
 '2026-06-15', '2026-08-14', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 53. TECHF4
('TECHF4', 'Tech Fundamentals', 'TECHF', 'KODIGO', '(nuevo coach 6)',
 '2026-06-15', '2026-07-28', 'Jue-Vie 18:30-20:30',
 16, 0, 16, 'not_started'),

-- 54. FSJ37
('FSJ37', 'Full Stack Jr.', 'FSJ', 'INCAF 3.1', 'Eduardo Calles',
 '2026-06-22', '2026-12-15', 'Lun-Mié-Vie 18:00-20:00',
 45, 0, 45, 'not_started'),

-- 55. AITCH6
('AITCH6', 'AI For Teacher', 'AITCH', 'INCAF 3.1', '(nuevo coach 4)',
 '2026-06-23', '2026-08-19', 'Mié-Vie 18:30-20:30',
 36, 9, 45, 'not_started'),

-- ── Julio 2026 ──────────────────────────────────────────────

-- 56. FSJ38
('FSJ38', 'Full Stack Jr.', 'FSJ', 'INCAF 3.2', 'Jairo Vega',
 '2026-07-06', '2027-01-15', 'Mar-Jue-Sáb 14:00-16:00',
 0, 45, 45, 'not_started'),

-- 57. DATTR4
('DATTR4', 'Data Trainee', 'DATTR', 'INCAF 3.1', 'German Granados',
 '2026-07-06', '2026-10-09', 'Lun-Mié-Vie 18:30-20:30',
 18, 27, 45, 'not_started'),

-- 58. AIDJR2
('AIDJR2', 'AI For Devs Jr.', 'AIDJR', 'INCAF 3.1', 'Ronald Mejía',
 '2026-07-06', '2026-08-25', 'Mié-Vie 18:30-20:30',
 0, 45, 45, 'not_started'),

-- 59. WDFRT1
('WDFRT1', 'Frontend Trainee', 'WDFRT', 'INCAF 3.1', 'Jairo Vega',
 '2026-07-07', '2026-10-09', 'Jue-Vie-Sáb 18:00-20:00',
 23, 23, 46, 'not_started'),

-- 60. AIGSK11
('AIGSK11', 'Gen AI Agents', 'AIGSK', 'INCAF 3.2', 'Carlos Vargas',
 '2026-07-07', '2026-08-07', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 61. AIGSK12
('AIGSK12', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 1)',
 '2026-07-13', '2026-08-12', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 62. ASD1
('ASD1', 'App & Services Development', 'ASD', 'MINEDUCYT', 'Kenia Paiz',
 '2026-07-13', '2026-09-27', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 63. ASD2
('ASD2', 'App & Services Development', 'ASD', 'MINEDUCYT', 'Fernando Aguilar',
 '2026-07-13', '2026-09-27', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 64. ASD3
('ASD3', 'App & Services Development', 'ASD', 'MINEDUCYT', 'Vic Flores',
 '2026-07-13', '2026-09-27', 'Lun-Mié-Vie 18:00-20:00',
 65, 0, 65, 'not_started'),

-- 65. AIDSR1
('AIDSR1', 'AI For Devs Sr.', 'AIDSR', 'KODIGO', 'Hugo Barrientos',
 '2026-07-20', '2026-10-20', 'Mar-Jue 18:30-20:30',
 7, 6, 13, 'not_started'),

-- 66. NETDV1
('NETDV1', '.NET', 'NETDV', 'KODIGO', '(nuevo coach 9)',
 '2026-07-20', '2026-11-20', 'Mar-Jue-Sáb 18:00-20:00',
 8, 6, 14, 'not_started'),

-- 67. AIGSK13
('AIGSK13', 'AI For Professional', 'AIGSK', 'INCAF 3.1', 'Gino Miles',
 '2026-07-20', '2026-08-20', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- ── Agosto 2026 ─────────────────────────────────────────────

-- 68. AISCA2
('AISCA2', 'AI Agents & Optimization', 'AISCA', 'INCAF 3.1', 'Sergio Hernández',
 '2026-08-02', '2026-09-04', 'Mar-Jue 15:00-17:00',
 27, 18, 45, 'not_started'),

-- 69. TECHF5
('TECHF5', 'Tech Fundamentals', 'TECHF', 'KODIGO', '(nuevo coach 5)',
 '2026-08-05', '2026-09-05', 'Jue-Vie 18:30-20:30',
 10, 0, 10, 'not_started'),

-- 70. AIESS8
('AIESS8', 'Gen AI Agents', 'AIESS', 'INCAF 3.2', 'Carlos Vargas',
 '2026-08-08', '2026-10-06', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 71. AIESS9
('AIESS9', 'AI Agents & Optimization', 'AIESS', 'INCAF 3.2', '(nuevo coach 4)',
 '2026-08-10', '2026-10-10', 'Mar-Jue 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 72. WDFRT2
('WDFRT2', 'Frontend Trainee', 'WDFRT', 'INCAF 3.2', 'Eduardo Calles',
 '2026-08-10', '2026-11-06', 'Mar-Jue-Sáb 18:30-20:30',
 23, 23, 46, 'not_started'),

-- 73. AIGSK14
('AIGSK14', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 1)',
 '2026-08-10', '2026-09-10', 'Lun-Mié 18:30-20:30',
 14, 4, 18, 'not_started'),

-- 74. AIGSK15
('AIGSK15', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-08-10', '2026-09-09', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 75. TECHF6
('TECHF6', 'Tech Fundamentals', 'TECHF', 'KODIGO', '(nuevo coach 6)',
 '2026-08-10', '2026-09-22', 'Jue-Vie 18:30-20:30',
 16, 0, 16, 'not_started'),

-- 76. DAJ26
('DAJ26', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.2', '(nuevo coach 9)',
 '2026-08-10', '2026-12-04', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 77. AIDJR3
('AIDJR3', 'AI For Devs Jr.', 'AIDJR', 'INCAF 3.1', 'Fernando Aguilar',
 '2026-08-11', '2026-09-23', 'Mar-Jue 18:30-20:30',
 0, 45, 45, 'not_started'),

-- 78. AIESS10
('AIESS10', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 2)',
 '2026-08-17', '2026-10-18', 'Mar-Jue 18:30-20:30',
 15, 0, 15, 'not_started'),

-- 79. AISCA3
('AISCA3', 'AI Scaling & Optimization', 'AISCA', 'KODIGO', '(nuevo coach 7)',
 '2026-08-17', '2026-09-14', 'Lun-Mié 18:30-20:30',
 11, 0, 11, 'not_started'),

-- 80. PY7
('PY7', 'Python para análisis de datos', 'PY', 'INCAF 3.1', 'Andrés Torres',
 '2026-08-17', '2026-12-11', 'Lun-Mié-Vie 18:00-20:00',
 12, 0, 12, 'not_started'),

-- 81. DAJ27
('DAJ27', 'BI para Análisis de Datos', 'BIANL', 'INCAF 3.1', '(nuevo coach 9)',
 '2026-08-17', '2026-12-15', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 82. AIESS11
('AIESS11', 'AI Agents & Optimization', 'AIESS', 'INCAF 3.1', 'Fabricio Quintanilla',
 '2026-08-18', '2026-10-18', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 83. SQL8
('SQL8', 'SQL', 'SQL', 'INCAF 3.1', '(nuevo coach 9)',
 '2026-08-18', '2026-12-15', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 84. AIGSK16
('AIGSK16', 'Gen AI Agents', 'AIGSK', 'INCAF 3.1', '(nuevo coach 9)',
 '2026-08-18', '2026-09-18', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 85. AIESS12
('AIESS12', 'AI For Professional', 'AIESS', 'INCAF 3.1', 'Gino Miles',
 '2026-08-21', '2026-10-20', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- ── Septiembre 2026 ─────────────────────────────────────────

-- 86. WDFRT3
('WDFRT3', 'Frontend Trainee', 'WDFRT', 'INCAF 3.1', 'Kenia Paiz',
 '2026-09-01', '2026-11-27', 'Lun-Mié-Vie 15:00-17:00',
 23, 23, 46, 'not_started'),

-- 87. DATTR5
('DATTR5', 'Data Trainee', 'DATTR', 'INCAF 3.2', '(nuevo coach 9)',
 '2026-09-01', '2026-11-27', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 88. AIGSK17
('AIGSK17', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 1)',
 '2026-09-14', '2026-10-10', 'Mié-Vie 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 89. AIESS13
('AIESS13', 'Gen AI Agents', 'AIESS', 'INCAF 3.1', '(nuevo coach 9)',
 '2026-09-18', '2026-11-13', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 90. AILMT-K2
('AILMT-K2', 'AI Machine Learning', 'AIMLT', 'KEY INSTITUTE', 'Joel Orellana',
 '2026-09-24', '2026-12-18', 'Jue 16:00-18:00',
 0, 0, 0, 'not_started'),

-- 91. FSJ39
('FSJ39', 'Full Stack Jr.', 'FSJ', 'KODIGO', 'Fernando Aguilar',
 '2026-09-28', '2027-03-23', 'Lun-Mié-Vie 18:00-20:00',
 7, 9, 16, 'not_started'),

-- ── Octubre 2026 ────────────────────────────────────────────

-- 92. AIMLF3
('AIMLF3', 'AI Machine Learning Foundation', 'AIMLF', 'INCAF 3.2', '(nuevo coach 9)',
 '2026-10-05', '2027-01-22', 'Por definir 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 93. AIGSK18
('AIGSK18', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Astrid Navarrete',
 '2026-10-09', '2026-11-09', 'Mar-Jue 18:30-20:30',
 14, 4, 18, 'not_started'),

-- 94. AISCA4
('AISCA4', 'AI Agents & Optimization', 'AISCA', 'INCAF 3.2', '(nuevo coach 4)',
 '2026-10-11', '2026-11-03', 'Mar-Jue 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 95. AIESS14
('AIESS14', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 3)',
 '2026-10-12', '2026-12-12', 'Mar-Jue 18:30-20:30',
 6, 2, 8, 'not_started'),

-- 96. AISCA5
('AISCA5', 'AI Scaling & Optimization', 'AISCA', 'KODIGO', '(nuevo coach 7)',
 '2026-10-12', '2026-11-12', 'Mar-Jue 18:30-20:30',
 6, 0, 6, 'not_started'),

-- 97. TECHF7
('TECHF7', 'Tech Fundamentals', 'TECHF', 'KODIGO', 'Eduardo Calles',
 '2026-10-12', '2026-11-24', 'Mar-Jue 16:00-18:00',
 16, 0, 16, 'not_started'),

-- 98. AIGSK19
('AIGSK19', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 1)',
 '2026-10-12', '2026-11-16', 'Mié-Vie 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 99. AISCA6
('AISCA6', 'AI Agents & Optimization', 'AISCA', 'INCAF 3.1', 'Fabricio Quintanilla',
 '2026-10-19', '2026-11-13', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- 100. AIESS15
('AIESS15', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 2)',
 '2026-10-19', '2026-12-20', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 101. AISCA7
('AISCA7', 'AI For Professional', 'AISCA', 'INCAF 3.1', 'Gino Miles',
 '2026-10-21', '2026-11-20', 'Lun-Mié 18:30-20:30',
 27, 18, 45, 'not_started'),

-- ── Noviembre 2026 ──────────────────────────────────────────

-- 102. AIDAT3
('AIDAT3', 'AI For Data', 'AIDAT', 'KODIGO', 'Andrés Hércules',
 '2026-11-09', '2027-01-09', 'Jue-Vie 18:30-20:30',
 6, 6, 12, 'not_started'),

-- 103. AIGSK20
('AIGSK20', 'Gen AI Skills', 'AIGSK', 'KODIGO', '(nuevo coach 3)',
 '2026-11-09', '2026-12-14', 'Mié-Vie 18:30-20:30',
 14, 4, 18, 'not_started'),

-- 104. AISCA8
('AISCA8', 'AI Scaling & Optimization', 'AISCA', 'KODIGO', '(nuevo coach 7)',
 '2026-11-17', '2026-12-15', 'Mar-Jue 18:30-20:30',
 11, 0, 11, 'not_started'),

-- 105. AIDAT4
('AIDAT4', 'AI For Data', 'AIDAT', 'KODIGO', 'Numas Salazar',
 '2026-11-17', '2027-01-16', 'Lun-Mié-Vie 18:30-20:30',
 13, 0, 13, 'not_started'),

-- ── Diciembre 2026 ──────────────────────────────────────────

-- 106. TECHF8
('TECHF8', 'Tech Fundamentals', 'TECHF', 'KODIGO', 'Eduardo Calles',
 '2026-12-07', '2027-01-19', 'Mar-Jue 18:30-20:30',
 10, 0, 10, 'not_started'),

-- 107. TECHF9
('TECHF9', 'Tech Fundamentals', 'TECHF', 'KODIGO', 'Eduardo Calles',
 '2026-12-07', '2027-01-19', 'Mar-Jue 18:30-20:30',
 16, 0, 16, 'not_started'),

-- 108. AIGSK21
('AIGSK21', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Carlos Vargas',
 '2026-12-09', '2027-01-13', 'Mar-Jue 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 109. AIESS16
('AIESS16', 'AI Agents Essentials', 'AIESS', 'KODIGO', '(nuevo coach 2)',
 '2026-12-11', '2027-02-11', 'Lun-Mié 18:30-20:30',
 11, 0, 11, 'not_started'),

-- 110. AIGSK22
('AIGSK22', 'Gen AI Skills', 'AIGSK', 'KODIGO', 'Oscar Lemus',
 '2026-12-11', '2027-01-15', 'Lun-Mié 18:30-20:30',
 12, 0, 12, 'not_started'),

-- 111. PY8
('PY8', 'Python para análisis de datos', 'PY', 'KODIGO', '(nuevo coach 1)',
 '2026-12-30', '2026-12-31', 'Lun-Mié-Vie 18:00-20:00',
 5, 4, 9, 'not_started'),

-- 112. SQL9
('SQL9', 'SQL', 'SQL', 'KODIGO', '(nuevo coach 1)',
 '2026-12-30', '2026-12-31', 'Lun-Mar-Mié 19:00-21:00',
 0, 0, 0, 'not_started');
