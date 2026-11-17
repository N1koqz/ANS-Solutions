<?php
require_once __DIR__ . "/../aplicacion/inicio.php";
require_once __DIR__ . "/../aplicacion/editor/partidos/editor.php";

function verificar_liga($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

// Las tablas temporales ocultan las reales solo durante esta conexión.
$conn->query("CREATE TEMPORARY TABLE partidos (
    id_partido INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE, estadio VARCHAR(50), id_club_local INT, id_club_visitante INT,
    goles_local INT, goles_visitante INT, id_categoria INT
)");
$conn->query(file_get_contents(__DIR__ . "/../aplicacion/migraciones/agregar_liga_partidos.sql"));
$conn->query("CREATE TEMPORARY TABLE club (id_club INT PRIMARY KEY, nombre VARCHAR(50))");
$conn->query("CREATE TEMPORARY TABLE categorias (id_categoria INT PRIMARY KEY, nombre VARCHAR(50))");
$conn->query("INSERT INTO club VALUES (1, 'Alfa'), (2, 'Beta')");
$conn->query("INSERT INTO categorias VALUES (2010, '2010')");
$conn->query("INSERT INTO partidos (id_partido, id_club_local, id_club_visitante, fecha, estadio, id_categoria, liga, goles_local, goles_visitante) VALUES
    (1, 1, 2, '2026-01-01', 'Cancha', 2010, 'oro', 2, 0),
    (2, 1, 2, '2026-01-02', 'Cancha', 2010, 'plata', 0, 3),
    (3, 1, 2, '2026-01-03', 'Cancha', 2010, NULL, 0, 5)");
$_SESSION["rol"] = "admin";
$_SESSION["email"] = "prueba";

$datos = [
    "id_club_local" => "1", "id_club_visitante" => "2",
    "id_categoria" => "2010", "liga" => "plata", "fecha" => "2026-01-04", "estadio" => "Cancha",
];
$campo = function ($nombre) use (&$datos) {
    return $datos[$nombre] ?? "";
};
$respuesta = null;
$volver = function ($ok, $texto) use (&$respuesta) {
    $respuesta = [$ok, $texto];
    throw new RuntimeException("Fin de acción");
};
try {
    crearPartido($conn, $campo, $volver);
} catch (RuntimeException $error) {
    verificar_liga($error->getMessage() === "Fin de acción", "Falló la creación del partido");
}
verificar_liga($respuesta[0] === true, "No se creó el partido");
verificar_liga($conn->query("SELECT liga FROM partidos WHERE fecha = '2026-01-04'")->fetch_assoc()["liga"] === "plata", "No se guardó la liga");

$datos["liga"] = "bronce";
try {
    crearPartido($conn, $campo, $volver);
} catch (RuntimeException $error) {
    verificar_liga($error->getMessage() === "Fin de acción", "Falló la validación de la liga");
}
verificar_liga($respuesta[0] === false, "Se aceptó una liga inválida");

$_GET = ["liga" => "oro"];
ob_start();
include __DIR__ . "/../posiciones.php";
$pagina_incompleta = ob_get_clean();
verificar_liga(strpos($pagina_incompleta, "sin liga") !== false, "No se notificó el partido sin liga");
verificar_liga((bool) preg_match("/<tbody>\\s*<tr>\\s*<td>1<\\/td>\\s*<td>Alfa<\\/td>/", $pagina_incompleta), "No se mostró la tabla con partidos incompletos");

$datos = ["id" => "3", "liga" => "plata"];
try {
    asignarLigaPartido($conn, $campo, $volver);
} catch (RuntimeException $error) {
    verificar_liga($error->getMessage() === "Fin de acción", "Falló la asignación de liga");
}
verificar_liga($respuesta[0] === true && $conn->query("SELECT liga FROM partidos WHERE id_partido = 3")->fetch_assoc()["liga"] === "plata", "No se asignó la liga");

$conn->query("UPDATE partidos SET id_categoria = NULL WHERE id_partido = 3");
$datos = ["id" => "3", "id_categoria" => "2010"];
try {
    asignarCategoriaPartido($conn, $campo, $volver);
} catch (RuntimeException $error) {
    verificar_liga($error->getMessage() === "Fin de acción", "Falló la asignación de categoría");
}
verificar_liga($respuesta[0] === true && (int) $conn->query("SELECT id_categoria FROM partidos WHERE id_partido = 3")->fetch_assoc()["id_categoria"] === 2010, "No se asignó la categoría");

$_GET = ["liga" => "oro"];
ob_start();
include __DIR__ . "/../posiciones.php";
$pagina_oro = ob_get_clean();
verificar_liga(strpos($pagina_oro, "<h2>Liga de Oro</h2>") !== false, "No se mostró la liga de oro");
verificar_liga((bool) preg_match("/<tbody>\\s*<tr>\\s*<td>1<\\/td>\\s*<td>Alfa<\\/td>/", $pagina_oro), "La tabla de oro no corresponde a sus partidos");

$_GET = ["liga" => "plata"];
ob_start();
include __DIR__ . "/../posiciones.php";
$pagina_plata = ob_get_clean();
verificar_liga(strpos($pagina_plata, "<h2>Liga de Plata</h2>") !== false, "No se mostró la liga de plata");
verificar_liga((bool) preg_match("/<tbody>\\s*<tr>\\s*<td>1<\\/td>\\s*<td>Beta<\\/td>/", $pagina_plata), "La tabla de plata no corresponde a sus partidos");

$_GET = ["liga" => "3"];
ob_start();
mostrarFormularioPartidos();
$formulario = ob_get_clean();
verificar_liga(strpos($formulario, 'value="plata"') !== false && strpos($formulario, 'value="oro"') !== false, "Faltan opciones de liga en el registro");
verificar_liga(strpos($formulario, 'id="sel-liga"') !== false && strpos($formulario, 'value="plata" selected') !== false, "No se mostró la liga asignada en el editor");

echo "Pruebas de integración de ligas correctas.\n";
