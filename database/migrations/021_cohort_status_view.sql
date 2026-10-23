-- =========================================================================
-- Migración 021: Vista v_cohort_status (DATA-001)
-- =========================================================================
-- Estado de cada cohorte calculado con la misma regla que la app
-- (App\Services\CohortStatus):
--   training_status = 'cancelled'               -> cancelled  (Cancelado)
--   start_date IS NULL                          -> no_dates   (Sin fechas)
--   hoy < start_date                            -> not_started (No iniciado)
--   start_date <= hoy y (sin end_date o hoy <= end_date) -> in_progress (En progreso)
--   hoy > end_date                              -> completed  (Completado)
--
-- "hoy" es la fecha de negocio de El Salvador (UTC-6, sin horario de
-- verano), igual que CohortStatus::TIMEZONE:
--   DATE(DATE_SUB(UTC_TIMESTAMP(), INTERVAL 6 HOUR))
-- No usa CURDATE() ni CONVERT_TZ(), así que no depende de la zona horaria
-- del servidor MySQL ni de las tablas de zonas horarias.
--
-- Solo agrega una vista: no modifica, renombra ni borra columnas de cohorts.
-- cohorts.training_status se conserva (la app lo sigue escribiendo, y
-- 'cancelled' es la única decisión manual). Power BI puede leer esta vista
-- para tener el estado siempre al día en lugar de training_status.
--
-- Reversión: DROP VIEW IF EXISTS v_cohort_status;
-- =========================================================================

CREATE OR REPLACE VIEW v_cohort_status AS
SELECT
    s.cohort_id,
    s.cohort_code,
    s.start_date,
    s.end_date,
    s.stored_status,
    s.effective_status,
    CASE s.effective_status
        WHEN 'not_started' THEN 'No iniciado'
        WHEN 'in_progress' THEN 'En progreso'
        WHEN 'completed'   THEN 'Completado'
        WHEN 'cancelled'   THEN 'Cancelado'
        ELSE 'Sin fechas'
    END AS effective_label,
    s.status_date
FROM (
    SELECT
        c.id              AS cohort_id,
        c.cohort_code,
        c.start_date,
        c.end_date,
        c.training_status AS stored_status,
        CASE
            WHEN c.training_status = 'cancelled' THEN 'cancelled'
            WHEN c.start_date IS NULL THEN 'no_dates'
            WHEN t.today < c.start_date THEN 'not_started'
            WHEN c.end_date IS NULL OR t.today <= c.end_date THEN 'in_progress'
            ELSE 'completed'
        END AS effective_status,
        t.today           AS status_date
    FROM cohorts c
    CROSS JOIN (
        SELECT DATE(DATE_SUB(UTC_TIMESTAMP(), INTERVAL 6 HOUR)) AS today
    ) t
) s;
