<?php
<<<<<<< HEAD
require __DIR__.'/config/auth.php';requireLogin();require __DIR__.'/config/db.php';require __DIR__.'/config/layout.php';
$slug=trim($_GET['sector']??'');$cid=!empty($_GET['carpeta'])?(int)$_GET['carpeta']:null;$q=trim($_GET['q']??'');
$st=$pdo->prepare('SELECT * FROM sectores WHERE slug=? LIMIT 1');$st->execute([$slug]);$s=$st->fetch();if(!$s)exit('Sector no encontrado');if(!puedeVerSector($pdo,$s['id'])){http_response_code(403);exit('Sin acceso');}
if($cid){$st=$pdo->prepare('SELECT * FROM carpetas WHERE sector_id=? AND carpeta_padre_id=? AND activa=1 ORDER BY orden,nombre');$st->execute([$s['id'],$cid]);}else{$st=$pdo->prepare('SELECT * FROM carpetas WHERE sector_id=? AND carpeta_padre_id IS NULL AND activa=1 ORDER BY orden,nombre');$st->execute([$s['id']]);}$folders=$st->fetchAll();
$sql='SELECT * FROM documentos WHERE sector_id=? AND activo=1';$params=[$s['id']];if($cid){$sql.=' AND carpeta_id=?';$params[]=$cid;}else{$sql.=' AND carpeta_id IS NULL';}if($q!==''){$sql.=' AND (titulo LIKE ? OR descripcion LIKE ?)';$like='%'.$q.'%';$params[]=$like;$params[]=$like;}$sql.=' ORDER BY titulo';$st=$pdo->prepare($sql);$st->execute($params);$docs=$st->fetchAll();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><link rel="stylesheet" href="../style.css"></head><body><div class="app"><?php sidebar($pdo,$s['slug']);?><main class="content"><header class="section-top"><div><p class="eyebrow">SECTOR</p><h1><?=htmlspecialchars($s['nombre'])?></h1><p>Carpetas, documentos y procedimientos.</p></div></header>
<form method="get" class="form-row" style="grid-template-columns:1fr auto;margin-bottom:18px"><input type="hidden" name="sector" value="<?=htmlspecialchars($slug)?>"><?php if($cid):?><input type="hidden" name="carpeta" value="<?=$cid?>"><?php endif;?><input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar..."><button class="btn primary">Buscar</button></form>
<div class="section-head"><div><p class="eyebrow">CARPETAS</p><h2>Explorar</h2></div></div><section class="sector-grid"><?php foreach($folders as $f):?><article class="sector-card"><div class="sector-code">📁</div><h3><?=htmlspecialchars($f['nombre'])?></h3><p>Carpeta documental.</p><div class="card-links"><a href="sector.php?sector=<?=urlencode($slug)?>&carpeta=<?=$f['id']?>">Abrir →</a></div></article><?php endforeach;?></section>
<div class="section-head"><div><p class="eyebrow">ARCHIVOS</p><h2>Documentos</h2></div></div><section class="document-grid"><?php if(!$docs):?><div class="empty">No hay archivos.</div><?php endif;?><?php foreach($docs as $d):?><article class="document-card"><span class="type-badge"><?=htmlspecialchars(ucfirst($d['tipo']))?></span><h3><?=htmlspecialchars($d['titulo'])?></h3><p>Versión <?=htmlspecialchars($d['version']??'1.0')?> · <?=htmlspecialchars($d['estado']??'aprobado')?></p><?php if($d['archivo']):?><div class="doc-actions"><a class="btn secondary" href="../uploads/<?=rawurlencode($d['archivo'])?>" target="_blank">Abrir</a></div><?php endif;?></article><?php endforeach;?></section></main></div></body></html>
=======

require __DIR__ . '/config/auth.php';
requireLogin();

require __DIR__ . '/config/db.php';

$slug = trim($_GET['sector'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

/*
|--------------------------------------------------------------------------
| CONSULTA DE SECTORES PARA LA BARRA LATERAL
|--------------------------------------------------------------------------
*/
if (($_SESSION['rol'] ?? '') === 'admin') {
    $stmtSectores = $pdo->query('
        SELECT id, nombre, slug 
        FROM sectores 
        ORDER BY orden, nombre
    ');
    $sectoresMenu = $stmtSectores->fetchAll();
} else {
    $stmtSectores = $pdo->prepare('
        SELECT s.id, s.nombre, s.slug 
        FROM sectores s 
        INNER JOIN usuario_sector us ON us.sector_id = s.id 
        WHERE us.usuario_id = ? 
        ORDER BY s.orden, s.nombre
    ');
    $stmtSectores->execute([$_SESSION['usuario_id']]);
    $sectoresMenu = $stmtSectores->fetchAll();
}

/*
|--------------------------------------------------------------------------
| OBTENER SECTOR ACTUAL
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT id, nombre, slug
     FROM sectores
     WHERE slug = ?
     LIMIT 1'
);

$stmt->execute([$slug]);

$sector = $stmt->fetch();

if (!$sector) {
    http_response_code(404);
    exit('Sector no encontrado.');
}

/*
|--------------------------------------------------------------------------
| FILTRO DE DOCUMENTOS
|--------------------------------------------------------------------------
*/
$params = [
    $sector['id']
];

$sql = '
    SELECT
        id,
        titulo,
        descripcion,
        tipo,
        archivo,
        fecha_actualizacion
    FROM documentos
    WHERE sector_id = ?
    AND activo = 1
';

if (
    in_array(
        $tipo,
        ['documentacion', 'procedimiento'],
        true
    )
) {
    $sql .= ' AND tipo = ?';
    $params[] = $tipo;
}

$sql .= '
    ORDER BY
    fecha_actualizacion DESC,
    titulo
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();

?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= htmlspecialchars($sector['nombre']) ?> | NASER SGI
    </title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

<!-- Overlay para el menú en celulares -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleMenu()"></div>

<div class="app-shell">

    <!-- BARRA SUPERIOR PARA CELULARES -->
    <div class="mobile-header-bar">
        <div class="brand" style="font-size: 1.1rem; font-weight: bold; color: var(--verde-fuerte);">
            NASER <span style="font-size: 0.8rem; color: var(--texto-secundario);">SRL</span>
        </div>
        <button type="button" class="btn-hamburger" onclick="toggleMenu()">☰</button>
    </div>

    <!-- BARRA LATERAL DE NAVEGACIÓN -->
    <aside id="mainSidebar" class="php-sidebar">
        <div class="logo-box">
            <div class="brand">NASER <span>SRL</span></div>
            <small>Sistema de Gestión Integrado</small>
        </div>

        <a class="nav-link" href="dashboard.php">Inicio</a>

        <?php foreach ($sectoresMenu as $sec): ?>
            <a 
                class="nav-link <?= $sec['slug'] === $slug ? 'active' : '' ?>" 
                href="sector.php?sector=<?= urlencode($sec['slug']) ?>"
            >
                <?= htmlspecialchars($sec['nombre']) ?>
            </a>
        <?php endforeach; ?>

        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
            <div class="nav-separator"></div>
            <a class="nav-link" href="admin/usuarios.php">Usuarios</a>
            <a class="nav-link" href="admin/documentos.php">Administrar documentos</a>
        <?php endif; ?>

        <div class="php-sidebar-user">
            <strong><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></strong>
            <small><?= htmlspecialchars(ucfirst($_SESSION['rol'] ?? '')) ?></small>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="php-content">

        <div class="section-heading spaced">
            <div>
                <p class="eyebrow">SECTOR</p>
                <h1>
                    <?= htmlspecialchars($sector['nombre']) ?>
                </h1>
                <p class="muted">
                    Documentación y procedimientos disponibles.
                </p>
            </div>
        </div>

        <div class="filter-row">
            <a
                class="btn <?= $tipo === '' ? 'primary' : 'secondary' ?>"
                href="sector.php?sector=<?= urlencode($sector['slug']) ?>"
            >
                Todo
            </a>

            <a
                class="btn <?= $tipo === 'documentacion' ? 'primary' : 'secondary' ?>"
                href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion"
            >
                Documentación
            </a>

            <a
                class="btn <?= $tipo === 'procedimiento' ? 'primary' : 'secondary' ?>"
                href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento"
            >
                Procedimientos
            </a>
        </div>

        <div class="document-list">

            <?php if (!$documentos): ?>
                <div class="empty-state">
                    Todavía no hay archivos cargados en esta sección.
                </div>
            <?php endif; ?>

            <?php foreach ($documentos as $doc): ?>

                <?php
                $extension = strtolower(
                    pathinfo(
                        $doc['archivo'] ?? '',
                        PATHINFO_EXTENSION
                    )
                );

                $rutaArchivo =
                    '../uploads/'
                    . rawurlencode(
                        $doc['archivo'] ?? ''
                    );
                ?>

                <article class="document-card">
                    <div>
                        <span class="badge">
                            <?= htmlspecialchars(
                                ucfirst($doc['tipo'])
                            ) ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($doc['titulo']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($doc['descripcion'] ?? '') ?>
                        </p>

                        <small>
                            Actualizado:
                            <?= htmlspecialchars($doc['fecha_actualizacion']) ?>
                        </small>
                    </div>

                    <?php if (!empty($doc['archivo'])): ?>
                        <div class="document-actions">

                            <?php if (
                                in_array(
                                    $extension,
                                    ['pdf', 'jpg', 'jpeg', 'png'],
                                    true
                                )
                            ): ?>
                                <button
                                    type="button"
                                    class="btn-preview"
                                    onclick="abrirVistaPrevia(
                                        '<?= $rutaArchivo ?>',
                                        '<?= htmlspecialchars($doc['titulo'], ENT_QUOTES) ?>'
                                    )"
                                >
                                    👁 Vista previa
                                </button>
                            <?php endif; ?>

                            <a
                                class="btn-open"
                                href="<?= $rutaArchivo ?>"
                                target="_blank"
                            >
                                ↗ Abrir
                            </a>
                        </div>
                    <?php endif; ?>
                </article>

            <?php endforeach; ?>

        </div>

    </main>

</div>

<!-- VISTA PREVIA -->
<div id="modalPreview" class="modal-preview">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloPreview">Vista Previa</h3>
            <button
                type="button"
                class="cerrar-modal"
                onclick="cerrarVistaPrevia()"
            >
                ✕
            </button>
        </div>

        <iframe id="previewFrame" src=""></iframe>
    </div>
</div>

<script>
// Desplegable menú móvil
function toggleMenu() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}

// Vista previa
function abrirVistaPrevia(ruta, titulo) {
    document.getElementById('previewFrame').src = ruta;
    document.getElementById('tituloPreview').textContent = titulo;
    document.getElementById('modalPreview').style.display = 'flex';
}

function cerrarVistaPrevia() {
    document.getElementById('modalPreview').style.display = 'none';
    document.getElementById('previewFrame').src = '';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalPreview');
    if (event.target === modal) {
        cerrarVistaPrevia();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarVistaPrevia();
    }
});
</script>

</body>
</html>
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
