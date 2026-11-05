<?php

//Entrega la imagen de un carnet a cualquier club con sesion activa, o al administrador.
require_once __DIR__ . "/aplicacion/inicio.php";
require_once __DIR__ . "/aplicacion/modulos/carnets/procesar_carnets.php";
requerirLogin();

$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false) {
    http_response_code(404);
    exit("No encontrado");
}

$stmt = $conn->prepare(
    "SELECT k.imagen, j.id_club FROM carnets_salud k INNER JOIN jugadores j ON k.ci = j.ci WHERE k.id_carnet = ?",
);
$stmt->bind_param("i", $id);
$stmt->execute();
$carnet = $stmt->get_result()->fetch_assoc();

//Solo los usuarios asignados a un club pueden consultar las imagenes de otros clubes.
$permitido = $carnet && (esAdmin() || clubDelUsuario() !== null);
$ruta = $carnet ? DIR_CARNETS . "/" . basename($carnet["imagen"]) : "";
if (!$permitido || !is_file($ruta)) {
    http_response_code(404);
    exit("No encontrado");
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
if (!isset(TIPOS_CARNET[$mime])) {
    http_response_code(404);
    exit("No encontrado");
}

header("Content-Type: " . $mime);
header("Content-Length: " . filesize($ruta));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
readfile($ruta);
