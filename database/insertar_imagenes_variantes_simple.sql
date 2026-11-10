-- Insertar datos de ejemplo con imágenes de variantes (versión simple)
-- Fecha: 2025-10-09

USE stadium;

-- Primero, eliminar los triggers problemáticos
DROP TRIGGER IF EXISTS before_variante_imagen_principal_insert;
DROP TRIGGER IF EXISTS before_variante_imagen_principal_update;

-- Verificar si existen variantes
SELECT 'Variantes existentes:' as mensaje;
SELECT id, sku_variante, nombre FROM producto_variantes LIMIT 5;

-- Insertar imágenes solo si las variantes existen
INSERT IGNORE INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden) 
SELECT id, 
       CONCAT('/uploads/productos/', LOWER(REPLACE(nombre, ' ', '-')), '-main.jpg'),
       CONCAT(nombre, ' - Vista Principal'),
       1,
       0
FROM producto_variantes 
WHERE id IS NOT NULL
LIMIT 5;

INSERT IGNORE INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden) 
SELECT id, 
       CONCAT('/uploads/productos/', LOWER(REPLACE(nombre, ' ', '-')), '-side.jpg'),
       CONCAT(nombre, ' - Vista Lateral'),
       0,
       1
FROM producto_variantes 
WHERE id IS NOT NULL
LIMIT 5;

SELECT 'Imágenes de variantes insertadas correctamente' as resultado;