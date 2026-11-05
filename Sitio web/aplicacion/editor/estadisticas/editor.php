<?php
require_once __DIR__ . "/../editor.php";

function validarEstadistica($conn, $valores, $idActual)
{
	$stmt = $conn->prepare("SELECT id_club_local, id_club_visitante, goles_local, goles_visitante FROM partidos WHERE id_partido = ?");
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
	$stmt = $conn->prepare("SELECT 1 FROM estadisticas WHERE id_partido = ? AND ci_jugador = ? AND id_estadistica <> ?");
	$stmt->bind_param("ssi", $valores["id_partido"], $valores["ci_jugador"], $actual);
	$stmt->execute();
	if ($stmt->get_result()->num_rows > 0) {
		return "Ese jugador ya tiene estadísticas cargadas en ese partido (editá el registro existente)";
	}

	$golesClub = $club === (int) $partido["id_club_local"] ? $partido["goles_local"] : $partido["goles_visitante"];
	if ($golesClub !== null && (int) $valores["goles"] > (int) $golesClub) {
		return "El jugador no puede tener más goles (" . (int) $valores["goles"] . ") que su club en ese partido (" . (int) $golesClub . ")";
	}

	return null;
}

registrarEntidadEditor("estadisticas", [
	"pagina" => "estadisticas.php",
	"titulo" => "estadísticas",
	"tabla" => "estadisticas",
	"pk" => "id_estadistica",
	"auto" => true,
	"etiqueta" => "CONCAT('Partido ', id_partido, ' - CI ', ci_jugador)",
	"busqueda" => "CONCAT_WS(' ', id_partido, ci_jugador)",
	"regla" => "validarEstadistica",
	"campos" => [
		"id_partido" => [
			"etiqueta" => "Partido",
			"tipo" => "select",
			"orden" => "t DESC",
			"origen" => [
				"partidos",
				"id_partido",
				"CONCAT(fecha, ' - ', (SELECT nombre FROM club WHERE club.id_club = partidos.id_club_local), ' vs ', (SELECT nombre FROM club WHERE club.id_club = partidos.id_club_visitante))",
			],
		],
		"ci_jugador" => [
			"etiqueta" => "Cédula del jugador",
			"tipo" => "number",
			"min" => 1,
			"max" => 2147483647,
			"paso" => 1,
			"entero" => true,
			"validacion" => "ci",
			"existe" => ["jugadores", "ci", "No existe ningún jugador con esa cédula"],
		],
		"goles" => ["etiqueta" => "Goles", "tipo" => "number", "min" => 0, "max" => 30, "paso" => 1, "entero" => true],
		"asistencias" => ["etiqueta" => "Asistencias", "tipo" => "number", "min" => 0, "max" => 30, "paso" => 1, "entero" => true],
		"amarillas" => ["etiqueta" => "Tarjetas amarillas", "tipo" => "number", "min" => 0, "max" => 2, "paso" => 1, "entero" => true],
		"rojas" => ["etiqueta" => "Tarjetas rojas", "tipo" => "number", "min" => 0, "max" => 1, "paso" => 1, "entero" => true],
	],
]);