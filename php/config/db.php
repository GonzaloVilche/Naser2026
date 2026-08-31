<?php
<<<<<<< HEAD
$pdo=new PDO('mysql:host=127.0.0.1;dbname=naser_sgi_prueba;charset=utf8mb4','root','',[
PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);
=======
$host = 'localhost';
$db   = 'naser_sgi';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
}
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
