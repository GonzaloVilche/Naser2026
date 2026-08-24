<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

function userHasSectorAccess(PDO $pdo, int $userId, int $sectorId, string $role): bool {
    if ($role === 'admin') {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM usuario_sector WHERE usuario_id = ? AND sector_id = ? LIMIT 1');
    $stmt->execute([$userId, $sectorId]);
    return (bool) $stmt->fetchColumn();
}
