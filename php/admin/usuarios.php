<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$mensaje = '';
$error = '';

function asignarPermisos(PDO $pdo, int $usuarioId, string $rol): void
{
    $pdo->prepare('DELETE FROM usuario_sector WHERE usuario_id = ?')->execute([$usuarioId]);

    if ($rol === 'admin') {
        $stmt = $pdo->prepare('INSERT INTO usuario_sector (usuario_id, sector_id, puede_ver, puede_editar) SELECT ?, id, 1, 1 FROM sectores');
        $stmt->execute([$usuarioId]);
        return;
    }

    if ($rol === 'supervisor') {
        $stmt = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id, puede_ver, puede_editar) SELECT ?, id, 1, 1 FROM sectores WHERE slug IN ('operaciones','seguridad','mantenimiento')");
        $stmt->execute([$usuarioId]);
        return;
    }

    if ($rol === 'ventas') {
        $stmt = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id, puede_ver, puede_editar) SELECT ?, id, 1, 0 FROM sectores WHERE slug IN ('operaciones','finanzas','gerencia')");
        $stmt->execute([$usuarioId]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO usuario_sector (usuario_id, sector_id, puede_ver, puede_editar) SELECT ?, id, 1, 0 FROM sectores WHERE slug IN ('operaciones','seguridad')");
    $stmt->execute([$usuarioId]);
}

$rolesValidos = ['admin', 'supervisor', 'ventas', 'operador'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? '';
            $zonaId = ($_POST['zona_id'] ?? '') !== '' ? (int)$_POST['zona_id'] : null;

            if ($nombre === '' || $email === '' || $password === '') {
                throw new RuntimeException('Completá nombre, correo y contraseña.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El correo ingresado no es válido.');
            }
            if (!in_array($rol, $rolesValidos, true)) {
                throw new RuntimeException('El rol seleccionado no es válido.');
            }
            if (in_array($rol, ['supervisor', 'operador'], true) && !$zonaId) {
                throw new RuntimeException('Supervisor y Operador deben tener una zona asignada.');
            }
            if ($rol === 'admin' || $rol === 'ventas') {
                $zonaId = null;
            }

            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password, rol, zona_id, activo) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT), $rol, $zonaId]);
            $nuevoId = (int)$pdo->lastInsertId();
            asignarPermisos($pdo, $nuevoId, $rol);
            $mensaje = 'Usuario creado correctamente.';
        }

        if ($accion === 'editar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? '';
            $zonaId = ($_POST['zona_id'] ?? '') !== '' ? (int)$_POST['zona_id'] : null;

            if ($id <= 0 || $nombre === '' || $email === '') {
                throw new RuntimeException('Datos de usuario incompletos.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El correo ingresado no es válido.');
            }
            if (!in_array($rol, $rolesValidos, true)) {
                throw new RuntimeException('El rol seleccionado no es válido.');
            }
            if (in_array($rol, ['supervisor', 'operador'], true) && !$zonaId) {
                throw new RuntimeException('Supervisor y Operador deben tener una zona asignada.');
            }
            if ($rol === 'admin' || $rol === 'ventas') {
                $zonaId = null;
            }

            $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ?, rol = ?, zona_id = ? WHERE id = ?');
            $stmt->execute([$nombre, $email, $rol, $zonaId, $id]);
            asignarPermisos($pdo, $id, $rol);

            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                $_SESSION['rol'] = $rol;
            }

            $mensaje = 'Usuario actualizado correctamente.';
        }

        if ($accion === 'password') {
            $id = (int)($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if ($id <= 0 || strlen($password) < 6) {
                throw new RuntimeException('La nueva contraseña debe tener al menos 6 caracteres.');
            }

            $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            $mensaje = 'Contraseña actualizada correctamente.';
        }

        if ($accion === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                throw new RuntimeException('No podés desactivar tu propio usuario mientras estás conectado.');
            }

            $stmt = $pdo->prepare('UPDATE usuarios SET activo = IF(activo = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            $mensaje = 'Estado del usuario actualizado.';
        }

        if ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                throw new RuntimeException('No podés eliminar tu propio usuario mientras estás conectado.');
            }

            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $mensaje = 'Usuario eliminado correctamente.';
        }
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) == 1062) {
            $error = 'Ese correo ya está registrado en otro usuario.';
        } else {
            $error = 'Ocurrió un error al guardar los cambios.';
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$zonas = $pdo->query('SELECT id, nombre FROM zonas WHERE activa = 1 ORDER BY id')->fetchAll();
$usuarios = $pdo->query("SELECT u.id, u.nombre, u.email, u.rol, u.activo, u.zona_id, z.nombre AS zona FROM usuarios u LEFT JOIN zonas z ON z.id = u.zona_id ORDER BY FIELD(u.rol,'admin','supervisor','ventas','operador','usuario'), u.nombre")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | NASER SGI</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .usuarios-actions{display:flex;gap:7px;flex-wrap:wrap}.usuarios-actions form{margin:0}.btn-small{padding:7px 10px;font-size:12px}.edit-panel{display:none;margin-top:22px}.edit-panel.open{display:block}.password-row{display:flex;gap:8px;align-items:center}.password-row input{min-width:180px}.modal-user{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:20px;z-index:5000}.modal-user.open{display:flex}.modal-user-box{width:min(620px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:14px;padding:26px;box-shadow:0 18px 55px rgba(0,0,0,.22)}.modal-user-head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px}.modal-user-head button{background:transparent;color:#555;padding:5px 8px;font-size:20px}.role-help{font-size:12px;color:var(--texto-secundario);margin-top:8px}.estado-activo{color:#17663a;font-weight:700}.estado-inactivo{color:#9d1c13;font-weight:700}@media(max-width:800px){.password-row{flex-direction:column;align-items:stretch}.users-table table{min-width:900px}}
    </style>
</head>
<body>
<main class="standalone">
    <a class="back-link" href="../dashboard.php">← Volver al panel</a>

    <div class="section-heading spaced">
        <div>
            <p class="eyebrow">ADMINISTRACIÓN</p>
            <h1>Usuarios y permisos</h1>
            <p class="muted">Creá, editá, desactivá, eliminá y cambiá contraseñas desde este panel.</p>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="content-card">
        <h3>Crear nuevo usuario</h3>
        <p class="muted">Los permisos se asignan automáticamente según el rol.</p>

        <form method="post" class="user-create-grid" style="margin-top:18px">
            <input type="hidden" name="accion" value="crear">
            <input type="text" name="nombre" placeholder="Nombre y apellido" required>
            <input type="email" name="email" placeholder="Correo / usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <select name="rol" required>
                <option value="operador">Operador</option>
                <option value="supervisor">Supervisor</option>
                <option value="ventas">Ventas</option>
                <option value="admin">Administrador</option>
            </select>
            <select name="zona_id">
                <option value="">Sin zona</option>
                <?php foreach ($zonas as $zona): ?>
                    <option value="<?= (int)$zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn primary">+ Crear usuario</button>
        </form>

        <p class="role-help">Admin: control total · Supervisor: control de Operaciones, Seguridad y Mantenimiento · Ventas: lectura comercial · Operador: lectura de Operaciones y Seguridad.</p>
    </section>

    <div class="table-wrapper users-table">
        <table>
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Zona</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($usuario['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                    <td><span class="badge <?= $usuario['rol'] === 'admin' ? 'badge-admin' : 'badge-user' ?>"><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span></td>
                    <td><?= htmlspecialchars($usuario['zona'] ?? '-') ?></td>
                    <td><span class="<?= $usuario['activo'] ? 'estado-activo' : 'estado-inactivo' ?>"><?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                        <div class="usuarios-actions">
                            <button type="button" class="btn secondary btn-small" onclick='editarUsuario(<?= json_encode($usuario, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
                            <button type="button" class="btn secondary btn-small" onclick="passwordUsuario(<?= (int)$usuario['id'] ?>, <?= htmlspecialchars(json_encode($usuario['nombre']), ENT_QUOTES) ?>)">Contraseña</button>

                            <?php if ((int)$usuario['id'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                                <form method="post">
                                    <input type="hidden" name="accion" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
                                    <button type="submit" class="btn secondary btn-small"><?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?></button>
                                </form>

                                <form method="post" onsubmit="return confirm('¿Seguro que querés eliminar este usuario? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-success">Tu usuario</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="modalEditar" class="modal-user">
    <div class="modal-user-box">
        <div class="modal-user-head"><h3>Editar usuario</h3><button type="button" onclick="cerrarModal('modalEditar')">×</button></div>
        <form method="post" class="form-grid">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <label>Nombre<input type="text" name="nombre" id="edit_nombre" required></label>
            <label>Correo / usuario<input type="email" name="email" id="edit_email" required></label>
            <label>Rol
                <select name="rol" id="edit_rol" required>
                    <option value="admin">Administrador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="ventas">Ventas</option>
                    <option value="operador">Operador</option>
                </select>
            </label>
            <label>Zona
                <select name="zona_id" id="edit_zona">
                    <option value="">Sin zona</option>
                    <?php foreach ($zonas as $zona): ?>
                        <option value="<?= (int)$zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn primary">Guardar cambios</button>
        </form>
    </div>
</div>

<div id="modalPassword" class="modal-user">
    <div class="modal-user-box">
        <div class="modal-user-head"><h3>Cambiar contraseña</h3><button type="button" onclick="cerrarModal('modalPassword')">×</button></div>
        <p class="muted" id="password_nombre"></p>
        <form method="post" class="form-grid" style="margin-top:18px">
            <input type="hidden" name="accion" value="password">
            <input type="hidden" name="id" id="password_id">
            <label>Nueva contraseña<input type="password" name="password" minlength="6" required placeholder="Mínimo 6 caracteres"></label>
            <button type="submit" class="btn primary">Actualizar contraseña</button>
        </form>
    </div>
</div>

<script>
function editarUsuario(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nombre').value = usuario.nombre;
    document.getElementById('edit_email').value = usuario.email;
    document.getElementById('edit_rol').value = usuario.rol;
    document.getElementById('edit_zona').value = usuario.zona_id || '';
    document.getElementById('modalEditar').classList.add('open');
}
function passwordUsuario(id, nombre) {
    document.getElementById('password_id').value = id;
    document.getElementById('password_nombre').textContent = 'Usuario: ' + nombre;
    document.getElementById('modalPassword').classList.add('open');
}
function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-user').forEach(function(modal){
    modal.addEventListener('click', function(e){ if(e.target === modal) modal.classList.remove('open'); });
});
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') document.querySelectorAll('.modal-user.open').forEach(function(m){m.classList.remove('open');}); });
</script>
</body>
</html>
