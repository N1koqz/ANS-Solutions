<?php
return [
    "pagina" => "sanciones.php",
    "titulo" => "sanciones",
    "tabla" => "sanciones",
    "pk" => "id_sancion",
    "auto" => true,
    "etiqueta" => "CONCAT(tipo_sancion, ' (', ci, ')')",
    "busqueda" => "CONCAT_WS(' ', id_sancion, ci, tipo_sancion, motivo)",
    "campos" => [
        "ci" => [
            "etiqueta" => "Cédula del jugador",
            "tipo" => "number",
            "min" => 1,
            "max" => 2147483647,
            "paso" => 1,
            "entero" => true,
            "validacion" => "ci",
            "existe" => ["jugadores", "ci", "No existe ningún jugador con esa cédula"],
        ],
        "tipo_sancion" => ["etiqueta" => "Tipo de Sanción", "tipo" => "text", "max" => 50],
        "motivo" => ["etiqueta" => "Motivo", "tipo" => "textarea", "max" => 255],
        "fecha_sancion" => ["etiqueta" => "Fecha de Suspensión", "tipo" => "date"],
        "fecha_limite" => ["etiqueta" => "Fecha Límite", "tipo" => "date"],
    ],
];