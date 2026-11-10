-- Agrega columna token único para identificación segura de pedidos en callbacks de MP
ALTER TABLE pedidos
    ADD COLUMN token VARCHAR(64) NULL DEFAULT NULL AFTER preferencias_checkout,
    ADD UNIQUE INDEX idx_token (token);
