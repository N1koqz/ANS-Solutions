<?php

//Punto de entrada unico de la aplicacion: toda pagina empieza con
//    require_once __DIR__ . "/aplicacion/inicio.php";
//Carga el nucleo (base de datos, sesion, permisos, CSRF, busqueda) y la plantilla (menu, pie, celdas).

//Carpeta raiz del proyecto (la que contiene aplicacion/, recursos/ y almacenamiento/)
if (!defined("RAIZ")) {
    define("RAIZ", dirname(__DIR__));
}

require_once RAIZ . "/aplicacion/nucleo/conexion.php";
require_once RAIZ . "/aplicacion/nucleo/permisos.php";
require_once RAIZ . "/aplicacion/nucleo/csrf.php";
require_once RAIZ . "/aplicacion/nucleo/busqueda.php";
require_once RAIZ . "/aplicacion/nucleo/validaciones.php";
require_once RAIZ . "/aplicacion/vistas/plantilla.php";
