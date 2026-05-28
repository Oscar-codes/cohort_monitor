-- ============================================================
-- Migration 010 — Finance role + operational users seed
-- ============================================================
USE cohort_monitor;

-- Expand enum to include finance role
ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','admissions_b2b','admissions_b2c','finance','marketing')
  NOT NULL DEFAULT 'marketing';

-- Password hash for: admin123
SET @default_password_hash = '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS';

-- 1) Finance user
INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
SELECT 'finance_ops', 'finance.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Finanzas', 'finance', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'finance_ops' OR email = 'finance.ops@cohortmonitor.com'
);

-- 2) Admissions B2B user
INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
SELECT 'admissions_b2b_ops', 'admissions.b2b.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Admisiones B2B', 'admissions_b2b', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admissions_b2b_ops' OR email = 'admissions.b2b.ops@cohortmonitor.com'
);

-- 3) Admissions B2C user
INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
SELECT 'admissions_b2c_ops', 'admissions.b2c.ops@cohortmonitor.com', @default_password_hash, 'Operaciones Admisiones B2C', 'admissions_b2c', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admissions_b2c_ops' OR email = 'admissions.b2c.ops@cohortmonitor.com'
);
