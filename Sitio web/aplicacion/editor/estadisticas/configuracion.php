<?php
return [
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
];