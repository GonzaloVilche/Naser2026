<?php
<<<<<<< HEAD
require __DIR__.'/../config/auth.php';
requireLogin();
require __DIR__.'/../config/db.php';
requireDocumentManager($pdo);
require __DIR__.'/../config/layout.php';

$msg='';
$err='';
$up=dirname(__DIR__,2).'/uploads/';
if(!is_dir($up)) mkdir($up,0775,true);

$sid=(int)$pdo->query("SELECT id FROM sectores WHERE slug='sgi' LIMIT 1")->fetchColumn();

function limpiarParte(string $s): string {
    $s=trim(str_replace('\\','/',$s),'/');
    $s=preg_replace('/[^\pL\pN _.-]+/u','_',$s);
    return trim($s);
}

function buscarOCrearCarpeta(PDO $pdo,int $sid,string $nombre,?int $padre): int {
    if($padre===null){
        $st=$pdo->prepare('SELECT id FROM carpetas WHERE sector_id=? AND nombre=? AND carpeta_padre_id IS NULL LIMIT 1');
        $st->execute([$sid,$nombre]);
    }else{
        $st=$pdo->prepare('SELECT id FROM carpetas WHERE sector_id=? AND nombre=? AND carpeta_padre_id=? LIMIT 1');
        $st->execute([$sid,$nombre,$padre]);
    }

    $id=$st->fetchColumn();
    if($id) return (int)$id;

    $st=$pdo->prepare('INSERT INTO carpetas(sector_id,nombre,carpeta_padre_id,orden,activa) VALUES(?,?,?,?,1)');
    $st->execute([$sid,$nombre,$padre,999]);

    return (int)$pdo->lastInsertId();
}

function crearRutaCarpetas(PDO $pdo,int $sid,array $partes,?int $base=null): ?int {
    $padre=$base;

    foreach($partes as $parte){
        $parte=limpiarParte($parte);
        if($parte==='') continue;
        $padre=buscarOCrearCarpeta($pdo,$sid,$parte,$padre);
    }

    return $padre;
}

function esArchivoTemporalOffice(string $nombre): bool {
    $base=basename(str_replace('\\','/',$nombre));
    return str_starts_with($base,'~$') || str_starts_with($base,'.~lock.');
}

function documentoYaExiste(PDO $pdo,int $sid,?int $carpetaId,string $titulo,string $nombreOriginal): bool {
    $ext=strtolower(pathinfo($nombreOriginal,PATHINFO_EXTENSION));

    // Word/Excel crean archivos temporales como ~$archivo.docx.
    // No deben importarse al SGI.
    if(esArchivoTemporalOffice($nombreOriginal)){
        return false;
    }

    $titulo=trim(pathinfo($nombreOriginal,PATHINFO_FILENAME));

    // Evita volver a cargar el mismo documento dentro de la misma carpeta.
    if(documentoYaExiste($pdo,$sid,$carpetaId,$titulo,$nombreOriginal)){
        return false;
    }

    if($carpetaId===null){
        $st=$pdo->prepare(
            'SELECT archivo FROM documentos
             WHERE sector_id=? AND carpeta_id IS NULL AND titulo=? AND activo=1'
        );
        $st->execute([$sid,$titulo]);
    }else{
        $st=$pdo->prepare(
            'SELECT archivo FROM documentos
             WHERE sector_id=? AND carpeta_id=? AND titulo=? AND activo=1'
        );
        $st->execute([$sid,$carpetaId,$titulo]);
    }

    foreach($st->fetchAll(PDO::FETCH_COLUMN) as $archivo){
        if(strtolower(pathinfo((string)$archivo,PATHINFO_EXTENSION))===$ext){
            return true;
        }
    }

    return false;
}

function guardarDocumento(
    PDO $pdo,
    int $sid,
    ?int $carpetaId,
    string $origen,
    string $nombreOriginal,
    string $tipo,
    string $estado,
    string $version,
    string $up
): bool {
    $ext=strtolower(pathinfo($nombreOriginal,PATHINFO_EXTENSION));

    // Ignorar archivos temporales creados por Word/Excel.
    $nombreBase=basename(str_replace('\\','/',$nombreOriginal));
    if(str_starts_with($nombreBase,'~$') || str_starts_with($nombreBase,'.~lock.')){
        return false;
    }

    // Usar siempre el nombre real del archivo como título.
    $titulo=trim(pathinfo($nombreOriginal,PATHINFO_FILENAME));
    if($titulo===''){
        return false;
    }

    $permitidos=[
        'pdf','doc','docx','xls','xlsx',
        'ppt','pptx','jpg','jpeg','png',
        'csv','txt'
    ];

    if(!in_array($ext,$permitidos,true)){
        return false;
    }

    // Evitar duplicar el mismo documento en la misma carpeta.
    if($carpetaId===null){
        $dup=$pdo->prepare('SELECT archivo FROM documentos WHERE sector_id=? AND carpeta_id IS NULL AND titulo=? AND activo=1');
        $dup->execute([$sid,$titulo]);
    }else{
        $dup=$pdo->prepare('SELECT archivo FROM documentos WHERE sector_id=? AND carpeta_id=? AND titulo=? AND activo=1');
        $dup->execute([$sid,$carpetaId,$titulo]);
    }

    foreach($dup->fetchAll(PDO::FETCH_COLUMN) as $archivoExistente){
        if(strtolower(pathinfo((string)$archivoExistente,PATHINFO_EXTENSION))===$ext){
            return false;
        }
    }

    $base=preg_replace(
        '/[^a-zA-Z0-9_-]+/',
        '_',
        pathinfo($nombreOriginal,PATHINFO_FILENAME)
    );

    $archivo=date('Ymd_His')
        .'_'.bin2hex(random_bytes(4))
        .'_'.$base.'.'.$ext;

    $destino=$up.$archivo;

    if(is_uploaded_file($origen)){
        $ok=move_uploaded_file($origen,$destino);
    }else{
        $ok=copy($origen,$destino);
    }

    if(!$ok) return false;

    $st=$pdo->prepare(
        'INSERT INTO documentos
        (sector_id,carpeta_id,titulo,descripcion,tipo,archivo,activo,fecha_actualizacion,estado,version)
        VALUES(?,?,?,?,?,?,1,CURDATE(),?,?)'
    );

    $st->execute([
        $sid,
        $carpetaId,
        $titulo,
        '',
        $tipo,
        $archivo,
        $estado,
        $version
    ]);

    return true;
}

/* =========================================================
   ELIMINAR DOCUMENTO
   Borra el registro y también el archivo físico de uploads.
========================================================= */
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='eliminar_documento'){
    $id=(int)($_POST['documento_id']??0);

    $st=$pdo->prepare('SELECT id,archivo,titulo FROM documentos WHERE id=? AND sector_id=? LIMIT 1');
    $st->execute([$id,$sid]);
    $doc=$st->fetch();

    if(!$doc){
        $err='El documento no existe o no pertenece al SGI.';
    }else{
        try{
            $pdo->beginTransaction();

            $st=$pdo->prepare('DELETE FROM documentos WHERE id=? AND sector_id=?');
            $st->execute([$id,$sid]);

            $rutaFisica=$up.basename($doc['archivo']);
            if(is_file($rutaFisica) && !@unlink($rutaFisica)){
                throw new Exception('No se pudo eliminar el archivo físico.');
            }

            $pdo->commit();
            $msg='Documento eliminado correctamente: '.$doc['titulo'];
        }catch(Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            $err='No se pudo eliminar el documento. '.$e->getMessage();
        }
    }
}

/* =========================================================
   CARGA NORMAL / VARIOS / CARPETA COMPLETA
========================================================= */
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='subir_archivos'){

    $modo=$_POST['modo']??'archivo';
    $base=!empty($_POST['carpeta_id'])?(int)$_POST['carpeta_id']:null;
    $tipo=$_POST['tipo']??'documentacion';
    $estado=$_POST['estado']??'aprobado';
    $version=trim($_POST['version']??'1.0');

    $ok=0;
    $omitidos=0;
    $rutasProcesadas=[];

    if(!isset($_FILES['archivos'])){
        $err='No se recibieron archivos.';
    }else{
        $nombres=$_FILES['archivos']['name'];
        $tmp=$_FILES['archivos']['tmp_name'];
        $errores=$_FILES['archivos']['error'];
        $rutas=$_POST['rutas']??[];

        foreach($nombres as $i=>$nombre){

            if(($errores[$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){
                $omitidos++;
                continue;
            }

            $carpetaId=$base;

            if($modo==='carpeta'){
                $rutaRelativa=$rutas[$i]??$nombre;
                $rutaRelativa=str_replace('\\','/',$rutaRelativa);

                $partes=array_values(array_filter(
                    explode('/',$rutaRelativa),
                    static fn($v)=>$v!==''
                ));

                array_pop($partes); // quitar el nombre del archivo

                if($partes){
                    $carpetaId=crearRutaCarpetas(
                        $pdo,
                        $sid,
                        $partes,
                        $base
                    );

                    $rutasProcesadas[implode('/',$partes)]=true;
                }
            }

            if(guardarDocumento(
                $pdo,
                $sid,
                $carpetaId,
                $tmp[$i],
                $nombre,
                $tipo,
                $estado,
                $version,
                $up
            )){
                $ok++;
            }else{
                $omitidos++;
            }
        }

        $msg="Se cargaron $ok documento(s).";

        if($modo==='carpeta'){
            $msg.=" Se reconstruyeron ".count($rutasProcesadas)." ruta(s) de carpetas.";
        }

        if($omitidos){
            $msg.=" $omitidos archivo(s) fueron omitidos.";
        }
    }
}

/* =========================================================
   IMPORTACIÓN ZIP
   Preserva carpetas, subcarpetas y también carpetas vacías.
========================================================= */
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='subir_zip'){

    $base=!empty($_POST['carpeta_id'])?(int)$_POST['carpeta_id']:null;
    $tipo=$_POST['tipo_zip']??'documentacion';
    $estado=$_POST['estado_zip']??'aprobado';
    $version=trim($_POST['version_zip']??'1.0');

    if(
        !isset($_FILES['zip']) ||
        $_FILES['zip']['error']!==UPLOAD_ERR_OK
    ){
        $err='Seleccioná un archivo ZIP válido.';
    }elseif(!class_exists('ZipArchive')){
        $err='PHP no tiene habilitada la extensión ZipArchive.';
    }else{
        $zip=new ZipArchive();

        if($zip->open($_FILES['zip']['tmp_name'])!==true){
            $err='No se pudo abrir el ZIP.';
        }else{
            $ok=0;
            $omitidos=0;
            $carpetasCreadas=0;
            $cache=[];

            for($i=0;$i<$zip->numFiles;$i++){

                $nombreEntrada=$zip->getNameIndex($i);

                if($nombreEntrada===false) continue;

                $nombreEntrada=str_replace('\\','/',$nombreEntrada);

                // Ignorar basura típica de macOS
                if(
                    str_starts_with($nombreEntrada,'__MACOSX/') ||
                    str_contains($nombreEntrada,'/.DS_Store') ||
                    str_ends_with($nombreEntrada,'.DS_Store')
                ){
                    continue;
                }

                $esCarpeta=str_ends_with($nombreEntrada,'/');

                $partes=array_values(array_filter(
                    explode('/',trim($nombreEntrada,'/')),
                    static fn($v)=>$v!==''
                ));

                if(!$partes) continue;

                if($esCarpeta){
                    $rutaKey=implode('/',$partes);

                    if(!isset($cache[$rutaKey])){
                        $cache[$rutaKey]=crearRutaCarpetas(
                            $pdo,
                            $sid,
                            $partes,
                            $base
                        );
                        $carpetasCreadas++;
                    }

                    continue;
                }

                $archivoNombre=array_pop($partes);
                $carpetaId=$base;

                if($partes){
                    $rutaKey=implode('/',$partes);

                    if(!isset($cache[$rutaKey])){
                        $cache[$rutaKey]=crearRutaCarpetas(
                            $pdo,
                            $sid,
                            $partes,
                            $base
                        );
                        $carpetasCreadas++;
                    }

                    $carpetaId=$cache[$rutaKey];
                }

                if(esArchivoTemporalOffice($archivoNombre)){
                    $omitidos++;
                    continue;
                }

                $ext=strtolower(pathinfo($archivoNombre,PATHINFO_EXTENSION));

                if(!in_array($ext,[
                    'pdf','doc','docx','xls','xlsx',
                    'ppt','pptx','jpg','jpeg','png',
                    'csv','txt'
                ],true)){
                    $omitidos++;
                    continue;
                }

                $tmpArchivo=tempnam(sys_get_temp_dir(),'naser_zip_');

                if(!$tmpArchivo){
                    $omitidos++;
                    continue;
                }

                $contenido=$zip->getFromIndex($i);

                if($contenido===false){
                    @unlink($tmpArchivo);
                    $omitidos++;
                    continue;
                }

                file_put_contents($tmpArchivo,$contenido);

                if(guardarDocumento(
                    $pdo,
                    $sid,
                    $carpetaId,
                    $tmpArchivo,
                    $archivoNombre,
                    $tipo,
                    $estado,
                    $version,
                    $up
                )){
                    $ok++;
                }else{
                    $omitidos++;
                }

                @unlink($tmpArchivo);
            }

            $zip->close();

            $msg="ZIP importado: $ok documento(s) cargados y $carpetasCreadas ruta(s) de carpetas procesadas.";

            if($omitidos){
                $msg.=" $omitidos archivo(s) no compatibles fueron omitidos.";
            }
=======
require __DIR__ . '/../config/auth.php';
requireDocumentManager();
require __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'] ?? '';
$esAdmin = $rol === 'admin';
$esSupervisor = $rol === 'supervisor';

$mensaje = '';
$error = '';

$uploadDir = dirname(__DIR__, 2) . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

/* =========================================================
   SECTORES HABILITADOS
========================================================= */

if ($esAdmin) {
    $sectores = $pdo->query(
        'SELECT id, nombre, slug
         FROM sectores
         ORDER BY orden, nombre'
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.nombre, s.slug
         FROM sectores s
         INNER JOIN usuario_sector us ON us.sector_id = s.id
         WHERE us.usuario_id = ?
         AND us.puede_ver = 1
         AND us.puede_editar = 1
         ORDER BY s.orden, s.nombre
         LIMIT 1'
    );
    $stmt->execute([$_SESSION['usuario_id']]);
    $sectores = $stmt->fetchAll();
}

$sectorPermitidoIds = array_map(
    static fn($sector) => (int)$sector['id'],
    $sectores
);

/* =========================================================
   ACCIONES
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {

        $id = (int)($_POST['id'] ?? 0);

        if ($esAdmin) {
            $stmt = $pdo->prepare(
                'SELECT id, sector_id, archivo
                 FROM documentos
                 WHERE id = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT d.id, d.sector_id, d.archivo
                 FROM documentos d
                 INNER JOIN usuario_sector us ON us.sector_id = d.sector_id
                 WHERE d.id = ?
                 AND us.usuario_id = ?
                 AND us.puede_editar = 1
                 LIMIT 1'
            );
            $stmt->execute([$id, $_SESSION['usuario_id']]);
        }

        $doc = $stmt->fetch();

        if (!$doc) {
            $error = 'No tenés permisos para eliminar ese documento.';
        } else {
            $pdo->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);

            if (!empty($doc['archivo']) && is_file($uploadDir . $doc['archivo'])) {
                @unlink($uploadDir . $doc['archivo']);
            }

            $mensaje = 'Documento eliminado correctamente.';
        }
    }

    if ($accion === 'crear') {

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $sectorId = (int)($_POST['sector_id'] ?? 0);

        if (
            $titulo === '' ||
            $sectorId <= 0 ||
            !in_array($tipo, ['documentacion', 'procedimiento'], true)
        ) {
            $error = 'Completá título, sector y tipo.';
        } elseif (!$esAdmin && !in_array($sectorId, $sectorPermitidoIds, true)) {
            $error = 'Solo podés cargar documentos en tu sector asignado.';
        } elseif (
            empty($_FILES['archivo']['name']) ||
            $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
        ) {
            $error = 'Seleccioná un archivo válido.';
        } else {

            $original = basename($_FILES['archivo']['name']);
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

            $permitidos = [
                'pdf', 'doc', 'docx',
                'xls', 'xlsx',
                'ppt', 'pptx',
                'jpg', 'jpeg', 'png'
            ];

            if (!in_array($extension, $permitidos, true)) {
                $error = 'Formato no permitido.';
            } else {

                $baseArchivo = pathinfo($original, PATHINFO_FILENAME);
                $baseArchivo = preg_replace(
                    '/[^a-zA-Z0-9_-]+/',
                    '_',
                    $baseArchivo
                );

                $nombreArchivo =
                    date('Ymd_His') .
                    '_' .
                    $baseArchivo .
                    '.' .
                    $extension;

                if (
                    move_uploaded_file(
                        $_FILES['archivo']['tmp_name'],
                        $uploadDir . $nombreArchivo
                    )
                ) {

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
                        VALUES (?, ?, ?, ?, ?, 1, CURDATE())'
                    );

                    $stmt->execute([
                        $sectorId,
                        $titulo,
                        $descripcion,
                        $tipo,
                        $nombreArchivo
                    ]);

                    $mensaje = 'Documento cargado correctamente.';
                } else {
                    $error = 'No se pudo guardar el archivo.';
                }
            }
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
        }
    }
}

/* =========================================================
<<<<<<< HEAD
   DATOS DE LA PÁGINA
========================================================= */
$st=$pdo->prepare(
    'SELECT c.id,c.nombre,p.nombre padre
     FROM carpetas c
     LEFT JOIN carpetas p ON p.id=c.carpeta_padre_id
     WHERE c.sector_id=? AND c.activa=1
     ORDER BY c.orden,c.nombre'
);
$st->execute([$sid]);
$carpetas=$st->fetchAll();

$st=$pdo->prepare(
    'SELECT d.*,c.nombre carpeta
     FROM documentos d
     LEFT JOIN carpetas c ON c.id=d.carpeta_id
     WHERE d.sector_id=?
     ORDER BY d.id DESC
     LIMIT 100'
);
$st->execute([$sid]);
$docs=$st->fetchAll();
=======
   LISTADO DE DOCUMENTOS
========================================================= */

if ($esAdmin) {

    $documentos = $pdo->query(
        'SELECT
            d.id,
            d.sector_id,
            d.titulo,
            d.descripcion,
            d.tipo,
            d.archivo,
            d.fecha_actualizacion,
            s.nombre AS sector,
            s.slug AS sector_slug
         FROM documentos d
         INNER JOIN sectores s ON s.id = d.sector_id
         ORDER BY d.fecha_actualizacion DESC, d.id DESC'
    )->fetchAll();

} else {

    $stmt = $pdo->prepare(
        'SELECT
            d.id,
            d.sector_id,
            d.titulo,
            d.descripcion,
            d.tipo,
            d.archivo,
            d.fecha_actualizacion,
            s.nombre AS sector,
            s.slug AS sector_slug
         FROM documentos d
         INNER JOIN sectores s ON s.id = d.sector_id
         INNER JOIN usuario_sector us ON us.sector_id = d.sector_id
         WHERE us.usuario_id = ?
         AND us.puede_ver = 1
         ORDER BY d.fecha_actualizacion DESC, d.id DESC'
    );

    $stmt->execute([$_SESSION['usuario_id']]);
    $documentos = $stmt->fetchAll();
}
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
?>
<!doctype html>
<html lang="es">
<head>
<<<<<<< HEAD
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Carga documental | NASER</title>
<link rel="stylesheet" href="../../style.css">

<style>
.upload-tabs{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:20px;
}
.upload-tab{
    border:1px solid #d9e6e0;
    border-radius:15px;
    padding:17px;
    background:#fff;
    cursor:pointer;
}
.upload-tab.active{
    border:2px solid #078c49;
    background:#f1faf5;
}
.upload-tab strong{
    display:block;
    margin-bottom:5px;
    font-size:16px;
}
.upload-tab small{
    color:#6c7e77;
    line-height:1.45;
}
.upload-zone{
    border:2px dashed #b9cec5;
    border-radius:16px;
    padding:22px;
    background:#f9fbfa;
}
.upload-pane{display:none}
.upload-pane.active{display:block}
.preview{
    display:none;
    margin-top:12px;
    padding:13px;
    background:#eef7f2;
    border-radius:11px;
}
.preview.show{display:block}
.preview-files{
    max-height:190px;
    overflow:auto;
    margin-top:7px;
    font-size:12px;
    line-height:1.6;
}
.zip-callout{
    padding:15px;
    margin-bottom:15px;
    border-radius:12px;
    background:#eef5fb;
    color:#345668;
    font-size:13px;
    line-height:1.55;
}
@media(max-width:950px){
    .upload-tabs{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
    .upload-tabs{grid-template-columns:1fr}
}
</style>
</head>

<body>
<div class="app">
<?php sidebar($pdo,'documentos');?>
<main class="content">

<header class="section-top">
<div>
<p class="eyebrow">SGI · GESTIÓN DOCUMENTAL</p>
<h1>Carga de documentación</h1>
<p>Subí archivos individuales, lotes o estructuras completas de carpetas.</p>
</div>

<a class="btn secondary" href="carpetas.php">
Administrar carpetas
</a>
</header>

<?php if($msg):?>
<div class="alert success"><?=htmlspecialchars($msg)?></div>
<?php endif;?>

<?php if($err):?>
<div class="alert error"><?=htmlspecialchars($err)?></div>
<?php endif;?>

<section class="form-panel">

<div class="upload-tabs">
<div class="upload-tab active" data-tab="uno">
<strong>📄 Un documento</strong>
<small>Para cargas puntuales.</small>
</div>

<div class="upload-tab" data-tab="varios">
<strong>📚 Varios archivos</strong>
<small>Seleccioná muchos documentos a la vez.</small>
</div>

<div class="upload-tab" data-tab="carpeta">
<strong>📁 Carpeta completa</strong>
<small>Importa archivos de todas las subcarpetas con contenido.</small>
</div>

<div class="upload-tab" data-tab="zip">
<strong>🗜️ Importar ZIP</strong>
<small>La opción más completa: conserva toda la estructura, incluso carpetas vacías.</small>
</div>
</div>

<!-- ARCHIVOS / CARPETA -->
<div class="upload-pane active" id="pane-files">

<form method="post" enctype="multipart/form-data" class="form-grid" id="formFiles">

<input type="hidden" name="accion" value="subir_archivos">
<input type="hidden" name="modo" id="modo" value="archivo">

<div class="upload-zone">
<label>
<strong id="tituloSelector">Seleccionar documento</strong>
<input type="file" id="archivos" name="archivos[]" required>
</label>

<div class="preview" id="preview">
<strong id="previewTitle"></strong>
<div class="preview-files" id="previewFiles"></div>
</div>

<div id="rutas"></div>
</div>

<label>
Guardar dentro de
<select name="carpeta_id">
<option value="">Raíz del SGI</option>
<?php foreach($carpetas as $c):?>
<option value="<?=$c['id']?>">
<?=htmlspecialchars(($c['padre']?$c['padre'].' / ':'').$c['nombre'])?>
</option>
<?php endforeach;?>
</select>
</label>

<label>
Tipo
<select name="tipo">
<option value="documentacion">Documentación</option>
<option value="procedimiento">Procedimiento</option>
</select>
</label>

<label>
Estado
<select name="estado">
<option value="aprobado">Vigente</option>
<option value="revision">En revisión</option>
<option value="borrador">Borrador</option>
<option value="obsoleto">Obsoleto</option>
</select>
</label>

<label>
Versión
<input name="version" value="1.0">
</label>

<button class="btn primary">
Cargar documentación
</button>

</form>
</div>

<!-- ZIP -->
<div class="upload-pane" id="pane-zip">

<div class="zip-callout">
<strong>Recomendado para estructuras grandes.</strong><br>
Comprimí tu carpeta en Windows como ZIP y subila acá. El sistema recreará automáticamente todas las carpetas, subcarpetas y documentos del ZIP.
</div>

<form method="post" enctype="multipart/form-data" class="form-grid">

<input type="hidden" name="accion" value="subir_zip">

<label>
Archivo ZIP
<input type="file" name="zip" accept=".zip,application/zip" required>
</label>

<label>
Importar dentro de
<select name="carpeta_id">
<option value="">Raíz del SGI</option>
<?php foreach($carpetas as $c):?>
<option value="<?=$c['id']?>">
<?=htmlspecialchars(($c['padre']?$c['padre'].' / ':'').$c['nombre'])?>
</option>
<?php endforeach;?>
</select>
</label>

<label>
Tipo por defecto
<select name="tipo_zip">
<option value="documentacion">Documentación</option>
<option value="procedimiento">Procedimiento</option>
</select>
</label>

<label>
Estado por defecto
<select name="estado_zip">
<option value="aprobado">Vigente</option>
<option value="revision">En revisión</option>
<option value="borrador">Borrador</option>
</select>
</label>

<label>
Versión por defecto
<input name="version_zip" value="1.0">
</label>

<button class="btn primary">
Importar estructura completa
</button>

</form>
</div>

</section>

<section class="table-panel">
<h2>Últimos documentos cargados</h2>

<div class="table-wrap">
<table>
<thead>
<tr>
<th>Título</th>
<th>Carpeta</th>
<th>Estado</th>
<th>Versión</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>

<?php foreach($docs as $d):?>
<tr>
<td><?=htmlspecialchars($d['titulo'])?></td>
<td><?=htmlspecialchars($d['carpeta']??'Raíz SGI')?></td>
<td><?=htmlspecialchars($d['estado']??'aprobado')?></td>
<td><?=htmlspecialchars($d['version']??'1.0')?></td>
<td>
<div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
<a class="btn secondary" href="../../uploads/<?=rawurlencode($d['archivo'])?>" target="_blank">Ver</a>
<form method="post" style="margin:0" onsubmit="return confirm('¿Seguro que querés eliminar este documento? Esta acción también borrará el archivo de uploads.');">
<input type="hidden" name="accion" value="eliminar_documento">
<input type="hidden" name="documento_id" value="<?=$d['id']?>">
<button type="submit" class="btn danger">Eliminar</button>
</form>
</div>
</td>
</tr>
<?php endforeach;?>

</tbody>
</table>
</div>
</section>

</main>
</div>

<script>
const tabs=document.querySelectorAll('.upload-tab');
const filesPane=document.getElementById('pane-files');
const zipPane=document.getElementById('pane-zip');
const input=document.getElementById('archivos');
const modo=document.getElementById('modo');
const titulo=document.getElementById('tituloSelector');
const preview=document.getElementById('preview');
const previewTitle=document.getElementById('previewTitle');
const previewFiles=document.getElementById('previewFiles');
const rutas=document.getElementById('rutas');

function cambiarModo(tab){
    tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===tab));

    filesPane.classList.toggle('active',tab!=='zip');
    zipPane.classList.toggle('active',tab==='zip');

    if(tab==='zip') return;

    input.value='';
    input.removeAttribute('multiple');
    input.removeAttribute('webkitdirectory');
    input.removeAttribute('directory');

    if(tab==='uno'){
        modo.value='archivo';
        titulo.textContent='Seleccionar documento';
    }

    if(tab==='varios'){
        modo.value='varios';
        titulo.textContent='Seleccionar varios archivos';
        input.setAttribute('multiple','');
    }

    if(tab==='carpeta'){
        modo.value='carpeta';
        titulo.textContent='Seleccionar carpeta completa';
        input.setAttribute('multiple','');
        input.setAttribute('webkitdirectory','');
        input.setAttribute('directory','');
    }

    preview.classList.remove('show');
    previewFiles.innerHTML='';
    rutas.innerHTML='';
}

tabs.forEach(tab=>{
    tab.addEventListener('click',()=>cambiarModo(tab.dataset.tab));
});

input.addEventListener('change',()=>{

    const files=[...input.files];

    if(!files.length) return;

    previewTitle.textContent=files.length+' archivo(s) detectados';

    previewFiles.innerHTML='';
    rutas.innerHTML='';

    files.forEach((file,i)=>{

        const ruta=file.webkitRelativePath || file.name;

        if(i<120){
            const row=document.createElement('div');
            row.textContent=ruta;
            previewFiles.appendChild(row);
        }

        const hidden=document.createElement('input');
        hidden.type='hidden';
        hidden.name='rutas[]';
        hidden.value=ruta;
        rutas.appendChild(hidden);
    });

    if(files.length>120){
        const row=document.createElement('div');
        row.textContent='... y '+(files.length-120)+' archivo(s) más';
        previewFiles.appendChild(row);
    }

    preview.classList.add('show');
});
</script>
=======
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>
        <?= $esSupervisor ? 'Documentos de mi sector' : 'Administrar documentos' ?>
        | NASER SGI
    </title>

    <link rel="stylesheet" href="../../style.css">

    <style>
        .doc-header-box {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            margin-bottom:25px;
        }

        .sector-permission {
            padding:10px 14px;
            border:1px solid var(--gris-borde);
            background:var(--verde-fondo);
            border-radius:10px;
            color:var(--verde-oscuro);
            font-size:12px;
            font-weight:700;
        }

        .doc-grid {
            display:grid;
            grid-template-columns:minmax(300px,.75fr) minmax(650px,1.8fr);
            gap:28px;
            align-items:start;
        }

        .doc-panel {
            padding:26px;
            background:#fff;
            border:1px solid var(--gris-borde);
            border-radius:14px;
            box-shadow:var(--sombra-suave);
        }

        .doc-panel h2 {
            margin-bottom:18px;
        }

        .doc-table {
            overflow-x:auto;
        }

        .document-title-small {
            display:block;
            margin-top:4px;
            color:var(--texto-secundario);
            font-size:11px;
        }

        @media(max-width:950px) {
            .doc-grid {
                grid-template-columns:1fr;
            }

            .doc-header-box {
                align-items:flex-start;
                flex-direction:column;
            }
        }
    </style>
</head>

<body>

<main class="standalone">

    <a class="back-link" href="../dashboard.php">
        ← Volver al panel
    </a>

    <div class="doc-header-box">

        <div>
            <p class="eyebrow">
                <?= $esSupervisor ? 'GESTIÓN DEL SECTOR' : 'ADMINISTRACIÓN' ?>
            </p>

            <h1>
                <?= $esSupervisor ? 'Documentos de mi sector' : 'Documentos y procedimientos' ?>
            </h1>

            <p class="muted">
                <?= $esSupervisor
                    ? 'Podés cargar y administrar únicamente la documentación del sector que tenés asignado.'
                    : 'Administración general de documentación y procedimientos del sistema.' ?>
            </p>
        </div>

        <?php if ($esSupervisor && !empty($sectores[0])): ?>
            <div class="sector-permission">
                Sector asignado:
                <?= htmlspecialchars($sectores[0]['nombre']) ?>
            </div>
        <?php endif; ?>

    </div>

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

    <?php if (!$sectores && $esSupervisor): ?>

        <div class="empty-state">
            Este supervisor todavía no tiene un sector editable asignado.
        </div>

    <?php else: ?>

        <div class="doc-grid">

            <section class="doc-panel">

                <p class="eyebrow">NUEVO ARCHIVO</p>
                <h2>Cargar documento</h2>

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
                        <textarea name="descripcion"></textarea>
                    </label>

                    <label>
                        Sector

                        <?php if ($esSupervisor): ?>

                            <input
                                type="hidden"
                                name="sector_id"
                                value="<?= (int)$sectores[0]['id'] ?>"
                            >

                            <input
                                type="text"
                                value="<?= htmlspecialchars($sectores[0]['nombre']) ?>"
                                disabled
                            >

                        <?php else: ?>

                            <select
                                name="sector_id"
                                required
                            >

                                <option value="">
                                    Seleccionar sector
                                </option>

                                <?php foreach ($sectores as $sector): ?>

                                    <option value="<?= (int)$sector['id'] ?>">
                                        <?= htmlspecialchars($sector['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php endif; ?>

                    </label>

                    <label>
                        Tipo

                        <select
                            name="tipo"
                            required
                        >
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
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                            required
                        >
                    </label>

                    <button
                        type="submit"
                        class="btn primary"
                    >
                        Cargar documento
                    </button>

                </form>

            </section>

            <section class="doc-panel">

                <p class="eyebrow">ARCHIVOS</p>

                <h2>
                    <?= $esSupervisor ? 'Documentos del sector' : 'Documentos cargados' ?>
                </h2>

                <?php if (!$documentos): ?>

                    <div class="empty-state">
                        Todavía no hay documentos disponibles.
                    </div>

                <?php else: ?>

                    <div class="doc-table">

                        <table>

                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Sector</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($documentos as $doc): ?>

                                <?php
                                $extension = strtolower(
                                    pathinfo(
                                        $doc['archivo'] ?? '',
                                        PATHINFO_EXTENSION
                                    )
                                );

                                $rutaArchivo =
                                    '../../uploads/' .
                                    rawurlencode($doc['archivo'] ?? '');
                                ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($doc['titulo']) ?>
                                        </strong>

                                        <?php if (!empty($doc['descripcion'])): ?>
                                            <span class="document-title-small">
                                                <?= htmlspecialchars($doc['descripcion']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($doc['sector']) ?>
                                    </td>

                                    <td>
                                        <span class="badge">
                                            <?= htmlspecialchars(ucfirst($doc['tipo'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($doc['fecha_actualizacion']) ?>
                                    </td>

                                    <td>

                                        <div class="table-actions">

                                            <?php if (
                                                !empty($doc['archivo']) &&
                                                in_array(
                                                    $extension,
                                                    ['pdf', 'jpg', 'jpeg', 'png'],
                                                    true
                                                )
                                            ): ?>

                                                <button
                                                    type="button"
                                                    class="btn-preview"
                                                    onclick="abrirVistaPrevia(
                                                        '<?= htmlspecialchars($rutaArchivo, ENT_QUOTES) ?>',
                                                        '<?= htmlspecialchars($doc['titulo'], ENT_QUOTES) ?>'
                                                    )"
                                                >
                                                    👁 Vista previa
                                                </button>

                                            <?php endif; ?>

                                            <?php if (!empty($doc['archivo'])): ?>

                                                <a
                                                    class="btn-open"
                                                    href="<?= htmlspecialchars($rutaArchivo) ?>"
                                                    target="_blank"
                                                >
                                                    ↗ Abrir
                                                </a>

                                            <?php endif; ?>

                                            <form
                                                method="post"
                                                style="display:inline"
                                                onsubmit="return confirm('¿Seguro que querés eliminar este documento?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$doc['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-delete"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    <?php endif; ?>

</main>

<div id="modalPreview" class="modal-preview">

    <div class="modal-contenido">

        <div class="modal-header">

            <h3 id="tituloPreview">
                Vista previa
            </h3>

            <button
                type="button"
                class="cerrar-modal"
                onclick="cerrarVistaPrevia()"
            >
                ✕
            </button>

        </div>

        <iframe
            id="previewFrame"
            src=""
        ></iframe>

    </div>

</div>

<script>
function abrirVistaPrevia(ruta, titulo) {
    document.getElementById('previewFrame').src = ruta;
    document.getElementById('tituloPreview').textContent = titulo;
    document.getElementById('modalPreview').style.display = 'flex';
}

function cerrarVistaPrevia() {
    document.getElementById('modalPreview').style.display = 'none';
    document.getElementById('previewFrame').src = '';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalPreview');

    if (event.target === modal) {
        cerrarVistaPrevia();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarVistaPrevia();
    }
});
</script>

>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
</body>
</html>
