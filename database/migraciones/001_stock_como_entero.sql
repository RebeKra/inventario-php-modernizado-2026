-- Corrige H-09: el stock se guardaba como texto (VARCHAR).
-- 1) Normaliza valores no numéricos para que la conversión no pierda datos en silencio.
UPDATE products SET quantity = '0' WHERE quantity IS NULL OR quantity NOT REGEXP '^-?[0-9]+$';
-- 2) Convierte la columna a entero.
ALTER TABLE products MODIFY quantity INT NOT NULL DEFAULT 0;
