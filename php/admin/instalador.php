<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Modificar columna ROL
    $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'coordinador', 'supervisor', 'usuario') NOT NULL DEFAULT 'usuario'");

    $passwordHash = password_hash('Naser2026!', PASSWORD_DEFAULT);

    // 2. Crear el sector 8 (Ventas) si no existe en la base de datos
    $pdo->exec("INSERT IGNORE INTO sectores (id, nombre) VALUES (8, 'Ventas')");

    // 3. Insertar Coordinadores
    $pdo->exec("INSERT IGNORE INTO usuarios (nombre, email, password, rol, activo) VALUES
        ('Coordinador General 1', 'coordinador1@naser.com', '$passwordHash', 'coordinador', 1),
        ('Coordinador General 2', 'coordinador2@naser.com', '$passwordHash', 'coordinador', 1)");

    // 4. Insertar Supervisores (CORREGIDO: separadas las líneas con comas ",")
    $pdo->exec("INSERT IGNORE INTO usuarios (nombre, email, password, rol, activo) VALUES
        ('Supervisor Seguridad', 'sup.seguridad@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Mantenimiento', 'sup.mantenimiento@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Operaciones', 'sup.operaciones@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor RRHH', 'sup.rrhh@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Finanzas', 'sup.finanzas@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Compras', 'sup.compras@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Gerencia', 'sup.gerencia@naser.com', '$passwordHash', 'supervisor', 1),
        ('Supervisor Ventas', 'sup.ventas@naser.com', '$passwordHash', 'supervisor', 1)");

    // 5. Asignar sectores a supervisores
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 1 FROM usuarios WHERE email = 'sup.seguridad@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 2 FROM usuarios WHERE email = 'sup.mantenimiento@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 3 FROM usuarios WHERE email = 'sup.operaciones@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 4 FROM usuarios WHERE email = 'sup.rrhh@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 5 FROM usuarios WHERE email = 'sup.finanzas@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 6 FROM usuarios WHERE email = 'sup.compras@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 7 FROM usuarios WHERE email = 'sup.gerencia@naser.com'");
    $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) SELECT id, 8 FROM usuarios WHERE email = 'sup.ventas@naser.com'");

    // 6. Insertar 20 usuarios generales distribuidos entre los 8 sectores
    for ($i = 1; $i <= 20; $i++) {
        $num = str_pad($i, 2, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, 'usuario', 1)");
        $stmt->execute(["Usuario General $num", "usuario$num@naser.com", $passwordHash]);
        
        $usrId = $pdo->lastInsertId();
        if ($usrId) {
            $sectorId = (($i - 1) % 8) + 1; // Distribuidos entre los 8 sectores
            $pdo->exec("INSERT IGNORE INTO usuario_sector (usuario_id, sector_id) VALUES ($usrId, $sectorId)");
        }
    }

    echo "<h1>¡Instalación de roles y usuarios completada con éxito!</h1>";
    echo "<p>Todos los usuarios tienen la contraseña inicial: <strong>Naser2026!</strong></p>";
} catch (Exception $e) {
    echo "<h1>Error:</h1> " . $e->getMessage();
}