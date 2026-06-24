-- ============================================================
-- Migration 016 - Add marketing info table for cohort marketing section
-- ============================================================
USE cohort_monitor;

-- Crear tabla para almacenar información de marketing por cohorte
CREATE TABLE IF NOT EXISTS cohort_marketing_info (
    cohort_id                   INT UNSIGNED    NOT NULL,
    campaign_status             ENUM('Completed', 'Active') NOT NULL DEFAULT 'Active',
    strategy_notes              TEXT            NULL,
    content_notes               TEXT            NULL,
    ads_notes                   TEXT            NULL,
    organic_notes               TEXT            NULL,
    events_notes                TEXT            NULL,
    partnerships_notes          TEXT            NULL,
    analytics_notes             TEXT            NULL,
    created_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (cohort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;EXT            NULL,
    organic_notes               TEXT            NULL,
    events_notes                TEXT            NULL,
    partnerships_notes          TEXT            NULL,
    analytics_notes             TEXT            NULL,
    created_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (cohort_id),

    CONSTRAINT fk_cmi_cohort
        FOREIGN KEY (cohort_id) REFERENCES cohort_sections (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
