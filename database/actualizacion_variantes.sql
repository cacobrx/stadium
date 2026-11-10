-- Actualización del esquema para agregar variantes y atributos
-- Fecha: 2025-10-08

USE stadium;

-- 1. Agregar campo slug a colecciones si no existe
-- Verificar primero si existe la columna
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'stadium' 
  AND TABLE_NAME = 'colecciones' 
  AND COLUMN_NAME = 'slug';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE colecciones ADD COLUMN slug VARCHAR(150) UNIQUE AFTER nombre', 
    'SELECT "Column slug already exists" as mensaje');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Generar slugs para colecciones existentes
UPDATE colecciones 
SET slug = LOWER(REPLACE(REPLACE(nombre, ' ', '-'), 'ñ', 'n'))
WHERE slug IS NULL;

-- 2. Crear tabla para variantes de productos
CREATE TABLE IF NOT EXISTS producto_variantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    stock INT NOT NULL DEFAULT 0,
    precio DECIMAL(10,2) NULL, -- precio específico de la variante (opcional)
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_variante (producto_id),
    INDEX idx_sku_variante (sku)
);

-- 3. Crear tabla para opciones de variantes (ej: talle, color)
CREATE TABLE IF NOT EXISTS variante_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL, -- ej: "Talle", "Color"
    valor VARCHAR(100) NOT NULL, -- ej: "M", "Rojo"
    
    FOREIGN KEY (variante_id) REFERENCES producto_variantes(id) ON DELETE CASCADE,
    INDEX idx_variante_opcion (variante_id),
    INDEX idx_nombre_valor (nombre, valor)
);

-- 4. Crear tabla para atributos de productos
CREATE TABLE IF NOT EXISTS producto_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL, -- ej: "Material", "Peso", "Origen"
    valor TEXT NOT NULL,          -- ej: "Algodón", "500g", "Argentina"
    orden INT DEFAULT 0,
    
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_atributo (producto_id),
    INDEX idx_nombre_atributo (nombre)
);

-- 5. Insertar algunos datos de ejemplo para testing

-- Ejemplo de variantes para Nike Air Max 90 (ID 1)
INSERT IGNORE INTO producto_variantes (producto_id, sku, stock, precio) VALUES
(1, 'NIK-001-40', 5, NULL),
(1, 'NIK-001-41', 8, NULL),
(1, 'NIK-001-42', 12, NULL),
(1, 'NIK-001-43', 3, NULL);

-- Opciones para las variantes (talles)
INSERT IGNORE INTO variante_opciones (variante_id, nombre, valor) VALUES
((SELECT id FROM producto_variantes WHERE sku = 'NIK-001-40'), 'Talle', '40'),
((SELECT id FROM producto_variantes WHERE sku = 'NIK-001-41'), 'Talle', '41'),
((SELECT id FROM producto_variantes WHERE sku = 'NIK-001-42'), 'Talle', '42'),
((SELECT id FROM producto_variantes WHERE sku = 'NIK-001-43'), 'Talle', '43');

-- Atributos para Nike Air Max 90
INSERT IGNORE INTO producto_atributos (producto_id, nombre, valor, orden) VALUES
(1, 'Material', 'Cuero sintético y malla', 1),
(1, 'Suela', 'Goma con cámara de aire', 2),
(1, 'Origen', 'Vietnam', 3),
(1, 'Peso', '350g', 4),
(1, 'Género', 'Unisex', 5);

-- Ejemplo de variantes para Adidas Ultraboost (ID 2)
INSERT IGNORE INTO producto_variantes (producto_id, sku, stock, precio) VALUES
(2, 'ADS-002-39', 4, 185.00),
(2, 'ADS-002-40', 7, 185.00),
(2, 'ADS-002-41', 6, 185.00),
(2, 'ADS-002-42', 9, 185.00);

-- Opciones para Adidas (talles)
INSERT IGNORE INTO variante_opciones (variante_id, nombre, valor) VALUES
((SELECT id FROM producto_variantes WHERE sku = 'ADS-002-39'), 'Talle', '39'),
((SELECT id FROM producto_variantes WHERE sku = 'ADS-002-40'), 'Talle', '40'),
((SELECT id FROM producto_variantes WHERE sku = 'ADS-002-41'), 'Talle', '41'),
((SELECT id FROM producto_variantes WHERE sku = 'ADS-002-42'), 'Talle', '42');

-- Atributos para Adidas
INSERT IGNORE INTO producto_atributos (producto_id, nombre, valor, orden) VALUES
(2, 'Material', 'Primeknit reciclado', 1),
(2, 'Tecnología', 'Boost Energy Return', 2),
(2, 'Origen', 'Indonesia', 3),
(2, 'Peso', '320g', 4),
(2, 'Género', 'Unisex', 5);

-- Actualizar slugs para colecciones existentes si no tienen
UPDATE colecciones SET slug = 'primavera-verano' WHERE nombre LIKE '%Primavera%' AND slug IS NULL;
UPDATE colecciones SET slug = 'otono-invierno' WHERE nombre LIKE '%Otoño%' AND slug IS NULL;
UPDATE colecciones SET slug = 'deportiva' WHERE nombre LIKE '%Deportiva%' AND slug IS NULL;

SELECT 'Esquema actualizado correctamente' as resultado;