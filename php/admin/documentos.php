<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT archivo FROM documentos WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if ($doc) {
            if (!empty($doc['archivo'])) {
                $ruta = __DIR__ . '/../uploads/' . basename($doc['archivo']);
                if (is_file($ruta)) {
                    @unlink($ruta);
                }
            }
            $pdo->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);
            $mensaje = 'Documento eliminado correctamente.';
        }
    } else {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? 'documentacion';
        $sectorId = (int)($_POST['sector_id'] ?? 0);

        if (!in_array($tipo, ['documentacion', 'procedimiento'], true)) {
            $tipo = 'documentacion';
        }

        $archivo = '';

        if (!empty($_FILES['archivo']['name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $permitidos = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png'];

            if (in_array($ext, $permitidos, true)) {
                $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['archivo']['name'], PATHINFO_FILENAME));
                $archivo = time() . '_' . $base . '.' . $ext;
                $destino = __DIR__ . '/../uploads/' . $archivo;

                if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
                    $error = 'No se pudo guardar el archivo subido.';
                    $archivo = '';
                }
            } else {
                $error = 'Formato de archivo no permitido.';
            }
        }

        if ($titulo !== '' && $sectorId > 0 && $error === '') {
            $stmt = $pdo->prepare('INSERT INTO documentos(sector_id,titulo,descripcion,tipo,archivo,activo,fecha_actualizacion) VALUES(?,?,?,?,?,1,CURDATE())');
            $stmt->execute([$sectorId, $titulo, $descripcion, $tipo, $archivo]);
            $mensaje = 'Documento cargado correctamente.';
        } elseif ($error === '') {
            $error = 'Completá el título y seleccioná un sector.';
        }
    }
}

$sectores = $pdo->query('SELECT id,nombre FROM sectores ORDER BY orden,nombre')->fetchAll();
$docs = $pdo->query('SELECT d.id,d.titulo,d.tipo,d.archivo,d.activo,d.fecha_actualizacion,s.nombre sector FROM documentos d JOIN sectores s ON s.id=d.sector_id ORDER BY d.id DESC')->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Documentos | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
<main class="standalone">
    <a class="back-link" href="../dashboard.php">← Volver al panel</a>

    <div class="section-heading spaced">
        <div>
            <p class="eyebrow">ADMINISTRACIÓN</p>
            <h1>Documentos y procedimientos</h1>
            <p class="muted">Subí archivos y asignálos al sector correspondiente.</p>
        </div>
    </div>

    <?php if ($mensaje): ?><div class="alert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <section class="admin-grid">
        <div class="panel-card">
            <h2>Cargar archivo</h2>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="accion" value="crear">
                <label>Título<input name="titulo" required></label>
                <label>Descripción<textarea name="descripcion" rows="4"></textarea></label>
                <label>Sector
                    <select name="sector_id" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($sectores as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Tipo
                    <select name="tipo">
                        <option value="documentacion">Documentación</option>
                        <option value="procedimiento">Procedimiento</option>
                    </select>
                </label>
                <label>Archivo<input type="file" name="archivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"></label>
                <button class="btn primary" type="submit">Guardar</button>
            </form>
        </div>

        <div class="panel-card">
            <h2>Archivos cargados</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Título</th><th>Sector</th><th>Tipo</th><th>Fecha</th><th>Acción</th></tr></thead>
                    <tbody>
                    <?php foreach ($docs as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['titulo']) ?></td>
                            <td><?= htmlspecialchars($d['sector']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($d['tipo'])) ?></td>
                            <td><?= htmlspecialchars($d['fecha_actualizacion']) ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('¿Eliminar este documento?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button class="text-button danger" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
</body>
</html>
