-- Datos de prueba: una categoría y dos productos con stock conocido.
INSERT INTO categories (id, name) VALUES (1, 'Filtros');
INSERT INTO products (id, name, quantity, buy_price, sale_price, categorie_id, media_id, date) VALUES
(1, 'Filtro de aceite', '30', 30000, 45000, 1, 0, '2025-10-01 10:00:00'),
(2, 'Batería 60Ah', '4', 700000, 890000, 1, 0, '2025-10-01 10:00:00');
