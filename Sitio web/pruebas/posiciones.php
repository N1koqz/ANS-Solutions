<?php
require_once __DIR__ . "/../aplicacion/modulos/posiciones/calcular_posiciones.php";

function comprobar($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$categorias = [
    ["id_categoria" => 1, "nombre" => "2010"],
    ["id_categoria" => 2, "nombre" => "2011"],
];
$clubes = [
    ["id_club" => 1, "nombre" => "Alfa"],
    ["id_club" => 2, "nombre" => "Beta"],
    ["id_club" => 3, "nombre" => "Celta"],
    ["id_club" => 4, "nombre" => "Delta"],
];
$partidos = [
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 1, "id_club_visitante" => 2, "goles_local" => 2, "goles_visitante" => 0],
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 3, "id_club_visitante" => 1, "goles_local" => 1, "goles_visitante" => 1],
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 2, "id_club_visitante" => 3, "goles_local" => 3, "goles_visitante" => 1],
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 4, "id_club_visitante" => 1, "goles_local" => null, "goles_visitante" => null],
    ["liga" => "oro", "id_categoria" => 2, "id_club_local" => 4, "id_club_visitante" => 2, "goles_local" => 0, "goles_visitante" => 2],
    ["liga" => "plata", "id_categoria" => 1, "id_club_local" => 4, "id_club_visitante" => 1, "goles_local" => 5, "goles_visitante" => 0],
];
$tablas = calcular_posiciones($categorias, $clubes, $partidos, "oro");
$filas = $tablas[1]["clubes"];
comprobar(array_column($filas, "nombre") === ["Alfa", "Beta", "Celta", "Delta"], "Orden incorrecto");
comprobar($filas[0] === ["nombre" => "Alfa", "partidos_jugados" => 2, "partidos_ganados" => 1, "partidos_empatados" => 1, "partidos_perdidos" => 0, "goles_a_favor" => 3, "goles_en_contra" => 1, "diferencia_de_goles" => 2, "puntos" => 4], "Totales de Alfa incorrectos");
comprobar($filas[1] === ["nombre" => "Beta", "partidos_jugados" => 2, "partidos_ganados" => 1, "partidos_empatados" => 0, "partidos_perdidos" => 1, "goles_a_favor" => 3, "goles_en_contra" => 3, "diferencia_de_goles" => 0, "puntos" => 3], "Totales de Beta incorrectos");
comprobar($filas[3]["partidos_jugados"] === 0 && $filas[3]["puntos"] === 0, "Se contó un partido pendiente o de otra liga");
comprobar($tablas[2]["clubes"][0]["nombre"] === "Beta" && $tablas[2]["clubes"][0]["puntos"] === 3, "Las categorías no son independientes");
comprobar(count(calcular_posiciones([], $clubes, [], "oro")) === 0, "Categorías vacías incorrectas");
comprobar(calcular_posiciones($categorias, [], [], "oro")[1]["clubes"] === [], "Clubes vacíos incorrectos");
$tablas_plata = calcular_posiciones($categorias, $clubes, $partidos, "plata");
comprobar($tablas_plata[1]["clubes"][0]["nombre"] === "Delta" && $tablas_plata[1]["clubes"][0]["puntos"] === 3 && $tablas_plata[1]["clubes"][0]["partidos_jugados"] === 1, "Liga de Plata incluye partidos de otra liga");
comprobar($tablas_plata[2]["clubes"][0]["puntos"] === 0, "Liga de Plata incluye partidos de Oro en otra categoría");

$empates = calcular_posiciones([$categorias[0]], $clubes, [
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 1, "id_club_visitante" => 2, "goles_local" => 1, "goles_visitante" => 0],
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 3, "id_club_visitante" => 4, "goles_local" => 3, "goles_visitante" => 2],
], "oro");
comprobar(array_column($empates[1]["clubes"], "nombre") === ["Celta", "Alfa", "Delta", "Beta"], "Desempate por goles o nombre incorrecto");

$diferencia = calcular_posiciones([$categorias[0]], $clubes, [
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 1, "id_club_visitante" => 2, "goles_local" => 1, "goles_visitante" => 0],
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 3, "id_club_visitante" => 4, "goles_local" => 2, "goles_visitante" => 0],
], "oro");
comprobar(array_column($diferencia[1]["clubes"], "nombre") === ["Celta", "Alfa", "Beta", "Delta"], "Desempate por diferencia de goles incorrecto");

$nombres = calcular_posiciones([$categorias[0]], $clubes, [
    ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 1, "id_club_visitante" => 2, "goles_local" => 0, "goles_visitante" => 0],
], "oro");
comprobar(array_column($nombres[1]["clubes"], "nombre") === ["Alfa", "Beta", "Celta", "Delta"], "Empate sin goles u orden por nombre incorrecto");

try {
    calcular_posiciones($categorias, $clubes, [
        ["liga" => "oro", "id_categoria" => 1, "id_club_local" => 1, "id_club_visitante" => 99, "goles_local" => 1, "goles_visitante" => 0],
    ], "oro");
    throw new RuntimeException("No se detectó un club inválido");
} catch (UnexpectedValueException $e) {
    comprobar($e->getMessage() !== "", "El error no tiene mensaje");
}

try {
    calcular_posiciones($categorias, $clubes, $partidos, "bronce");
    throw new RuntimeException("No se detectó una liga inválida");
} catch (InvalidArgumentException $e) {
    comprobar($e->getMessage() !== "", "El error de liga no tiene mensaje");
}

$partidos_incompletos = [
    ["liga" => null, "id_categoria" => 1, "goles_local" => 1, "goles_visitante" => 0],
    ["liga" => "oro", "id_categoria" => null, "goles_local" => 1, "goles_visitante" => 0],
    ["liga" => "oro", "id_categoria" => 1, "goles_local" => 1, "goles_visitante" => null],
];
$tabla_incompleta = calcular_posiciones($categorias, $clubes, $partidos_incompletos, "oro");
comprobar($tabla_incompleta[1]["clubes"][0]["partidos_jugados"] === 0, "Se contó un partido incompleto");

echo "Pruebas de posiciones correctas.\n";
