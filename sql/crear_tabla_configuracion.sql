-- Tabla de configuración general (clave/valor)
CREATE TABLE IF NOT EXISTS configuracion (
  clave       varchar(50)  NOT NULL,
  valor       text         DEFAULT NULL,
  descripcion varchar(100) DEFAULT NULL,
  fechamod    timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configuración de correo SMTP (completar desde Utilidades > Configuración)
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
  ('mail_host',       '',                          'Servidor SMTP'),
  ('mail_port',       '465',                       'Puerto SMTP'),
  ('mail_username',   '',                          'Usuario SMTP'),
  ('mail_password',   '',                          'Clave SMTP'),
  ('mail_encryption', 'ssl',                       'Seguridad (ssl / tls / none)'),
  ('mail_from_email', '',                          'Email remitente'),
  ('mail_from_name',  'Stadium',                   'Nombre remitente'),
  ('mail_presupuestos_destino', 'info@stadiumdeportes.com', 'Destinatario de presupuestos web');
