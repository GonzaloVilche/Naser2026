<?php
require __DIR__ . '/../config/auth.php';
requireDocumentManager();
require __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'] ?? '';
$esAdmin = $rol === 'admin';
$esSupervisor = $rol === 'supervisor';

$mensaje = '';
$error = '';

$uploadDir = dirname(__DIR__, 2) . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

/* =========================================================
   SECTORES HABILITADOS
========================================================= */

if ($esAdmin) {
    $sectores = $pdo->query(
        'SELECT id, nombre, slug
         FROM sectores
         ORDER BY orden, nombre'
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.nombre, s.slug
         FROM sectores s
         INNER JOIN usuario_sector us ON us.sector_id = s.id
         WHERE us.usuario_id = ?
         AND us.puede_ver = 1
         AND us.puede_editar = 1
         ORDER BY s.orden, s.nombre
         LIMIT 1'
    );
    $stmt->execute([$_SESSION['usuario_id']]);
    $sectores = $stmt->fetchAll();
}

$sectorPermitidoIds = array_map(
    static fn($sector) => (int)$sector['id'],
    $sectores
);

/* =========================================================
   ACCIONES
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {

        $id = (int)($_POST['id'] ?? 0);

        if ($esAdmin) {
            $stmt = $pdo->prepare(
                'SELECT id, sector_id, archivo
                 FROM documentos
                 WHERE id = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT d.id, d.sector_id, d.archivo
                 FROM documentos d
                 INNER JOIN usuario_sector us ON us.sector_id = d.sector_id
                 WHERE d.id = ?
                 AND us.usuario_id = ?
                 AND us.puede_editar = 1
                 LIMIT 1'
            );
            $stmt->execute([$id, $_SESSION['usuario_id']]);
        }

        $doc = $stmt->fetch();

        if (!$doc) {
            $error = 'No tenés permisos para eliminar ese documento.';
        } else {
            $pdo->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);

            if (!empty($doc['archivo']) && is_file($uploadDir . $doc['archivo'])) {
                @unlink($uploadDir . $doc['archivo']);
            }

            $mensaje = 'Documento eliminado correctamente.';
        }
    }

    if ($accion === 'crear') {

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $sectorId = (int)($_POST['sector_id'] ?? 0);

        if (
            $titulo === '' ||
            $sectorId <= 0 ||
            !in_array($tipo, ['documentacion', 'procedimiento'], true)
        ) {
            $error = 'Completá título, sector y tipo.';
        } elseif (!$esAdmin && !in_array($sectorId, $sectorPermitidoIds, true)) {
            $error = 'Solo podés cargar documentos en tu sector asignado.';
        } elseif (
            empty($_FILES['archivo']['name']) ||
            $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
        ) {
            $error = 'Seleccioná un archivo válido.';
        } else {

            $original = basename($_FILES['archivo']['name']);
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

            $permitidos = [
                'pdf', 'doc', 'docx',
                'xls', 'xlsx',
                'ppt', 'pptx',
                'jpg', 'jpeg', 'png'
            ];

            if (!in_array($extension, $permitidos, true)) {
                $error = 'Formato no permitido.';
            } else {

                $baseArchivo = pathinfo($original, PATHINFO_FILENAME);
                $baseArchivo = preg_replace(
                    '/[^a-zA-Z0-9_-]+/',
                    '_',
                    $baseArchivo
                );

                $nombreArchivo =
                    date('Ymd_His') .
                    '_' .
                    $baseArchivo .
                    '.' .
                    $extension;

                if (
                    move_uploaded_file(
                        $_FILES['archivo']['tmp_name'],
                        $uploadDir . $nombreArchivo
                    )
                ) {

                    $stmt = $pdo->prepare(
                        'INSERT INTO documentos
                        (
                            sector_id,
                            titulo,
                            descripcion,
                            tipo,
                            archivo,
                            activo,
                            fecha_actualizacion
                        )
                        VALUES (?, ?, ?, ?, ?, 1, CURDATE())'
                    );

                    $stmt->execute([
                        $sectorId,
                        $titulo,
                        $descripcion,
                        $tipo,
                        $nombreArchivo
                    ]);

                    $mensaje = 'Documento cargado correctamente.';
                } else {
                    $error = 'No se pudo guardar el archivo.';
                }
            }
        }
    }
}

/* =========================================================
   LISTADO DE DOCUMENTOS
========================================================= */

if ($esAdmin) {

    $documentos = $pdo->query(
        'SELECT
            d.id,
            d.sector_id,
            d.titulo,
            d.descripcion,
            d.tipo,
            d.archivo,
            d.fecha_actualizacion,
            s.nombre AS sector,
            s.slug AS sector_slug
         FROM documentos d
         INNER JOIN sectores s ON s.id = d.sector_id
         ORDER BY d.fecha_actualizacion DESC, d.id DESC'
    )->fetchAll();

} else {

    $stmt = $pdo->prepare(
        'SELECT
            d.id,
            d.sector_id,
            d.titulo,
            d.descripcion,
            d.tipo,
            d.archivo,
            d.fecha_actualizacion,
            s.nombre AS sector,
            s.slug AS sector_slug
         FROM documentos d
         INNER JOIN sectores s ON s.id = d.sector_id
         INNER JOIN usuario_sector us ON us.sector_id = d.sector_id
         WHERE us.usuario_id = ?
         AND us.puede_ver = 1
         ORDER BY d.fecha_actualizacion DESC, d.id DESC'
    );

    $stmt->execute([$_SESSION['usuario_id']]);
    $documentos = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>
        <?= $esSupervisor ? 'Documentos de mi sector' : 'Administrar documentos' ?>
        | NASER SGI
    </title>

    <link rel="stylesheet" href="../../style.css">

    <style>
        .doc-header-box {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin-bottom:25px;
        }

        .sector-permission {
            padding:10px 14px;
            border:1px solid var(--gris-borde);
            background:var(--verde-fondo);
            border-radius:10px;
            color:var(--verde-oscuro);
            font-size:12px;
            font-weight:700;
        }

        .doc-grid {
            display:grid;
            grid-template-columns:minmax(300px,.75fr) minmax(650px,1.8fr);
            gap:28px;
            align-items:start;
        }

        .doc-panel {
            padding:26px;
            background:#fff;
            border:1px solid var(--gris-borde);
            border-radius:14px;
            box-shadow:var(--sombra-suave);
        }

        .doc-panel h2 {
            margin-bottom:18px;
        }

        .doc-table {
            overflow-x:auto;
        }

        .document-title-small {
            display:block;
            margin-top:4px;
            color:var(--texto-secundario);
            font-size:11px;
        }

        @media(max-width:950px) {
            .doc-grid {
                grid-template-columns:1fr;
            }

            .doc-header-box {
                align-items:flex-start;
                flex-direction:column;
            }
        }
    </style>
</head>

<body>

<main class="standalone">

    <a class="back-link" href="../dashboard.php">
        ← Volver al panel
    </a>

    <div class="doc-header-box">

        <div>
            <p class="eyebrow">
                <?= $esSupervisor ? 'GESTIÓN DEL SECTOR' : 'ADMINISTRACIÓN' ?>
            </p>

            <h1>
                <?= $esSupervisor ? 'Documentos de mi sector' : 'Documentos y procedimientos' ?>
            </h1>

            <p class="muted">
                <?= $esSupervisor
                    ? 'Podés cargar y administrar únicamente la documentación del sector que tenés asignado.'
                    : 'Administración general de documentación y procedimientos del sistema.' ?>
            </p>
        </div>

        <?php if ($esSupervisor && !empty($sectores[0])): ?>
            <div class="sector-permission">
                Sector asignado:
                <?= htmlspecialchars($sectores[0]['nombre']) ?>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($mensaje): ?>
        <div class="alert success">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$sectores && $esSupervisor): ?>

        <div class="empty-state">
            Este supervisor todavía no tiene un sector editable asignado.
        </div>

    <?php else: ?>

        <div class="doc-grid">

            <section class="doc-panel">

                <p class="eyebrow">NUEVO ARCHIVO</p>
                <h2>Cargar documento</h2>

                <form
                    method="post"
                    enctype="multipart/form-data"
                    class="form-grid"
                >

                    <input
                        type="hidden"
                        name="accion"
                        value="crear"
                    >

                    <label>
                        Título
                        <input
                            type="text"
                            name="titulo"
                            required
                        >
                    </label>

                    <label>
                        Descripción
                        <textarea name="descripcion"></textarea>
                    </label>

                    <label>
                        Sector

                        <?php if ($esSupervisor): ?>

                            <input
                                type="hidden"
                                name="sector_id"
                                value="<?= (int)$sectores[0]['id'] ?>"
                            >

                            <input
                                type="text"
                                value="<?= htmlspecialchars($sectores[0]['nombre']) ?>"
                                disabled
                            >

                        <?php else: ?>

                            <select
                                name="sector_id"
                                required
                            >

                                <option value="">
                                    Seleccionar sector
                                </option>

                                <?php foreach ($sectores as $sector): ?>

                                    <option value="<?= (int)$sector['id'] ?>">
                                        <?= htmlspecialchars($sector['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php endif; ?>

                    </label>

                    <label>
                        Tipo

                        <select
                            name="tipo"
                            required
                        >
                            <option value="documentacion">
                                Documentación
                            </option>

                            <option value="procedimiento">
                                Procedimiento
                            </option>
                        </select>
                    </label>

                    <label>
                        Archivo

                        <input
                            type="file"
                            name="archivo"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                            required
                        >
                    </label>

                    <button
                        type="submit"
                        class="btn primary"
                    >
                        Cargar documento
                    </button>

                </form>

            </section>

            <section class="doc-panel">

                <p class="eyebrow">ARCHIVOS</p>

                <h2>
                    <?= $esSupervisor ? 'Documentos del sector' : 'Documentos cargados' ?>
                </h2>

                <?php if (!$documentos): ?>

                    <div class="empty-state">
                        Todavía no hay documentos disponibles.
                    </div>

                <?php else: ?>

                    <div class="doc-table">

                        <table>

                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Sector</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($documentos as $doc): ?>

                                <?php
                                $extension = strtolower(
                                    pathinfo(
                                        $doc['archivo'] ?? '',
                                        PATHINFO_EXTENSION
                                    )
                                );

                                $rutaArchivo =
                                    '../../uploads/' .
                                    rawurlencode($doc['archivo'] ?? '');
                                ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($doc['titulo']) ?>
                                        </strong>

                                        <?php if (!empty($doc['descripcion'])): ?>
                                            <span class="document-title-small">
                                                <?= htmlspecialchars($doc['descripcion']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($doc['sector']) ?>
                                    </td>

                                    <td>
                                        <span class="badge">
                                            <?= htmlspecialchars(ucfirst($doc['tipo'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($doc['fecha_actualizacion']) ?>
                                    </td>

                                    <td>

                                        <div class="table-actions">

                                            <?php if (
                                                !empty($doc['archivo']) &&
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
                                                        '<?= htmlspecialchars($rutaArchivo, ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($doc['titulo'], ENT_QUOTES) ?>'
                                                    )"
                                                >
                                                    👁 Vista previa
                                                </button>

                                            <?php endif; ?>

                                            <?php if (!empty($doc['archivo'])): ?>

                                                <a
                                                    class="btn-open"
                                                    href="<?= htmlspecialchars($rutaArchivo) ?>"
                                                    target="_blank"
                                                >
                                                    ↗ Abrir
                                                </a>

                                            <?php endif; ?>

                                            <form
                                                method="post"
                                                style="display:inline"
                                                onsubmit="return confirm('¿Seguro que querés eliminar este documento?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$doc['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-delete"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    <?php endif; ?>

</main>

<div id="modalPreview" class="modal-preview">

    <div class="modal-contenido">

        <div class="modal-header">

            <h3 id="tituloPreview">
                Vista previa
            </h3>

            <button
                type="button"
                class="cerrar-modal"
                onclick="cerrarVistaPrevia()"
            >
                ✕
            </button>

        </div>

        <iframe
            id="previewFrame"
            src=""
        ></iframe>

    </div>

</div>

<script>
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
