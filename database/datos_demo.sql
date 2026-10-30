-- DATOS DE PRUEBA (opcional). Solo para ver el panel con información.
-- Importar DESPUÉS de karaoke_bar.sql. Borra o cambia estos datos cuando registres los reales.
USE karaoke_bar;

INSERT INTO mesas (numero, capacidad, estado, qr_token) VALUES
  ('1', 4, 'libre',     MD5(UUID())),
  ('2', 4, 'ocupada',   MD5(UUID())),
  ('3', 6, 'libre',     MD5(UUID())),
  ('4', 6, 'ocupada',   MD5(UUID())),
  ('5', 4, 'reservada', MD5(UUID())),
  ('6', 8, 'libre',     MD5(UUID())),
  ('7', 4, 'libre',     MD5(UUID())),
  ('8', 10,'libre',     MD5(UUID()));

INSERT INTO productos (categoria_id, nombre, precio, stock, stock_minimo) VALUES
  (1, 'Cerveza 620 ml',         12.00, 48, 12),
  (1, 'Cerveza 355 ml',          8.00,  6, 12),
  (2, 'Pisco 750 ml',           65.00,  3,  4),
  (2, 'Ron 750 ml',             70.00,  0,  3),
  (3, 'Pisco sour',             18.00, 30,  5),
  (4, 'Agua mineral',            4.00, 40, 10),
  (5, 'Piqueo mixto',           28.00, 15,  3);

INSERT INTO reservas (mesa_id, usuario_id, cliente, telefono, personas, fecha_hora, estado)
SELECT id, 1, 'Carla Quispe', '999111222', 6, NOW() + INTERVAL 3 HOUR, 'confirmada' FROM mesas WHERE numero = '5';

-- Cajero de prueba: usuario  cajero  /  contraseña  Cajero123
INSERT INTO usuarios (rol_id, nombre, usuario, password) VALUES
  (2, 'Rosa Huamán', 'cajero', '$2y$10$wYzjSA3eBpD1QeSWi2QN1u9VWgH.rT1Usg3PFZF3BBohSVG6X.NKm');
