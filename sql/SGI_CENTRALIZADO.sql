USE naser_sgi_prueba;
SET @sgi=(SELECT id FROM sectores WHERE slug='sgi' LIMIT 1);

INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'01 - Políticas',NULL,1 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='01 - Políticas');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'02 - Manuales',NULL,2 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='02 - Manuales');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'03 - Procedimientos',NULL,3 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='03 - Procedimientos');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'04 - Instructivos',NULL,4 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='04 - Instructivos');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'05 - Formularios',NULL,5 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='05 - Formularios');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'06 - Registros',NULL,6 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='06 - Registros');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'07 - Checklists',NULL,7 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='07 - Checklists');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'08 - Certificados',NULL,8 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='08 - Certificados');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'09 - Documentación obsoleta',NULL,9 WHERE @sgi IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND nombre='09 - Documentación obsoleta');

SET @proc=(SELECT id FROM carpetas WHERE sector_id=@sgi AND nombre='03 - Procedimientos' LIMIT 1);
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'Operaciones',@proc,1 WHERE @proc IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND carpeta_padre_id=@proc AND nombre='Operaciones');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'HSEQ',@proc,2 WHERE @proc IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND carpeta_padre_id=@proc AND nombre='HSEQ');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'Mantenimiento',@proc,3 WHERE @proc IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND carpeta_padre_id=@proc AND nombre='Mantenimiento');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'RRHH',@proc,4 WHERE @proc IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND carpeta_padre_id=@proc AND nombre='RRHH');
INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden)
SELECT @sgi,'Compras',@proc,5 WHERE @proc IS NOT NULL AND NOT EXISTS(SELECT 1 FROM carpetas WHERE sector_id=@sgi AND carpeta_padre_id=@proc AND nombre='Compras');
