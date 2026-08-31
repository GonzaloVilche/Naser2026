<?php
require __DIR__.'/../config/auth.php';
requireLogin();
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/layout.php';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Unidad Liviana | NASER</title><link rel="stylesheet" href="../../style.css"></head>
<body><div class="app"><?php sidebar($pdo,'inicio');?><main class="content">
<header class="section-top"><div><p class="eyebrow">GESTIÓN DE ACTIVOS</p><h1>Unidad Liviana</h1><p>Documentación, controles e inspecciones de unidades livianas.</p></div><a class="btn secondary" href="../dashboard.php">Volver al inicio</a></header>
<section class="dashboard-grid"><article class="activity-panel"><p class="eyebrow">DOCUMENTACIÓN</p><h2>Unidad Liviana</h2><p>Este módulo queda preparado para agregar documentación, procedimientos, checklists, inspecciones, vencimientos e historial relacionados con este recurso.</p></article></section>
</main></div></body></html>