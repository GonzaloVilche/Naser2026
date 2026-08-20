CREATE DATABASE IF NOT EXISTS naser_auditoria;
USE naser_auditoria;

CREATE TABLE IF NOT EXISTS auditorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    auditor VARCHAR(255) NOT NULL,
    gerente VARCHAR(255) NULL,
    supervisor VARCHAR(255) NULL,
    medio VARCHAR(50) NULL,
    acompanantes TEXT NULL,
    cantidad INT NULL,
    herramientas TEXT NULL,
    equipos TEXT NULL,
    elementos_seguridad TEXT NULL, -- Aquí guardaremos los checkboxes seleccionados
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Tabla para registrar los mensajes de contacto de la página principal
CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    empresa VARCHAR(150) NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(50) NULL,
    servicio VARCHAR(100) NULL,
    mensaje TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para el nuevo formulario de Inspección HSE
CREATE TABLE IF NOT EXISTS inspecciones_hse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    inspector VARCHAR(255) NOT NULL, -- Cambió de auditor a inspector
    gerente VARCHAR(255) NULL,
    supervisor VARCHAR(255) NULL,
    medio VARCHAR(50) NULL,
    acompanantes TEXT NULL,
    cantidad INT NULL,
    herramientas TEXT NULL,
    equipos TEXT NULL,
    elementos_seguridad TEXT NULL, -- Aquí guardaremos los checkboxes seleccionados
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
