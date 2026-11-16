<?php

//Validacion de la cedula

function cedulaUruguayaValida($cedula)
{
    if (!is_string($cedula)) {
        return false;
    }

    //La cedula se ingresa solo con digitos, sin puntos ni guiones.
    if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
        return false;
    }

    $verificador = (int) substr($cedula, -1);
    $base = substr($cedula, 0, -1);
    $ponderaciones =
        strlen($base) === 7 ? [2, 9, 8, 7, 6, 3, 4] : [9, 8, 7, 6, 3, 4];
    $suma = 0;

    foreach (str_split($base) as $indice => $digito) {
        $suma += (int) $digito * $ponderaciones[$indice];
    }

    return (10 - ($suma % 10)) % 10 === $verificador;
}
