<?php
require_once __DIR__ . "/../editor.php";
require_once __DIR__ . "/reglas.php";
registrarEntidadEditor("jugadores", require __DIR__ . "/configuracion.php");

function procesarEditorJugadores()
{
    procesarEditor("jugadores");
}

function estilosEditorJugadores()
{
    estilosEditor();
}

function mostrarEditorJugadores()
{
    mostrarEditor("jugadores");
}