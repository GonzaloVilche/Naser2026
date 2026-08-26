<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Solo admin y coordinador pueden administrar usuarios
requireAdminOrCoordinador();

$mensaje = '';
$error = '';

// Procesar formulario (Crear / Editar usuario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $sectoresAsignados = $_POST['sectores'] ?? [];

    if (empty($nombre) || empty($email)) {
        $error = "Nombre y correo son obligatorios.";
    } else {
        if ($id > 0) {
            // Actualizar usuario existente
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $hash, $rol, $activo, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $activo, $id]);
            }
            $mensaje = "Usuario actualizado correctamente.";
        } else {
            // Crear nuevo usuario
            if (empty($password)) {
                $error = "La contraseña es obligatoria para usuarios nuevos.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $email, $hash, $rol, $activo]);
                $id = $pdo->lastInsertId();
                $mensaje = "Usuario creado correctamente.";
            }
        }

        // Actualizar sectores asignados
        if ($id > 0 && empty($error)) {
            $pdo->prepare("DELETE FROM usuario_sector WHERE usuario_id = ?")->execute([$id]);
            $stmtSec = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id) VALUES (?, ?)");
            foreach ($sectoresAsignados as $secId) {
                $stmtSec->execute([$id, (int)$secId]);
            }
        }
    }
}

// Obtener lista de usuarios y sectores
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();
$sectores = $pdo->query("SELECT * FROM sectores ORDER BY orden ASC")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Gestión de Usuarios | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
    <main class="container">
        <h1>Gestión de Usuarios</h1>
        <a href="../dashboard.php">← Volver al Dashboard</a>

        <?php if ($mensaje): ?><div class="alert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <h2>Lista de Usuarios</h2>
        <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse; margin-top:15px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><strong><?= strtoupper($u['rol']) ?></strong></td>
                    <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>