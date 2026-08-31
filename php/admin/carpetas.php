<?php
require __DIR__.'/../config/auth.php';
requireLogin();
require __DIR__.'/../config/db.php';
requireDocumentManager($pdo);
require __DIR__.'/../config/layout.php';

$msg='';
$err='';
$sid=(int)$pdo->query("SELECT id FROM sectores WHERE slug='sgi' LIMIT 1")->fetchColumn();

function carpetaPerteneceASGI(PDO $pdo, int $id, int $sid): bool {
    $st=$pdo->prepare('SELECT 1 FROM carpetas WHERE id=? AND sector_id=? LIMIT 1');
    $st->execute([$id,$sid]);
    return (bool)$st->fetchColumn();
}

function hayCiclo(PDO $pdo, int $carpetaId, ?int $nuevoPadre): bool {
    if ($nuevoPadre===null) return false;
    if ($nuevoPadre===$carpetaId) return true;

    $actual=$nuevoPadre;
    $limite=0;

    while($actual && $limite<100){
        if($actual===$carpetaId) return true;

        $st=$pdo->prepare('SELECT carpeta_padre_id FROM carpetas WHERE id=? LIMIT 1');
        $st->execute([$actual]);
        $actual=$st->fetchColumn();
        $actual=$actual!==false && $actual!==null ? (int)$actual : null;
        $limite++;
    }

    return false;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $accion=$_POST['accion']??'crear';

    if($accion==='crear'){
        $nombre=trim($_POST['nombre']??'');
        $padre=!empty($_POST['padre'])?(int)$_POST['padre']:null;

        if($nombre===''){
            $err='Ingresá un nombre para la carpeta.';
        }elseif($padre!==null && !carpetaPerteneceASGI($pdo,$padre,$sid)){
            $err='La carpeta padre no pertenece al SGI.';
        }else{
            $st=$pdo->prepare('INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden,activa) VALUES(?,?,?,?,1)');
            $st->execute([$sid,$nombre,$padre,999]);
            $msg='Carpeta creada correctamente.';
        }
    }

    if($accion==='editar'){
        $id=(int)($_POST['id']??0);
        $nombre=trim($_POST['nombre']??'');
        $padre=isset($_POST['padre']) && $_POST['padre']!=='' ? (int)$_POST['padre'] : null;

        if(!$id || !carpetaPerteneceASGI($pdo,$id,$sid)){
            $err='Carpeta inválida.';
        }elseif($nombre===''){
            $err='El nombre no puede quedar vacío.';
        }elseif($padre!==null && !carpetaPerteneceASGI($pdo,$padre,$sid)){
            $err='La carpeta destino no pertenece al SGI.';
        }elseif(hayCiclo($pdo,$id,$padre)){
            $err='No podés mover una carpeta dentro de sí misma o de una de sus subcarpetas.';
        }else{
            $st=$pdo->prepare('UPDATE carpetas SET nombre=?,carpeta_padre_id=? WHERE id=? AND sector_id=?');
            $st->execute([$nombre,$padre,$id,$sid]);
            $msg='Carpeta actualizada correctamente.';
        }
    }

    if($accion==='eliminar'){
        $id=(int)($_POST['id']??0);

        if(!$id || !carpetaPerteneceASGI($pdo,$id,$sid)){
            $err='Carpeta inválida.';
        }else{
            try{
                $pdo->beginTransaction();

                // Reunir carpeta seleccionada + todas sus subcarpetas
                $ids=[$id];
                $pendientes=[$id];

                while($pendientes){
                    $actual=array_shift($pendientes);

                    $st=$pdo->prepare(
                        'SELECT id
                         FROM carpetas
                         WHERE carpeta_padre_id=?
                         AND sector_id=?'
                    );
                    $st->execute([$actual,$sid]);

                    foreach($st->fetchAll(PDO::FETCH_COLUMN) as $hijo){
                        $hijo=(int)$hijo;

                        if(!in_array($hijo,$ids,true)){
                            $ids[]=$hijo;
                            $pendientes[]=$hijo;
                        }
                    }
                }

                $placeholders=implode(',',array_fill(0,count($ids),'?'));

                // Buscar archivos físicos antes de borrar registros
                $st=$pdo->prepare(
                    "SELECT id,archivo,titulo
                     FROM documentos
                     WHERE carpeta_id IN ($placeholders)"
                );
                $st->execute($ids);
                $documentos=$st->fetchAll();

                // Borrar documentos de base
                if($documentos){
                    $docIds=array_map(
                        static fn($d)=>(int)$d['id'],
                        $documentos
                    );

                    $docPlaceholders=implode(
                        ',',
                        array_fill(0,count($docIds),'?')
                    );

                    // Favoritos, si existe la tabla
                    try{
                        $st=$pdo->prepare(
                            "DELETE FROM favoritos
                             WHERE documento_id IN ($docPlaceholders)"
                        );
                        $st->execute($docIds);
                    }catch(Throwable $e){}

                    // Historial de versiones, si existe
                    try{
                        $st=$pdo->prepare(
                            "DELETE FROM documento_versiones
                             WHERE documento_id IN ($docPlaceholders)"
                        );
                        $st->execute($docIds);
                    }catch(Throwable $e){}

                    $st=$pdo->prepare(
                        "DELETE FROM documentos
                         WHERE id IN ($docPlaceholders)"
                    );
                    $st->execute($docIds);
                }

                // Borrar carpetas de abajo hacia arriba
                foreach(array_reverse($ids) as $carpetaId){
                    $st=$pdo->prepare(
                        'DELETE FROM carpetas
                         WHERE id=?
                         AND sector_id=?'
                    );
                    $st->execute([$carpetaId,$sid]);
                }

                $pdo->commit();

                // Borrar archivos físicos después del commit.
                // Si alguno ya no existe, simplemente se ignora.
                $up=dirname(__DIR__,2).'/uploads/';

                foreach($documentos as $doc){
                    if(empty($doc['archivo'])) continue;

                    $rutaFisica=$up.basename($doc['archivo']);

                    if(is_file($rutaFisica)){
                        @unlink($rutaFisica);
                    }
                }

                $msg='Carpeta eliminada junto con '
                    .max(0,count($ids)-1)
                    .' subcarpeta(s) y '
                    .count($documentos)
                    .' documento(s).';

            }catch(Throwable $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $err='No se pudo eliminar la carpeta completa. '
                    .$e->getMessage();
            }
        }
    }
}

$st=$pdo->prepare(
    'SELECT c.*,
            p.nombre padre,
            (SELECT COUNT(*) FROM documentos d WHERE d.carpeta_id=c.id AND d.activo=1) documentos,
            (SELECT COUNT(*) FROM carpetas h WHERE h.carpeta_padre_id=c.id AND h.activa=1) subcarpetas
     FROM carpetas c
     LEFT JOIN carpetas p ON p.id=c.carpeta_padre_id
     WHERE c.sector_id=? AND c.activa=1
     ORDER BY c.orden,c.nombre'
);
$st->execute([$sid]);
$rows=$st->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Carpetas SGI | NASER</title>
<link rel="stylesheet" href="../../style.css">
<style>
.folder-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.folder-name{display:flex;align-items:center;gap:8px;font-weight:700}
.folder-meta{color:#72817c;font-size:12px;margin-top:4px}
.folder-actions{display:flex;gap:7px;flex-wrap:wrap}
.folder-edit{margin-top:10px;padding:14px;border:1px solid #dce7e3;border-radius:12px;background:#f8fbfa}
.folder-edit .form-row{grid-template-columns:1fr 1fr auto}
.folder-count{display:inline-block;padding:5px 8px;border-radius:999px;background:#eef7f2;color:#26704e;font-size:11px;font-weight:700}
.folder-danger-note{margin-top:12px;padding:11px 12px;border-radius:10px;background:#fff7ed;color:#8a4b10;font-size:12px}
@media(max-width:850px){.folder-edit .form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="app">
<?php sidebar($pdo,'carpetas');?>
<main class="content">

<header class="section-top">
<div>
<p class="eyebrow">SGI · GESTIÓN DOCUMENTAL</p>
<h1>Carpetas y subcarpetas</h1>
<p>Creá, renombrá, mové y eliminá carpetas de forma segura.</p>
</div>
<a class="btn secondary" href="documentos.php">Cargar documentos</a>
</header>

<?php if($msg):?>
<div class="alert success"><?=htmlspecialchars($msg)?></div>
<?php endif;?>

<?php if($err):?>
<div class="alert error"><?=htmlspecialchars($err)?></div>
<?php endif;?>

<div class="admin-layout">

<section class="form-panel">
<p class="eyebrow">NUEVA CARPETA</p>
<h2>Crear carpeta</h2>

<form method="post" class="form-grid">
<input type="hidden" name="accion" value="crear">

<label>
Nombre
<input name="nombre" placeholder="Ej: 10 - Matrices" required>
</label>

<label>
Dentro de
<select name="padre">
<option value="">Raíz SGI</option>
<?php foreach($rows as $r):?>
<option value="<?=$r['id']?>">
<?=htmlspecialchars(($r['padre']?$r['padre'].' / ':'').$r['nombre'])?>
</option>
<?php endforeach;?>
</select>
</label>

<button class="btn primary">Crear carpeta</button>
</form>

<div class="folder-danger-note">
Podés eliminar una carpeta aunque tenga contenido. Al hacerlo se borrarán también todas sus subcarpetas y documentos.
</div>
</section>

<section class="table-panel">
<p class="eyebrow">ESTRUCTURA DOCUMENTAL</p>
<h2>Carpetas SGI</h2>

<div class="table-wrap">
<table>
<thead>
<tr>
<th>Carpeta</th>
<th>Ubicación</th>
<th>Contenido</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>

<?php foreach($rows as $r):?>
<tr>
<td>
<div class="folder-name">📁 <?=htmlspecialchars($r['nombre'])?></div>
</td>

<td><?=htmlspecialchars($r['padre']??'Raíz SGI')?></td>

<td>
<span class="folder-count"><?=$r['documentos']?> doc.</span>
<span class="folder-count"><?=$r['subcarpetas']?> subcarp.</span>
</td>

<td>
<div class="folder-actions">

<details>
<summary class="btn secondary" style="cursor:pointer">Editar / mover</summary>

<div class="folder-edit">
<form method="post" class="form-row">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?=$r['id']?>">

<input name="nombre" value="<?=htmlspecialchars($r['nombre'])?>" required>

<select name="padre">
<option value="">Raíz SGI</option>
<?php foreach($rows as $p):?>
<?php if((int)$p['id']===(int)$r['id']) continue;?>
<option value="<?=$p['id']?>" <?=((int)($r['carpeta_padre_id']??0)===(int)$p['id'])?'selected':''?>>
<?=htmlspecialchars(($p['padre']?$p['padre'].' / ':'').$p['nombre'])?>
</option>
<?php endforeach;?>
</select>

<button class="btn primary">Guardar</button>
</form>
</div>
</details>

<form method="post" onsubmit="return confirm('¿Seguro que querés eliminar esta carpeta? También se borrarán TODAS sus subcarpetas y documentos. Esta acción no se puede deshacer.');">
<input type="hidden" name="accion" value="eliminar">
<input type="hidden" name="id" value="<?=$r['id']?>">
<button class="btn danger">Eliminar</button>
</form>

</div>
</td>
</tr>
<?php endforeach;?>

<?php if(!$rows):?>
<tr><td colspan="4">Todavía no hay carpetas creadas.</td></tr>
<?php endif;?>

</tbody>
</table>
</div>
</section>

</div>
</main>
</div>
</body>
</html>
