-- ============================================================
-- ACTUALIZACIÓN: ROL DE EMPLEADO
-- Para quien ya tiene la base importada (conserva los datos).
-- Usar en phpMyAdmin > SQL. Si es una instalación nueva NO hace
-- falta: importar veterinariaalem.sql directamente.
-- ============================================================
USE veterinariaalem;

-- 1) Nuevo valor de rol
ALTER TABLE usuarios
  MODIFY rol ENUM('admin','empleado','cliente') NOT NULL DEFAULT 'cliente';

-- 2) Vínculo empleado <-> usuario (por si la base es muy antigua)
ALTER TABLE empleados
  ADD COLUMN IF NOT EXISTS id_usuario INT NULL;

-- 3) Cuenta de ejemplo: Dra. Laura Silva (contraseña: empleado123)
INSERT IGNORE INTO usuarios(nombre,email,password,rol,activo,id_cliente,email_verificado)
VALUES ('Dra. Laura Silva','laura@veterinariaalem.com',
        '$2y$10$FVsq2pkw1oHmZ2fESsCvFOjsT/NJsarMGWZs0ADWzv.8TXXns3Jm.','empleado',1,NULL,1);

UPDATE empleados e
  JOIN usuarios u ON u.email = e.email AND u.rol = 'empleado'
  SET e.id_usuario = u.id
  WHERE e.id_usuario IS NULL;
