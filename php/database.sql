CREATE DATABASE IF NOT EXISTS naser_sgi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE naser_sgi;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS usuario_sector;
DROP TABLE IF EXISTS sectores;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sectores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE usuario_sector (
  usuario_id INT NOT NULL,
  sector_id INT NOT NULL,
  PRIMARY KEY (usuario_id,sector_id),
  CONSTRAINT fk_us_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_us_sector FOREIGN KEY (sector_id) REFERENCES sectores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sector_id INT NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  tipo ENUM('documentacion','procedimiento') NOT NULL,
  archivo VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_actualizacion DATE NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_sector FOREIGN KEY (sector_id) REFERENCES sectores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO sectores(nombre,slug,orden) VALUES
('Seguridad','seguridad',1),
('Mantenimiento','mantenimiento',2),
('Operaciones','operaciones',3),
('Recursos Humanos','rrhh',4),
('Finanzas','finanzas',5),
('Compras','compras',6),
('Gerencia','gerencia',7);

-- Usuario inicial: admin@naser.com / admin123
INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES
('Administrador','admin@naser.com','$2y$12$EI2loJVeOdIC3I8WhX/15OZwZG5/jgVp44BUSFFlt0NWVNsWwSsYK','admin',1);
