<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$mensaje = '';
$error = '';

$uploadDir = dirname(__DIR__, 2) . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare('SELECT archivo FROM documentos WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if ($doc) {
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

        if ($titulo === '' || $sectorId <= 0 || !in_array($tipo, ['documentacion', 'procedimiento'], true)) {
            $error = 'Completá título, sector y tipo.';
        } elseif (empty($_FILES['archivo']['name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
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
                $baseArchivo = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseArchivo);
                $nombreArchivo = date('Ymd_His') . '_' . $baseArchivo . '.' . $extension;

                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadDir . $nombreArchivo)) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO documentos
                        (sector_id, titulo, descripcion, tipo, archivo, activo, fecha_actualizacion)
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

$sectores = $pdo->query(
    'SELECT id, nombre
     FROM sectores
     ORDER BY orden, nombre'
)->fetchAll();

$documentos = $pdo->query(
    'SELECT
        d.id,
        d.titulo,
        d.descripcion,
        d.tipo,
        d.archivo,
        d.fecha_actualizacion,
        s.nombre AS sector
     FROM documentos d
     INNER JOIN sectores s ON s.id = d.sector_id
     ORDER BY d.fecha_actualizacion DESC, d.id DESC'
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Administrar documentos | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
</head>

<body>

<main class="standalone">

    <a class="back-link" href="../dashboard.php">← Volver al panel</a>

    <div class="section-heading spaced">
        <div>
            <p class="eyebrow">ADMINISTRACIÓN</p>
            <h1>Documentos y procedimientos</h1>
            <p class="muted">
                Cargá documentos por sector y administrá los archivos existentes.
            </p>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-grid">

        <section class="panel-card">

            <h2>Cargar documento</h2>

            <form method="post" enctype="multipart/form-data" class="form-grid">

                <input type="hidden" name="accion" value="crear">

                <label>
                    Título
                    <input type="text" name="titulo" required>
                </label>

                <label>
                    Descripción
                    <textarea name="descripcion"></textarea>
                </label>

                <label>
                    Sector
                    <select name="sector_id" required>
                        <option value="">Seleccionar sector</option>

                        <?php foreach ($sectores as $sector): ?>
                            <option value="<?= (int)$sector['id'] ?>">
                                <?= htmlspecialchars($sector['nombre']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Tipo
                    <select name="tipo" required>
                        <option value="documentacion">Documentación</option>
                        <option value="procedimiento">Procedimiento</option>
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

                <button type="submit" class="btn primary">
                    Cargar documento
                </button>

            </form>

        </section>


        <section class="panel-card">

            <h2>Documentos cargados</h2>

            <?php if (!$documentos): ?>

                <div class="empty-state">
                    Todavía no hay documentos cargados.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>
                        <tr>
                            <th>Título</th>
                            <th>Sector</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Archivo</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($documentos as $doc): ?>

                            <?php
                            $extension = strtolower(
                                pathinfo($doc['archivo'] ?? '', PATHINFO_EXTENSION)
                            );

                            $rutaArchivo =
                                '../../uploads/' .
                                rawurlencode($doc['archivo'] ?? '');
                            ?>

                            <tr>

                                <td>
                                    <strong><?= htmlspecialchars($doc['titulo']) ?></strong>

                                    <?php if (!empty($doc['descripcion'])): ?>
                                        <small style="display:block;margin-top:4px;">
                                            <?= htmlspecialchars($doc['descripcion']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($doc['sector']) ?></td>

                                <td>
                                    <span class="badge">
                                        <?= htmlspecialchars(ucfirst($doc['tipo'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['fecha_actualizacion']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($doc['archivo'] ?? '') ?>
                                </td>

                                <td>

                                    <div class="document-actions">

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
