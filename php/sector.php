<?php

require __DIR__ . '/config/auth.php';
requireLogin();

require __DIR__ . '/config/db.php';

$slug = trim($_GET['sector'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

// Buscar el sector
$stmt = $pdo->prepare(
    'SELECT id, nombre, slug
     FROM sectores
     WHERE slug = ?
     LIMIT 1'
);

$stmt->execute([$slug]);

$sector = $stmt->fetch();

// Si no existe el sector
if (!$sector) {
    http_response_code(404);
    exit('Sector no encontrado.');
}


/*
|--------------------------------------------------------------------------
| PERMISOS DEL SECTOR
|--------------------------------------------------------------------------
|
| Por ahora NO usamos userHasSectorAccess() porque esa función
| todavía no está definida.
|
| Cuando terminen el sistema de usuarios y permisos podemos
| volver a activarlo correctamente.
|
*/


// Parámetros de búsqueda
$params = [$sector['id']];

$sql = '
    SELECT 
        id,
        titulo,
        descripcion,
        tipo,
        archivo,
        fecha_actualizacion
    FROM documentos
    WHERE sector_id = ?
    AND activo = 1
';


// Filtrar por tipo
if (in_array($tipo, ['documentacion', 'procedimiento'], true)) {

    $sql .= ' AND tipo = ?';

    $params[] = $tipo;
}


// Ordenar documentos
$sql .= ' ORDER BY fecha_actualizacion DESC, titulo';

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$documentos = $stmt->fetchAll();

?>

<!doctype html>

<html lang="es">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= htmlspecialchars($sector['nombre']) ?> | NASER SGI
    </title>

    <link rel="stylesheet" href="../style.css">

</head>


<body>

<main class="standalone">


    <!-- VOLVER -->

    <a class="back-link" href="dashboard.php">
        ← Volver al panel
    </a>



    <!-- ENCABEZADO -->

    <div class="section-heading spaced">

        <div>

            <p class="eyebrow">
                SECTOR
            </p>

            <h1>
                <?= htmlspecialchars($sector['nombre']) ?>
            </h1>

            <p class="muted">
                Documentación y procedimientos disponibles.
            </p>

        </div>

    </div>



    <!-- FILTROS -->

    <div class="filter-row">


        <!-- TODOS -->

        <a
            class="btn <?= $tipo === '' ? 'primary' : 'secondary' ?>"

            href="sector.php?sector=<?= urlencode($sector['slug']) ?>"
        >

            Todo

        </a>



        <!-- DOCUMENTACIÓN -->

        <a
            class="btn <?= $tipo === 'documentacion' ? 'primary' : 'secondary' ?>"

            href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=documentacion"
        >

            Documentación

        </a>



        <!-- PROCEDIMIENTOS -->

        <a
            class="btn <?= $tipo === 'procedimiento' ? 'primary' : 'secondary' ?>"

            href="sector.php?sector=<?= urlencode($sector['slug']) ?>&tipo=procedimiento"
        >

            Procedimientos

        </a>


    </div>



    <!-- LISTADO DE DOCUMENTOS -->

    <div class="document-list">


        <?php if (!$documentos): ?>


            <div class="empty-state">

                Todavía no hay archivos cargados en esta sección.

            </div>


        <?php endif; ?>



        <?php foreach ($documentos as $doc): ?>


            <article class="document-card">


                <!-- INFORMACIÓN -->

                <div>


                    <span class="badge">

                        <?= htmlspecialchars(ucfirst($doc['tipo'])) ?>

                    </span>


                    <h3>

                        <?= htmlspecialchars($doc['titulo']) ?>

                    </h3>


                    <p>

                        <?= htmlspecialchars($doc['descripcion'] ?? '') ?>

                    </p>


                    <small>

                        Actualizado:
                        <?= htmlspecialchars($doc['fecha_actualizacion']) ?>

                    </small>


                </div>



                <!-- BOTONES -->

                <?php if (!empty($doc['archivo'])): ?>


                    <div class="document-actions">


                        <!-- VISTA PREVIA -->

                        <a
                            class="btn primary"

                            href="vista_previa.php?archivo=<?= urlencode($doc['archivo']) ?>"
                        >

                            👁 Vista previa

                        </a>



                        <!-- DESCARGAR / ABRIR -->

                        <a
                            class="btn secondary"

                            href="../uploads/<?= rawurlencode($doc['archivo']) ?>"

                            target="_blank"
                        >

                            Abrir archivo

                        </a>


                    </div>


                <?php endif; ?>


            </article>


        <?php endforeach; ?>


    </div>


</main>


</body>

</html>