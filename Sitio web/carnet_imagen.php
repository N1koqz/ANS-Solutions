<?php
    //Entrega la imagen de un carnet. Exige sesion y que el carnet sea de un jugador del club del usuario (o ser admin)
    require_once "baseDeDatos.php";
    require_once "procesar_carnets.php";
    requerirLogin();

    $id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
    if ($id === false) {
        http_response_code(404);
        exit("No encontrado");
    }

    $stmt = $conn->prepare("SELECT k.imagen, j.id_club FROM carnets_salud k INNER JOIN jugadores j ON k.ci = j.ci WHERE k.id_carnet = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $carnet = $stmt->get_result()->fetch_assoc();

    //Mismo mensaje para "no existe" y "no es tuyo", asi no se revela que ids existen
    $permitido = $carnet && (esAdmin() || ($carnet["id_club"] !== null && (int)$carnet["id_club"] === clubDelUsuario()));
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
