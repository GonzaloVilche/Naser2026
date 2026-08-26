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

// Configuración de las 4 imágenes
// Si las tienes dentro de la carpeta 'img/' de tu proyecto:
$galeria = [
    [
        'ruta' => 'img/trabajador-slickline.JPEG',
        'alt'  => 'Trabajador Slickline'
    ],
    [
        'ruta' => 'img/Camion-Naser.png',
        'alt'  => 'Camión Naser'
    ],
    [
        'ruta' => 'img/unidad-liviana.png',
        'alt'  => 'Unidad Liviana'
    ],
    [
        'ruta' => 'img/hidrogrua.jpeg',
        'alt'  => 'Hidrogrúa'
    ]
];

$totalSectores = count($sectores);
$totalDocumentos = 0;

try {
    if (($_SESSION['rol'] ?? '') === 'admin') {
        $totalDocumentos = (int)$pdo->query('SELECT COUNT(*) FROM documentos WHERE activo = 1')->fetchColumn();
    } else {
        $stmtDocs = $pdo->prepare(
            'SELECT COUNT(*) FROM documentos d
             INNER JOIN usuario_sector us ON us.sector_id = d.sector_id
             WHERE us.usuario_id = ? AND d.activo = 1'
        );
        $stmtDocs->execute([$_SESSION['usuario_id']]);
        $totalDocumentos = (int)$stmtDocs->fetchColumn();
    }
} catch (Throwable $e) {
    $totalDocumentos = 0;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Panel | NASER SGI</title>
    <link rel="stylesheet" href="../style.css">

    <!-- ESTILOS EXCLUSIVOS PARA EL DASHBOARD -->
    <style>
        .logo-box.logo-box-naser {
            height: auto !important;
            min-height: 0 !important;
            padding: 8px 8px 12px !important;
            margin: 0 0 10px !important;
            text-align: center !important;
            overflow: hidden !important;
        }

        .logo-box.logo-box-naser .logo-naser {
            display: block !important;
            width: 145px !important;
            max-width: 145px !important;
            height: 62px !important;
            max-height: 62px !important;
            margin: 0 auto 4px !important;
            padding: 0 !important;
            object-fit: contain !important;
        }

        .logo-box.logo-box-naser small {
            display: block;
            margin: 0;
            font-size: 9px;
            line-height: 1.2;
            letter-spacing: .35px;
            color: var(--texto-secundario);
        }

        .sidebar-group-title {
            padding: 8px 12px 5px;
            color: var(--gris);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.1px;
        }

        .dashboard-topbar {
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gris-borde);
        }

        .dashboard-subtitle {
            margin-top: 3px;
            color: var(--texto-secundario);
            font-size: 13px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .system-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border: 1px solid var(--gris-borde);
            border-radius: 999px;
            background: #fff;
            color: var(--texto-secundario);
            font-size: 11px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--verde-fuerte);
        }

        .admin-summary {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 12px !important;
            margin: 20px 0 26px !important;
        }

        .summary-card {
            display: block !important;
            min-height: 100px !important;
            padding: 16px 18px !important;
            background: #fff !important;
            border: 1px solid var(--gris-borde) !important;
            border-left: 4px solid var(--verde-fuerte) !important;
            border-radius: 10px !important;
            box-shadow: var(--sombra-suave) !important;
        }

        .summary-label {
            display: block !important;
            margin-bottom: 6px !important;
            color: var(--gris) !important;
            font-size: 9px !important;
            font-weight: 800 !important;
            letter-spacing: 1px !important;
        }

        .summary-card strong {
            display: block !important;
            color: var(--texto) !important;
            font-size: 21px !important;
            line-height: 1.1 !important;
        }

        .summary-card small {
            display: block !important;
            margin-top: 7px !important;
            color: var(--texto-secundario) !important;
            font-size: 11px !important;
        }

        /* ESTILOS DE LA GALERÍA DE IMÁGENES */
        .info-images-container {
            display: flex !important;
            gap: 10px !important;
            align-items: center !important;
            flex-wrap: wrap !important;
        }

        .info-images-container .info-image img {
            width: 90px !important;
            height: 68px !important;
            object-fit: cover !important;
            border-radius: 8px !important;
            border: 1px solid var(--gris-borde) !important;
            display: block !important;
        }

        @media (max-width: 1050px) {
            .admin-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 700px) {
            .admin-summary {
                grid-template-columns: 1fr !important;
            }

            .topbar-right {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="php-sidebar">

        <div class="logo-box logo-box-naser">
            <img src="/Naser2026/img/logo-naser.png" alt="Naser División Petróleo" class="logo-naser">
            <small>Sistema de Gestión Integrado</small>
        </div>

        <a class="nav-link active" href="dashboard.php">Inicio</a>
        <a class="nav-link" href="operaciones.php">Operaciones</a>

        <?php foreach ($sectores as $sector): ?>
            <a class="nav-link" href="sector.php?sector=<?= urlencode($sector['slug']) ?>">
                <?= htmlspecialchars($sector['nombre']) ?>
            </a>
        <?php endforeach; ?>

        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            <div class="nav-separator"></div>
            <div class="sidebar-group-title">ADMINISTRACIÓN</div>
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

        <header class="topbar dashboard-topbar">
            <div>
                <p class="eyebrow">SERVICIOS NASER SRL</p>
                <h1>Panel principal</h1>
                <p class="dashboard-subtitle">Sistema de Gestión Integrado · División Petróleo</p>
            </div>

            <div class="topbar-right">
                <div class="system-status">
                    <span class="status-dot"></span>
                    Sistema activo
                </div>
                <div class="user-chip"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            </div>
        </header>

        <section class="admin-summary">
            <article class="summary-card">
                <span class="summary-label">ROL ACTUAL</span>
                <strong><?= htmlspecialchars(ucfirst($_SESSION['rol'])) ?></strong>
                <small>Nivel de acceso asignado</small>
            </article>

            <article class="summary-card">
                <span class="summary-label">SECTORES</span>
                <strong><?= $totalSectores ?></strong>
                <small>Sectores habilitados</small>
            </article>

            <article class="summary-card">
                <span class="summary-label">DOCUMENTACIÓN</span>
                <strong><?= $totalDocumentos ?></strong>
                <small>Archivos disponibles</small>
            </article>

            <article class="summary-card">
                <span class="summary-label">ESTADO</span>
                <strong>Activo</strong>
                <small>Sesión iniciada correctamente</small>
            </article>
        </section>

        <section class="info-intermedia info-banner">
            <div class="info-content">
                <small>INFORMACIÓN GENERAL</small>
                <h2>Flotas y Equipamiento Operativo</h2>
                <p>Monitoreo, estado e información relevante sobre las unidades de campo de Naser SRL.</p>
                <a href="#" class="btn-banner">Ver más información <span>→</span></a>
            </div>

            <!-- CONTENEDOR DE IMÁGENES -->
            <div class="info-images-container">
                <?php foreach ($galeria as $item): ?>
                    <div class="info-image">
                        <a href="<?= $item['ruta'] ?>" target="_blank" class="img-link">
                            <img src="<?= $item['ruta'] ?>" alt="<?= htmlspecialchars($item['alt']) ?>">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="info-intermedia">
            <small>INFORMACIÓN GENERAL</small>
            <h2>Política de Calidad, Ambiente, Seguridad y Salud</h2>

            <p>Ofrece soluciones a la industria hidrocarburífera mediante operaciones de Slick Line, Well Testing y Flow Back, impulsando el uso de tecnología y mejores prácticas.</p>

            <p>Realizamos nuestro trabajo creando una cultura de seguridad generativa, con foco en la protección de la salud y seguridad de las personas, el cuidado del ambiente, la integridad de activos, la calidad de los servicios y el uso eficiente de recursos. Para lograr nuestros objetivos, asumimos los siguientes compromisos:</p>

            <ul style="margin-top:10px;padding-left:20px;color:var(--texto-secundario);line-height:1.6;">
                <li>Implementar, mantener y mejorar un sistema de gestión integrado, basado en la calidad del servicio, la protección ambiental, la seguridad y salud en el trabajo.</li>
                <li>Aplicar la mejora continua a los procesos de trabajo para satisfacer las necesidades de nuestros clientes, mejorar el desempeño ambiental, en seguridad y salud.</li>
                <li>Cumplir con los requisitos legales y normativos aplicables, de clientes y partes interesadas.</li>
                <li>Formar a nuestro personal y promover el compromiso en la prevención de incidentes, enfermedades profesionales y contaminación ambiental.</li>
                <li>Proporcionar un ambiente de trabajo seguro y saludable.</li>
                <li>Planificar las tareas para lograr propósitos definidos, eliminar peligros y reducir riesgos a la salud y seguridad de las personas, manteniendo vías para la consulta y participación.</li>
                <li>Llevar adelante nuestros procesos con ética y transparencia.</li>
                <li>Garantizar el derecho y autoridad a detener cualquier tarea que se considere insegura.</li>
                <li>Prohibir el consumo de estupefacientes en nuestros procesos e instalaciones.</li>
                <li>Conducir vehículos propios y de contratistas en forma segura.</li>
                <li>Comunicar esta política a nuestro personal, clientes y partes interesadas.</li>
            </ul>
        </section>

        <section class="hero-panel">
            <p class="eyebrow">GESTIÓN CENTRALIZADA</p>
            <h2>Documentación y procedimientos por sector</h2>
            <p>Ingresá directamente al área que necesites. Los accesos se habilitan según el usuario.</p>
        </section>

        <section class="section-block">
            <?php if (!$sectores): ?>
                <div class="empty-state">Este usuario todavía no tiene sectores habilitados.</div>
            <?php else: ?>
                <div class="sector-grid">
                    <?php foreach ($sectores as $sector): ?>
                        <article class="sector-card">
                            <div class="sector-letter"><?= htmlspecialchars(strtoupper(substr($sector['nombre'], 0, 2))) ?></div>
                            <h3><?= htmlspecialchars($sector['nombre']) ?></h3>

                            <div class="quick-links">
                                <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion">
                                    Documentación <span>→</span>
                                </a>

                                <a href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento">
                                    Procedimientos <span>→</span>
                                </a>
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