-- ============================================================
-- Migration 011 — Safe auth users reseed (idempotent)
-- ============================================================
USE cohort_monitor;

-- Password hash for: admin123
SET @default_password_hash = '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS';

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admin', 'admin@cohortmonitor.com', @default_password_hash, 'Super Administrador', 'admin', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admissions_b2b', 'b2b@cohortmonitor.com', @default_password_hash, 'Analista Admisiones B2B', 'admissions_b2b', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admissions_b2c', 'b2c@cohortmonitor.com', @default_password_hash, 'Analista Admisiones B2C', 'admissions_b2c', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('finance', 'finance@cohortmonitor.com', @default_password_hash, 'Analista de Finanzas', 'finance', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('marketing', 'marketing@cohortmonitor.com', @default_password_hash, 'Coordinador Marketing', 'marketing', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('finance_ops', 'finance.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Finanzas', 'finance', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admissions_b2b_ops', 'admissions.b2b.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Admisiones B2B', 'admissions_b2b', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();

INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admissions_b2c_ops', 'admissions.b2c.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Admisiones B2C', 'admissions_b2c', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = 1,
    updated_at = NOW();
