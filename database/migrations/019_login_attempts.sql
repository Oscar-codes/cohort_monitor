-- Migration: 019_login_attempts.sql
-- Purpose: CM-SEC-003 rate-limiting & lockout tracking for login attempts.
--
-- A dedicated login_attempts table beats reusing audit_log for this:
--   1. Privacy — login attempts data should not show up in the audit_log
--      view that admins use to investigate operations.
--   2. Index shape — we scan by (identifier_hash, created_at) and
--      (ip_address, created_at); audit_log indexes don't fit.
--   3. Identifier is hashed before storage so a DB dump can't reveal
--      which usernames or emails were probed.
--
-- Apply with:
--   psql ... -f 019_login_attempts.sql   (PostgreSQL)
--   mysql ... < 019_login_attempts.sql   (MySQL: compatible as written)

CREATE TABLE IF NOT EXISTS login_attempts (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier_hash CHAR(64) NOT NULL,
  ip_address      VARCHAR(45) NULL,
  user_agent      VARCHAR(255) NULL,
  success         TINYINT(1) NOT NULL DEFAULT 0,
  reason          VARCHAR(32) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_identifier (identifier_hash, created_at),
  INDEX idx_login_attempts_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8_unicode_ci;
