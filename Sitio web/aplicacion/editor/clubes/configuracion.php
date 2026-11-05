<?php
return [
    "pagina" => "clubes.php",
    "titulo" => "clubes",
    "tabla" => "club",
    "pk" => "id_club",
    "auto" => true,
    "etiqueta" => "nombre",
    "busqueda" => "CONCAT_WS(' ', nombre, ciudad, fundacion)",
    "campos" => [
        "nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
        "ciudad" => ["etiqueta" => "Ciudad", "tipo" => "text", "max" => 50],
        "fundacion" => ["etiqueta" => "Año de Fundación", "tipo" => "number", "min" => 1800, "max" => 2100, "paso" => 1, "entero" => true],
    ],
];