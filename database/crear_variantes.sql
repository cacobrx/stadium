-- Actualización del esquema para agregar variantes y atributos - SIMPLE
-- Fecha: 2025-10-08

USE stadium;

-- 1. Agregar campo slug a colecciones
ALTER TABLE colecciones ADD COLUMN slug VARCHAR(150) UNIQUE AFTER nombre;

-- 2. Crear tabla para variantes de productos
CREATE TABLE producto_variantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    stock INT NOT NULL DEFAULT 0,
    precio DECIMAL(10,2) NULL,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_variante (producto_id),
    INDEX idx_sku_variante (sku)
);

-- 3. Crear tabla para opciones de variantes
CREATE TABLE variante_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    valor VARCHAR(100) NOT NULL,
    
    FOREIGN KEY (variante_id) REFERENCES producto_variantes(id) ON DELETE CASCADE,
    INDEX idx_variante_opcion (variante_id),
    INDEX idx_nombre_valor (nombre, valor)
);

-- 4. Crear tabla para atributos de productos
CREATE TABLE producto_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    orden INT DEFAULT 0,
    
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_atributo (producto_id),
    INDEX idx_nombre_atributo (nombre)
);

SELECT 'Tablas creadas correctamente' as resultado;