-- =========================================================================
-- Migración 017: Agregar rol 'finance' al ENUM de la tabla users
-- =========================================================================
-- Esta migración agrega el valor 'finance' al ENUM de la columna role,
-- permitiendo crear usuarios con rol de finanzas.
-- =========================================================================

ALTER TABLE users
MODIFY COLUMN role ENUM('admin','admissions_b2b','admissions_b2c','finance','marketing') NOT NULL DEFAULT 'admin';
