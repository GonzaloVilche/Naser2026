<?php

include "conexion.php";

$nombre = $_POST["nombre"];
$empresa = $_POST["empresa"];

$p1 = $_POST["p1"];
$p2 = $_POST["p2"];
$p3 = $_POST["p3"];
$p4 = $_POST["p4"];
$p5 = $_POST["p5"];
$p6 = $_POST["p6"];
$p7 = $_POST["p7"];
$p8 = $_POST["p8"];
$p9 = $_POST["p9"];

$comentarios = $_POST["comentarios"];

$sql = "INSERT INTO clientes (nombre_apellido, empresa)
        VALUES ('$nombre', '$empresa')";

$conexion->query($sql);

$id_cliente = $conexion->insert_id;

$sql = "INSERT INTO encuestas
        (id_cliente, p1, p2, p3, p4, p5, p6, p7, p8, p9, comentarios)
        VALUES
        ('$id_cliente', '$p1', '$p2', '$p3', '$p4', '$p5', '$p6', '$p7', '$p8', '$p9', '$comentarios')";

if ($conexion->query($sql)) {
    echo "Encuesta guardada correctamente";
} else {
    echo "Error al guardar la encuesta";
}

$conexion->close();

?>