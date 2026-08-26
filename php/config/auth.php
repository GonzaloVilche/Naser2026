<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        $admin = strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false;
        header('Location: ' . ($admin ? '../login.php' : 'login.php'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado. Esta sección es exclusiva de Gerencia.');
    }
}

function requireDocumentManager(): void {
    requireLogin();
    if (!in_array($_SESSION['rol'] ?? '', ['admin','supervisor'], true)) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

function esAdmin(): bool {
    return (($_SESSION['rol'] ?? '') === 'admin');
}

function obtenerSectorUsuario(PDO $pdo): ?array {
    if (esAdmin()) return null;
    $stmt=$pdo->prepare('SELECT s.id,s.nombre,s.slug,us.puede_ver,us.puede_editar FROM sectores s INNER JOIN usuario_sector us ON us.sector_id=s.id WHERE us.usuario_id=? AND us.puede_ver=1 ORDER BY us.puede_editar DESC,s.orden LIMIT 1');
    $stmt->execute([$_SESSION['usuario_id']]);
    $sector=$stmt->fetch();
    return $sector ?: null;
}

function puedeVerSector(PDO $pdo,int $sectorId): bool {
    if (esAdmin()) return true;
    $stmt=$pdo->prepare('SELECT 1 FROM usuario_sector WHERE usuario_id=? AND sector_id=? AND puede_ver=1 LIMIT 1');
    $stmt->execute([$_SESSION['usuario_id'],$sectorId]);
    return (bool)$stmt->fetchColumn();
}

function puedeEditarSector(PDO $pdo,int $sectorId): bool {
    if (esAdmin()) return true;
    $stmt=$pdo->prepare('SELECT puede_editar FROM usuario_sector WHERE usuario_id=? AND sector_id=? LIMIT 1');
    $stmt->execute([$_SESSION['usuario_id'],$sectorId]);
    return (bool)$stmt->fetchColumn();
}
