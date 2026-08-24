<?php
require __DIR__ . '/config/auth.php';
requireLogin();
require __DIR__ . '/config/db.php';

$stmt = $pdo->query('SELECT id, nombre, slug FROM sectores ORDER BY orden, nombre');
$sectores = $stmt->fetchAll();

if (($_SESSION['rol'] ?? '') !== 'admin') {
    $stmt = $pdo->prepare('SELECT s.id, s.nombre, s.slug FROM sectores s INNER JOIN usuario_sector us ON us.sector_id = s.id WHERE us.usuario_id = ? ORDER BY s.orden, s.nombre');
    $stmt->execute([$_SESSION['usuario_id']]);
    $sectores = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Panel | NASER SGI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="logo-box">
            <div class="brand">NASER <span>SRL</span></div>
            <small>Sistema de Gestión Integrado</small>
        </div>
        <a class="nav-link active" href="dashboard.php">Inicio</a>
        <?php foreach ($sectores as $sector): ?>
            <a class="nav-link" href="sector.php?sector=<?= urlencode($sector['slug']) ?>"><?= htmlspecialchars($sector['nombre']) ?></a>
        <?php endforeach; ?>
        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            <div class="nav-separator"></div>
            <a class="nav-link" href="admin/usuarios.php">Usuarios</a>
            <a class="nav-link" href="admin/documentos.php">Administrar documentos</a>
        <?php endif; ?>
        <div class="sidebar-user">
            <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
            <small><?= htmlspecialchars($_SESSION['rol']) ?></small>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <div>
                <p class="eyebrow">SERVICIOS NASER SRL</p>
                <h1>Panel principal</h1>
            </div>
            <div class="user-chip"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        </header>

        <section class="hero-panel">
            <div>
                <p class="eyebrow">GESTIÓN CENTRALIZADA</p>
                <h2>Documentación y procedimientos por sector</h2>
                <p>Ingresá directamente al área que necesites. Los accesos se habilitan según el usuario.</p>
            </div>
        </section>

        <section class="section-block">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">ACCESOS DIRECTOS</p>
                    <h2>Sectores habilitados</h2>
                </div>
            </div>

            <div class="sector-grid">
                <?php foreach ($sectores as $sector): ?>
                    <article class="sector-card">
                        <div class="sector-icon"><?= htmlspecialchars(strtoupper(substr($sector['nombre'], 0, 2))) ?></div>
                        <h3><?= htmlspecialchars($sector['nombre']) ?></h3>
                        <div class="quick-links">
                            <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion">Documentación <span>→</span></a>
                            <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento">Procedimientos <span>→</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
