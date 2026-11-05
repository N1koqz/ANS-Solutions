<?php

//Texto y patron de la barra de busqueda

//Texto de busqueda recibido por GET (siempre un string)
function textoBusqueda()
{
    return isset($_GET["busqueda"]) && is_string($_GET["busqueda"])
        ? trim($_GET["busqueda"])
        : "";
}

//Arma el patron LIKE de la busqueda, escapando los comodines del usuario
function patronBusqueda()
{
    return "%" . addcslashes(textoBusqueda(), "%_\\") . "%";
}
