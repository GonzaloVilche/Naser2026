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
