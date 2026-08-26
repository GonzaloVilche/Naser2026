<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si el usuario inició sesión
function requireLogin() {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

// 2. Verificar si es Administrador o Coordinador (acceso total)
function requireAdminOrCoordinador() {
    requireLogin();
    $rol = $_SESSION['rol'] ?? '';
    if ($rol !== 'admin' && $rol !== 'coordinador') {
        header('Location: ../dashboard.php');
        exit;
    }
}

// 3. Obtener los IDs de sectores permitidos para el usuario actual
function getSectoresPermitidos($pdo, $usuarioId, $rol) {
    // Administradores y Coordinadores ven TODOS los sectores
    if ($rol === 'admin' || $rol === 'coordinador') {
        $stmt = $pdo->query("SELECT id FROM sectores");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Supervisores y Usuarios ven solo los sectores que tienen asignados
    $stmt = $pdo->prepare("SELECT sector_id FROM usuario_sector WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// 4. Verificar si el usuario tiene acceso a un sector específico
function tieneAccesoSector($pdo, $usuarioId, $rol, $sectorId) {
    if ($rol === 'admin' || $rol === 'coordinador') {
        return true;
    }
    
    $sectores = getSectoresPermitidos($pdo, $usuarioId, $rol);
    return in_array($sectorId, $sectores);
}