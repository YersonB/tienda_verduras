-- ============================================================================
-- migracion_seguimiento.sql  ·  Rastreo de delivery
-- Agrega código de seguimiento y el estado "en_camino" a las solicitudes.
-- Ejecutar UNA vez en phpMyAdmin sobre la base del proyecto.
-- IMPORTANTE: primero SELECCIONA tu base en el panel izquierdo de phpMyAdmin
-- (en local: tienda_verduras · en la nube: if0_xxxx_tienda_verduras) y luego importa.
-- ============================================================================

-- 1) Código público de seguimiento
ALTER TABLE solicitudes
    ADD COLUMN codigo VARCHAR(12) NULL AFTER id;

-- 2) Nuevo estado "en_camino" (pedido en reparto)
ALTER TABLE solicitudes
    MODIFY estado ENUM('nueva','en_proceso','comprando','en_camino','entregada','cancelada')
    NOT NULL DEFAULT 'nueva';

-- 3) Generar código para las solicitudes que ya existan
UPDATE solicitudes
   SET codigo = CONCAT('MJ', UPPER(SUBSTRING(MD5(CONCAT(id, RAND())), 1, 6)))
 WHERE codigo IS NULL;

-- 4) Hacer el código único
ALTER TABLE solicitudes
    ADD UNIQUE KEY uq_solicitud_codigo (codigo);
