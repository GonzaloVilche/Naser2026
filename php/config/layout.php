<?php
function sidebar($pdo,$active=''){
$rol=$_SESSION['rol']??'';
if($rol==='admin'){$sectores=$pdo->query("SELECT id,nombre,slug FROM sectores WHERE slug<>'sgi' ORDER BY orden")->fetchAll();}
else{$st=$pdo->prepare("SELECT s.id,s.nombre,s.slug FROM usuario_sector us JOIN sectores s ON s.id=us.sector_id WHERE us.usuario_id=? AND us.puede_ver=1 AND s.slug<>'sgi' ORDER BY s.orden");$st->execute([$_SESSION['usuario_id']]);$sectores=$st->fetchAll();}
$puedeDocs=puedeGestionarDocumentos($pdo);
?>
<aside class="sidebar">
<a class="brand-panel" href="/naser/php/dashboard.php"><img src="/naser/img/logo-naser.png" alt="NASER"></a>
<nav class="side-nav">
<a class="<?=$active==='inicio'?'active':''?>" href="/naser/php/dashboard.php">Inicio</a>
<div class="nav-label">SISTEMA DE GESTIÓN</div>
<a class="<?=$active==='sgi'?'active':''?>" href="/naser/php/sgi.php">SGI</a>
<a href="/naser/php/buscar.php">Buscar documentación</a>
<div class="nav-label">SECTORES</div>
<a class="<?=$active==='operaciones'?'active':''?>" href="/naser/php/operaciones.php">Gestión operativa</a>
<?php foreach($sectores as $s):?><a class="<?=$active===$s['slug']?'active':''?>" href="/naser/php/sector.php?sector=<?=urlencode($s['slug'])?>"><?=htmlspecialchars($s['nombre'])?></a><?php endforeach;?>
<?php if($puedeDocs):?><div class="nav-label">GESTIÓN DOCUMENTAL</div><a href="/naser/php/admin/documentos.php">Cargar documentos</a><a href="/naser/php/admin/carpetas.php">Carpetas</a><?php endif;?>
<?php if($rol==='admin'):?><div class="nav-label">ADMINISTRACIÓN</div><a href="/naser/php/admin/usuarios.php">Usuarios</a><?php endif;?>
</nav>
<div class="sidebar-user"><strong><?=htmlspecialchars($_SESSION['nombre'])?></strong><span><?=htmlspecialchars($rol==='admin'?'Gerencia':ucfirst($rol))?></span><a href="/naser/php/logout.php">Cerrar sesión</a></div>
</aside><?php } ?>
