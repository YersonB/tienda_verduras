-- ============================================================================
-- productos_ejemplo.sql  ·  Catálogo base (precios REFERENCIALES, ajústalos)
-- Productos frecuentes de mercado en Perú para que el cotizador funcione.
-- IMPORTANTE: selecciona tu base en phpMyAdmin y luego importa.
-- (No duplica Tomate, Papa, Zanahoria ni Cebolla Roja si ya existen.)
-- ============================================================================

INSERT INTO productos (nombre, categoria, precio_compra, precio_venta, stock, unidad_medida) VALUES
-- Verduras y tubérculos
('Camote',           'Tubérculos', 2.20, 3.00, 40, 'kg'),
('Cebolla Blanca',   'Verduras',   2.60, 3.50, 40, 'kg'),
('Zapallo',          'Verduras',   2.00, 3.00, 30, 'kg'),
('Lechuga',          'Verduras',   1.00, 1.50, 50, 'unidad'),
('Lechuga Morada',   'Verduras',   1.40, 2.00, 30, 'unidad'),
('Ají Amarillo',     'Verduras',   5.00, 7.00, 20, 'kg'),
('Rocoto',           'Verduras',   5.00, 7.00, 20, 'kg'),
('Nabo',             'Verduras',   1.00, 1.50, 25, 'unidad'),
('Apio',             'Verduras',   1.30, 2.00, 25, 'atado'),
('Vainita',          'Verduras',   4.50, 6.00, 20, 'kg'),
('Espinaca',         'Verduras',   1.70, 2.50, 25, 'atado'),
('Acelga',           'Verduras',   1.70, 2.50, 25, 'atado'),
('Olluco',           'Tubérculos', 3.20, 4.50, 20, 'kg'),
('Betarraga',        'Verduras',   2.40, 3.50, 25, 'kg'),
('Limón',            'Frutas',     3.20, 4.50, 30, 'kg'),
-- Carnes
('Carne de Res para Guiso', 'Carnes', 20.00, 24.00, 15, 'kg'),
('Carne Molida',     'Carnes',    16.00, 20.00, 15, 'kg'),
('Bistec de Res',    'Carnes',    22.00, 26.00, 12, 'kg'),
('Carne para Sopa',  'Carnes',    10.00, 14.00, 12, 'kg'),
('Pechuga de Pollo', 'Carnes',    11.00, 14.00, 20, 'kg'),
('Hamburguesa de Carne', 'Carnes', 1.70, 2.50, 40, 'unidad'),
('Hamburguesa de Pollo', 'Carnes', 1.70, 2.50, 40, 'unidad'),
-- Abarrotes
('Huevos',           'Abarrotes',  0.45, 0.60, 200, 'unidad'),
-- Frutas
('Manzana Roja',     'Frutas',     0.80, 1.20, 60, 'unidad'),
('Manzana Verde',    'Frutas',     0.90, 1.30, 60, 'unidad'),
('Papaya',           'Frutas',     4.50, 6.00, 20, 'unidad'),
('Piña',             'Frutas',     3.20, 4.50, 20, 'unidad'),
('Plátano',          'Frutas',     0.45, 0.70, 100, 'unidad'),
('Naranja',          'Frutas',     0.45, 0.70, 80, 'unidad'),
('Mandarina',        'Frutas',     0.40, 0.60, 80, 'unidad'),
('Pera',             'Frutas',     1.00, 1.50, 40, 'unidad'),
('Sandía',           'Frutas',     6.50, 9.00, 15, 'unidad'),
('Mango',            'Frutas',     1.70, 2.50, 40, 'unidad');
