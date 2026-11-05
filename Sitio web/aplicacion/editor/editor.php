<?php
//Nucleo compartido de los editores de clubes, jugadores, sanciones y estadisticas.
//Cada pagina carga su modulo en editor/<entidad>/editor.php.
require_once __DIR__ . "/../inicio.php";
require_once __DIR__ . "/comun/validaciones.php";

//Cada editor agrega su configuracion al cargar su propio modulo.
function entidadesEditor()
{
    return $GLOBALS["entidadesEditor"] ?? [];
}

function registrarEntidadEditor($tipo, $configuracion)
{
    $GLOBALS["entidadesEditor"][$tipo] = $configuracion;
}

require_once __DIR__ . "/comun/procesar.php";
require_once __DIR__ . "/comun/vista.php";
