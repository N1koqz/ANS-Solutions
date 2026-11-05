<?php
require_once __DIR__ . "/../editor.php";
registrarEntidadEditor("sanciones", require __DIR__ . "/configuracion.php");

function procesarEditorSanciones()
{
    procesarEditor("sanciones");
}

function estilosEditorSanciones()
{
    estilosEditor();
}

function mostrarEditorSanciones()
{
    mostrarEditor("sanciones");
}