<?php
// Conectamos a la base de datos
require_once 'database/conexion.php';

// Verificamos si los datos llegaron por el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Aquí guardas lo que tu compañero programó en los "name" del HTML
    // Ejemplo: si el HTML pide nombre y correo:
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';

    try {
        // Preparamos la orden SQL para insertar los datos
        $sql = "INSERT INTO usuarios (nombre, correo) VALUES (:nombre, :correo)";
        $stmt = $pdo->prepare($sql);
        
        // Ejecutamos la orden con los datos reales
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo
        ]);

        echo "¡Datos guardados correctamente!";
    } catch (PDOException $e) {
        echo "Error al guardar: " . $e->getMessage();
    }
}
