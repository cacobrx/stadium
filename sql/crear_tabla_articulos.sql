-- Crear tabla de artículos básica para el sistema de pedidos
CREATE TABLE `articulos` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descripcion` text,
  `activo` int NOT NULL DEFAULT '1',
  `fechacreate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- Insertar algunos artículos de ejemplo
INSERT INTO `articulos` (`nombre`, `precio`, `descripcion`) VALUES
('Camiseta Básica', 15000.00, 'Camiseta de algodón 100%'),
('Pantalón Deportivo', 25000.00, 'Pantalón deportivo con tecnología Dri-FIT'),
('Zapatillas Running', 45000.00, 'Zapatillas para running con amortiguación'),
('Medias Deportivas', 3500.00, 'Pack de 3 pares de medias deportivas'),
('Shorts Deportivos', 12000.00, 'Shorts para entrenamiento'),
('Buzo con Capucha', 28000.00, 'Buzo de algodón con capucha'),
('Gorra Deportiva', 8500.00, 'Gorra ajustable con protección UV'),
('Mochila Deportiva', 22000.00, 'Mochila resistente al agua'),
('Botella de Agua', 4500.00, 'Botella térmica de 750ml'),
('Toalla Deportiva', 6800.00, 'Toalla de microfibra absorbente');