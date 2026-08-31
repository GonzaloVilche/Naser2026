USE naser_sgi_prueba;
SET @sgi=(SELECT id FROM sectores WHERE slug='sgi' LIMIT 1);

INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar)
SELECT u.id,@sgi,1,CASE WHEN u.rol='admin' THEN 1 ELSE 0 END
FROM usuarios u
WHERE @sgi IS NOT NULL
AND NOT EXISTS(SELECT 1 FROM usuario_sector us WHERE us.usuario_id=u.id AND us.sector_id=@sgi);

UPDATE usuario_sector SET puede_ver=1 WHERE sector_id=@sgi;
