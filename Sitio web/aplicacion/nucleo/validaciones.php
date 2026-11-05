<?php

//Validaciones compartidas por formularios y procesos del sistema.

function cedulaUruguayaValida($cedula)
{
    if (!is_string($cedula)) {
        return false;
    }

    //Se acepta el numero sin separadores o en el formato habitual 1.234.567-8.
    if (
        !preg_match(
            '/^(?:[0-9]{7,8}|[0-9]{1,3}(?:\.[0-9]{3}){1,2}-[0-9])$/',
            $cedula,
        )
    ) {
        return false;
    }

    $digitos = preg_replace("/[^0-9]/", "", $cedula);
    $verificador = (int) substr($digitos, -1);
    $base = substr($digitos, 0, -1);
    $ponderaciones =
        strlen($base) === 7 ? [8, 1, 2, 3, 4, 7, 6] : [1, 2, 3, 4, 7, 6];
    $suma = 0;

    foreach (str_split($base) as $indice => $digito) {
        $suma += (int) $digito * $ponderaciones[$indice];
    }

    return (10 - ($suma % 10)) % 10 === $verificador;
}

function normalizarCedulaUruguaya($cedula)
{
    return preg_replace("/[^0-9]/", "", $cedula);
}
