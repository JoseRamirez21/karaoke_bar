-- =====================================================================
--  SISTEMA KARAOKE BAR  -  BASE DE DATOS COMPLETA
--  MySQL / MariaDB (XAMPP)   |   Importar desde phpMyAdmin > Importar
--
--  CONTENIDO
--    1. Roles y usuarios            (roles, usuarios)
--    2. Productos, stock y combos   (categorias, productos, movimientos_stock, combos, combo_productos)
--    3. Mesas y cuentas              (mesas, sesiones_mesa)
--    4. Caja, ventas y pagos        (metodos_pago, turnos_caja, ventas, detalle_venta)
--    5. Configuracion (configuracion)
--    6. Vistas                      (v_stock_bajo, v_resumen_turno)
--    7. Datos iniciales             (roles, metodos de pago, usuario admin, categorias, ajustes)
--    8. DATOS DE EJEMPLO (opcional) (mesas, productos, combos y un cajero de prueba)
--
--  USUARIOS:   admin  /  Admin123*        (cambiar al primer ingreso)
--              cajero /  Cajero123        (solo de ejemplo: borrarlo antes de usar de verdad)
--
--  SE PUEDE IMPORTAR MAS DE UNA VEZ: no da error ni duplica los datos.
--  Para EMPEZAR DE CERO borra primero la base "karaoke_club" en phpMyAdmin
--  (pestana Operaciones > Eliminar la base de datos) y vuelve a importar.
--
--  Si NO quieres los datos de ejemplo, borra todo desde la linea
--  "8. DATOS DE EJEMPLO" hasta el final del archivo antes de importar.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-05:00';   -- hora local del sistema

CREATE DATABASE IF NOT EXISTS karaoke_club
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE karaoke_club;

SET FOREIGN_KEY_CHECKS = 0;

-- Elimina objetos retirados del esquema.
DROP VIEW IF EXISTS v_cola;
DROP TABLE IF EXISTS cola_canciones;
DROP TABLE IF EXISTS bitacora;
DROP TABLE IF EXISTS reservas;

-- ---------------------------------------------------------------------
-- 1. ROLES Y USUARIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id      TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre  VARCHAR(30) NOT NULL UNIQUE          -- admin | cajero
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  rol_id      TINYINT UNSIGNED NOT NULL,
  nombre      VARCHAR(100) NOT NULL,
  usuario     VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,           -- password_hash() de PHP
  telefono    VARCHAR(20)  NULL,
  estado      TINYINT(1) NOT NULL DEFAULT 1,   -- 1 activo, 0 desactivado
  ultimo_login DATETIME NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PRODUCTOS, STOCK Y COMBOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id      INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre  VARCHAR(80) NOT NULL UNIQUE,
  estado  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  categoria_id  INT UNSIGNED NOT NULL,
  nombre        VARCHAR(120) NOT NULL,
  precio        DECIMAL(10,2) NOT NULL,
  stock         INT NOT NULL DEFAULT 0,
  stock_minimo  INT NOT NULL DEFAULT 5,        -- alerta de stock bajo
  imagen        VARCHAR(255) NULL,
  estado        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  INDEX idx_productos_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimientos_stock (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  producto_id INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NOT NULL,
  tipo        ENUM('entrada','salida','ajuste','venta','anulacion') NOT NULL,
  cantidad    INT NOT NULL,                    -- siempre positiva; el tipo indica el sentido
  stock_resultante INT NOT NULL,
  motivo      VARCHAR(200) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_mov_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
  INDEX idx_mov_fecha (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS combos (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre       VARCHAR(120) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  precio       DECIMAL(10,2) NOT NULL,
  vigente_desde DATE NULL,
  vigente_hasta DATE NULL,
  estado       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS combo_productos (
  combo_id     INT UNSIGNED NOT NULL,
  producto_id  INT UNSIGNED NOT NULL,
  cantidad     INT NOT NULL DEFAULT 1,
  PRIMARY KEY (combo_id, producto_id),
  CONSTRAINT fk_cp_combo    FOREIGN KEY (combo_id)    REFERENCES combos(id) ON DELETE CASCADE,
  CONSTRAINT fk_cp_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. MESAS Y SESIONES (cuenta de la mesa)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mesas (
  id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  numero     VARCHAR(20) NOT NULL UNIQUE,
  capacidad  TINYINT UNSIGNED NOT NULL DEFAULT 4,
  estado     ENUM('libre','ocupada','inactiva') NOT NULL DEFAULT 'libre'
) ENGINE=InnoDB;

-- Limpia el estado de reservas de bases instaladas anteriormente.
UPDATE mesas SET estado = 'libre' WHERE estado = 'reservada';
ALTER TABLE mesas MODIFY estado ENUM('libre','ocupada','inactiva') NOT NULL DEFAULT 'libre';

-- Retira la columna QR de instalaciones anteriores.
SET @tiene_qr = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mesas' AND COLUMN_NAME = 'qr_token');
SET @sql_qr = IF(@tiene_qr > 0, 'ALTER TABLE mesas DROP COLUMN qr_token', 'SELECT 1');
PREPARE stmt_qr FROM @sql_qr;
EXECUTE stmt_qr;
DEALLOCATE PREPARE stmt_qr;

-- Cada vez que una mesa se abre se crea una sesion.
CREATE TABLE IF NOT EXISTS sesiones_mesa (
  id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  mesa_id         INT UNSIGNED NOT NULL,
  abierta_por     INT UNSIGNED NOT NULL,
  cerrada_por     INT UNSIGNED NULL,
  cliente         VARCHAR(100) NULL,           -- nombre opcional / referencia
  estado          ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  abierta_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cerrada_en      DATETIME NULL,
  CONSTRAINT fk_ses_mesa  FOREIGN KEY (mesa_id)     REFERENCES mesas(id),
  CONSTRAINT fk_ses_abre  FOREIGN KEY (abierta_por) REFERENCES usuarios(id),
  CONSTRAINT fk_ses_cierra FOREIGN KEY (cerrada_por) REFERENCES usuarios(id),
  INDEX idx_ses_estado (mesa_id, estado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. CAJA, VENTAS Y PAGOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS metodos_pago (
  id      TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre  VARCHAR(30) NOT NULL UNIQUE,
  es_efectivo TINYINT(1) NOT NULL DEFAULT 0,
  estado  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Cada trabajador abre su turno al empezar y lo cierra contando su caja.
CREATE TABLE IF NOT EXISTS turnos_caja (
  id                  INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id          INT UNSIGNED NOT NULL,
  monto_inicial       DECIMAL(10,2) NOT NULL DEFAULT 0,
  abierto_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cerrado_en          DATETIME NULL,
  efectivo_esperado   DECIMAL(10,2) NULL,      -- inicial + ventas en efectivo
  efectivo_contado    DECIMAL(10,2) NULL,      -- lo que el trabajador cuenta
  diferencia          DECIMAL(10,2) NULL,      -- contado - esperado
  observacion         VARCHAR(255) NULL,
  estado              ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  CONSTRAINT fk_turno_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  INDEX idx_turno_estado (usuario_id, estado)
) ENGINE=InnoDB;

-- Una venta = un pedido ya cobrado. Primero se paga, luego se entrega.
-- sesion_mesa_id puede ser NULL para ventas de mostrador (sin mesa).
CREATE TABLE IF NOT EXISTS ventas (
  id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  codigo           VARCHAR(20) NOT NULL UNIQUE,   -- numero de ticket, ej. T-000123
  sesion_mesa_id   INT UNSIGNED NULL,
  turno_id         INT UNSIGNED NOT NULL,
  usuario_id       INT UNSIGNED NOT NULL,         -- cajero que cobro
  metodo_pago_id   TINYINT UNSIGNED NOT NULL,
  total            DECIMAL(10,2) NOT NULL,
  estado           ENUM('pagada','entregada','anulada') NOT NULL DEFAULT 'pagada',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  entregada_en     DATETIME NULL,
  anulada_por      INT UNSIGNED NULL,
  anulada_en       DATETIME NULL,
  motivo_anulacion VARCHAR(200) NULL,
  CONSTRAINT fk_ven_sesion  FOREIGN KEY (sesion_mesa_id) REFERENCES sesiones_mesa(id),
  CONSTRAINT fk_ven_turno   FOREIGN KEY (turno_id)       REFERENCES turnos_caja(id),
  CONSTRAINT fk_ven_usuario FOREIGN KEY (usuario_id)     REFERENCES usuarios(id),
  CONSTRAINT fk_ven_metodo  FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id),
  CONSTRAINT fk_ven_anula   FOREIGN KEY (anulada_por)    REFERENCES usuarios(id),
  INDEX idx_ven_fecha (created_at),
  INDEX idx_ven_estado (estado)
) ENGINE=InnoDB;

-- Guarda el nombre y precio al momento de la venta (si luego cambia el
-- precio del producto, el historial no se altera).
CREATE TABLE IF NOT EXISTS detalle_venta (
  id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  venta_id         INT UNSIGNED NOT NULL,
  producto_id      INT UNSIGNED NULL,
  combo_id         INT UNSIGNED NULL,
  descripcion      VARCHAR(150) NOT NULL,
  cantidad         INT NOT NULL,
  precio_unitario  DECIMAL(10,2) NOT NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_det_venta    FOREIGN KEY (venta_id)    REFERENCES ventas(id) ON DELETE CASCADE,
  CONSTRAINT fk_det_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_det_combo    FOREIGN KEY (combo_id)    REFERENCES combos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. CONFIGURACION
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
  clave  VARCHAR(50) PRIMARY KEY,
  valor  VARCHAR(255) NOT NULL,
  descripcion VARCHAR(200) NULL
) ENGINE=InnoDB;

DELETE FROM configuracion WHERE clave = 'max_canciones_mesa';

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 6. VISTAS UTILES
-- ---------------------------------------------------------------------
-- Productos con stock bajo (alerta en el dashboard)
CREATE OR REPLACE VIEW v_stock_bajo AS
SELECT p.id, p.nombre, c.nombre AS categoria, p.stock, p.stock_minimo
FROM productos p
JOIN categorias c ON c.id = p.categoria_id
WHERE p.estado = 1 AND p.stock <= p.stock_minimo;

-- Resumen por turno y metodo de pago (base del cierre de caja)
CREATE OR REPLACE VIEW v_resumen_turno AS
SELECT v.turno_id, mp.nombre AS metodo_pago, mp.es_efectivo,
       COUNT(*) AS cantidad_ventas, SUM(v.total) AS total
FROM ventas v
JOIN metodos_pago mp ON mp.id = v.metodo_pago_id
WHERE v.estado <> 'anulada'
GROUP BY v.turno_id, mp.id;

-- ---------------------------------------------------------------------
-- 7. DATOS INICIALES
-- ---------------------------------------------------------------------
INSERT IGNORE INTO roles (id, nombre) VALUES (1,'admin'), (2,'cajero');

INSERT IGNORE INTO metodos_pago (nombre, es_efectivo) VALUES
  ('Efectivo',1), ('Yape',0), ('Plin',0), ('Tarjeta',0);

-- Contrasena: Admin123*   (hash bcrypt, compatible con password_verify)
INSERT IGNORE INTO usuarios (rol_id, nombre, usuario, password) VALUES
  (1, 'Administrador', 'admin', '$2y$10$ifKon//QTDqwCflK6iYABeuEsp3voiCuuq1BXX3TJSY6c2u1W11PW');

INSERT IGNORE INTO categorias (nombre) VALUES
  ('Cervezas'), ('Licores'), ('Cocteles'), ('Gaseosas y agua'), ('Piqueos');

INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
  ('nombre_local', 'Karaoke Club', 'Nombre que aparece en el sistema y tickets'),
  ('moneda', 'S/', 'Simbolo de moneda');


-- ---------------------------------------------------------------------
-- 8. DATOS DE EJEMPLO (opcional)  -  borra desde aqui hasta el final si no los quieres
-- ---------------------------------------------------------------------
-- Cajero de prueba: usuario cajero / contrasena Cajero123
INSERT IGNORE INTO usuarios (rol_id, nombre, usuario, password) VALUES
  (2, 'Rosa Huaman', 'cajero', '$2y$10$wYzjSA3eBpD1QeSWi2QN1u9VWgH.rT1Usg3PFZF3BBohSVG6X.NKm');

-- 8 mesas
INSERT IGNORE INTO mesas (numero, capacidad, estado) VALUES
  ('1', 4, 'libre'),
  ('2', 4, 'libre'),
  ('3', 6, 'libre'),
  ('4', 6, 'libre'),
  ('5', 4, 'libre'),
  ('6', 8, 'libre'),
  ('7', 4, 'libre'),
  ('8', 10, 'libre');

-- Productos de ejemplo (cambia nombres y precios por los tuyos)
INSERT INTO productos (categoria_id, nombre, precio, stock, stock_minimo)
SELECT c.id, d.nombre, d.precio, d.stock, d.minimo
  FROM (
        SELECT 'Cervezas' AS cat, 'Cerveza 620 ml' AS nombre, 12.00 AS precio, 48 AS stock, 12 AS minimo
        UNION ALL SELECT 'Cervezas',        'Cerveza 355 ml',  8.00,  6, 12
        UNION ALL SELECT 'Licores',         'Pisco 750 ml',   65.00,  3,  4
        UNION ALL SELECT 'Licores',         'Ron 750 ml',     70.00,  0,  3
        UNION ALL SELECT 'Cocteles',        'Pisco sour',     18.00, 30,  5
        UNION ALL SELECT 'Gaseosas y agua', 'Agua mineral',    4.00, 40, 10
        UNION ALL SELECT 'Piqueos',         'Piqueo mixto',   28.00, 15,  3
       ) d
  JOIN categorias c ON c.nombre = d.cat
 WHERE NOT EXISTS (SELECT 1 FROM productos p WHERE p.nombre = d.nombre);

-- Dos combos de ejemplo
INSERT INTO combos (nombre, descripcion, precio, estado)
SELECT 'Promo 6 cervezas', '6 cervezas de 620 ml a precio especial', 60.00, 1 FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM combos WHERE nombre = 'Promo 6 cervezas');
INSERT INTO combos (nombre, descripcion, precio, estado)
SELECT 'Botella + piqueo', 'Pisco de 750 ml con un piqueo mixto', 85.00, 1 FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM combos WHERE nombre = 'Botella + piqueo');

INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT c.id, p.id, 6 FROM combos c JOIN productos p ON p.nombre = 'Cerveza 620 ml'
 WHERE c.nombre = 'Promo 6 cervezas' AND NOT EXISTS (SELECT 1 FROM combo_productos x WHERE x.combo_id = c.id AND x.producto_id = p.id);
INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT c.id, p.id, 1 FROM combos c JOIN productos p ON p.nombre IN ('Pisco 750 ml', 'Piqueo mixto')
 WHERE c.nombre = 'Botella + piqueo' AND NOT EXISTS (SELECT 1 FROM combo_productos x WHERE x.combo_id = c.id AND x.producto_id = p.id);
