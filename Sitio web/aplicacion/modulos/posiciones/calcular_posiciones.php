<?php

require_once __DIR__ . "/../../nucleo/ligas.php";

function calcular_posiciones($categorias, $clubes, $partidos, $liga)
{
    if (!is_string($liga) || !array_key_exists($liga, ligas_disponibles())) {
        throw new InvalidArgumentException("La liga seleccionada no es válida.");
    }
    $tablas = [];
    // Cada categoría comienza con todos los clubes y sus estadísticas en cero.
    foreach ($categorias as $categoria) {
        $id_categoria = (int) $categoria["id_categoria"];
        $tablas[$id_categoria] = ["nombre" => $categoria["nombre"], "clubes" => []];
        foreach ($clubes as $club) {
            $tablas[$id_categoria]["clubes"][(int) $club["id_club"]] = [
                "nombre" => $club["nombre"],
                "partidos_jugados" => 0,
                "partidos_ganados" => 0,
                "partidos_empatados" => 0,
                "partidos_perdidos" => 0,
                "goles_a_favor" => 0,
                "goles_en_contra" => 0,
                "diferencia_de_goles" => 0,
                "puntos" => 0,
            ];
        }
    }

    foreach ($partidos as $partido) {
        // Los partidos sin liga, categoría o resultado completo no suman.
        if ($partido["liga"] !== $liga || $partido["id_categoria"] === null || $partido["goles_local"] === null || $partido["goles_visitante"] === null) {
            continue;
        }
        $id_categoria = (int) $partido["id_categoria"];
        $id_club_local = (int) $partido["id_club_local"];
        $id_club_visitante = (int) $partido["id_club_visitante"];
        if (!isset($tablas[$id_categoria]["clubes"][$id_club_local], $tablas[$id_categoria]["clubes"][$id_club_visitante]) || $id_club_local === $id_club_visitante) {
            throw new UnexpectedValueException("El partido tiene una categoría o clubes inválidos.");
        }
        $goles_local = (int) $partido["goles_local"];
        $goles_visitante = (int) $partido["goles_visitante"];

        // Se procesa el mismo resultado desde la perspectiva de cada club.
        $resultados_por_club = [
            [$id_club_local, $goles_local, $goles_visitante],
            [$id_club_visitante, $goles_visitante, $goles_local],
        ];
        foreach ($resultados_por_club as [$id_club, $goles_a_favor, $goles_en_contra]) {
            $fila = &$tablas[$id_categoria]["clubes"][$id_club];
            $fila["partidos_jugados"]++;
            $fila["goles_a_favor"] += $goles_a_favor;
            $fila["goles_en_contra"] += $goles_en_contra;
            $fila["diferencia_de_goles"] += $goles_a_favor - $goles_en_contra;
            if ($goles_a_favor > $goles_en_contra) {
                $fila["partidos_ganados"]++;
                $fila["puntos"] += 3;
            } elseif ($goles_a_favor === $goles_en_contra) {
                $fila["partidos_empatados"]++;
                $fila["puntos"]++;
            } else {
                $fila["partidos_perdidos"]++;
            }
            // Rompe la referencia para que no afecte a otra fila.
            unset($fila);
        }
    }

    // Ordena por puntos, diferencia y goles a favor; el nombre desempata al final.
    foreach ($tablas as &$tabla) {
        usort($tabla["clubes"], function ($primer_club, $segundo_club) {
            return ($segundo_club["puntos"] <=> $primer_club["puntos"])
                ?: ($segundo_club["diferencia_de_goles"] <=> $primer_club["diferencia_de_goles"])
                ?: ($segundo_club["goles_a_favor"] <=> $primer_club["goles_a_favor"])
                ?: strcasecmp((string) $primer_club["nombre"], (string) $segundo_club["nombre"]);
        });
    }
    unset($tabla);
    return $tablas;
}
