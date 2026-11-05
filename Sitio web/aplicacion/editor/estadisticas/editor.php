<?php
require_once __DIR__ . "/../editor.php";
require_once __DIR__ . "/reglas.php";
registrarEntidadEditor("estadisticas", require __DIR__ . "/configuracion.php");

function procesarEditorEstadisticas()
{
    procesarEditor("estadisticas");
}

function estilosEditorEstadisticas()
{
    estilosEditor();
}

function mostrarEditorEstadisticas()
{
    mostrarEditor("estadisticas");
}