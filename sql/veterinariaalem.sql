CREATE DATABASE IF NOT EXISTS veterinariaalem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE veterinariaalem;

DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS pedido_items;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS historial;
DROP TABLE IF EXISTS citas;
DROP TABLE IF EXISTS mascotas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS empleados;

CREATE TABLE clientes(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 cedula VARCHAR(30),
 telefono VARCHAR(30),
 email VARCHAR(150),
 direccion VARCHAR(200)
);

CREATE TABLE usuarios(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 rol ENUM('admin','empleado','cliente') NOT NULL DEFAULT 'cliente',
 activo TINYINT(1) NOT NULL DEFAULT 1,
 id_cliente INT NULL,
 email_verificado TINYINT(1) NOT NULL DEFAULT 0,
 token_verificacion CHAR(64) NULL,
 token_expira DATETIME NULL,
 verificacion_enviada DATETIME NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(id_cliente) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE empleados(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_usuario INT NULL,
 nombre VARCHAR(100) NOT NULL,
 especialidad VARCHAR(100),
 puestos VARCHAR(100),
 horario VARCHAR(100),
 salario DECIMAL(10,2) NULL,
 rango VARCHAR(50),
 telefono VARCHAR(30),
 email VARCHAR(150)
);

CREATE TABLE servicios(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 descripcion TEXT,
 precio DECIMAL(10,2) DEFAULT 0,
 tipo VARCHAR(80),
 duracion VARCHAR(50)
);

CREATE TABLE proveedores(
 id INT AUTO_INCREMENT PRIMARY KEY,
 codigo_proveedor VARCHAR(30) NOT NULL UNIQUE,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(150),
 direccion VARCHAR(200),
 telefono VARCHAR(30),
 fecha_registro DATE DEFAULT (CURRENT_DATE)
);

CREATE TABLE productos(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_proveedor INT NULL,
 nombre VARCHAR(100) NOT NULL,
 categoria VARCHAR(100),
 precio DECIMAL(10,2) DEFAULT 0,
 stock INT DEFAULT 0,
 FOREIGN KEY(id_proveedor) REFERENCES proveedores(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE mascotas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 especie VARCHAR(50) NOT NULL,
 raza VARCHAR(80),
 sexo VARCHAR(20),
 fecha_nacimiento DATE NULL,
 peso DECIMAL(6,2) NULL,
 id_cliente INT NOT NULL,
 FOREIGN KEY(id_cliente) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE citas(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_cliente INT NOT NULL,
 id_mascota INT NOT NULL,
 id_empleado INT NOT NULL,
 id_servicio INT NOT NULL,
 fecha DATE NOT NULL,
 hora TIME NOT NULL,
 motivo VARCHAR(255),
 estado VARCHAR(30) DEFAULT 'Pendiente',
 FOREIGN KEY(id_cliente) REFERENCES clientes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 FOREIGN KEY(id_mascota) REFERENCES mascotas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 FOREIGN KEY(id_empleado) REFERENCES empleados(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 FOREIGN KEY(id_servicio) REFERENCES servicios(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE historial(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_mascota INT NOT NULL,
 id_empleado INT NOT NULL,
 fecha DATE NOT NULL,
 diagnostico TEXT NOT NULL,
 tratamiento TEXT,
 observaciones TEXT,
 vacunas TEXT,
 FOREIGN KEY(id_mascota) REFERENCES mascotas(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 FOREIGN KEY(id_empleado) REFERENCES empleados(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE pedidos(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_usuario INT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 total DECIMAL(10,2) NOT NULL,
 metodo_pago VARCHAR(40) NOT NULL,
 estado VARCHAR(30) NOT NULL DEFAULT 'Pagado',
 FOREIGN KEY(id_usuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE pedido_items(
 id INT AUTO_INCREMENT PRIMARY KEY,
 id_pedido INT NOT NULL,
 id_producto INT NULL,
 nombre VARCHAR(100) NOT NULL,
 precio DECIMAL(10,2) NOT NULL,
 cantidad INT NOT NULL,
 FOREIGN KEY(id_pedido) REFERENCES pedidos(id) ON UPDATE CASCADE ON DELETE CASCADE,
 FOREIGN KEY(id_producto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE SET NULL
);

-- Factura electrónica: guarda una copia de los datos del cliente al momento
-- de la compra, para que no cambie si el cliente modifica su perfil después.
CREATE TABLE facturas(
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

-- Usuario administrador de ejemplo.
-- Contraseña: admin123
INSERT INTO clientes(nombre,cedula,telefono,email,direccion) VALUES
('Juan Pérez','12345678','099000111','juan@email.com','Centro'),
('Ana Gómez','23456789','099000222','ana@email.com','Maldonado');

INSERT INTO usuarios(nombre,email,password,rol,activo,id_cliente,email_verificado) VALUES
('Administrador','admin@veterinariaalem.com','$2y$12$qR8UrTxzkARMJ41bXn.9Tutui/Tyxgd5G/rmGZspiBbyAee0PhrAi','admin',1,NULL,1),
('Cliente Demo','cliente@veterinariaalem.com','$2y$12$xymKRzC.abYAyJ65caf78O0tcH/UGdKWW0B2HwP8GGH.Qmn3WlrIe','cliente',1,1,1),
-- Empleado de ejemplo (contraseña: empleado123), vinculado a la Dra. Laura Silva.
('Dra. Laura Silva','laura@veterinariaalem.com','$2y$10$FVsq2pkw1oHmZ2fESsCvFOjsT/NJsarMGWZs0ADWzv.8TXXns3Jm.','empleado',1,NULL,1);

INSERT INTO empleados(id_usuario,nombre,especialidad,telefono,email) VALUES
((SELECT id FROM usuarios WHERE email='laura@veterinariaalem.com'),'Dra. Laura Silva','Medicina general','099111111','laura@veterinariaalem.com'),
(NULL,'Dr. Martín Rodríguez','Cirugía','099222222','martin@veterinariaalem.com');

INSERT INTO servicios(nombre,descripcion,precio) VALUES
('Consulta','Consulta veterinaria general',800),
('Vacunación','Aplicación de vacunas',600),
('Baño y peluquería','Servicio de higiene y estética',700);

INSERT INTO proveedores(codigo_proveedor,nombre,correo,direccion,telefono,fecha_registro) VALUES
('PROV-001','Distribuidora Mascotas','ventas@distribuidoramascotas.com','Maldonado','099333444','2026-10-01'),
('PROV-002','Veterinaria Insumos','contacto@vetinsumos.com','Punta del Este','099555666','2026-10-01');

INSERT INTO productos(nombre,categoria,precio,stock,id_proveedor) VALUES
('Alimento para perro','Alimentos',1200,15,1),
('Shampoo para mascotas','Higiene',450,10,2),
('Antiparasitario','Medicamentos',550,8,2);

INSERT INTO mascotas(nombre,especie,raza,sexo,fecha_nacimiento,peso,id_cliente) VALUES
('Firulais','Perro','Mestizo','Macho','2021-04-10',18.5,1),
('Michi','Gato','Común europeo','Hembra','2022-08-15',4.2,2);

INSERT INTO citas(id_cliente,id_mascota,id_empleado,id_servicio,fecha,hora,motivo,estado) VALUES
(1,1,1,1,'2026-09-15','10:00:00','Control general','Pendiente'),
(2,2,2,2,'2026-09-16','15:30:00','Vacuna anual','Confirmado');

INSERT INTO historial(id_mascota,id_empleado,fecha,diagnostico,tratamiento,observaciones) VALUES
(1,1,'2026-09-01','Buen estado general','Ninguno','Control de rutina'),
(2,2,'2026-09-02','Necesita vacunación','Aplicar vacuna correspondiente','Sin complicaciones');

-- IMPORTANTE:
-- El hash anterior corresponde al ejemplo didáctico de contraseña admin123/cliente123.
