-- Agregar columna provincia a la tabla clientes
ALTER TABLE `clientes` ADD COLUMN `provincia` varchar(50) NOT NULL DEFAULT '' AFTER `ciudad`;