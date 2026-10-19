<?php
    require_once "plantilla.php";
    requerirLogin();

    // Estadisticas por partido y jugador (con busqueda opcional)

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT e.id_estadistica, p.fecha, cl.nombre AS local, cv.nombre AS visitante,
               j.ci, j.nombre, j.apellido, COALESCE(c.nombre, 'Sin club') AS club,
               e.goles, e.asistencias, e.amarillas, e.rojas
        FROM estadisticas e
        INNER JOIN partidos p ON e.id_partido = p.id_partido
        INNER JOIN club cl ON p.id_club_local = cl.id_club
        INNER JOIN club cv ON p.id_club_visitante = cv.id_club
        INNER JOIN jugadores j ON e.ci_jugador = j.ci
        LEFT JOIN club c ON j.id_club = c.id_club
        WHERE CONCAT_WS(' ', j.ci, j.nombre, j.apellido, c.nombre, cl.nombre, cv.nombre, p.fecha) LIKE ?
        ORDER BY p.fecha DESC, p.id_partido DESC, c.nombre, j.apellido
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    //Se muestra toda la información

    while ($fila = $result->fetch_assoc()) {
        echo "<tr>";
        echo celda(date("d/m/Y", strtotime($fila["fecha"])));
        echo celda($fila["local"] . " vs " . $fila["visitante"]);
        foreach (["ci", "nombre", "apellido", "club", "goles", "asistencias", "amarillas", "rojas"] as $columna) {
            echo celda($fila[$columna]);
        }
        botonesAdmin("estadisticas.php", $fila["id_estadistica"]);
        echo "</tr>";
    }
?>
