<?php
    require_once "plantilla.php";
    requerirLogin();

    // Acumulado por jugador: partidos con estadisticas y totales (con busqueda opcional). Ordenado por goles

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT j.ci, j.nombre, j.apellido, COALESCE(c.nombre, 'Sin club') AS club,
               COUNT(*) AS partidos, SUM(e.goles) AS goles, SUM(e.asistencias) AS asistencias,
               SUM(e.amarillas) AS amarillas, SUM(e.rojas) AS rojas
        FROM estadisticas e
        INNER JOIN jugadores j ON e.ci_jugador = j.ci
        LEFT JOIN club c ON j.id_club = c.id_club
        WHERE CONCAT_WS(' ', j.ci, j.nombre, j.apellido, c.nombre) LIKE ?
        GROUP BY j.ci, j.nombre, j.apellido, c.nombre
        ORDER BY goles DESC, asistencias DESC, j.apellido
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    $puesto = 0;
    while ($fila = $result->fetch_assoc()) {
        $puesto++;
        echo "<tr>" . celda($puesto);
        foreach (["nombre", "apellido", "club", "partidos", "goles", "asistencias", "amarillas", "rojas"] as $columna) {
            echo celda($fila[$columna]);
        }
        echo "</tr>";
    }
?>
