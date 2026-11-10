-- Migración: agregar campos de preferencias a clientes
-- Ejecutar en producción (planbsis_stadium) y en local (stadium)

ALTER TABLE clientes
    ADD COLUMN recibirOfertas  TINYINT(1)   NOT NULL DEFAULT 1 AFTER actualizaprecio,
    ADD COLUMN recibirWhatsApp TINYINT(1)   NOT NULL DEFAULT 1 AFTER recibirOfertas,
    ADD COLUMN newsletter      TINYINT(1)   NOT NULL DEFAULT 1 AFTER recibirWhatsApp,
    ADD COLUMN idioma          VARCHAR(2)   NOT NULL DEFAULT 'es' AFTER newsletter;
