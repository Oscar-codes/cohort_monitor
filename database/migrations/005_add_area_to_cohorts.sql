-- ============================================================
--  Migration 005 — Add 'area' column to cohorts table
-- ============================================================
--  Required by the Reports module for filtering by area.
-- ============================================================

ALTER TABLE cohorts
    ADD COLUMN area ENUM('academic', 'marketing', 'admissions')
        NULL DEFAULT NULL
        AFTER bootcamp_type;

-- Index for report filtering
CREATE INDEX idx_cohorts_area ON cohorts (area);

-- Seed existing cohorts with area values
UPDATE cohorts SET area = 'academic'   WHERE cohort_code = 'COH-2026-001';
UPDATE cohorts SET area = 'academic'   WHERE cohort_code = 'COH-2025-002';
UPDATE cohorts SET area = 'marketing'  WHERE cohort_code = 'COH-2025-003';
