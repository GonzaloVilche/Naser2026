<?php

require __DIR__ . '/../config/auth.php';
requireAdmin();

require __DIR__ . '/../config/db.php';

$mensaje = '';
$error = '';

/*
|--------------------------------------------------------------------------
| CARPETA DE ARCHIVOS
|--------------------------------------------------------------------------
| documentos.php está dentro de php/admin/
| Por eso usamos ../../uploads/
*/

$carpetaUploads = __DIR__ . '/../../uploads/';

// Crear carpeta automáticamente si no existe
if (!is_dir($carpetaUploads)) {
    mkdir($carpetaUploads, 0777, true);
}


/*
|--------------------------------------------------------------------------
| PROCESAR FORMULARIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? 'crear';


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    if ($accion === 'eliminar') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare(
            'SELECT archivo FROM documentos WHERE id = ?'
        );

        $stmt->execute([$id]);

        $doc = $stmt->fetch();


        if ($doc) {

            // Borrar archivo físico
            if (!empty($doc['archivo'])) {

                $rutaArchivo = $carpetaUploads . basename($doc['archivo']);

                if (is_file($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }


            // Borrar de la base de datos
            $stmt = $pdo->prepare(
                'DELETE FROM documentos WHERE id = ?'
            );

            $stmt->execute([$id]);

            $mensaje = 'Documento eliminado correctamente.';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | CREAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    else {

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        $tipo = $_POST['tipo'] ?? 'documentacion';

        $sectorId = (int)($_POST['sector_id'] ?? 0);


        // Validar tipo
        if (!in_array(
            $tipo,
            ['documentacion', 'procedimiento'],
            true
        )) {

            $tipo = 'documentacion';
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES
        |--------------------------------------------------------------------------
        */

        if ($titulo === '') {

            $error = 'Ingresá un título.';

        } elseif ($sectorId <= 0) {

            $error = 'Seleccioná un sector.';

        } elseif (
            !isset($_FILES['archivo']) ||
            $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = 'Seleccioná un archivo para cargar.';
        }


        /*
        |--------------------------------------------------------------------------
        | GUARDAR ARCHIVO
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            $nombreOriginal = $_FILES['archivo']['name'];

            $extension = strtolower(
                pathinfo(
                    $nombreOriginal,
                    PATHINFO_EXTENSION
                )
            );


            $permitidos = [
                'pdf',
                'doc',
                'docx',
                'xls',
                'xlsx',
                'jpg',
                'jpeg',
                'png'
            ];


            if (!in_array($extension, $permitidos, true)) {

                $error = 'Formato de archivo no permitido.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | CREAR NOMBRE SEGURO
                |--------------------------------------------------------------------------
                */

                $nombreSinExtension = pathinfo(
                    $nombreOriginal,
                    PATHINFO_FILENAME
                );


                $nombreSeguro = preg_replace(
                    '/[^a-zA-Z0-9_-]/',
                    '_',
                    $nombreSinExtension
                );


                $archivo =
                    time()
                    . '_'
                    . $nombreSeguro
                    . '.'
                    . $extension;


                $destino =
                    $carpetaUploads
                    . $archivo;


                /*
                |--------------------------------------------------------------------------
                | MOVER ARCHIVO
                |--------------------------------------------------------------------------
                */

                if (
                    move_uploaded_file(
                        $_FILES['archivo']['tmp_name'],
                        $destino
                    )
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | GUARDAR EN MYSQL
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare(
                        'INSERT INTO documentos
                        (
                            sector_id,
                            titulo,
                            descripcion,
                            tipo,
                            archivo,
                            activo,
                            fecha_actualizacion
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            1,
                            CURDATE()
                        )'
                    );


                    $stmt->execute([
                        $sectorId,
                        $titulo,
                        $descripcion,
                        $tipo,
                        $archivo
                    ]);


                    $mensaje =
                        'Documento cargado correctamente.';


                } else {

                    $error =
                        'No se pudo guardar el archivo en la carpeta uploads.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CARGAR SECTORES
|--------------------------------------------------------------------------
*/

$sectores = $pdo->query(
    'SELECT id, nombre
     FROM sectores
     ORDER BY orden, nombre'
)->fetchAll();


/*
|--------------------------------------------------------------------------
| CARGAR DOCUMENTOS
|--------------------------------------------------------------------------
*/

$docs = $pdo->query(
    'SELECT
        d.id,
        d.titulo,
        d.tipo,
        d.archivo,
        d.activo,
        d.fecha_actualizacion,
        s.nombre AS sector

     FROM documentos d

     JOIN sectores s
     ON s.id = d.sector_id

     ORDER BY d.id DESC'
)->fetchAll();

?>

<!doctype html>

<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Documentos | NASER SGI
    </title>

    <link
        rel="stylesheet"
        href="../../style.css"
    >

</head>


<body>


<main class="standalone">


    <!-- VOLVER -->

    <a
        class="back-link"
        href="../dashboard.php"
    >
        ← Volver al panel
    </a>



    <!-- ENCABEZADO -->

    <div class="section-heading spaced">

        <div>

            <p class="eyebrow">
                ADMINISTRACIÓN
            </p>

            <h1>
                Documentos y procedimientos
            </h1>

            <p class="muted">
                Subí archivos y asignálos al sector correspondiente.
            </p>

        </div>

    </div>



    <!-- MENSAJES -->

    <?php if ($mensaje): ?>

        <div class="alert success">

            <?= htmlspecialchars($mensaje) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <section class="admin-grid">


        <!-- CARGAR -->

        <div class="panel-card">

            <h2>
                Cargar archivo
            </h2>


            <form
                method="post"
                enctype="multipart/form-data"
                class="form-grid"
            >


                <input
                    type="hidden"
                    name="accion"
                    value="crear"
                >


                <label>

                    Título

                    <input
                        type="text"
                        name="titulo"
                        required
                    >

                </label>



                <label>

                    Descripción

                    <textarea
                        name="descripcion"
                        rows="4"
                    ></textarea>

                </label>



                <label>

                    Sector

                    <select
                        name="sector_id"
                        required
                    >

                        <option value="">
                            Seleccionar
                        </option>


                        <?php foreach ($sectores as $s): ?>

                            <option
                                value="<?= (int)$s['id'] ?>"
                            >

                                <?= htmlspecialchars($s['nombre']) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </label>



                <label>

                    Tipo

                    <select name="tipo">

                        <option value="documentacion">
                            Documentación
                        </option>

                        <option value="procedimiento">
                            Procedimiento
                        </option>

                    </select>

                </label>



                <label>

                    Archivo

                    <input
                        type="file"
                        name="archivo"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        required
                    >

                </label>



                <button
                    class="btn primary"
                    type="submit"
                >

                    Guardar documento

                </button>


            </form>

        </div>



        <!-- DOCUMENTOS CARGADOS -->

        <div class="panel-card">


            <h2>
                Archivos cargados
            </h2>


            <div class="table-wrap">


                <table>


                    <thead>

                        <tr>

                            <th>Título</th>

                            <th>Sector</th>

                            <th>Tipo</th>

                            <th>Fecha</th>

                            <th>Archivo</th>

                            <th>Acción</th>

                        </tr>

                    </thead>



                    <tbody>


                    <?php if (!$docs): ?>

                        <tr>

                            <td colspan="6">

                                Todavía no hay documentos cargados.

                            </td>

                        </tr>

                    <?php endif; ?>



                    <?php foreach ($docs as $d): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars($d['titulo']) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars($d['sector']) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    ucfirst($d['tipo'])
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $d['fecha_actualizacion']
                                ) ?>

                            </td>



                            <td>

                                <?php if (!empty($d['archivo'])): ?>


                                    <a
                                        class="text-button"
                                        href="../../uploads/<?= rawurlencode($d['archivo']) ?>"
                                        target="_blank"
                                    >

                                        Ver archivo

                                    </a>


                                <?php else: ?>


                                    Sin archivo


                                <?php endif; ?>

                            </td>



                            <td>


                                <form
                                    method="post"
                                    onsubmit="return confirm('¿Eliminar este documento?');"
                                >

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$d['id'] ?>"
                                    >


                                    <button
                                        class="text-button danger"
                                        type="submit"
                                    >

                                        Eliminar

                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>

        </div>


    </section>


</main>


</body>

</html>