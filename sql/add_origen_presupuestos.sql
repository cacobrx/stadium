-- Agrega campo origen a presupuestos (referencia a tablas.valor con codtab='ORI')
ALTER TABLE presupuestos
  ADD COLUMN origen smallint DEFAULT NULL COMMENT 'tablas.valor con codtab=ORI' AFTER tipoProyecto;

-- Definición de la tabla Origen (para el ABM de Tablas)
INSERT INTO tablas (codtab, descripcion, valor, orden, valorc) VALUES ('DEF', 'Origen', NULL, NULL, 'ORI');

-- Valores de Origen
INSERT INTO tablas (codtab, descripcion, valor, orden, valorc) VALUES
  ('ORI', 'Web',       1, 1, NULL),
  ('ORI', 'WhatsApp',  2, 2, NULL),
  ('ORI', 'Instagram', 3, 3, NULL),
  ('ORI', 'Facebook',  4, 4, NULL),
  ('ORI', 'Teléfono',  5, 5, NULL);
