-- Insertar datos de ejemplo con imágenes de variantes
-- Fecha: 2025-10-09

USE stadium;

-- Insertar algunas imágenes de ejemplo para las variantes existentes
-- Suponiendo que ya existen variantes del producto Nike Air Max 90

-- Obtener los IDs de las variantes existentes
SET @variante_40 = (SELECT id FROM producto_variantes WHERE sku_variante = 'NIK-001-40' LIMIT 1);
SET @variante_41 = (SELECT id FROM producto_variantes WHERE sku_variante = 'NIK-001-41' LIMIT 1);
SET @variante_42 = (SELECT id FROM producto_variantes WHERE sku_variante = 'NIK-001-42' LIMIT 1);
SET @variante_43 = (SELECT id FROM producto_variantes WHERE sku_variante = 'NIK-001-43' LIMIT 1);

-- Insertar imágenes para cada variante (si las variantes existen)
INSERT IGNORE INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden) VALUES
-- Variante talle 40
(@variante_40, '/uploads/productos/nike-air-max-90-talle-40-main.jpg', 'Nike Air Max 90 Talle 40 - Vista Principal', 1, 0),
(@variante_40, '/uploads/productos/nike-air-max-90-talle-40-side.jpg', 'Nike Air Max 90 Talle 40 - Vista Lateral', 0, 1),
(@variante_40, '/uploads/productos/nike-air-max-90-talle-40-sole.jpg', 'Nike Air Max 90 Talle 40 - Suela', 0, 2),

-- Variante talle 41
(@variante_41, '/uploads/productos/nike-air-max-90-talle-41-main.jpg', 'Nike Air Max 90 Talle 41 - Vista Principal', 1, 0),
(@variante_41, '/uploads/productos/nike-air-max-90-talle-41-side.jpg', 'Nike Air Max 90 Talle 41 - Vista Lateral', 0, 1),

-- Variante talle 42
(@variante_42, '/uploads/productos/nike-air-max-90-talle-42-main.jpg', 'Nike Air Max 90 Talle 42 - Vista Principal', 1, 0),
(@variante_42, '/uploads/productos/nike-air-max-90-talle-42-side.jpg', 'Nike Air Max 90 Talle 42 - Vista Lateral', 0, 1),
(@variante_42, '/uploads/productos/nike-air-max-90-talle-42-back.jpg', 'Nike Air Max 90 Talle 42 - Vista Trasera', 0, 2),

-- Variante talle 43
(@variante_43, '/uploads/productos/nike-air-max-90-talle-43-main.jpg', 'Nike Air Max 90 Talle 43 - Vista Principal', 1, 0);

SELECT 'Imágenes de variantes insertadas correctamente' as resultado;