USE naser_sgi_prueba;

CREATE TABLE IF NOT EXISTS carpetas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 sector_id INT NOT NULL,
 nombre VARCHAR(160) NOT NULL,
 carpeta_padre_id INT NULL,
 orden INT NOT NULL DEFAULT 0,
 activa TINYINT(1) NOT NULL DEFAULT 1,
 FOREIGN KEY(sector_id) REFERENCES sectores(id) ON DELETE CASCADE,
 FOREIGN KEY(carpeta_padre_id) REFERENCES carpetas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documentos' AND COLUMN_NAME='carpeta_id');
SET @q=IF(@x=0,'ALTER TABLE documentos ADD COLUMN carpeta_id INT NULL AFTER sector_id','SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documentos' AND COLUMN_NAME='estado');
SET @q=IF(@x=0,'ALTER TABLE documentos ADD COLUMN estado ENUM("borrador","revision","aprobado","obsoleto") NOT NULL DEFAULT "aprobado"','SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documentos' AND COLUMN_NAME='version');
SET @q=IF(@x=0,'ALTER TABLE documentos ADD COLUMN version VARCHAR(20) NOT NULL DEFAULT "1.0"','SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='documentos' AND COLUMN_NAME='fecha_vencimiento');
SET @q=IF(@x=0,'ALTER TABLE documentos ADD COLUMN fecha_vencimiento DATE NULL','SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS documento_versiones (
 id INT AUTO_INCREMENT PRIMARY KEY,
 documento_id INT NOT NULL,
 version VARCHAR(20) NOT NULL,
 archivo VARCHAR(255) NOT NULL,
 fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 usuario_id INT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favoritos (
 usuario_id INT NOT NULL,
 documento_id INT NOT NULL,
 PRIMARY KEY(usuario_id,documento_id)
) ENGINE=InnoDB;

INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT s.id,'Documentación',NULL,1 FROM sectores s
WHERE NOT EXISTS(SELECT 1 FROM carpetas c WHERE c.sector_id=s.id AND c.nombre='Documentación' AND c.carpeta_padre_id IS NULL);

INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT s.id,'Procedimientos',NULL,2 FROM sectores s
WHERE NOT EXISTS(SELECT 1 FROM carpetas c WHERE c.sector_id=s.id AND c.nombre='Procedimientos' AND c.carpeta_padre_id IS NULL);

INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT s.id,'Formularios',NULL,3 FROM sectores s
WHERE NOT EXISTS(SELECT 1 FROM carpetas c WHERE c.sector_id=s.id AND c.nombre='Formularios' AND c.carpeta_padre_id IS NULL);

INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT s.id,'Checklists',NULL,4 FROM sectores s
WHERE NOT EXISTS(SELECT 1 FROM carpetas c WHERE c.sector_id=s.id AND c.nombre='Checklists' AND c.carpeta_padre_id IS NULL);
