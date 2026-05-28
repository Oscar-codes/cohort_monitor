-- ============================================================
-- Migration 013 — Admin access rescue
-- ============================================================
USE cohort_monitor;

-- Password hash for: admin123
SET @default_password_hash = '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS';

-- Ensure primary admin exists and is active
INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admin', 'admin@cohortmonitor.com', @default_password_hash, 'Super Administrador', 'admin', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = 'admin',
    is_active = 1,
    updated_at = NOW();

-- Create/refresh a rescue admin user
INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at, updated_at)
VALUES ('admin_rescue', 'admin.rescue@cohortmonitor.com', @default_password_hash, 'Administrador Rescate', 'admin', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = 'admin',
    is_active = 1,
    updated_at = NOW();
