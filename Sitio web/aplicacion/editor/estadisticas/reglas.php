<?php
//Valida que el jugador pertenezca al partido y que sus estadisticas sean coherentes.
function validarEstadistica($conn, $valores, $idActual)
{
    $stmt = $conn->prepare(
        "SELECT id_club_local, id_club_visitante, goles_local, goles_visitante FROM partidos WHERE id_partido = ?",
    );
    $stmt->bind_param("s", $valores["id_partido"]);
    $stmt->execute();
    $partido = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("SELECT id_club FROM jugadores WHERE ci = ?");
    $stmt->bind_param("s", $valores["ci_jugador"]);
    $stmt->execute();
    $jugador = $stmt->get_result()->fetch_assoc();

    if ($partido === null || $jugador === null) {
        return "Partido o jugador no válido";
    }

    $club = (int) $jugador["id_club"];
    if ($club !== (int) $partido["id_club_local"] && $club !== (int) $partido["id_club_visitante"]) {
        return "El jugador no pertenece a ninguno de los clubes de ese partido";
    }

    $actual = (int) $idActual;
    $stmt = $conn->prepare(
        "SELECT 1 FROM estadisticas WHERE id_partido = ? AND ci_jugador = ? AND id_estadistica <> ?",
    );
    $stmt->bind_param("ssi", $valores["id_partido"], $valores["ci_jugador"], $actual);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Ese jugador ya tiene estadísticas cargadas en ese partido (editá el registro existente)";
    }

    $golesClub = $club === (int) $partido["id_club_local"]
        ? $partido["goles_local"]
        : $partido["goles_visitante"];
    if ($golesClub !== null && (int) $valores["goles"] > (int) $golesClub) {
        return "El jugador no puede tener más goles (" . (int) $valores["goles"] . ") que su club en ese partido (" . (int) $golesClub . ")";
    }

    return null;
}