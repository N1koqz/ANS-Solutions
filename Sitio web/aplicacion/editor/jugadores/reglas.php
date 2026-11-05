<?php
//Limita la fecha de nacimiento al rango de edades de las categorias registradas.
function validarJugador($conn, $valores, $idActual)
{
    $hoy = new DateTimeImmutable("today");
    $fechaNacimiento = new DateTimeImmutable($valores["fecha_nacimiento"]);
    if ($fechaNacimiento > $hoy) {
        return "La fecha de nacimiento no puede estar en el futuro";
    }

    $categorias = $conn->query("SELECT nombre FROM categorias")->fetch_all(MYSQLI_ASSOC);
    $edades = [];
    foreach ($categorias as $categoria) {
        if (preg_match('/\b(?:sub[\s-]*|u[\s-]*)(\d{1,2})\b/i', $categoria["nombre"], $coincidencia)) {
            $edades[] = (int) $coincidencia[1];
        }
    }

    if (count($edades) === 0) {
        return null;
    }

    $edadMasJoven = min($edades);
    $edadMasVieja = max($edades);
    $nacimientoMasAntiguo = $hoy->modify("-$edadMasVieja years");
    $nacimientoMasNuevo = $hoy->modify("-$edadMasJoven years");

    if ($fechaNacimiento < $nacimientoMasAntiguo || $fechaNacimiento > $nacimientoMasNuevo) {
        return "La fecha de nacimiento debe estar dentro de las edades de las categorías registradas (" . $edadMasJoven . " a " . $edadMasVieja . " años)";
    }

    return null;
}