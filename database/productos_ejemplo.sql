-- Insertar productos de ejemplo
INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, stock, esta_activo, destacado, marca_id, deporte, peso, alto, ancho, profundidad) VALUES 
('NIK-001', 'Nike Air Max 90', 'Zapatillas deportivas clásicas con tecnología Air Max', 150.00, 120.00, 25, 1, 1, 1, 'Running', 0.8, 12, 30, 20),
('ADS-002', 'Adidas Ultraboost 22', 'Zapatillas de running con tecnología Boost', 180.00, NULL, 15, 1, 1, 2, 'Running', 0.9, 13, 31, 21),
('PUM-003', 'Puma Future Z', 'Botines de fútbol para césped natural', 120.00, 100.00, 30, 1, 0, 3, 'Fútbol', 0.6, 8, 25, 15),
('UA-004', 'Under Armour HeatGear', 'Camiseta deportiva de entrenamiento', 45.00, NULL, 50, 1, 0, 4, 'Training', 0.2, 2, 40, 30);

-- Insertar imágenes para los productos
INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) VALUES 
(1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/99486859-0ff3-46b4-949b-2d16af2ad421/air-max-90-zapatillas-hombre.png', 'Nike Air Max 90 Vista Principal', 1, 0),
(1, 'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/air-max-90-zapatillas-hombre-2.png', 'Nike Air Max 90 Vista Lateral', 0, 1),
(2, 'https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/ultraboost-22-shoes-black.jpg', 'Adidas Ultraboost 22 Negro', 1, 0),
(3, 'https://images.puma.com/image/upload/f_auto,q_auto,b_rgb:fafafa/global/future-z-football-boots.jpg', 'Puma Future Z Botines', 1, 0),
(4, 'https://underarmour.scene7.com/is/image/Underarmour/heatgear-shirt.jpg', 'Under Armour HeatGear Shirt', 1, 0);

-- Relacionar productos con categorías
INSERT INTO producto_categorias (producto_id, categoria_id) VALUES 
(1, 1), -- Nike Air Max 90 -> Calzado
(2, 1), -- Adidas Ultraboost -> Calzado  
(3, 1), -- Puma Future Z -> Calzado
(4, 2); -- Under Armour HeatGear -> Indumentaria

-- Relacionar productos con colecciones
INSERT INTO producto_colecciones (producto_id, coleccion_id) VALUES 
(1, 2), -- Nike Air Max 90 -> Clásicos
(1, 3), -- Nike Air Max 90 -> Performance
(2, 1), -- Adidas Ultraboost -> Temporada 2024
(2, 3), -- Adidas Ultraboost -> Performance
(3, 1), -- Puma Future Z -> Temporada 2024
(4, 3); -- Under Armour HeatGear -> Performance

-- Insertar atributos para los productos
INSERT INTO producto_atributos (producto_id, nombre, valor) VALUES 
(1, 'Color', 'Blanco/Gris'),
(1, 'Material', 'Cuero sintético'),
(1, 'Suela', 'Goma'),
(2, 'Color', 'Negro/Azul'),
(2, 'Tecnología', 'Boost'),
(2, 'Tipo', 'Running'),
(3, 'Color', 'Negro/Amarillo'),
(3, 'Superficie', 'Césped natural'),
(3, 'Tipo', 'FG'),
(4, 'Color', 'Azul marino'),
(4, 'Talla', 'M'),
(4, 'Material', 'Poliéster');