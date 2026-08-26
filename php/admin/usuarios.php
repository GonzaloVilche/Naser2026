<?php
require __DIR__ . '/../config/auth.php';
requireAdmin();
require __DIR__ . '/../config/db.php';

$mensaje = '';
$error = '';

$sectores = $pdo->query(
    "SELECT id,nombre,slug FROM sectores WHERE slug <> 'gerencia' ORDER BY orden,nombre"
)->fetchAll();

function asignarSector(PDO $pdo, int $usuarioId, ?int $sectorId, string $rol): void {
    $pdo->prepare('DELETE FROM usuario_sector WHERE usuario_id=?')->execute([$usuarioId]);

    if ($rol === 'admin') {
        $stmt=$pdo->prepare(
            'INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar)
             SELECT ?,id,1,1 FROM sectores'
        );
        $stmt->execute([$usuarioId]);
        return;
    }

    if ($sectorId) {
        $stmt=$pdo->prepare(
            'INSERT INTO usuario_sector(usuario_id,sector_id,puede_ver,puede_editar)
             VALUES(?,?,1,?)'
        );
        $stmt->execute([$usuarioId,$sectorId,$rol==='supervisor'?1:0]);
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $accion=$_POST['accion'] ?? '';

    if ($accion==='crear') {
        $nombre=trim($_POST['nombre'] ?? '');
        $email=trim($_POST['email'] ?? '');
        $password=$_POST['password'] ?? '';
        $rol=$_POST['rol'] ?? 'operador';
        $sectorId=!empty($_POST['sector_id'])?(int)$_POST['sector_id']:null;

        if ($nombre==='' || $email==='' || $password==='') {
            $error='Completá nombre, correo y contraseña.';
        } else {
            try {
                $stmt=$pdo->prepare('INSERT INTO usuarios(nombre,email,password,rol,activo) VALUES(?,?,?,?,1)');
                $stmt->execute([$nombre,$email,password_hash($password,PASSWORD_DEFAULT),$rol]);
                $id=(int)$pdo->lastInsertId();
                asignarSector($pdo,$id,$sectorId,$rol);
                $mensaje='Usuario creado correctamente.';
            } catch (Throwable $e) {
                $error='No se pudo crear el usuario. Revisá que el correo no esté repetido.';
            }
        }
    }

    if ($accion==='editar') {
        $id=(int)($_POST['id'] ?? 0);
        $nombre=trim($_POST['nombre'] ?? '');
        $email=trim($_POST['email'] ?? '');
        $rol=$_POST['rol'] ?? 'operador';
        $sectorId=!empty($_POST['sector_id'])?(int)$_POST['sector_id']:null;
        $password=$_POST['password'] ?? '';

        if ($id>0) {
            if ($password!=='') {
                $stmt=$pdo->prepare('UPDATE usuarios SET nombre=?,email=?,rol=?,password=? WHERE id=?');
                $stmt->execute([$nombre,$email,$rol,password_hash($password,PASSWORD_DEFAULT),$id]);
            } else {
                $stmt=$pdo->prepare('UPDATE usuarios SET nombre=?,email=?,rol=? WHERE id=?');
                $stmt->execute([$nombre,$email,$rol,$id]);
            }
            asignarSector($pdo,$id,$sectorId,$rol);
            $mensaje='Usuario actualizado.';
        }
    }

    if ($accion==='estado') {
        $id=(int)($_POST['id'] ?? 0);
        if ($id !== (int)$_SESSION['usuario_id']) {
            $pdo->prepare('UPDATE usuarios SET activo=IF(activo=1,0,1) WHERE id=?')->execute([$id]);
            $mensaje='Estado actualizado.';
        }
    }

    if ($accion==='eliminar') {
        $id=(int)($_POST['id'] ?? 0);
        if ($id !== (int)$_SESSION['usuario_id']) {
            $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
            $mensaje='Usuario eliminado.';
        }
    }
}

$usuarios=$pdo->query(
    "SELECT
        u.id,
        u.nombre,
        u.email,
        u.rol,
        u.activo,

        (
            SELECT s.id
            FROM usuario_sector us2
            INNER JOIN sectores s ON s.id = us2.sector_id
            WHERE us2.usuario_id = u.id
              AND us2.puede_ver = 1
            ORDER BY us2.puede_editar DESC, s.orden, s.nombre
            LIMIT 1
        ) AS sector_id,

        (
            SELECT s.nombre
            FROM usuario_sector us2
            INNER JOIN sectores s ON s.id = us2.sector_id
            WHERE us2.usuario_id = u.id
              AND us2.puede_ver = 1
            ORDER BY us2.puede_editar DESC, s.orden, s.nombre
            LIMIT 1
        ) AS sector

     FROM usuarios u

     ORDER BY
        FIELD(u.rol,'admin','supervisor','ventas','operador','usuario'),
        u.nombre"
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Usuarios | NASER SGI</title>
<link rel="stylesheet" href="../../style.css">
<style>
.user-admin-grid{display:grid;grid-template-columns:360px 1fr;gap:26px;align-items:start}
.user-panel{background:#fff;border:1px solid var(--gris-borde);border-radius:14px;padding:24px;box-shadow:var(--sombra-suave)}
.user-table{overflow-x:auto}
.user-role{font-weight:800;color:var(--verde-oscuro)}
.user-sector{font-size:11px;color:var(--texto-secundario)}
@media(max-width:950px){.user-admin-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="standalone">
<a class="back-link" href="../dashboard.php">← Volver al panel</a>
<div class="section-heading spaced">
<div><p class="eyebrow">GERENCIA</p><h1>Administración de usuarios</h1><p class="muted">Cada supervisor administra un único sector.</p></div>
</div>

<?php if($mensaje): ?><div class="alert success"><?=htmlspecialchars($mensaje)?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif; ?>

<div class="user-admin-grid">
<section class="user-panel">
<h2>Nuevo usuario</h2>
<form method="post" class="form-grid">
<input type="hidden" name="accion" value="crear">
<label>Nombre<input name="nombre" required></label>
<label>Correo<input type="email" name="email" required></label>
<label>Contraseña<input type="password" name="password" required></label>
<label>Rol
<select name="rol" required>
<option value="supervisor">Supervisor</option>
<option value="operador">Operador</option>
<option value="ventas">Ventas</option>
<option value="admin">Gerencia / Admin</option>
</select>
</label>
<label>Sector
<select name="sector_id">
<option value="">Sin sector / Gerencia</option>
<?php foreach($sectores as $s): ?>
<option value="<?=$s['id']?>"><?=htmlspecialchars($s['nombre'])?></option>
<?php endforeach; ?>
</select>
</label>
<button class="btn primary">Crear usuario</button>
</form>
</section>

<section class="user-panel">
<h2>Usuarios del sistema</h2>
<div class="user-table">
<table>
<thead><tr><th>Usuario</th><th>Rol</th><th>Sector</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($usuarios as $u): ?>
<tr>
<td><strong><?=htmlspecialchars($u['nombre'])?></strong><br><small><?=htmlspecialchars($u['email'])?></small></td>
<td><span class="user-role"><?=htmlspecialchars($u['rol']==='admin'?'Gerencia':ucfirst($u['rol']))?></span></td>
<td><span class="user-sector"><?=htmlspecialchars($u['sector'] ?? ($u['rol']==='admin'?'Todos':'Sin asignar'))?></span></td>
<td><?=((int)$u['activo']===1)?'Activo':'Inactivo'?></td>
<td>
<details>
<summary class="btn secondary" style="cursor:pointer">Editar</summary>
<form method="post" class="form-grid" style="margin-top:12px;min-width:260px">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?=$u['id']?>">
<input name="nombre" value="<?=htmlspecialchars($u['nombre'])?>" required>
<input type="email" name="email" value="<?=htmlspecialchars($u['email'])?>" required>
<select name="rol">
<?php foreach(['admin'=>'Gerencia / Admin','supervisor'=>'Supervisor','ventas'=>'Ventas','operador'=>'Operador'] as $rv=>$rn): ?>
<option value="<?=$rv?>" <?=$u['rol']===$rv?'selected':''?>><?=$rn?></option>
<?php endforeach; ?>
</select>
<select name="sector_id">
<option value="">Sin sector / Gerencia</option>
<?php foreach($sectores as $s): ?>
<option value="<?=$s['id']?>" <?=((int)$u['sector_id']===(int)$s['id'])?'selected':''?>><?=htmlspecialchars($s['nombre'])?></option>
<?php endforeach; ?>
</select>
<input type="password" name="password" placeholder="Nueva clave (opcional)">
<button class="btn primary">Guardar</button>
</form>
</details>
<form method="post" style="display:inline">
<input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?=$u['id']?>">
<button class="btn secondary"><?=$u['activo']?'Desactivar':'Activar'?></button>
</form>
<?php if((int)$u['id']!==(int)$_SESSION['usuario_id']): ?>
<form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar usuario?')">
<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=$u['id']?>">
<button class="btn-delete">Eliminar</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
</div>
</main>
</body>
</html>
