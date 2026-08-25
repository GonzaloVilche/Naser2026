<?php
$archivo = "../uploads/procedimiento_seguridad.pdf";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Vista previa | NASER SGI</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f5;
            margin: 0;
            padding: 30px;
        }

        .contenedor {
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            margin-bottom: 10px;
        }

        .acciones {
            margin-bottom: 20px;
        }

        .boton {
            display: inline-block;
            padding: 10px 18px;
            background: #008f4c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }

        .visor {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        iframe {
            width: 100%;
            height: 750px;
            border: none;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Vista previa del documento</h1>

    <p>Procedimiento de Seguridad</p>

    <div class="acciones">

        <a class="boton" href="dashboard.php">
            Volver
        </a>

        <a class="boton" href="<?php echo $archivo; ?>" download>
            Descargar PDF
        </a>

    </div>

    <div class="visor">

        <iframe src="<?php echo $archivo; ?>"></iframe>

    </div>

</div>

</body>

</html>