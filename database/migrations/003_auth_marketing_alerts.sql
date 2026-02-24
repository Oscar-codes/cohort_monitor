-- ============================================================
-- Migration 003 — Authentication, Marketing Workflow, Alerts
-- ============================================================
-- WARNING: Run this AFTER migration 002 (refactored cohorts).
-- ============================================================

USE cohort_monitor;

-- ─── 1. Users table ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100)    NOT NULL UNIQUE,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    full_name       VARCHAR(255)    NOT NULL,
    role            ENUM('admin','admissions_b2b','admissions_b2c','marketing') NOT NULL DEFAULT 'admin',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_role       (role),
    INDEX idx_users_active     (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. Sessions table (DB-backed sessions) ────────────────
CREATE TABLE IF NOT EXISTS sessions (
    id              VARCHAR(128)    NOT NULL PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    ip_address      VARCHAR(45)     NULL,
    user_agent      VARCHAR(500)    NULL,
    payload         TEXT            NULL,
    last_activity   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sessions_user   (user_id),
    INDEX idx_sessions_activity (last_activity),

    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3. Marketing workflow stages per cohort ────────────────
CREATE TABLE IF NOT EXISTS marketing_stages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_id       INT UNSIGNED    NOT NULL,
    stage_name      ENUM(
                        'workflow_campaign',
                        'campaign_build',
                        'campaign_start',
                        'lead_funnel'
                    ) NOT NULL,
    status          ENUM('completed','pending','at_risk') NOT NULL DEFAULT 'pending',
    risk_notes      TEXT            NULL,
    updated_by      INT UNSIGNED    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_cohort_stage (cohort_id, stage_name),
    INDEX idx_mkt_status       (status),

    CONSTRAINT fk_mkt_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mkt_user
        FOREIGN KEY (updated_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 4. Cohort comments / risk flags ───────────────────────
CREATE TABLE IF NOT EXISTS cohort_comments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_id       INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NOT NULL,
    category        ENUM('risk','general','admission','marketing') NOT NULL DEFAULT 'general',
    body            TEXT            NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_comments_cohort  (cohort_id),
    INDEX idx_comments_cat     (category),

    CONSTRAINT fk_comments_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohorts (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 5. Audit log — who changed what, when ─────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NULL,
    action          VARCHAR(100)    NOT NULL,
    entity_type     VARCHAR(50)     NOT NULL,
    entity_id       INT UNSIGNED    NULL,
    old_values      JSON            NULL,
    new_values      JSON            NULL,
    ip_address      VARCHAR(45)     NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_user       (user_id),
    INDEX idx_audit_entity     (entity_type, entity_id),
    INDEX idx_audit_created    (created_at),

    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 6. Seed default admin user ────────────────────────────
-- Password: admin123  (bcrypt hash)
INSERT INTO users (username, email, password_hash, full_name, role)
VALUES (
    'admin',
    'admin@cohortmonitor.com',
    '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS',
    'Super Administrador',
    'admin'
);

-- Seed demo users (one per role)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admissions_b2b', 'b2b@cohortmonitor.com',
 '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS',
 'Analista Admisiones B2B', 'admissions_b2b'),

('admissions_b2c', 'b2c@cohortmonitor.com',
 '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS',
 'Analista Admisiones B2C', 'admissions_b2c'),

('marketing', 'marketing@cohortmonitor.com',
 '$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS',
 'Coordinador Marketing', 'marketing');

-- Seed default marketing stages for existing cohorts
INSERT INTO marketing_stages (cohort_id, stage_name, status) VALUES
(1, 'workflow_campaign', 'completed'),
(1, 'campaign_build',    'completed'),
(1, 'campaign_start',    'pending'),
(1, 'lead_funnel',       'pending'),
(2, 'workflow_campaign', 'completed'),
(2, 'campaign_build',    'at_risk'),
(2, 'campaign_start',    'pending'),
(2, 'lead_funnel',       'pending'),
(3, 'workflow_campaign', 'completed'),
(3, 'campaign_build',    'completed'),
(3, 'campaign_start',    'completed'),
(3, 'lead_funnel',       'completed');

-- Seed a sample risk comment
INSERT INTO cohort_comments (cohort_id, user_id, category, body) VALUES
(2, 1, 'risk', 'Campaña de marketing presenta retraso. La construcción del workflow está en riesgo por falta de contenido aprobado.');
