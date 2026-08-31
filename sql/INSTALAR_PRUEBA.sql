CREATE DATABASE IF NOT EXISTS naser_sgi_prueba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE naser_sgi_prueba;

CREATE TABLE sectores(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 slug VARCHAR(100) NOT NULL UNIQUE,
 orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE usuarios(
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 email VARCHAR(160) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 rol ENUM('admin','supervisor','ventas','operador','usuario') NOT NULL DEFAULT 'operador',
 activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE usuario_sector(
 usuario_id INT NOT NULL,
 sector_id INT NOT NULL,
 puede_ver TINYINT(1) NOT NULL DEFAULT 1,
 puede_editar TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY(usuario_id,sector_id),
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY(sector_id) REFERENCES sectores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE documentos(
 id INT AUTO_INCREMENT PRIMARY KEY,
 sector_id INT NOT NULL,
 titulo VARCHAR(180) NOT NULL,
 descripcion TEXT NULL,
 tipo ENUM('documentacion','procedimiento') NOT NULL DEFAULT 'documentacion',
 archivo VARCHAR(255) NULL,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 fecha_actualizacion DATE NOT NULL,
 FOREIGN KEY(sector_id) REFERENCES sectores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE operaciones(
 id INT AUTO_INCREMENT PRIMARY KEY,
 sector_id INT NOT NULL,
 nombre VARCHAR(160) NOT NULL,
 descripcion TEXT NULL,
 estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
 orden INT NOT NULL DEFAULT 0,
 FOREIGN KEY(sector_id) REFERENCES sectores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE actividad(
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NULL,
 accion VARCHAR(80) NOT NULL,
 detalle VARCHAR(255) NULL,
 fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO sectores(nombre,slug,orden) VALUES
('HSEQ','hseq',1),
('Mantenimiento','mantenimiento',2),
('Operaciones','operaciones',3),
('Recursos Humanos','rrhh',4),
('Finanzas','finanzas',5),
('Compras','compras',6),
('Ventas','ventas',7),
('Gerencia','gerencia',8),
('SGI','sgi',9);

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Administrador General','admin@naser.test','$2y$12$D3Qt6wZ60QGUZZXEm.vNM.Yq6/ATmUM7ORuuHLWrA38wyMFDvEFD6','admin',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores;

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor HSEQ','hseq@naser.test','$2y$12$p2uwFl4yo2B6vKoWdQc0J.L2MUftO4HFx5jR8ZbL7YmcH4nv.Ky1W','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='hseq';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor Mantenimiento','mantenimiento@naser.test','$2y$12$JLsTiWIvmlQjKcCyOECveed0gom/dKue3MSI5NoZubaeZlptDT/k2','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='mantenimiento';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor Operaciones','operaciones@naser.test','$2y$12$5AM1ux1bWD36L5mQzpeg9.WSkmqM2YSfLAnec9Td/Lb7I4WSTd7I2','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='operaciones';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor RRHH','rrhh@naser.test','$2y$12$oCjNoIrHZAnSbevzv/qF3OuxDTlsQJyEv7CC3IlXHf.vOUEQzg7/O','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='rrhh';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor Finanzas','finanzas@naser.test','$2y$12$8E.j0feeH9nN8ScVPYLDfOLU.UShdxckepx9Y8VRQLn55R4655sF.','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='finanzas';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor Compras','compras@naser.test','$2y$12$f5I8BGB3WJdVrMf1NbwMdu281.kKzb3Uf2oJheC7/bL7kEW5H1h76','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='compras';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor Ventas','ventas@naser.test','$2y$12$otNsYvFYqfbgwkNhQo/kOOBb4Spf1IXbApWQSndliy5yq0peyn39O','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='ventas';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Supervisor SGI','sgi@naser.test','$2y$12$HC2RoWRqOA8wIkHzkCD2yuKXNn/dD6GSAIN4uVjCkZezpTyppu.Le','supervisor',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,1 FROM sectores WHERE slug='sgi';

INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES('Operador Prueba','operador@naser.test','$2y$12$DoDEReSO36Ms/EO3kjfKsurX0AHOg5OyA6DttXPW.ON3TSV5ryJrG','operador',1);
SET @uid=LAST_INSERT_ID();
INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar) SELECT @uid,id,1,0 FROM sectores WHERE slug='operaciones';

INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Inspección HSEQ','Operación de prueba para hseq.','activa',1 FROM sectores WHERE slug='hseq';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Permiso de trabajo','Operación de prueba para hseq.','activa',2 FROM sectores WHERE slug='hseq';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Mantenimiento preventivo','Operación de prueba para mantenimiento.','activa',1 FROM sectores WHERE slug='mantenimiento';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Control de unidades','Operación de prueba para mantenimiento.','activa',2 FROM sectores WHERE slug='mantenimiento';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Slickline','Operación de prueba para operaciones.','activa',1 FROM sectores WHERE slug='operaciones';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Well Testing','Operación de prueba para operaciones.','activa',2 FROM sectores WHERE slug='operaciones';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Flow Back','Operación de prueba para operaciones.','activa',3 FROM sectores WHERE slug='operaciones';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Inducción de personal','Operación de prueba para rrhh.','activa',1 FROM sectores WHERE slug='rrhh';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Control presupuestario','Operación de prueba para finanzas.','activa',1 FROM sectores WHERE slug='finanzas';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Solicitud de compra','Operación de prueba para compras.','activa',1 FROM sectores WHERE slug='compras';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Seguimiento comercial','Operación de prueba para ventas.','activa',1 FROM sectores WHERE slug='ventas';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Auditoría interna SGI','Operación de prueba para sgi.','activa',1 FROM sectores WHERE slug='sgi';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Control de indicadores','Operación de prueba para sgi.','activa',2 FROM sectores WHERE slug='sgi';
INSERT INTO operaciones(sector_id,nombre,descripcion,estado,orden) SELECT id,'Gestión de no conformidades','Operación de prueba para sgi.','activa',3 FROM sectores WHERE slug='sgi';