<?php
require_once 'database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturamos los campos usando los nombres exactos del HTML
    $fecha        = $_POST['fecha'] ?? null;
    $hora         = $_POST['hora'] ?? null;
    $ubicacion    = $_POST['ubicacion'] ?? null;
    $inspector    = $_POST['inspector'] ?? null; // Variable específica para HSE
    $gerente      = $_POST['gerente'] ?? null;
    $supervisor   = $_POST['supervisor'] ?? null;
    $medio        = $_POST['medio'] ?? null;
    $acompanantes = $_POST['acompanantes'] ?? null;
    $cantidad     = !empty($_POST['cantidad']) ? intval($_POST['cantidad']) : null;
    $herramientas = $_POST['herramientas'] ?? null;
    $equipos      = $_POST['equipos'] ?? null;

    // 2. Procesamos los checkboxes de EPP (une los seleccionados por comas)
    $elementos_seguridad = '';
    if (isset($_POST['epp']) && is_array($_POST['epp'])) {
        $elementos_seguridad = implode(', ', $_POST['epp']);
    }

    // Validación de campos obligatorios
    if (empty($fecha) || empty($hora) || empty($ubicacion) || empty($inspector)) {
        die("Error: Los campos Fecha, Hora, Ubicación e Inspector son obligatorios.");
    }

    try {
        // 3. Preparamos la consulta SQL segura
        $sql = "INSERT INTO inspecciones_hse (fecha, hora, ubicacion, inspector, gerente, supervisor, medio, acompanantes, cantidad, herramientas, equipos, elementos_seguridad) 
                VALUES (:fecha, :hora, :ubicacion, :inspector, :gerente, :supervisor, :medio, :acompanantes, :cantidad, :herramientas, :equipos, :elementos_seguridad)";
        
        $stmt = $pdo->prepare($sql);
        
        // 4. Ejecutamos la consulta pasándole los datos
        $stmt->execute([
            ':fecha'        => $fecha,
            ':hora'         => $hora,
            ':ubicacion'    => $ubicacion,
            ':inspector'    => $inspector,
            ':gerente'      => $gerente,
            ':supervisor'   => $supervisor,
            ':medio'        => $medio,
            ':acompanantes' => $acompanantes,
            ':cantidad'     => $cantidad,
            ':herramientas' => $herramientas,
            ':equipos'      => $equipos,
            ':elementos_seguridad' => $elementos_seguridad
        ]);

        echo "<h2>¡Inspección HSE registrada con éxito en el sistema Naser!</h2>";
        echo "<a href='inspeccion hse.html'>Volver al formulario</a>";

    } catch (PDOException $e) {
        echo "Error al guardar la inspección: " . $e->getMessage();
    }
} else {
    echo "Método de acceso no permitido.";
}
