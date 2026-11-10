-- Tabla de múltiples direcciones por cliente
-- Ejecutar en la base de datos de Stadium

CREATE TABLE IF NOT EXISTS clientes_direcciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descripcion VARCHAR(100) NOT NULL DEFAULT 'Principal',
    direccion VARCHAR(255) NOT NULL DEFAULT '',
    piso VARCHAR(10) NOT NULL DEFAULT '',
    departamento VARCHAR(10) NOT NULL DEFAULT '',
    cpostal VARCHAR(10) NOT NULL DEFAULT '',
    ciudad VARCHAR(100) NOT NULL DEFAULT '',
    provincia VARCHAR(100) NOT NULL DEFAULT '',
    principal TINYINT(1) NOT NULL DEFAULT 0,
    fechacreate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clientes_direcciones_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índice para búsquedas por cliente
CREATE INDEX idx_clientes_direcciones_cliente ON clientes_direcciones(cliente_id);

-- Migrar datos existentes: copiar la dirección actual de cada cliente como dirección principal
INSERT INTO clientes_direcciones (cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal)
SELECT id, 'Principal', COALESCE(direccion,''), '', '', '', COALESCE(ciudad,''), COALESCE(provincia,''), 1
FROM clientes
WHERE direccion IS NOT NULL AND direccion <> '';

-- ─── Si la tabla ya existe, agregar las columnas piso y departamento ───────────
-- Ejecutar solo si ya tenías la tabla sin esas columnas:
-- ALTER TABLE clientes_direcciones
--     ADD COLUMN piso VARCHAR(10) NOT NULL DEFAULT '' AFTER direccion,
--     ADD COLUMN departamento VARCHAR(10) NOT NULL DEFAULT '' AFTER piso,
--     ADD COLUMN cpostal VARCHAR(10) NOT NULL DEFAULT '' AFTER departamento;

-- ─── Agregar columna instrucciones (si no existe) ──────────────────────────────
ALTER TABLE clientes_direcciones
    ADD COLUMN IF NOT EXISTS indicaciones TEXT NOT NULL DEFAULT '' AFTER provincia;
