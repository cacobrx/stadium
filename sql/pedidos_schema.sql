-- Esquema de base de datos para el sistema de pedidos Stadium
-- Este esquema incluye todas las tablas necesarias para gestionar pedidos completos

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apellido VARCHAR(100) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    provincia VARCHAR(100),
    telefono VARCHAR(50),
    email VARCHAR(150),
    condicioniva VARCHAR(50),
    cuit VARCHAR(20),
    observaciones TEXT,
    fechacreate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    saldoini DECIMAL(10,2) DEFAULT 0.00,
    actualizaprecio TINYINT DEFAULT 0,
    -- Campos adicionales para sistema de e-commerce
    tipodoc VARCHAR(10) DEFAULT 'DNI',
    nrodoc VARCHAR(20),
    nrodir VARCHAR(20),
    codpostal VARCHAR(10),
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cuit (cuit),
    INDEX idx_nrodoc (nrodoc)
);

-- Tabla de pedidos (cabecera)
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    idcli INT NOT NULL,
    observaciones TEXT,
    ubicacion VARCHAR(255),
    nroremito INT DEFAULT 0,
    fechacreate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fechamod TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado INT DEFAULT 0,  -- 0: pendiente, 1: confirmado, 2: preparando, 3: enviado, 4: entregado, 5: cancelado
    bultos INT DEFAULT 0,
    idrep INT DEFAULT 0,   -- ID del repartidor
    total DECIMAL(10,2) DEFAULT 0.00,
    orden INT DEFAULT 0,
    fechareparto DATE NULL,
    tipo VARCHAR(50),
    retiro TINYINT DEFAULT 0,  -- 0: envío, 1: retira en local
    observarep TEXT,
    usuario INT DEFAULT 0,
    cerrado TINYINT DEFAULT 0,
    actualizacion TIMESTAMP NULL,
    fechaentrega DATE NULL,
    fechapago DATE NULL,
    -- Campos adicionales para e-commerce
    subtotal DECIMAL(10,2),
    envio DECIMAL(10,2) DEFAULT 0.00,
    modo_pago VARCHAR(50),  -- sandbox, production
    tipo_envio VARCHAR(50),
    nombre_envio VARCHAR(100),
    preferencias_checkout TEXT,  -- JSON con preferencias
    url_exito VARCHAR(500),
    url_fallo VARCHAR(500),
    url_pendiente VARCHAR(500),
    mercadopago_preference_id VARCHAR(100),
    mercadopago_payment_id VARCHAR(100),
    mercadopago_status VARCHAR(50),
    FOREIGN KEY (idcli) REFERENCES clientes(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_cliente (idcli),
    INDEX idx_estado (estado),
    INDEX idx_fechacreate (fechacreate)
);

-- Tabla de detalles de pedidos
CREATE TABLE IF NOT EXISTS pedidos_det (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idped INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    idart INT,  -- ID del artículo/producto/variante
    fechacreate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fechamod TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    precio DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    terminado TINYINT DEFAULT 0,
    detalle TEXT,
    -- Campos adicionales para e-commerce
    descripcion VARCHAR(255),
    subtotal DECIMAL(10,2),
    imagen_url VARCHAR(500),
    producto_id VARCHAR(150),  -- ID compuesto del producto (ej: "producto-nombre-123")
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idped) REFERENCES pedidos(id) ON DELETE CASCADE,
    INDEX idx_pedido (idped),
    INDEX idx_articulo (idart),
    INDEX idx_producto_id (producto_id)
);

-- Tabla para seguimiento de estados de pedidos (opcional pero recomendada)
CREATE TABLE IF NOT EXISTS pedidos_estados_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idped INT NOT NULL,
    estado_anterior INT,
    estado_nuevo INT NOT NULL,
    usuario INT,
    observaciones TEXT,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idped) REFERENCES pedidos(id) ON DELETE CASCADE,
    INDEX idx_pedido (idped),
    INDEX idx_fecha (fecha_cambio)
);

-- Tabla para gestión de envíos (opcional)
CREATE TABLE IF NOT EXISTS pedidos_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idped INT NOT NULL UNIQUE,
    tipo VARCHAR(50) NOT NULL,  -- domicilio, correo, retiro
    costo DECIMAL(10,2) DEFAULT 0.00,
    nombre_destinatario VARCHAR(200),
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    provincia VARCHAR(100),
    codigo_postal VARCHAR(10),
    telefono VARCHAR(50),
    numero_seguimiento VARCHAR(100),
    empresa_envio VARCHAR(100),
    fecha_despacho DATETIME NULL,
    fecha_entrega_estimada DATETIME NULL,
    fecha_entrega_real DATETIME NULL,
    observaciones TEXT,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modificado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idped) REFERENCES pedidos(id) ON DELETE CASCADE,
    INDEX idx_pedido (idped),
    INDEX idx_seguimiento (numero_seguimiento)
);

-- Vista útil para consultas de pedidos completos
CREATE OR REPLACE VIEW vista_pedidos_completos AS
SELECT 
    p.id as pedido_id,
    p.fecha,
    p.estado,
    p.total,
    p.subtotal,
    p.envio,
    p.modo_pago,
    p.fechacreate,
    c.id as cliente_id,
    c.nombre as cliente_nombre,
    c.apellido as cliente_apellido,
    c.email as cliente_email,
    c.telefono as cliente_telefono,
    COUNT(pd.id) as cantidad_items,
    SUM(pd.cantidad) as total_productos
FROM pedidos p
INNER JOIN clientes c ON p.idcli = c.id
LEFT JOIN pedidos_det pd ON p.id = pd.idped
GROUP BY p.id, p.fecha, p.estado, p.total, p.subtotal, p.envio, p.modo_pago, 
         p.fechacreate, c.id, c.nombre, c.apellido, c.email, c.telefono;
