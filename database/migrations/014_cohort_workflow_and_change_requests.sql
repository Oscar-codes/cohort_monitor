-- ============================================================
-- Migration 014 - Cohort workflow and change request comments
-- ============================================================
USE cohort_monitor;

ALTER TABLE cohort_sections
  MODIFY training_status ENUM('not_started','planned','in_progress','completed','cancelled','pending_reschedule') NOT NULL DEFAULT 'planned';

UPDATE cohort_sections
SET training_status = 'planned'
WHERE training_status = 'not_started';

ALTER TABLE cohort_sections
  MODIFY training_status ENUM('planned','in_progress','completed','cancelled','pending_reschedule') NOT NULL DEFAULT 'planned';

ALTER TABLE cohort_comments
  MODIFY category ENUM('general','risk','change_request','admission','marketing') NOT NULL DEFAULT 'general';

UPDATE cohort_comments
SET category = 'change_request'
WHERE category IN ('admission', 'marketing');

ALTER TABLE cohort_comments
  MODIFY category ENUM('general','risk','change_request') NOT NULL DEFAULT 'general';
