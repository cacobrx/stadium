-- Crear tabla de presupuestos (similar a pedidos)
CREATE TABLE IF NOT EXISTS `presupuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `idcli` int(11) NOT NULL,
  `observaciones` text,
  `ubicacion` text,
  `nro_presupuesto` varchar(50) DEFAULT NULL,
  `fechacreate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechamod` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` int(11) NOT NULL DEFAULT '0' COMMENT '0=Borrador, 1=Enviado, 2=Aprobado, 3=Rechazado, 4=Vencido',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `usuario` int(11) DEFAULT NULL,
  `validez` int(11) DEFAULT '30' COMMENT 'Días de validez',
  `fecha_vencimiento` date DEFAULT NULL,
  `notas_internas` text,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_idcli` (`idcli`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de detalles de presupuestos (similar a pedidos_det)
CREATE TABLE IF NOT EXISTS `presupuestos_det` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idpres` int(11) NOT NULL COMMENT 'ID del presupuesto',
  `cantidad` int(11) NOT NULL,
  `idart` int(11) NOT NULL COMMENT 'ID del producto/artículo',
  `precio` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `detalle` text,
  `iva` decimal(5,2) DEFAULT '21.00',
  `tipo_producto` enum('producto','equipamiento') DEFAULT 'producto' COMMENT 'Indica si es de tabla productos o equipamiento_profesional',
  PRIMARY KEY (`id`),
  KEY `idx_idpres` (`idpres`),
  KEY `idx_idart` (`idart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
