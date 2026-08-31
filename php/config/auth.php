<?php
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
function requireLogin(){if(empty($_SESSION['usuario_id'])){header('Location: /naser/php/login.php');exit;}}
function requireAdmin(){if(empty($_SESSION['usuario_id'])||($_SESSION['rol']??'')!=='admin'){http_response_code(403);exit('Acceso denegado');}}
function esAdmin(){return ($_SESSION['rol']??'')==='admin';}
function puedeGestionarDocumentos($pdo){
 if(esAdmin()) return true;
 $st=$pdo->prepare('SELECT 1 FROM usuario_sector WHERE usuario_id=? AND puede_editar=1 LIMIT 1');
 $st->execute([$_SESSION['usuario_id']??0]);
 return (bool)$st->fetchColumn();
}
function requireDocumentManager($pdo){requireLogin();if(!puedeGestionarDocumentos($pdo)){http_response_code(403);exit('No tenés permisos para cargar documentación.');}}
function sectorUsuario($pdo){if(esAdmin())return null;$st=$pdo->prepare('SELECT s.id,s.nombre,s.slug,us.puede_editar FROM usuario_sector us JOIN sectores s ON s.id=us.sector_id WHERE us.usuario_id=? AND us.puede_ver=1 ORDER BY us.puede_editar DESC,s.orden LIMIT 1');$st->execute([$_SESSION['usuario_id']]);return $st->fetch()?:null;}
function puedeVerSector($pdo,$sid){
 if(esAdmin()) return true;
 $st=$pdo->prepare('SELECT slug FROM sectores WHERE id=? LIMIT 1');$st->execute([$sid]);$slug=$st->fetchColumn();
 if($slug==='sgi') return !empty($_SESSION['usuario_id']);
 $st=$pdo->prepare('SELECT 1 FROM usuario_sector WHERE usuario_id=? AND sector_id=? AND puede_ver=1 LIMIT 1');$st->execute([$_SESSION['usuario_id'],$sid]);return(bool)$st->fetchColumn();
}
