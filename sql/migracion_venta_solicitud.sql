-- ============================================================================
-- migracion_venta_solicitud.sql  ·  Enlaza una solicitud con su venta registrada
-- IMPORTANTE: primero SELECCIONA tu base en phpMyAdmin y luego importa.
-- ============================================================================

ALTER TABLE solicitudes
    ADD COLUMN venta_id INT NULL AFTER total_estimado;
