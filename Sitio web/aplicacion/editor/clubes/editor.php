<?php
require_once __DIR__ . "/../editor.php";
registrarEntidadEditor("clubes", require __DIR__ . "/configuracion.php");

function procesarEditorClubes()
{
    procesarEditor("clubes");
}

function estilosEditorClubes()
{
    estilosEditor();
}

function mostrarEditorClubes()
{
    mostrarEditor("clubes");
}