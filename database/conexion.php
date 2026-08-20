<?php
$host = 'localhost';
$db   = 'naser_auditoria';
$user = 'root';
$pass = ''; // Por defecto en XAMPP viene vacío

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}
$conexion = new mysqli("localhost", "root", "", "naser");

if ($conexion->connect_error) {
    die("error de conexion");
}
