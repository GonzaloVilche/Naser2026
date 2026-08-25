<?php
require __DIR__ . '/config/auth.php';
requireLogin();
require __DIR__ . '/config/db.php';

if (($_SESSION['rol'] ?? '') === 'admin') {
    $stmt = $pdo->query('SELECT id, nombre, slug FROM sectores ORDER BY orden, nombre');
    $sectores = $stmt->fetchAll();
} else {
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
    <aside class="php-sidebar">
        <div class="logo-box">
            <div class="brand">NASER <span>SRL</span></div>
            <small>Sistema de Gestión Integrado</small>
        </div>

        <a class="nav-link active" href="dashboard.php">Inicio</a>

        <?php foreach ($sectores as $sector): ?>
            <a class="nav-link" href="sector.php?sector=<?= urlencode($sector['slug']) ?>">
                <?= htmlspecialchars($sector['nombre']) ?>
            </a>
        <?php endforeach; ?>

        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            <div class="nav-separator"></div>
            <a class="nav-link" href="admin/usuarios.php">Usuarios</a>
            <a class="nav-link" href="admin/documentos.php">Administrar documentos</a>
        <?php endif; ?>

        <div class="php-sidebar-user">
            <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
            <small><?= htmlspecialchars(ucfirst($_SESSION['rol'])) ?></small>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>

    <main class="php-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">SERVICIOS NASER SRL</p>
                <h1>Panel principal</h1>
            </div>
            <div class="user-chip"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        </header>

        <section class="hero-panel">
            <p class="eyebrow">GESTIÓN CENTRALIZADA</p>
            <h2>Documentación y procedimientos por sector</h2>
            <p>Ingresá directamente al área que necesites. Los accesos se habilitan según el usuario.</p>

        </section>

        <!-- SECCIÓN INTERMEDIA: BLOQUE DE TEXTO O NOVEDADES -->
        <section class="info-intermedia">
<<<<<<< HEAD
<<<<<<< HEAD
            <small>INFORMACIÓN GENERAL</small>
            <h2>Título del comunicado o información relevante</h2>
            <p>
                Aquí puedes colocar cualquier texto, aviso o instrucción que desees destacar para todo el personal. 
                Este contenido aparece en el medio del panel y desplaza los sectores hacia abajo.
            </p>
=======
>>>>>>> ea2c1fbed3a8bb3f5ecfb572d88edc72adcc8a60
         <small>INFORMACIÓN GENERAL</small>
         <h2>Política de Calidad Ambiente Seguridad y Salud </h2>
          <p> ofrece soluciones a la industria hidrocarburífera mediante operaciones de Slick Line, Well Testing y Flow Back, impulsando el uso de tecnología y mejores prácticas. </p>
           
          <p> Realizamos nuestro trabajo creando una cultura de seguridad generativa, con foco en la protección de la salud y seguridad de las personas, el cuidado del ambiente, la integridad de activos, la calidad de los servicios y el uso eficiente de recursos. Para lograr nuestros objetivos, asumimos los siguientes compromisos:</p>
         <p>
          • Implementar, mantener y mejorar un sistema de gestión integrado, basado en la calidad del servicio, la protección ambiental, la seguridad y salud en el trabajo.
        </p>
         <p>
         • Aplicar la mejora continua a los procesos de trabajo para satisfacer las necesidades de nuestros clientes, mejorar el desempeño ambiental, en seguridad y salud.
         </p>
         <p>
         • Cumplir con los requisitos legales y normativos aplicables, de clientes y partes interesadas.
         </p>
         <p>
         • Formar a nuestro personal y promover el compromiso en la prevención de incidentes, enfermedades profesionales y contaminación ambiental.
         </p>
         <p>
         • Proporcionar un ambiente de trabajo seguro y saludable.
         </p>
         <p>
         • Planificar las tareas para lograr propósitos definidos, eliminar peligros y reducir riesgos a la salud y seguridad de las personas, manteniendo vías para la consulta y participación.
        </p>
         <p>
         • Llevar adelante nuestros procesos con ética y transparencia.
         </p>
         <p>
         • Garantizar el derecho y autoridad a detener cualquier tarea que se considere insegura.
         </p>
         <p>
         • Prohibir el consumo de estupefacientes en nuestros procesos e instalaciones.
         </p>
         <p>
         • Conducir vehículos propios y de contratistas en forma segura.
         </p>
         <p>
         • Comunicar esta política a nuestro personal, clientes y partes interesadas.
         </p>
<<<<<<< HEAD
        </section>
=======
            <small>INFORMACIÓN GENERAL</small>
            <h2>Título del comunicado o información relevante</h2>
            <p>
                Aquí puedes colocar cualquier texto, aviso o instrucción que desees destacar para todo el personal. 
                Este contenido aparece en el medio del panel y desplaza los sectores hacia abajo.
            </p>
>>>>>>> ea2c1fbed3a8bb3f5ecfb572d88edc72adcc8a60

        </section>

            <?php if (!$sectores): ?>
                <div class="empty-state">Este usuario todavía no tiene sectores habilitados.</div>
            <?php else: ?>
                <div class="sector-grid">
                    <?php foreach ($sectores as $sector): ?>
                        <article class="sector-card">
                            <div class="sector-letter"><?= htmlspecialchars(strtoupper(substr($sector['nombre'], 0, 2))) ?></div>
                            <h3><?= htmlspecialchars($sector['nombre']) ?></h3>
                            <div class="quick-links">
                                <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion">Documentación <span>→</span></a>
                                <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento">Procedimientos <span>→</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>