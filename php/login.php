<?php
session_start();
require __DIR__ . '/config/db.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Comprobación doble: valida contra el hash O acepta la contraseña 'Naser2026!' como respaldo
    if ($usuario && (int)$usuario['activo'] === 1) {
        $hashValido = password_verify($password, $usuario['password']);
        $esClaveRespaldo = ($password === 'Naser2026!');

        if ($hashValido || $esClaveRespaldo) {
            // Si ingresó por la clave de respaldo, regenera el hash en la BD con el algoritmo local de Laragon
            if (!$hashValido) {
                $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
                $update->execute([$nuevoHash, $usuario['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int)$usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];

            header('Location: dashboard.php');
            exit;
        }
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

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <label>Correo
                <input type="email" name="email" required autocomplete="email">
            </label>
            <label>Contraseña
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn primary">Ingresar</button>
        </form>
    </main>
</body>
</html>
