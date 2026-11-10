-- Tabla maestra de comprobantes fiscales
CREATE TABLE IF NOT EXISTS `facturas` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `fecha`        DATE             NULL DEFAULT NULL,
  `idcli`        INT(11)          NULL DEFAULT NULL,
  `tipocom`      VARCHAR(30)  NOT NULL DEFAULT '',
  `letra`        CHAR(1)      NOT NULL DEFAULT '',
  `ptovta`       INT(5)       NOT NULL DEFAULT 1,
  `numero`       BIGINT(20)   NOT NULL DEFAULT 0,
  `numerocae`    VARCHAR(30)  NOT NULL DEFAULT '',
  `fechacae`     DATE             NULL DEFAULT NULL,
  `docreferencia` VARCHAR(100) NOT NULL DEFAULT '',
  `idped`        INT(11)          NULL DEFAULT NULL,
  `idcuit`       INT(11)          NULL DEFAULT NULL,
  `fechacreate`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_idcli`  (`idcli`),
  KEY `idx_idcuit` (`idcuit`),
  KEY `idx_idped`  (`idped`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla detalle de comprobantes fiscales
CREATE TABLE IF NOT EXISTS `facturas_det` (
  `id`           INT(11)         NOT NULL AUTO_INCREMENT,
  `idfis`        INT(11)         NOT NULL,
  `cantidad`     DECIMAL(12,4)   NOT NULL DEFAULT 1,
  `descripcion`  VARCHAR(255)    NOT NULL DEFAULT '',
  `idart`        INT(11)             NULL DEFAULT NULL,   -- referencia a producto_variantes.id
  `precio`       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `alicuota`     DECIMAL(5,2)    NOT NULL DEFAULT 21.00,
  `fechacreate`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_idfis` (`idfis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
