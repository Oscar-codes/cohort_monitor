-- ============================================================
--  Cohort Monitor — Database Schema
-- ============================================================
--  Run this script to initialize the database.
--
--  Usage:
--    mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS cohort_monitor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cohort_monitor;

-- ─── Cohorts Table ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS cohorts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)    NOT NULL,
    description TEXT            NULL,
    start_date  DATE            NULL,
    end_date    DATE            NULL,
    status      ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cohorts_status (status),
    INDEX idx_cohorts_dates  (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Students Table (prepared for future use) ───────────────

CREATE TABLE IF NOT EXISTS students (
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

INSERT INTO cohorts (name, description, start_date, end_date, status) VALUES
('Spring 2026 — Full Stack Web Dev', 'Full-stack web development bootcamp covering HTML, CSS, JS, PHP, and MySQL.', '2026-03-01', '2026-08-31', 'active'),
('Winter 2025 — Data Science',       'Introductory data science cohort with Python and SQL.',                      '2025-11-01', '2026-04-30', 'active'),
('Fall 2025 — UX/UI Design',         'User experience and interface design program.',                              '2025-09-01', '2026-02-28', 'archived');
