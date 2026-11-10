-- Tabla de pagos de pedidos (MercadoPago / manual)
CREATE TABLE IF NOT EXISTS pagos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    idped           MEDIUMINT NOT NULL,
    token           VARCHAR(64) NOT NULL,
    monto           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado          VARCHAR(30) NOT NULL DEFAULT 'pending',
                    -- approved | pending | rejected | cancelled | refunded | in_process
    mp_payment_id   VARCHAR(50) NULL,
    mp_status       VARCHAR(30) NULL,
    mp_status_detail VARCHAR(80) NULL,
    mp_payment_type VARCHAR(50) NULL,
    mp_external_ref VARCHAR(50) NULL,
    datos_json      LONGTEXT NULL,      -- respuesta completa de MP
    fechacreate     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idped) REFERENCES pedidos(id) ON DELETE RESTRICT,
    INDEX idx_idped  (idped),
    INDEX idx_token  (token),
    INDEX idx_estado (estado),
    UNIQUE KEY uk_mp_payment (mp_payment_id)   -- evitar duplicados del mismo pago
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
