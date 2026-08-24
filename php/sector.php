<?php
require __DIR__ . '/config/auth.php';
requireLogin();
require __DIR__ . '/config/db.php';

$slug = trim($_GET['sector'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

$stmt = $pdo->prepare('SELECT id, nombre, slug FROM sectores WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$sector = $stmt->fetch();

if (!$sector) {
    http_response_code(404);
    exit('Sector no encontrado.');
}

if (!userHasSectorAccess($pdo, (int)$_SESSION['usuario_id'], (int)$sector['id'], $_SESSION['rol'])) {
    http_response_code(403);
    exit('No tenés permiso para acceder a este sector.');
}

$params = [$sector['id']];
$sql = 'SELECT id, titulo, descripcion, tipo, archivo, fecha_actualizacion FROM documentos WHERE sector_id = ? AND activo = 1';
if (in_array($tipo, ['documentacion', 'procedimiento'], true)) {
    $sql .= ' AND tipo = ?';
    $params[] = $tipo;
}
$sql .= ' ORDER BY fecha_actualizacion DESC, titulo';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($sector['nombre']) ?> | NASER SGI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<main class="standalone">
    <a class="back-link" href="dashboard.php">← Volver al panel</a>
    <div class="section-heading spaced">
        <div>
            <p class="eyebrow">SECTOR</p>
            <h1><?= htmlspecialchars($sector['nombre']) ?></h1>
            <p class="muted">Documentación y procedimientos disponibles.</p>
        </div>
    </div>

    <div class="filter-row">
        <a class="btn <?= $tipo === '' ? 'primary' : 'secondary' ?>" href="sector.php?sector=<?= urlencode($sector['slug']) ?>">Todo</a>
        <a class="btn <?= $tipo === 'documentacion' ? 'primary' : 'secondary' ?>" href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion">Documentación</a>
        <a class="btn <?= $tipo === 'procedimiento' ? 'primary' : 'secondary' ?>" href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento">Procedimientos</a>
    </div>

    <div class="document-list">
        <?php if (!$documentos): ?>
            <div class="empty-state">Todavía no hay archivos cargados en esta sección.</div>
        <?php endif; ?>

        <?php foreach ($documentos as $doc): ?>
            <article class="document-card">
                <div>
                    <span class="badge"><?= htmlspecialchars($doc['tipo']) ?></span>
                    <h3><?= htmlspecialchars($doc['titulo']) ?></h3>
                    <p><?= htmlspecialchars($doc['descripcion'] ?? '') ?></p>
                    <small>Actualizado: <?= htmlspecialchars($doc['fecha_actualizacion']) ?></small>
                </div>
                <?php if (!empty($doc['archivo'])): ?>
                    <a class="btn primary" href="uploads/<?= rawurlencode($doc['archivo']) ?>" target="_blank">Abrir archivo</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
