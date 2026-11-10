-- Tabla para datos fiscales / CUIT de la empresa
CREATE TABLE IF NOT EXISTS `datoscuit` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `razonsocial`   VARCHAR(255)  NOT NULL DEFAULT '',
  `fantasia`      VARCHAR(255)  NOT NULL DEFAULT '',
  `cuit`          VARCHAR(20)   NOT NULL DEFAULT '',
  `condicioniva`  VARCHAR(100)  NOT NULL DEFAULT '',
  `direccion`     VARCHAR(255)  NOT NULL DEFAULT '',
  `ciudad`        VARCHAR(100)  NOT NULL DEFAULT '',
  `puntoventa`    INT(5)        NOT NULL DEFAULT 1,
  `fechainicio`   DATE              NULL DEFAULT NULL,
  `test`          TINYINT(1)    NOT NULL DEFAULT 0,
  `pordefecto`    TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
