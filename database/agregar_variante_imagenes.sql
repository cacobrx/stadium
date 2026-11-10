-- Agregar tabla para imágenes de variantes
-- Fecha: 2025-10-09

USE stadium;

-- Crear tabla para imágenes de variantes
CREATE TABLE IF NOT EXISTS variante_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    es_principal TINYINT(1) DEFAULT 0,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (variante_id) REFERENCES producto_variantes(id) ON DELETE CASCADE,
    INDEX idx_variante_imagen (variante_id),
    INDEX idx_es_principal (es_principal),
    INDEX idx_orden (orden)
);

-- Asegurar que solo una imagen por variante sea principal
-- Trigger para mantener la integridad de imagen principal
DELIMITER //

CREATE TRIGGER IF NOT EXISTS before_variante_imagen_principal_insert
    BEFORE INSERT ON variante_imagenes
    FOR EACH ROW
BEGIN
    IF NEW.es_principal = 1 THEN
        UPDATE variante_imagenes 
        SET es_principal = 0 
        WHERE variante_id = NEW.variante_id;
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS before_variante_imagen_principal_update
    BEFORE UPDATE ON variante_imagenes
    FOR EACH ROW
BEGIN
    IF NEW.es_principal = 1 AND OLD.es_principal = 0 THEN
        UPDATE variante_imagenes 
        SET es_principal = 0 
        WHERE variante_id = NEW.variante_id AND id != NEW.id;
    END IF;
END //

DELIMITER ;

SELECT 'Tabla variante_imagenes creada correctamente' as resultado;