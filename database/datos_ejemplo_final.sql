-- Insertar datos de ejemplo paso a paso
USE stadium;

-- Limpiar datos anteriores
DELETE FROM variante_opciones;
DELETE FROM producto_variantes;
DELETE FROM producto_atributos;

-- Actualizar slugs en colecciones
UPDATE colecciones SET slug = LOWER(REPLACE(REPLACE(REPLACE(nombre, ' ', '-'), 'ñ', 'n'), 'ó', 'o')) WHERE slug IS NULL OR slug = '';

-- Insertar variantes para Nike Air Max 90 (ID 1)
INSERT INTO producto_variantes (producto_id, sku_variante, nombre, stock, precio) VALUES
(1, 'NIK-001-40', 'Nike Air Max 90 - Talle 40', 5, NULL),
(1, 'NIK-001-41', 'Nike Air Max 90 - Talle 41', 8, NULL),
(1, 'NIK-001-42', 'Nike Air Max 90 - Talle 42', 12, NULL),
(1, 'NIK-001-43', 'Nike Air Max 90 - Talle 43', 3, NULL);

-- Insertar opciones para Nike (usando IDs automáticos)
INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '40' FROM producto_variantes WHERE sku_variante = 'NIK-001-40';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '41' FROM producto_variantes WHERE sku_variante = 'NIK-001-41';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '42' FROM producto_variantes WHERE sku_variante = 'NIK-001-42';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '43' FROM producto_variantes WHERE sku_variante = 'NIK-001-43';

-- Atributos para Nike Air Max 90
INSERT INTO producto_atributos (producto_id, nombre, valor) VALUES
(1, 'Material', 'Cuero sintético y malla'),
(1, 'Suela', 'Goma con cámara de aire'),
(1, 'Origen', 'Vietnam'),
(1, 'Peso', '350g'),
(1, 'Género', 'Unisex');

-- Insertar variantes para Adidas Ultraboost (ID 2)
INSERT INTO producto_variantes (producto_id, sku_variante, nombre, stock, precio) VALUES
(2, 'ADS-002-39', 'Adidas Ultraboost - Talle 39', 4, 185.00),
(2, 'ADS-002-40', 'Adidas Ultraboost - Talle 40', 7, 185.00),
(2, 'ADS-002-41', 'Adidas Ultraboost - Talle 41', 6, 185.00),
(2, 'ADS-002-42', 'Adidas Ultraboost - Talle 42', 9, 185.00);

-- Insertar opciones para Adidas
INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '39' FROM producto_variantes WHERE sku_variante = 'ADS-002-39';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '40' FROM producto_variantes WHERE sku_variante = 'ADS-002-40';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '41' FROM producto_variantes WHERE sku_variante = 'ADS-002-41';

INSERT INTO variante_opciones (variante_id, nombre, valor) 
SELECT id, 'Talle', '42' FROM producto_variantes WHERE sku_variante = 'ADS-002-42';

-- Atributos para Adidas
INSERT INTO producto_atributos (producto_id, nombre, valor) VALUES
(2, 'Material', 'Primeknit reciclado'),
(2, 'Tecnología', 'Boost Energy Return'),
(2, 'Origen', 'Indonesia'),
(2, 'Peso', '320g'),
(2, 'Género', 'Unisex');

SELECT 'Datos insertados correctamente' as resultado;