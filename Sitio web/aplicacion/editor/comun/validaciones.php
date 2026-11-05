<?php
//Opciones para los selectores y validacion de campos compartida por los editores.
function opcionesDe($conn, $campo)
{
    [$tabla, $valor, $texto] = $campo["origen"];
    $orden = $campo["orden"] ?? "t";

    return $conn
        ->query("SELECT $valor AS v, $texto AS t FROM $tabla ORDER BY $orden")
        ->fetch_all(MYSQLI_ASSOC);
}

function validarCampo($conn, $campo, $valor)
{
    if (!is_string($valor) || trim($valor) === "") {
        return $campo["etiqueta"] . " es obligatorio";
    }

    $valor = trim($valor);
    if (
        ($campo["validacion"] ?? "") === "ci" &&
        cedulaUruguayaValida($valor) !== true
    ) {
        return $campo["etiqueta"] . " no tiene un formato o dígito verificador válido";
    }

    switch ($campo["tipo"]) {
        case "number":
            if (
                !is_numeric($valor) ||
                $valor < $campo["min"] ||
                $valor > $campo["max"] ||
                (!empty($campo["entero"]) && filter_var($valor, FILTER_VALIDATE_INT) === false)
            ) {
                return $campo["etiqueta"] . " debe ser un número entre " . $campo["min"] . " y " . $campo["max"];
            }

            if (isset($campo["existe"])) {
                [$tablaRef, $columnaRef, $mensajeRef] = $campo["existe"];
                $stmt = $conn->prepare("SELECT 1 FROM $tablaRef WHERE $columnaRef = ?");
                $stmt->bind_param("s", $valor);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    return $mensajeRef;
                }
            }
            break;

        case "date":
            $fecha = DateTime::createFromFormat("Y-m-d", $valor);
            if (!$fecha || $fecha->format("Y-m-d") !== $valor) {
                return $campo["etiqueta"] . " no es una fecha válida";
            }
            break;

        case "select":
            [$tabla, $columna] = $campo["origen"];
            $stmt = $conn->prepare("SELECT 1 FROM $tabla WHERE $columna = ?");
            $stmt->bind_param("s", $valor);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return $campo["etiqueta"] . " no es válido";
            }
            break;

        default:
            if (mb_strlen($valor) > $campo["max"]) {
                return $campo["etiqueta"] . " admite hasta " . $campo["max"] . " caracteres";
            }
    }

    return null;
}