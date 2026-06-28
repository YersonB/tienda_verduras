-- ============================================================================
-- migracion_servicio.sql  ·  "Nosotros hacemos tu mercado"
-- Tablas para el servicio de compras a domicilio: canastas y solicitudes.
-- Ejecutar UNA vez en phpMyAdmin sobre la base `tienda_verduras`.
-- ============================================================================

USE tienda_verduras;

-- ── Canastas predefinidas ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS canastas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    contenido   TEXT NULL,                 -- listado de lo que incluye
    precio      DECIMAL(10,2) NOT NULL DEFAULT 0,
    etiqueta    VARCHAR(40) NULL,          -- ej. Familiar, Parrilla, Fitness
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Solicitudes de los clientes ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS solicitudes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120) NOT NULL,
    telefono       VARCHAR(30)  NOT NULL,
    email          VARCHAR(120) NULL,
    direccion      VARCHAR(200) NOT NULL,
    referencia     VARCHAR(200) NULL,
    distrito       VARCHAR(80)  NULL,
    fecha_entrega  DATE NULL,
    frecuencia     ENUM('unica','semanal','quincenal','mensual') NOT NULL DEFAULT 'unica',
    lista_libre    TEXT NULL,
    notas          TEXT NULL,
    total_estimado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado         ENUM('nueva','en_proceso','comprando','entregada','cancelada') NOT NULL DEFAULT 'nueva',
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Canastas elegidas en cada solicitud (snapshot de precio/nombre) ─────────
CREATE TABLE IF NOT EXISTS solicitud_canastas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id    INT NOT NULL,
    canasta_id      INT NULL,
    nombre_canasta  VARCHAR(120) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad        INT NOT NULL DEFAULT 1,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_sc_solicitud FOREIGN KEY (solicitud_id)
        REFERENCES solicitudes(id) ON DELETE CASCADE,
    CONSTRAINT fk_sc_canasta FOREIGN KEY (canasta_id)
        REFERENCES canastas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Canastas de ejemplo ─────────────────────────────────────────────────────
INSERT INTO canastas (nombre, descripcion, contenido, precio, etiqueta) VALUES
('Canasta Semanal Familiar',
 'Lo esencial de la semana para una familia de 4.',
 'Frutas de estación, verduras variadas, 2 kg de pollo, huevos, arroz, aceite, fideos y menestras.',
 120.00, 'Familiar'),
('Canasta Parrilla del Finde',
 'Todo listo para tu parrilla del fin de semana.',
 'Cortes de res y cerdo, chorizo, carbón, papas, ensalada fresca, pan y bebidas.',
 150.00, 'Parrilla'),
('Canasta Frutas & Verduras',
 'Frescura directa del mercado, solo frutas y verduras.',
 'Selección semanal de frutas y verduras de estación (8-10 variedades).',
 60.00, 'Saludable'),
('Canasta Abarrotes Básica',
 'Despensa surtida para el mes.',
 'Arroz, azúcar, aceite, fideos, menestras, conservas, leche y productos de limpieza.',
 90.00, 'Despensa');
