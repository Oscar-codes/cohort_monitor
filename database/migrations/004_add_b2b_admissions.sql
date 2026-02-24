-- ============================================================
--  Migration 004: Add b2b_admissions column
-- ============================================================
--  Adds the b2b_admissions field to track actual B2B admissions
--  (separate from b2b_admission_target which is the goal).
--
--  Run: mysql -u root -p cohort_monitor < database/migrations/004_add_b2b_admissions.sql
-- ============================================================

USE cohort_monitor;

-- Add b2b_admissions column after b2b_admission_target
ALTER TABLE cohorts
    ADD COLUMN b2b_admissions INT UNSIGNED NOT NULL DEFAULT 0 AFTER b2b_admission_target;

-- Update seed data to have sample values
UPDATE cohorts SET b2b_admissions = 8 WHERE cohort_code = 'COH-2026-001';
UPDATE cohorts SET b2b_admissions = 5 WHERE cohort_code = 'COH-2025-002';
UPDATE cohorts SET b2b_admissions = 8 WHERE cohort_code = 'COH-2025-003';
