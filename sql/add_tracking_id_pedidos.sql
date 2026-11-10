-- Migración: agregar columna tracking_id a la tabla pedidos
-- Fecha: 2026-03-06
--
-- tracking_id: código de seguimiento del envío (OCA, Correo Argentino, etc.)

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS tracking_id VARCHAR(30) DEFAULT NULL COMMENT 'Código de seguimiento del envío'
    AFTER mercadopago_status;

-- Índice para búsqueda por tracking
CREATE INDEX IF NOT EXISTS idx_tracking_id ON pedidos (tracking_id);
