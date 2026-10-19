<?php
    require_once "plantilla.php";
    requerirLogin();

    // Consulta de partidos con los nombres de ambos clubes (con busqueda opcional)

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT p.id_partido, p.fecha, p.estadio, p.goles_local, p.goles_visitante,
               cl.nombre AS local, cv.nombre AS visitante
        FROM partidos p
        INNER JOIN club cl ON p.id_club_local = cl.id_club
        INNER JOIN club cv ON p.id_club_visitante = cv.id_club
        WHERE CONCAT_WS(' ', cl.nombre, cv.nombre, p.estadio, p.fecha) LIKE ?
        ORDER BY p.fecha DESC, p.id_partido DESC
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    //Se muestra toda la información

    while ($partido = $result->fetch_assoc()) {
        //Finalizado = ya tiene los goles cargados
        $finalizado = $partido["goles_local"] !== null && $partido["goles_visitante"] !== null;

        //El ganador se calcula con el resultado, asi nunca queda inconsistente
        $ganador = "-";
        if ($finalizado) {
            $gl = (int)$partido["goles_local"];
            $gv = (int)$partido["goles_visitante"];
            $ganador = $gl > $gv ? $partido["local"] : ($gl < $gv ? $partido["visitante"] : "Empate");
        }

        echo "<tr>";
        echo celda(date("d/m/Y", strtotime($partido["fecha"])));
        echo celda($partido["estadio"]);
        echo celda($partido["local"]);
        echo celda($finalizado ? $partido["goles_local"] . " - " . $partido["goles_visitante"] : "vs");
        echo celda($partido["visitante"]);
        echo celda($finalizado ? "Finalizado" : "Programado");
        echo celda($ganador);
        if (esAdmin()) {
            echo "<td>" . ($finalizado ? "" : "<a class='btn-nuevo' href='partidos.php?concluir=" . (int)$partido["id_partido"] . "#sel'>Concluir</a>") . "</td>";
        }
        echo "</tr>";
    }
?>
