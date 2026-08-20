<?php
require_once 'database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturamos los campos simples del HTML usando el atributo "name"
    $fecha        = $_POST['fecha'] ?? null;
    $hora         = $_POST['hora'] ?? null;
    $ubicacion    = $_POST['ubicacion'] ?? null;
    $auditor      = $_POST['auditor'] ?? null;
    $gerente      = $_POST['gerente'] ?? null;
    $supervisor   = $_POST['supervisor'] ?? null;
    $medio        = $_POST['medio'] ?? null;
    $acompanantes = $_POST['acompanantes'] ?? null;
    $cantidad     = !empty($_POST['cantidad']) ? intval($_POST['cantidad']) : null;
    $herramientas = $_POST['herramientas'] ?? null;
    $equipos      = $_POST['equipos'] ?? null;

    // 2. Procesamos los checkboxes de Elementos de Seguridad (EPP)
    // Como pueden seleccionar varios, los unimos separados por comas para guardarlos juntos
    $elementos_seguridad = '';
    if (isset($_POST['epp']) && is_array($_POST['epp'])) {
        $elementos_seguridad = implode(', ', $_POST['epp']);
    }

    try {
        // 3. Preparamos la consulta SQL de inserción segura
        $sql = "INSERT INTO auditorias (fecha, hora, ubicacion, auditor, gerente, supervisor, medio, acompanantes, cantidad, herramientas, equipos, elementos_seguridad) 
                VALUES (:fecha, :hora, :ubicacion, :auditor, :gerente, :supervisor, :medio, :acompanantes, :cantidad, :herramientas, :equipos, :elementos_seguridad)";
        
        $stmt = $pdo->prepare($sql);
        
        // 4. Ejecutamos pasando los parámetros mapeados
        $stmt->execute([
            ':fecha'        => $fecha,
            ':hora'         => $hora,
            ':ubicacion'    => $ubicacion,
            ':auditor'      => $auditor,
            ':gerente'      => $gerente,
            ':supervisor'   => $supervisor,
            ':medio'        => $medio,
            ':acompanantes' => $acompanantes,
            ':cantidad'     => $cantidad,
            ':herramientas' => $herramientas,
            ':equipos'      => $equipos,
            ':elementos_seguridad' => $elementos_seguridad
        ]);

        echo "<h2>¡Auditoría registrada exitosamente en el sistema Naser!</h2>";
        echo "<a href='auditoria.html'>Volver al formulario</a>";

    } catch (PDOException $e) {
        echo "Error al guardar el registro en la base de datos: " . $e->getMessage();
    }
} else {
    echo "Acceso no permitido.";
}
