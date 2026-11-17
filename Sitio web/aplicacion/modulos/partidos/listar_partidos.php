<?php

require_once __DIR__ . "/../../inicio.php";
require_once __DIR__ . "/../../nucleo/ligas.php";
requerirLogin();
$ligas = ligas_disponibles();

// Consulta de partidos con los nombres de ambos clubes (con busqueda opcional)

$busqueda = patronBusqueda();
try {
    $stmt = $conn->prepare("
        SELECT p.id_partido, p.id_categoria, p.liga, p.fecha, p.estadio, p.goles_local, p.goles_visitante,
               cl.nombre AS local, cv.nombre AS visitante, ca.nombre AS categoria
        FROM partidos p
        INNER JOIN club cl ON p.id_club_local = cl.id_club
        INNER JOIN club cv ON p.id_club_visitante = cv.id_club
        LEFT JOIN categorias ca ON p.id_categoria = ca.id_categoria
        WHERE CONCAT_WS(' ', cl.nombre, cv.nombre, ca.nombre, p.liga, p.estadio, p.fecha) LIKE ?
        ORDER BY p.fecha DESC, p.id_partido DESC
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (mysqli_sql_exception $error) {
    error_log("Error al listar partidos: " . $error->getMessage());
    http_response_code(500);
    $columnas = esAdmin() ? 10 : 9;
    echo "<tr><td colspan='$columnas'>No se pudieron cargar los partidos. Revisá la base de datos.</td></tr>";
    return;
}

//Se muestra toda la información

while ($partido = $result->fetch_assoc()) {
    //Finalizado = ya tiene los goles cargados
    $finalizado =
        $partido["goles_local"] !== null &&
        $partido["goles_visitante"] !== null;

    //El ganador se calcula con el resultado, asi nunca queda inconsistente
    $ganador = "-";
    if ($finalizado) {
        $gl = (int) $partido["goles_local"];
        $gv = (int) $partido["goles_visitante"];
        $ganador =
            $gl > $gv
                ? $partido["local"]
                : ($gl < $gv
                    ? $partido["visitante"]
                    : "Empate");
    }

    echo "<tr>";
    echo celda(date("d/m/Y", strtotime($partido["fecha"])));
    echo celda($partido["categoria"] ?? "Sin categoría");
    echo celda($partido["liga"] === null ? "Sin liga" : $ligas[$partido["liga"]]);
    echo celda($partido["estadio"]);
    echo celda($partido["local"]);
    echo celda(
        $finalizado
            ? $partido["goles_local"] . " - " . $partido["goles_visitante"]
            : "vs",
    );
    echo celda($partido["visitante"]);
    echo celda($finalizado ? "Finalizado" : "Programado");
    echo celda($ganador);
    if (esAdmin()) {
        $idPartido = (int) $partido["id_partido"];
        echo "<td>";
        if ($partido["id_categoria"] === null) {
            echo "<a class='btn-nuevo' href='partidos.php?categoria=$idPartido#sel-categoria'>Categoría</a>";
        } elseif (!$finalizado) {
            echo "<a class='btn-nuevo' href='partidos.php?concluir=$idPartido#sel'>Concluir</a>";
        } elseif ($partido["liga"] === null) {
            echo " <a class='btn-nuevo' href='partidos.php?liga=$idPartido#sel-liga'>Liga</a>";
        }
        echo "</td>";
    }
    echo "</tr>";
}
