-- ============================================================
-- ACTUALIZACIÓN PARA QUIEN YA TIENE LA BASE ANTERIOR IMPORTADA
-- (conserva los datos existentes). Usar en phpMyAdmin > SQL.
-- Pensado para MariaDB (la que trae XAMPP).
-- Si es una instalación nueva, NO hace falta: importar
-- veterinariaalem.sql directamente.
-- ============================================================
USE veterinariaalem;

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS id_cliente INT NULL,
  ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS token_verificacion CHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS token_expira DATETIME NULL,
  ADD COLUMN IF NOT EXISTS verificacion_enviada DATETIME NULL;

-- Las cuentas que ya existían se consideran verificadas.
UPDATE usuarios SET email_verificado = 1;

-- El cliente de demostración queda asociado al dueño Juan Pérez.
UPDATE usuarios SET id_cliente = 1 WHERE email = 'cliente@veterinariaalem.com' AND id_cliente IS NULL;

ALTER TABLE usuarios
  ADD CONSTRAINT fk_usuarios_dueno FOREIGN KEY (id_cliente) REFERENCES clientes(id)
  ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS pedidos(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_usuario INT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 total DECIMAL(10,2) NOT NULL,
 metodo_pago VARCHAR(40) NOT NULL,
 estado VARCHAR(30) NOT NULL DEFAULT 'Pagado',
 FOREIGN KEY(id_usuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pedido_items(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_pedido INT NOT NULL,
 id_producto INT NULL,
 nombre VARCHAR(100) NOT NULL,
 precio DECIMAL(10,2) NOT NULL,
 cantidad INT NOT NULL,
 FOREIGN KEY(id_pedido) REFERENCES pedidos(id) ON UPDATE CASCADE ON DELETE CASCADE,
 FOREIGN KEY(id_producto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS facturas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 numero VARCHAR(20) NOT NULL UNIQUE,
 id_pedido INT NOT NULL,
 id_usuario INT NULL,
 cliente_nombre VARCHAR(100) NOT NULL,
 cliente_documento VARCHAR(30),
 cliente_email VARCHAR(150),
 cliente_direccion VARCHAR(200),
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 subtotal DECIMAL(10,2) NOT NULL,
 iva DECIMAL(10,2) NOT NULL,
 total DECIMAL(10,2) NOT NULL,
 metodo_pago VARCHAR(40) NOT NULL,
 FOREIGN KEY(id_pedido) REFERENCES pedidos(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 FOREIGN KEY(id_usuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL
);
