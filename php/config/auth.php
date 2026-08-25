<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function appBaseUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $marker = '/php/';
    $pos = strpos($script, $marker);

    if ($pos !== false) {
        return substr($script, 0, $pos + 4);
    }

    if (str_ends_with($script, '/php')) {
        return $script;
    }

    return rtrim(dirname($script), '/');
}

function appUrl(string $path = ''): string {
    return rtrim(appBaseUrl(), '/') . '/' . ltrim($path, '/');
}

function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . appUrl('login.php'));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();

    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, ['admin', 'coordinador'], true)) {
        http_response_code(403);
        exit('Acceso denegado. Se requieren permisos de administración.');
    }
}

function canUserDo(PDO $pdo, int $userId, string $role, int $sectorId, string $accion): bool {
    if (in_array($role, ['admin', 'coordinador'], true)) {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM usuario_sector WHERE usuario_id = ? AND sector_id = ? LIMIT 1');
    $stmt->execute([$userId, $sectorId]);
    $tieneSector = (bool) $stmt->fetchColumn();

    if (!$tieneSector) {
        return false;
    }

    if ($role === 'supervisor') {
        return in_array($accion, ['ver', 'crear', 'editar', 'eliminar'], true);
    }

    if ($role === 'usuario') {
        return in_array($accion, ['ver', 'crear'], true);
    }

    return false;
}