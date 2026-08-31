<?php
<<<<<<< HEAD
require __DIR__.'/config/auth.php';requireLogin();require __DIR__.'/config/db.php';require __DIR__.'/config/layout.php';
$rol=$_SESSION['rol']??'';$sector=sectorUsuario($pdo);
if($rol==='admin'){$ops=$pdo->query('SELECT o.*,s.nombre sector FROM operaciones o JOIN sectores s ON s.id=o.sector_id ORDER BY s.orden,o.orden')->fetchAll();}
elseif($sector){$st=$pdo->prepare('SELECT o.*,s.nombre sector FROM operaciones o JOIN sectores s ON s.id=o.sector_id WHERE o.sector_id=? ORDER BY o.orden');$st->execute([$sector['id']]);$ops=$st->fetchAll();}else{$ops=[];}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><link rel="stylesheet" href="../style.css"></head><body><div class="app"><?php sidebar($pdo,'operaciones');?><main class="content"><header class="section-top"><div><p class="eyebrow">GESTIÓN OPERATIVA</p><h1>Operaciones</h1><p>Visualización según permisos del usuario.</p></div></header><section class="operation-grid"><?php if(!$ops):?><div class="empty">No hay operaciones.</div><?php endif;?><?php foreach($ops as $o):?><article class="operation-card"><div class="op-head"><span class="type-badge"><?=htmlspecialchars($o['sector'])?></span><span class="<?=$o['estado']==='activa'?'state-on':'state-off'?>"><?=htmlspecialchars(ucfirst($o['estado']))?></span></div><h3><?=htmlspecialchars($o['nombre'])?></h3><p><?=htmlspecialchars($o['descripcion']??'')?></p></article><?php endforeach;?></section></main></div></body></html>
=======
require __DIR__ . '/config/auth.php';
requireLogin();
require __DIR__ . '/config/db.php';

$rol=$_SESSION['rol'] ?? '';
$esAdmin=$rol==='admin';
$sectorUsuario=$esAdmin?null:obtenerSectorUsuario($pdo);
$sectorId=$sectorUsuario['id'] ?? null;
$sectorNombre=$esAdmin?'Todos los sectores':($sectorUsuario['nombre'] ?? 'Sin asignar');

if($esAdmin){
    $operaciones=$pdo->query(
        'SELECT o.*,s.nombre AS sector FROM operaciones o
         INNER JOIN sectores s ON s.id=o.sector_id
         ORDER BY s.orden,o.orden,o.nombre'
    )->fetchAll();
}elseif($sectorId){
    $stmt=$pdo->prepare(
        'SELECT o.*,s.nombre AS sector FROM operaciones o
         INNER JOIN sectores s ON s.id=o.sector_id
         WHERE o.sector_id=? ORDER BY o.orden,o.nombre'
    );
    $stmt->execute([$sectorId]);
    $operaciones=$stmt->fetchAll();
}else{$operaciones=[];}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Operaciones | NASER SGI</title><link rel="stylesheet" href="../style.css">
<style>
.ops-hero{padding:32px;margin-bottom:24px;background:linear-gradient(135deg,#fff,var(--verde-fondo));border:1px solid var(--gris-borde);border-left:5px solid var(--verde-fuerte);border-radius:14px;box-shadow:var(--sombra-suave)}
.ops-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.ops-stat{padding:18px;background:#fff;border:1px solid var(--gris-borde);border-radius:12px;box-shadow:var(--sombra-suave)}
.ops-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.op-card{padding:20px;background:#fff;border:1px solid var(--gris-borde);border-left:4px solid var(--verde-fuerte);border-radius:12px;box-shadow:var(--sombra-suave)}
.op-card p{color:var(--texto-secundario);font-size:13px}.op-sector{margin-top:12px;color:var(--verde-oscuro);font-size:11px;font-weight:800}
@media(max-width:800px){.ops-grid,.ops-summary{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="standalone">
<a class="back-link" href="dashboard.php">← Volver al panel</a>
<section class="ops-hero"><p class="eyebrow">GESTIÓN OPERATIVA</p><h1>Operaciones</h1><p class="muted">Visualización según permisos del usuario · <?=$sectorNombre?></p></section>
<section class="ops-summary">
<div class="ops-stat"><small>SECTOR</small><h3><?=htmlspecialchars($sectorNombre)?></h3></div>
<div class="ops-stat"><small>OPERACIONES</small><h3><?=count($operaciones)?></h3></div>
<div class="ops-stat"><small>ACCESO</small><h3><?=htmlspecialchars($rol==='admin'?'Gerencia':ucfirst($rol))?></h3></div>
</section>
<div class="ops-grid">
<?php if(!$operaciones): ?><div class="empty-state">No hay operaciones cargadas para este sector.</div><?php endif; ?>
<?php foreach($operaciones as $op): ?>
<article class="op-card"><span class="badge <?=$op['estado']==='activa'?'badge-success':'badge-danger'?>"><?=htmlspecialchars(ucfirst($op['estado']))?></span><h3><?=htmlspecialchars($op['nombre'])?></h3><p><?=htmlspecialchars($op['descripcion'] ?: 'Sin descripción cargada.')?></p><div class="op-sector"><?=htmlspecialchars($op['sector'])?></div></article>
<?php endforeach; ?>
</div>
</main>
</body>
</html>
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
