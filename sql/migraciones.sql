-- ============================================================================
-- migraciones.sql  ·  Tienda de Verduras
-- Estos cambios son necesarios para los módulos de entradas de stock y anulación.
-- IMPORTANTE: primero SELECCIONA tu base en el panel izquierdo de phpMyAdmin
-- (en local: tienda_verduras · en la nube: if0_xxxx_tienda_verduras) y luego importa.
-- ============================================================================

-- ── 1. Estado de las ventas (para anulación) ────────────────────────────────
-- Agrega la columna sólo si no existe. En MySQL/MariaDB recientes:
ALTER TABLE ventas
    ADD COLUMN estado ENUM('completada','anulada') NOT NULL DEFAULT 'completada' AFTER total,
    ADD COLUMN anulada_por INT NULL DEFAULT NULL AFTER estado,
    ADD COLUMN anulada_fecha DATETIME NULL DEFAULT NULL AFTER anulada_por;

-- ── 2. Registro de entradas de stock (reposición de mercadería) ─────────────
CREATE TABLE IF NOT EXISTS entradas_stock (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    producto_id   INT NOT NULL,
    usuario_id    INT NULL,
    cantidad      DECIMAL(10,3) NOT NULL,
    costo_unitario DECIMAL(10,2) NULL DEFAULT NULL,
    nota          VARCHAR(255) NULL DEFAULT NULL,
    fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrada_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrada_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. (Opcional) Asegurar que la columna rol exista con valores esperados ──
-- Si tu tabla usuarios no tiene un ENUM de rol, puedes normalizarlo:
-- ALTER TABLE usuarios MODIFY rol ENUM('admin','vendedor') NOT NULL DEFAULT 'vendedor';
