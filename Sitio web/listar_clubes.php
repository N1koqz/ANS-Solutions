<?php
    require_once "plantilla.php";
    requerirLogin();

    // Consulta de clubes (con busqueda opcional)

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT id_club, nombre, ciudad, fundacion
        FROM club
        WHERE CONCAT_WS(' ', nombre, ciudad, fundacion) LIKE ?
        ORDER BY nombre
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    //Se muestra toda la información

    while ($club = $result->fetch_assoc()) {
        echo "<tr>";
        echo celda($club["id_club"]) .
        celda($club["nombre"]) . 
        celda($club["ciudad"]) . 
        celda($club["fundacion"]);
        botonesAdmin("clubes.php", $club["id_club"]);
        echo "</tr>";
    }
?>
