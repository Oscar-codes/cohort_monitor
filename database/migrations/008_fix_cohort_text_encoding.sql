-- ============================================================
--  Migration 008 - Fix cohort text encoding artifacts
-- ============================================================
--  Repairs common corrupted strings such as:
--  - an??lisis / An??lisis
--  - Andr??s / H??rcules
--  - Mi?? / S??b
--  - mojibake forms (anÃ¡lisis, AndrÃ©s, MiÃ©, etc.)
-- ============================================================

USE cohort_monitor;

-- Fix cohort names
UPDATE cohorts SET name = REPLACE(name, 'an??lisis', 'análisis') WHERE name LIKE '%an??lisis%';
UPDATE cohorts SET name = REPLACE(name, 'An??lisis', 'Análisis') WHERE name LIKE '%An??lisis%';
UPDATE cohorts SET name = REPLACE(name, 'anÃ¡lisis', 'análisis') WHERE name LIKE '%anÃ¡lisis%';
UPDATE cohorts SET name = REPLACE(name, 'AnÃ¡lisis', 'Análisis') WHERE name LIKE '%AnÃ¡lisis%';

-- Fix coach names
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'Andr??s', 'Andrés') WHERE assigned_coach LIKE '%Andr??s%';
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'H??rcules', 'Hércules') WHERE assigned_coach LIKE '%H??rcules%';
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'Hern??ndez', 'Hernández') WHERE assigned_coach LIKE '%Hern??ndez%';
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'AndrÃ©s', 'Andrés') WHERE assigned_coach LIKE '%AndrÃ©s%';
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'HÃ©rcules', 'Hércules') WHERE assigned_coach LIKE '%HÃ©rcules%';
UPDATE cohorts SET assigned_coach = REPLACE(assigned_coach, 'HernÃ¡ndez', 'Hernández') WHERE assigned_coach LIKE '%HernÃ¡ndez%';

-- Fix class schedule strings
UPDATE cohorts SET assigned_class_schedule = REPLACE(assigned_class_schedule, 'Mi??', 'Mié') WHERE assigned_class_schedule LIKE '%Mi??%';
UPDATE cohorts SET assigned_class_schedule = REPLACE(assigned_class_schedule, 'S??b', 'Sáb') WHERE assigned_class_schedule LIKE '%S??b%';
UPDATE cohorts SET assigned_class_schedule = REPLACE(assigned_class_schedule, 'MiÃ©', 'Mié') WHERE assigned_class_schedule LIKE '%MiÃ©%';
UPDATE cohorts SET assigned_class_schedule = REPLACE(assigned_class_schedule, 'SÃ¡b', 'Sáb') WHERE assigned_class_schedule LIKE '%SÃ¡b%';
