<?php
<<<<<<< HEAD
session_start();require __DIR__.'/config/db.php';
if(!empty($_SESSION['usuario_id'])){header('Location: dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
$email=strtolower(trim($_POST['email']??''));$password=$_POST['password']??'';
$st=$pdo->prepare('SELECT * FROM usuarios WHERE LOWER(email)=? LIMIT 1');$st->execute([$email]);$u=$st->fetch();
if($u&&(int)$u['activo']===1&&password_verify($password,$u['password'])){session_regenerate_id(true);$_SESSION['usuario_id']=$u['id'];$_SESSION['nombre']=$u['nombre'];$_SESSION['email']=$u['email'];$_SESSION['rol']=$u['rol'];header('Location: dashboard.php');exit;}
$error='Correo o contraseña incorrectos.';
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ingreso NASER</title><link rel="stylesheet" href="../style.css"></head><body class="login-page"><div class="login-shell"><section class="login-brand"><img src="../img/logo-naser.png"><h1>Sistema de Gestión Integrado</h1><p>Entorno corporativo de prueba.</p></section><section class="login-card"><p class="eyebrow">ACCESO INTERNO</p><h2>Ingresar</h2><?php if($error):?><div class="alert error"><?=$error?></div><?php endif;?><form method="post" class="form-grid"><label>Correo<input type="email" name="email" required></label><label>Contraseña<input type="password" name="password" required></label><button class="btn primary">Ingresar</button></form></section></div></body></html>
=======
session_start();
require __DIR__ . '/config/db.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id,nombre,email,password,rol,activo FROM usuarios WHERE LOWER(email)=? LIMIT 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && (int)$usuario['activo'] === 1 && password_verify($password, $usuario['password'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ingresar | NASER SGI</title>
<link rel="stylesheet" href="../style.css">
</head>
<body class="login-body">
<main class="login-card">
<div class="brand">SERVICIOS NASER <span>SRL</span></div>
<p class="eyebrow">Sistema de Gestión Integrado</p>
<h1>Ingreso al sistema</h1>
<p class="muted">Accedé con tu usuario asignado.</p>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="form-grid">
<label>Correo<input type="email" name="email" required></label>
<label>Contraseña<input type="password" name="password" required></label>
<button type="submit" class="btn primary">Ingresar</button>
</form>
</main>
</body>
</html>
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
