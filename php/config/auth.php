<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit;
    }

    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado. Solo los administradores pueden ingresar a esta sección.');
    }
}

function esAdmin(): bool
{
    return (($_SESSION['rol'] ?? '') === 'admin');
}

function puedeVerSector(PDO $pdo, int $sectorId): bool
{
    if (esAdmin()) {
        return true;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM usuario_sector
             WHERE usuario_id = ? AND sector_id = ?
             LIMIT 1'
        );
        $stmt->execute([$_SESSION['usuario_id'], $sectorId]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function puedeEditarSector(PDO $pdo, int $sectorId): bool
{
    if (esAdmin()) {
        return true;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT puede_editar
             FROM usuario_sector
             WHERE usuario_id = ? AND sector_id = ?
             LIMIT 1'
        );
        $stmt->execute([$_SESSION['usuario_id'], $sectorId]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}
