-- ============================================================
-- Cohort Monitor — Nuevo esquema normalizado para Cohort Plan
-- MySQL 8.0+ / InnoDB / utf8mb4
-- ============================================================
-- La tabla `cohorts` utiliza una CLAVE PRIMARIA COMPUESTA para identificar
-- cohortes de forma funcional por:
--   1) bootcamp_family_id  = familia del bootcamp
--   2) cohort_type_code    = tipo de cohort: B2B o B2C
--   3) cohort_year         = año de inicio
--   4) cohort_month        = mes de inicio
-- Esto reemplaza el uso del campo histórico `cohort_code` como identificador
-- principal. El código del Excel (por ejemplo AIGSK4) queda normalizado como
-- `cohort_sections.section_code`, porque puede haber varias secciones/grupos
-- dentro de la misma cohorte funcional familia-tipo-mes-año.
-- ============================================================

CREATE DATABASE IF NOT EXISTS cohort_monitor
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE cohort_monitor;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS cohort_comments;
DROP TABLE IF EXISTS marketing_stage_history;
DROP TABLE IF EXISTS marketing_stages;
DROP TABLE IF EXISTS marketing_campaigns;
DROP TABLE IF EXISTS cohort_section_memberships;
DROP TABLE IF EXISTS cohort_section_class_days;
DROP TABLE IF EXISTS cohort_sections;
DROP TABLE IF EXISTS cohorts;
DROP TABLE IF EXISTS bootcamp_certifications;
DROP TABLE IF EXISTS certifications;
DROP TABLE IF EXISTS certification_suppliers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS bootcamps;
DROP TABLE IF EXISTS bootcamp_families;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS bundles;
DROP TABLE IF EXISTS mentors;
DROP TABLE IF EXISTS coaches;
DROP TABLE IF EXISTS weekdays;
DROP TABLE IF EXISTS cohort_types;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE cohort_types (
  code CHAR(3) PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE weekdays (
  id TINYINT UNSIGNED PRIMARY KEY,
  day_name_es VARCHAR(20) NOT NULL UNIQUE,
  day_code CHAR(1) NOT NULL UNIQUE,
  iso_day_number TINYINT UNSIGNED NOT NULL UNIQUE,
  CHECK (iso_day_number BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE routes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  route_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bootcamp_families (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  route_id INT UNSIGNED NULL,
  family_name VARCHAR(150) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_family_route FOREIGN KEY (route_id) REFERENCES routes(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bootcamps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  bootcamp_code VARCHAR(30) NOT NULL UNIQUE,
  bootcamp_name VARCHAR(150) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_bootcamp_family FOREIGN KEY (family_id) REFERENCES bootcamp_families(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_bootcamps_family (family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_name VARCHAR(150) NOT NULL UNIQUE,
  client_status ENUM('open','closed','unknown') NOT NULL DEFAULT 'unknown'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bundles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bundle_code VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coaches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coach_name VARCHAR(150) NOT NULL UNIQUE,
  is_placeholder BOOLEAN NOT NULL DEFAULT FALSE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mentors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mentor_name VARCHAR(150) NOT NULL UNIQUE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certification_suppliers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NULL,
  certification_name VARCHAR(255) NOT NULL UNIQUE,
  source_link VARCHAR(500) NULL,
  unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_cert_supplier FOREIGN KEY (supplier_id) REFERENCES certification_suppliers(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bootcamp_certifications (
  bootcamp_id INT UNSIGNED NOT NULL,
  certification_id INT UNSIGNED NOT NULL,
  grad_ratio DECIMAL(5,4) NULL,
  top_students_pct DECIMAL(5,4) NULL,
  admitted_reference INT UNSIGNED NULL,
  PRIMARY KEY (bootcamp_id, certification_id),
  CONSTRAINT fk_bc_bootcamp FOREIGN KEY (bootcamp_id) REFERENCES bootcamps(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_bc_cert FOREIGN KEY (certification_id) REFERENCES certifications(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) NULL,
  role ENUM('admin','admissions_b2b','admissions_b2c','finance','marketing') NOT NULL DEFAULT 'marketing',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohorts (
  bootcamp_family_id INT UNSIGNED NOT NULL,
  cohort_type_code CHAR(3) NOT NULL,
  cohort_year SMALLINT UNSIGNED NOT NULL,
  cohort_month TINYINT UNSIGNED NOT NULL,
  cohort_key VARCHAR(180) NOT NULL UNIQUE,
  status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month),
  CONSTRAINT fk_cohort_family FOREIGN KEY (bootcamp_family_id) REFERENCES bootcamp_families(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cohort_type FOREIGN KEY (cohort_type_code) REFERENCES cohort_types(code)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CHECK (cohort_month BETWEEN 1 AND 12),
  INDEX idx_cohorts_period (cohort_year, cohort_month),
  INDEX idx_cohorts_type_period (cohort_type_code, cohort_year, cohort_month),
  INDEX idx_cohorts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohort_sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_row_number INT UNSIGNED NULL,
  section_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código histórico del Excel; ya no identifica de forma única la cohorte funcional.',
  bootcamp_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NULL,
  client_id INT UNSIGNED NULL,
  bundle_id INT UNSIGNED NULL,
  mentor_id INT UNSIGNED NULL,
  coach_id INT UNSIGNED NULL,
  certification_required BOOLEAN NOT NULL DEFAULT FALSE,
  assignment_status ENUM('assigned','pending') NOT NULL DEFAULT 'pending',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  training_date_50 DATE NULL,
  training_date_75 DATE NULL,
  clash_status VARCHAR(30) NULL,
  calendar_pattern VARCHAR(20) NULL,
  start_weekday_label VARCHAR(20) NULL,
  end_weekday_label VARCHAR(20) NULL,
  b2b_target INT UNSIGNED NOT NULL DEFAULT 0,
  b2c_target INT UNSIGNED NOT NULL DEFAULT 0,
  total_students_target INT UNSIGNED NOT NULL DEFAULT 0,
  class_count INT UNSIGNED NOT NULL DEFAULT 0,
  calendar_week_count INT UNSIGNED NOT NULL DEFAULT 0,
  calendar_month_count INT UNSIGNED NOT NULL DEFAULT 0,
  kickoff_day_label VARCHAR(20) NULL,
  start_month_label VARCHAR(20) NULL,
  end_month_label VARCHAR(20) NULL,
  start_year SMALLINT UNSIGNED NULL,
  training_status ENUM('not_started','in_progress','completed','cancelled') NOT NULL DEFAULT 'not_started',
  status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_section_bootcamp FOREIGN KEY (bootcamp_id) REFERENCES bootcamps(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_section_project FOREIGN KEY (project_id) REFERENCES projects(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_section_client FOREIGN KEY (client_id) REFERENCES clients(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_section_bundle FOREIGN KEY (bundle_id) REFERENCES bundles(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_section_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_section_coach FOREIGN KEY (coach_id) REFERENCES coaches(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CHECK (end_date >= start_date),
  INDEX idx_sections_dates (start_date, end_date),
  INDEX idx_sections_status (training_status, status),
  INDEX idx_sections_bootcamp (bootcamp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohort_section_memberships (
  section_id INT UNSIGNED NOT NULL,
  bootcamp_family_id INT UNSIGNED NOT NULL,
  cohort_type_code CHAR(3) NOT NULL,
  cohort_year SMALLINT UNSIGNED NOT NULL,
  cohort_month TINYINT UNSIGNED NOT NULL,
  target_students INT UNSIGNED NOT NULL DEFAULT 0,
  actual_students INT UNSIGNED NOT NULL DEFAULT 0,
  target_revenue DECIMAL(14,2) NULL,
  actual_revenue DECIMAL(14,2) NULL,
  PRIMARY KEY (section_id, bootcamp_family_id, cohort_type_code, cohort_year, cohort_month),
  CONSTRAINT fk_membership_section FOREIGN KEY (section_id) REFERENCES cohort_sections(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_membership_cohort FOREIGN KEY (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    REFERENCES cohorts(bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_membership_cohort (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month),
  INDEX idx_membership_type_period (cohort_type_code, cohort_year, cohort_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohort_section_class_days (
  section_id INT UNSIGNED NOT NULL,
  day_position TINYINT UNSIGNED NOT NULL,
  weekday_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (section_id, day_position),
  UNIQUE KEY uq_section_weekday (section_id, weekday_id),
  CONSTRAINT fk_class_day_section FOREIGN KEY (section_id) REFERENCES cohort_sections(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_class_day_weekday FOREIGN KEY (weekday_id) REFERENCES weekdays(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CHECK (day_position BETWEEN 1 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marketing_campaigns (
  campaign_id VARCHAR(100) PRIMARY KEY,
  bootcamp_family_id INT UNSIGNED NOT NULL,
  cohort_type_code CHAR(3) NOT NULL,
  cohort_year SMALLINT UNSIGNED NOT NULL,
  cohort_month TINYINT UNSIGNED NOT NULL,
  campaign_name VARCHAR(255) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_campaign_cohort FOREIGN KEY (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    REFERENCES cohorts(bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_campaign_cohort (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marketing_stages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bootcamp_family_id INT UNSIGNED NOT NULL,
  cohort_type_code CHAR(3) NOT NULL,
  cohort_year SMALLINT UNSIGNED NOT NULL,
  cohort_month TINYINT UNSIGNED NOT NULL,
  stage_code ENUM('strategy','content','ads','organic','events','partnerships','analytics') NOT NULL,
  status ENUM('pending','completed','at_risk') NOT NULL DEFAULT 'pending',
  risk_notes TEXT NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_marketing_stage (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month, stage_code),
  CONSTRAINT fk_stage_cohort FOREIGN KEY (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    REFERENCES cohorts(bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_stage_user FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_marketing_stage_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marketing_stage_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marketing_stage_id INT UNSIGNED NOT NULL,
  old_status ENUM('pending','completed','at_risk') NULL,
  new_status ENUM('pending','completed','at_risk') NOT NULL,
  risk_notes TEXT NULL,
  changed_by INT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stage_history_stage FOREIGN KEY (marketing_stage_id) REFERENCES marketing_stages(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_stage_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohort_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bootcamp_family_id INT UNSIGNED NOT NULL,
  cohort_type_code CHAR(3) NOT NULL,
  cohort_year SMALLINT UNSIGNED NOT NULL,
  cohort_month TINYINT UNSIGNED NOT NULL,
  section_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  category ENUM('general','risk','admission','marketing') NOT NULL DEFAULT 'general',
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comment_cohort FOREIGN KEY (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    REFERENCES cohorts(bootcamp_family_id, cohort_type_code, cohort_year, cohort_month)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_comment_section FOREIGN KEY (section_id) REFERENCES cohort_sections(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_comment_cohort (bootcamp_family_id, cohort_type_code, cohort_year, cohort_month),
  INDEX idx_comment_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id INT UNSIGNED NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NULL UNIQUE,
  student_type CHAR(3) NULL,
  status ENUM('active','inactive','graduated','dropped') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_section FOREIGN KEY (section_id) REFERENCES cohort_sections(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_student_type FOREIGN KEY (student_type) REFERENCES cohort_types(code)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_students_section (section_id),
  INDEX idx_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id VARCHAR(128) PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  payload TEXT NULL,
  last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_sessions_user (user_id),
  INDEX idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_key VARCHAR(255) NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_entity (entity_type, entity_key),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
