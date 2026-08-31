<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

requireAdminOrCoordinador();

$mensaje = '';
$error = '';

// 1. PROCESAR ACCIÓN: ACCIONES DE BORRADO
if (isset($_GET['accion']) && $_GET['accion'] === 'borrar') {
    $borrarId = (int)($_GET['id'] ?? 0);
    
    if ($borrarId === (int)$_SESSION['usuario_id']) {
        $error = "No puedes eliminar tu propia cuenta de usuario.";
    } elseif ($borrarId > 0) {
        $stmtDelSec = $pdo->prepare("DELETE FROM usuario_sector WHERE usuario_id = ?");
        $stmtDelSec->execute([$borrarId]);

        $stmtDelUser = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmtDelUser->execute([$borrarId]);
        $mensaje = "Usuario eliminado correctamente.";
    }
}

// 2. PROCESAR ACCIÓN: AÑADIR NUEVO USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';
    $sectoresNuevos = $_POST['sectores_nuevos'] ?? [];

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Nombre, correo y contraseña son obligatorios.";
    } else {
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "El correo ya existe en el sistema.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $email, $hash, $rol]);
            $nuevoId = $pdo->lastInsertId();

            if ($nuevoId && !empty($sectoresNuevos)) {
                $stmtSec = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id) VALUES (?, ?)");
                foreach ($sectoresNuevos as $secId) {
                    $stmtSec->execute([$nuevoId, (int)$secId]);
                }
            }
            $mensaje = "Nuevo usuario y permisos registrados correctamente.";
        }
    }
}

// 3. PROCESAR ACCIÓN: ACTUALIZAR PERMISOS EXISTENTES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_permisos'])) {
    $usuarioId = (int)($_POST['usuario_id'] ?? 0);
    $sectores = $_POST['sectores'] ?? [];

    if ($usuarioId > 0) {
        $stmtDelete = $pdo->prepare("DELETE FROM usuario_sector WHERE usuario_id = ?");
        $stmtDelete->execute([$usuarioId]);

        $stmtInsert = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id) VALUES (?, ?)");
        foreach ($sectores as $sectorId) {
            $stmtInsert->execute([$usuarioId, (int)$sectorId]);
        }
        $mensaje = "Permisos de sectores actualizados correctamente.";
    }
}

// Cargar listas para la interfaz
$usuarios = $pdo->query("SELECT id, nombre, email, rol FROM usuarios ORDER BY nombre ASC")->fetchAll();
$sectores = $pdo->query("SELECT id, nombre FROM sectores ORDER BY orden ASC")->fetchAll();

$asignaciones = [];
$relaciones = $pdo->query("SELECT usuario_id, sector_id FROM usuario_sector")->fetchAll();
foreach ($relaciones as $r) {
    $asignaciones[$r['usuario_id']][] = $r['sector_id'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Permisos y Usuarios | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
    <main class="container" style="padding:20px;">
        <h1>Gestión Integrada de Usuarios y Permisos</h1>
        <a href="../dashboard.php">← Volver al Dashboard</a>

        <?php if ($mensaje): ?><p style="color:green; font-weight:bold; margin-top:15px;"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color:red; font-weight:bold; margin-top:15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

        <!-- SECCIÓN 1: AÑADIR USUARIO Y PERMISOS -->
        <section style="background:#f9f9f9; padding:15px; margin:20px 0; border:1px solid #ddd; border-radius:5px;">
            <h2>Añadir Nuevo Usuario</h2>
            <form method="post">
                <input type="hidden" name="crear_usuario" value="1">
                <p><label>Nombre:*</label><br><input type="text" name="nombre" required style="width:300px;"></p>
                <p><label>Correo:*</label><br><input type="email" name="email" required style="width:300px;"></p>
                <p><label>Contraseña:*</label><br><input type="password" name="password" required style="width:300px;"></p>
                <p>
                    <label>Rol:*</label><br>
                    <select name="rol" required>
                        <option value="usuario">Usuario General</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="coordinador">Coordinador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </p>
                <p>
                    <label>Asignar Sectores Iniciales:</label><br>
                    <?php foreach ($sectores as $s): ?>
                        <label style="margin-right:10px;"><input type="checkbox" name="sectores_nuevos[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['nombre']) ?></label>
                    <?php endforeach; ?>
                </p>
                <button type="submit" class="btn primary">Añadir Usuario</button>
            </form>
        </section>

        <!-- SECCIÓN 2: ACTUALIZAR PERMISOS A EXISTENTES -->
        <section style="background:#f9f9f9; padding:15px; margin:20px 0; border:1px solid #ddd; border-radius:5px;">
            <h2>Modificar Permisos de Sectores</h2>
            <form method="post">
                <input type="hidden" name="actualizar_permisos" value="1">
                <p>
                    <label>Seleccionar Usuario:</label><br>
                    <select name="usuario_id" required>
                        <option value="">-- Selecciona --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?> (<?= strtoupper($u['rol']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <?php foreach ($sectores as $s): ?>
                        <label style="margin-right:10px;"><input type="checkbox" name="sectores[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['nombre']) ?></label>
                    <?php endforeach; ?>
                </p>
                <button type="submit" class="btn primary">Guardar Cambios de Permisos</button>
            </form>
        </section>

        <!-- SECCIÓN 3: LISTADO Y ELIMINACIÓN -->
        <h2>Usuarios y Acciones</h2>
        <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#eee;">
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><strong><?= strtoupper($u['rol']) ?></strong></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                            <a href="permisos.php?accion=borrar&id=<?= $u['id'] ?>" 
                               onclick="return confirm('¿Seguro que deseas eliminar este usuario?');" 
                               style="color:red; font-weight:bold;">Borrar</a>
                        <?php else: ?>
                            <em>(Sesión actual)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>