<?php
require __DIR__.'/config/auth.php';
requireLogin();
require __DIR__.'/config/db.php';
requireDocumentManager($pdo);

$msg='';
$err='';

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['confirmar']??'')==='SI_ELIMINAR_TODO'){
    try{
        $sid=(int)$pdo->query("SELECT id FROM sectores WHERE slug='sgi' LIMIT 1")->fetchColumn();
        if(!$sid) throw new Exception('No se encontró el sector SGI.');

        $pdo->beginTransaction();

        $st=$pdo->prepare('SELECT archivo FROM documentos WHERE sector_id=?');
        $st->execute([$sid]);
        $archivos=$st->fetchAll(PDO::FETCH_COLUMN);

        // Delete dependent rows if those optional tables exist.
        $st=$pdo->prepare('SELECT id FROM documentos WHERE sector_id=?');
        $st->execute([$sid]);
        $docIds=array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));

        if($docIds){
            $ph=implode(',',array_fill(0,count($docIds),'?'));
            foreach(['favoritos','documento_versiones'] as $tabla){
                try{
                    $q=$pdo->prepare("DELETE FROM $tabla WHERE documento_id IN ($ph)");
                    $q->execute($docIds);
                }catch(Throwable $e){}
            }
        }

        $st=$pdo->prepare('DELETE FROM documentos WHERE sector_id=?');
        $st->execute([$sid]);
        $docsBorrados=$st->rowCount();

        // Remove all SGI folders, children first.
        $st=$pdo->prepare('SELECT id FROM carpetas WHERE sector_id=?');
        $st->execute([$sid]);
        $folderIds=array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));

        // Null parents first to avoid FK ordering issues, then delete all.
        if($folderIds){
            $ph=implode(',',array_fill(0,count($folderIds),'?'));
            try{
                $q=$pdo->prepare("UPDATE carpetas SET carpeta_padre_id=NULL WHERE id IN ($ph)");
                $q->execute($folderIds);
            }catch(Throwable $e){}
        }

        $st=$pdo->prepare('DELETE FROM carpetas WHERE sector_id=?');
        $st->execute([$sid]);
        $carpetasBorradas=$st->rowCount();

        $pdo->commit();

        $up=dirname(__DIR__).'/uploads/';
        $fisicos=0;
        foreach($archivos as $archivo){
            if(!$archivo) continue;
            $ruta=$up.basename($archivo);
            if(is_file($ruta) && @unlink($ruta)) $fisicos++;
        }

        $msg="SGI vaciado correctamente: $docsBorrados documento(s), $carpetasBorradas carpeta(s) y $fisicos archivo(s) físicos eliminados.";
    }catch(Throwable $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        $err='No se pudo vaciar el SGI: '.$e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vaciar SGI | NASER</title>
<link rel="stylesheet" href="../style.css">
<style>
.box{max-width:760px;margin:70px auto;background:#fff;border:1px solid #dce7e3;border-radius:18px;padding:28px}
.warning{background:#fff4f2;border:1px solid #f0c6bf;padding:16px;border-radius:12px;margin:18px 0;line-height:1.55}
.actions{display:flex;gap:10px;flex-wrap:wrap}
</style>
</head>
<body>
<div class="box">
<p class="eyebrow">SGI · LIMPIEZA</p>
<h1>Eliminar toda la documentación</h1>
<p>Esta herramienta deja el SGI vacío para que vuelvas a cargar las carpetas y documentos manualmente.</p>

<?php if($msg):?><div class="alert success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert error"><?=htmlspecialchars($err)?></div><?php endif;?>

<div class="warning">
<strong>Atención:</strong> se eliminarán todos los documentos del SGI, todas sus carpetas y subcarpetas y los archivos correspondientes de <code>uploads</code>. No se modifican usuarios, sectores, permisos ni el resto del sistema.
</div>

<form method="post" onsubmit="return confirm('¿Confirmás que querés eliminar TODAS las carpetas y documentos del SGI? Esta acción no se puede deshacer.');">
<input type="hidden" name="confirmar" value="SI_ELIMINAR_TODO">
<div class="actions">
<button class="btn danger" type="submit">Eliminar todo el SGI</button>
<a class="btn secondary" href="sgi.php">Cancelar</a>
</div>
</form>
</div>
</body>
</html>