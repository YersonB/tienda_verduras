-- ============================================================================
-- datos_iniciales.sql  ·  El mercadito de Julia
-- Datos mínimos para arrancar en una instalación NUEVA (nube).
-- Orden de importación recomendado:
--   1) esquema_completo.sql   (estructura de tablas)
--   2) datos_iniciales.sql    (este archivo)
-- ============================================================================

-- ── Usuario administrador inicial ───────────────────────────────────────────
-- Correo:      admin@mercadito.com
-- Contraseña:  Julia2026!     (CÁMBIALA al entrar: Mi Perfil → Cambiar contraseña)
INSERT INTO usuarios (nombre, correo, password, rol) VALUES
('Julia (Admin)', 'admin@mercadito.com',
 '$2y$10$ircZl/RvUzXdWAnnhVUhiuYLq6kwT9TGqKcNirILBM5ex1RWL84DS', 'admin');

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
