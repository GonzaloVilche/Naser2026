<?php
require __DIR__.'/config/auth.php';requireLogin();require __DIR__.'/config/db.php';require __DIR__.'/config/layout.php';
$rol=$_SESSION['rol']??'';
if($rol==='admin'){$sectores=$pdo->query('SELECT id,nombre,slug FROM sectores ORDER BY orden')->fetchAll();$totalDocs=(int)$pdo->query('SELECT COUNT(*) FROM documentos WHERE activo=1')->fetchColumn();}
else{$st=$pdo->prepare('SELECT s.id,s.nombre,s.slug FROM usuario_sector us JOIN sectores s ON s.id=us.sector_id WHERE us.usuario_id=? AND us.puede_ver=1 ORDER BY s.orden');$st->execute([$_SESSION['usuario_id']]);$sectores=$st->fetchAll();$st=$pdo->prepare('SELECT COUNT(*) FROM documentos d JOIN usuario_sector us ON us.sector_id=d.sector_id WHERE us.usuario_id=? AND d.activo=1');$st->execute([$_SESSION['usuario_id']]);$totalDocs=(int)$st->fetchColumn();}
$galeria=[['/naser/img/trabajador-slickline.jpeg','Trabajador'],['/naser/img/Camion-Naser.png','Camión Slickline'],['/naser/img/Unidad-liviana.png','Unidad Liviana'],['/naser/img/Hidrogrua.jpeg','Hidrogrúa']];
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Panel NASER</title><link rel="stylesheet" href="../style.css"></head><body><div class="app"><?php sidebar($pdo,'inicio');?><main class="content">

<header class="topbar"><div><p class="eyebrow">SERVICIOS NASER SRL</p><h1>Panel principal</h1><p>Gestión centralizada de documentación, sectores y operaciones.</p></div>
<div class="top-actions"><span class="status"><i></i>Sistema activo</span><span class="user-chip"><?=htmlspecialchars($_SESSION['nombre'])?></span></div></header>

<section class="dashboard-corporate-photo dashboard-corporate-photo-final">
    <img src="../img/sgi-corporativo.jpeg" alt="NASER - Operación en campo">
    <div class="dashboard-banner-shade"></div>
    <div class="dashboard-banner-copy">
        <img class="dashboard-banner-logo" src="../img/logo-naser.png" alt="NASER">
        <span></span>
        <p>COMPROMISO · SEGURIDAD · OPERACIÓN</p>
    </div>
</section>

<section class="stats"><article><span>ROL ACTUAL</span><strong><?=$rol==='admin'?'Gerencia':ucfirst($rol)?></strong><small>Nivel de acceso</small></article><article><span>SECTORES</span><strong><?=count($sectores)?></strong><small>Habilitados</small></article><article><span>DOCUMENTACIÓN</span><strong><?=$totalDocs?></strong><small>Archivos disponibles</small></article><article><span>ESTADO</span><strong>Activo</strong><small>Sesión validada</small></article></section>
<section class="fleet-panel"><div class="fleet-copy"><p class="eyebrow">INFORMACIÓN GENERAL</p><h2>Flotas y Equipamiento Operativo</h2><p>Monitoreo, estado e información relevante sobre las unidades de campo de Naser SRL.</p></div><div class="fleet-images"><?php foreach($galeria as [$r,$a]):?><article class="fleet-card"><div class="fleet-image"><img src="<?=$r?>" alt="<?=$a?>"></div><h3><?=htmlspecialchars($a)?></h3></article><?php endforeach;?></div></section>
<section class="policy-panel"><p class="eyebrow">INFORMACIÓN GENERAL</p><h2>Política de Calidad, Ambiente, Seguridad y Salud</h2><p>Ofrece soluciones a la industria hidrocarburífera mediante operaciones de Slick Line, Well Testing y Flow Back.</p><ul><li>Mejora continua.</li><li>Cumplimiento legal y normativo.</li><li>Prevención de incidentes.</li><li>Ambiente de trabajo seguro y saludable.</li></ul></section>
<div class="section-head"><div><p class="eyebrow">GESTIÓN CENTRALIZADA</p><h2>Sectores habilitados</h2></div></div><section class="sector-grid"><?php foreach($sectores as $s):?><article class="sector-card"><div class="sector-code"><?=htmlspecialchars(strtoupper(substr($s['nombre'],0,2)))?></div><h3><?=htmlspecialchars($s['nombre'])?></h3><p>Documentación y procedimientos del sector.</p><div class="card-links"><a href="sector.php?sector=<?=urlencode($s['slug'])?>&tipo=documentacion">Documentación →</a><a href="sector.php?sector=<?=urlencode($s['slug'])?>&tipo=procedimiento">Procedimientos →</a></div></article><?php endforeach;?></section>
</main></div></body></html>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const destinos = [
    ['Trabajador','equipos/trabajador.php'],
    ['Camión Slickline','equipos/camion-slickline.php'],
    ['Camion Slickline','equipos/camion-slickline.php'],
    ['Unidad Liviana','equipos/unidad-liviana.php'],
    ['Hidrogrúa','equipos/hidrogrua.php'],
    ['Hidrogrua','equipos/hidrogrua.php']
  ];
  const cards = document.querySelectorAll('.fleet-card,.equipment-card,.image-card,.asset-card,.feature-card');
  cards.forEach(card => {
    const text = card.textContent.trim();
    const found = destinos.find(([label]) => text.includes(label));
    if (!found) return;
    card.classList.add('fleet-clickable');
    card.setAttribute('role','link');
    card.setAttribute('tabindex','0');
    card.addEventListener('click', () => window.location.href = found[1]);
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        window.location.href = found[1];
      }
    });
  });
});
</script>
