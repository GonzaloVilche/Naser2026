<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
        if ($nombre && $email && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES(?,?,?,?,1)');
            $stmt->execute([$nombre,$email,$hash,$rol]);
            $mensaje = 'Usuario creado correctamente.';
        }
    }

    if ($accion === 'estado') {
        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;
        if ($id !== (int)$_SESSION['usuario_id']) {
            $stmt = $pdo->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
            $stmt->execute([$activo,$id]);
            $mensaje = 'Estado actualizado.';
        }
    }
}

$usuarios = $pdo->query('SELECT id,nombre,email,rol,activo,creado_en FROM usuarios ORDER BY nombre')->fetchAll();
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios | NASER SGI</title><link rel="stylesheet" href="../../style.css"></head>
<body><main class="standalone">
<a class="back-link" href="../dashboard.php">← Volver al panel</a>
<div class="section-heading spaced"><div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Usuarios y accesos</h1><p class="muted">Creá usuarios y definí qué sectores puede ver cada uno.</p></div></div>
<?php if ($mensaje): ?><div class="alert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<section class="admin-grid">
<div class="panel-card"><h2>Nuevo usuario</h2><form method="post" class="form-grid"><input type="hidden" name="accion" value="crear"><label>Nombre<input name="nombre" required></label><label>Correo<input type="email" name="email" required></label><label>Contraseña<input type="password" name="password" required></label><label>Rol<select name="rol"><option value="usuario">Usuario</option><option value="admin">Administrador</option></select></label><button class="btn primary" type="submit">Crear usuario</button></form></div>
<div class="panel-card"><h2>Usuarios existentes</h2><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Permisos</th></tr></thead><tbody><?php foreach($usuarios as $u): ?><tr><td><?= htmlspecialchars($u['nombre']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['rol']) ?></td><td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td><td><a class="text-link" href="permisos.php?id=<?= (int)$u['id'] ?>">Modificar accesos</a></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>
</main></body></html>
