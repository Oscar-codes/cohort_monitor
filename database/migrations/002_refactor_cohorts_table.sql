-- ============================================================
--  Migration: Refactor cohorts table with new fields
--  Date: 2026-02-22
-- ============================================================
--  This migration transforms the original cohorts table to
--  support the full set of cohort management fields.
--
--  50% and 75% Training Dates are calculated in the Service
--  layer — they are NOT stored in the database.
--
--  Usage:
--    mysql -u root -p cohort_monitor < database/migrations/002_refactor_cohorts_table.sql
-- ============================================================

USE cohort_monitor;

-- ─── Drop the old cohorts table and recreate ────────────────
-- WARNING: This will lose existing data. Back up first if needed.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS cohorts;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── Recreate cohorts table with new structure ──────────────

CREATE TABLE cohorts (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_code              VARCHAR(50)     NOT NULL UNIQUE,
    name                     VARCHAR(255)    NOT NULL,
    correlative_number       INT UNSIGNED    NOT NULL DEFAULT 0,
    total_admission_target   INT UNSIGNED    NOT NULL DEFAULT 0,
    b2b_admission_target     INT UNSIGNED    NOT NULL DEFAULT 0,
    b2c_admissions           INT UNSIGNED    NOT NULL DEFAULT 0,
    admission_deadline_date  DATE            NULL,
    start_date               DATE            NULL,
    end_date                 DATE            NULL,
    related_project          VARCHAR(255)    NULL,
    assigned_coach           VARCHAR(255)    NULL,
    bootcamp_type            VARCHAR(100)    NULL,
    assigned_class_schedule  VARCHAR(255)    NULL,
    training_status          ENUM('not_started', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'not_started',
    created_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cohorts_code             (cohort_code),
    INDEX idx_cohorts_training_status  (training_status),
    INDEX idx_cohorts_dates            (start_date, end_date),
    INDEX idx_cohorts_bootcamp_type    (bootcamp_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Recreate students table ────────────────────────────────

CREATE TABLE students (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(100)    NOT NULL,
    last_name   VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NULL UNIQUE,
    cohort_id   INT UNSIGNED    NULL,
    status      ENUM('active', 'inactive', 'graduated', 'dropped') NOT NULL DEFAULT 'active',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_students_cohort (cohort_id),
    INDEX idx_students_status (status),

    CONSTRAINT fk_students_cohort
        FOREIGN KEY (cohort_id)
        REFERENCES cohorts (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed sample data ───────────────────────────────────────

INSERT INTO cohorts (
    cohort_code, name, correlative_number,
    total_admission_target, b2b_admission_target, b2c_admissions,
    admission_deadline_date, start_date, end_date,
    related_project, assigned_coach, bootcamp_type,
    assigned_class_schedule, training_status
) VALUES
(
    'COH-2026-001', 'Spring 2026 — Full Stack Web Dev', 1,
    30, 15, 12,
    '2026-02-15', '2026-03-01', '2026-08-31',
    'Tech Talent Pipeline', 'Maria Garcia', 'Full Stack',
    'Mon-Wed-Fri 09:00-13:00', 'in_progress'
),
(
    'COH-2025-002', 'Winter 2025 — Data Science', 2,
    25, 10, 8,
    '2025-10-15', '2025-11-01', '2026-04-30',
    'Data Analytics Initiative', 'Carlos Rodriguez', 'Data Science',
    'Tue-Thu 14:00-18:00', 'in_progress'
),
(
    'COH-2025-003', 'Fall 2025 — UX/UI Design', 3,
    20, 8, 15,
    '2025-08-15', '2025-09-01', '2026-02-28',
    'Digital Skills Program', 'Ana Martinez', 'UX/UI Design',
    'Mon-Wed 18:00-21:00', 'completed'
);
