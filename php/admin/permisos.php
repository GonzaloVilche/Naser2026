<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$userId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id,nombre,email,rol FROM usuarios WHERE id = ?');
$stmt->execute([$userId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    exit('Usuario no encontrado.');
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario['rol'] !== 'admin') {
    $sectoresSeleccionados = array_map('intval', $_POST['sectores'] ?? []);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM usuario_sector WHERE usuario_id = ?')->execute([$userId]);
        $ins = $pdo->prepare('INSERT INTO usuario_sector(usuario_id,sector_id) VALUES(?,?)');

        foreach ($sectoresSeleccionados as $sid) {
            $ins->execute([$userId, $sid]);
        }

        $pdo->commit();
        $mensaje = 'Accesos guardados correctamente.';
    } catch (Throwable $e) {
        $pdo->rollBack();
        exit('No se pudieron guardar los accesos.');
    }
}

$sectores = $pdo->query('SELECT id,nombre FROM sectores ORDER BY orden,nombre')->fetchAll();
$stmt = $pdo->prepare('SELECT sector_id FROM usuario_sector WHERE usuario_id = ?');
$stmt->execute([$userId]);
$asignados = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Permisos | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
<main class="standalone">
    <a class="back-link" href="usuarios.php">← Volver a usuarios</a>

    <div class="panel-card narrow">
        <p class="eyebrow">PERMISOS</p>
        <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
        <p class="muted"><?= htmlspecialchars($usuario['email']) ?></p>

        <?php if ($mensaje): ?><div class="alert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

        <?php if ($usuario['rol'] === 'admin'): ?>
            <div class="alert success">Los administradores tienen acceso a todos los sectores.</div>
        <?php else: ?>
            <form method="post" class="check-grid">
                <?php foreach ($sectores as $s): ?>
                    <label class="check-card">
                        <input type="checkbox" name="sectores[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $asignados, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($s['nombre']) ?></span>
                    </label>
                <?php endforeach; ?>
                <button class="btn primary" type="submit">Guardar accesos</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
