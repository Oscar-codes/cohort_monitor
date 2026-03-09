-- ============================================================
--  Migration 007 — Optimize cohorts filters and sorting
-- ============================================================
--  Adds indexes used by cohorts list filtering/sorting:
--  - start_date default sorting
--  - end_date range filtering
--  - business model filters (B2B/B2C)
-- ============================================================

USE cohort_monitor;

ALTER TABLE cohorts
    ADD INDEX idx_cohorts_start_date (start_date),
    ADD INDEX idx_cohorts_end_date (end_date),
    ADD INDEX idx_cohorts_b2b_target (b2b_admission_target),
    ADD INDEX idx_cohorts_b2b_admissions (b2b_admissions),
    ADD INDEX idx_cohorts_b2c_admissions (b2c_admissions);
