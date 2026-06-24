-- ============================================================
-- Migration 015 - Marketing campaign status Active/Completed
-- ============================================================
USE cohort_monitor;

ALTER TABLE marketing_stages
  MODIFY status ENUM('active','completed','pending','at_risk') NOT NULL DEFAULT 'active';

UPDATE marketing_stages
SET status = 'active'
WHERE status IN ('pending', 'at_risk');

ALTER TABLE marketing_stage_history
  MODIFY old_status ENUM('active','completed','pending','at_risk') NULL,
  MODIFY new_status ENUM('active','completed','pending','at_risk') NOT NULL;
