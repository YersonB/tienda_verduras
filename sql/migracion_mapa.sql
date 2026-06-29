-- ============================================================================
-- migracion_mapa.sql  ·  Seguimiento con mapa en vivo (GPS)
-- Guarda la última ubicación del repartidor para cada solicitud.
-- IMPORTANTE: primero SELECCIONA tu base en phpMyAdmin y luego importa.
-- ============================================================================

ALTER TABLE solicitudes
    ADD COLUMN lat DECIMAL(10,7) NULL AFTER estado,
    ADD COLUMN lng DECIMAL(10,7) NULL AFTER lat,
    ADD COLUMN ubicacion_actualizada DATETIME NULL AFTER lng;
