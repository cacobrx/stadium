-- Esquema de base de datos para el sistema de productos Stadium
-- Basado en la interfaz Producto TypeScript

-- Tabla de marcas
CREATE TABLE IF NOT EXISTS marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    logo VARCHAR(255),
    sitio_web VARCHAR(255),
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de deportes
CREATE TABLE IF NOT EXISTS deportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    icono VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria_padre_id INT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    orden INT DEFAULT 0,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_padre_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla de colecciones
CREATE TABLE IF NOT EXISTS colecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    imagen VARCHAR(255),
    fecha_inicio DATE,
    fecha_fin DATE,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla principal de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    precio_descuento DECIMAL(10,2) NULL,
    stock INT DEFAULT 0,
    esta_activo BOOLEAN DEFAULT TRUE,
    destacado BOOLEAN DEFAULT FALSE,
    marca_id INT NULL,
    deporte VARCHAR(100) NULL,
    peso DECIMAL(8,2) NULL,
    alto DECIMAL(8,2) NULL,
    ancho DECIMAL(8,2) NULL,
    profundidad DECIMAL(8,2) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_nombre (nombre),
    INDEX idx_activo (esta_activo),
    INDEX idx_destacado (destacado),
    INDEX idx_marca (marca_id)
);

-- Tabla de imágenes de productos
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    es_principal BOOLEAN DEFAULT FALSE,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_principal (es_principal)
);

-- Tabla de relación productos-categorías (muchos a muchos)
CREATE TABLE IF NOT EXISTS producto_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    categoria_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_producto_categoria (producto_id, categoria_id)
);

-- Tabla de relación productos-colecciones (muchos a muchos)
CREATE TABLE IF NOT EXISTS producto_colecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    coleccion_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (coleccion_id) REFERENCES colecciones(id) ON DELETE CASCADE,
    UNIQUE KEY unique_producto_coleccion (producto_id, coleccion_id)
);

-- Tabla de atributos de productos (color, talla, material, etc.)
CREATE TABLE IF NOT EXISTS producto_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_nombre (nombre)
);

-- Tabla de variantes de productos (tallas, colores específicos)
CREATE TABLE IF NOT EXISTS producto_variantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sku_variante VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NULL, -- Si es NULL, usa el precio del producto padre
    stock INT DEFAULT 0,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_sku (sku_variante),
    INDEX idx_activa (activa)
);

-- Tabla de atributos de variantes
CREATE TABLE IF NOT EXISTS variante_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variante_id) REFERENCES producto_variantes(id) ON DELETE CASCADE,
    INDEX idx_variante (variante_id),
    INDEX idx_nombre (nombre)
);

-- Insertar datos de ejemplo
INSERT INTO marcas (nombre, descripcion) VALUES 
('Nike', 'Marca deportiva líder mundial'),
('Adidas', 'Marca alemana de artículos deportivos'),
('Puma', 'Marca alemana de ropa y accesorios deportivos'),
('Under Armour', 'Marca estadounidense de ropa deportiva');

INSERT INTO deportes (nombre, descripcion) VALUES 
('Fútbol', 'El deporte más popular del mundo'),
('Basketball', 'Deporte de canasta'),
('Tennis', 'Deporte de raqueta'),
('Running', 'Atletismo y carrera'),
('Training', 'Entrenamiento general');

INSERT INTO categorias (nombre, descripcion, slug) VALUES 
('Calzado', 'Zapatos deportivos', 'calzado'),
('Indumentaria', 'Ropa deportiva', 'indumentaria'),
('Accesorios', 'Accesorios deportivos', 'accesorios'),
('Equipamiento', 'Equipos y herramientas deportivas', 'equipamiento');

INSERT INTO colecciones (nombre, descripcion) VALUES 
('Temporada 2024', 'Colección primavera-verano 2024'),
('Clásicos', 'Productos clásicos atemporales'),
('Performance', 'Línea de alto rendimiento');