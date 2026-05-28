-- ============================================================
-- Migration 012 — Align audit_log schema with app expectations
-- ============================================================
USE cohort_monitor;

-- Ensure entity_key exists for newer code paths.
ALTER TABLE audit_log
  ADD COLUMN IF NOT EXISTS entity_key VARCHAR(255) NULL AFTER entity_type;

-- Backfill entity_key from legacy entity_id where possible.
UPDATE audit_log
SET entity_key = CAST(entity_id AS CHAR)
WHERE entity_key IS NULL AND entity_id IS NOT NULL;

-- Add index for entity lookups on new column.
ALTER TABLE audit_log
  ADD INDEX IF NOT EXISTS idx_audit_entity_key (entity_type, entity_key);
