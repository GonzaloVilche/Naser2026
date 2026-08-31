<?php
require __DIR__.'/config/auth.php'; requireLogin();
require __DIR__.'/config/db.php'; require __DIR__.'/config/layout.php';

$st=$pdo->prepare("SELECT * FROM sectores WHERE slug='sgi' LIMIT 1"); $st->execute(); $sector=$st->fetch();
if(!$sector){ exit('Primero debe existir el sector SGI.'); }
$sid=(int)$sector['id'];

$total=(int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE activo=1")->fetchColumn();
$vig=(int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE activo=1 AND COALESCE(estado,'aprobado')='aprobado'")->fetchColumn();
$rev=(int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE activo=1 AND estado='revision'")->fetchColumn();
$obs=(int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE activo=1 AND estado='obsoleto'")->fetchColumn();

$st=$pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM documentos d WHERE d.carpeta_id=c.id AND d.activo=1) cantidad FROM carpetas c WHERE c.sector_id=? AND c.carpeta_padre_id IS NULL AND c.activa=1 ORDER BY c.orden,c.nombre");
$st->execute([$sid]); $folders=$st->fetchAll();

$st=$pdo->prepare("SELECT d.*,c.nombre carpeta FROM documentos d LEFT JOIN carpetas c ON c.id=d.carpeta_id WHERE d.sector_id=? AND d.activo=1 ORDER BY d.id DESC LIMIT 8");
$st->execute([$sid]); $docs=$st->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SGI | NASER</title><link rel="stylesheet" href="../style.css"></head>
<body><div class="app"><?php sidebar($pdo,'sgi');?><main class="content">
<header class="section-top sgi-clean-header">
<div>
<p class="eyebrow">SISTEMA DE GESTIÓN INTEGRADO</p>
<h1>Gestión documental</h1>
<p>Documentación, procedimientos, registros y carpetas del SGI.</p>
</div>
</header>

<section class="sgi-stats">
<div><strong><?=$total?></strong><span>Documentos</span></div><div><strong><?=count($folders)?></strong><span>Carpetas principales</span></div><div><strong><?=$vig?></strong><span>Vigentes</span></div><div><strong><?=$rev?></strong><span>En revisión</span></div><div><strong><?=$obs?></strong><span>Obsoletos</span></div>
</section>

<header class="section-top compact"><div><p class="eyebrow">GESTIÓN DOCUMENTAL</p><h2>Carpetas principales</h2><p>Toda la documentación organizada por categoría.</p></div><?php if(puedeGestionarDocumentos($pdo)):?><div class="top-actions"><a class="btn secondary" href="admin/carpetas.php">Nueva carpeta</a><a class="btn primary" href="admin/documentos.php">Cargar documentos</a></div><?php endif;?></header>
<section class="sgi-folders">
<?php foreach($folders as $f):?><a class="sgi-folder" href="sector.php?sector=sgi&carpeta=<?=$f['id']?>"><span class="folder-icon">📁</span><div><strong><?=htmlspecialchars($f['nombre'])?></strong><small><?=$f['cantidad']?> documentos</small></div></a><?php endforeach;?>
<?php if(!$folders):?><div class="empty">Todavía no hay carpetas creadas.</div><?php endif;?>
</section>

<div class="sgi-bottom">
<section class="table-panel"><div class="section-head"><div><p class="eyebrow">ÚLTIMAS CARGAS</p><h2>Documentos recientes</h2></div></div><div class="table-wrap"><table><thead><tr><th>Documento</th><th>Carpeta</th><th>Tipo</th><th>Versión</th><th>Estado</th></tr></thead><tbody>
<?php foreach($docs as $d):?><tr><td><strong><?=htmlspecialchars($d['titulo'])?></strong></td><td><?=htmlspecialchars($d['carpeta']??'Sin carpeta')?></td><td><?=htmlspecialchars($d['tipo'])?></td><td><?=htmlspecialchars($d['version']??'1.0')?></td><td><span class="status-pill <?=htmlspecialchars($d['estado']??'aprobado')?>"><?=htmlspecialchars($d['estado']??'aprobado')?></span></td></tr><?php endforeach;?>
</tbody></table></div></section>
<aside class="quick-panel"><p class="eyebrow">ACCESOS RÁPIDOS</p><h2>Herramientas</h2><a href="buscar.php">🔎 Buscar documentos</a><?php if(puedeGestionarDocumentos($pdo)):?><a href="admin/documentos.php">⬆ Carga masiva</a><a href="admin/carpetas.php">📁 Administrar carpetas</a><?php endif;?><a href="operaciones.php">⚙ Gestión operativa</a></aside>
</div>
</main></div></body></html>