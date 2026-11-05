<?php
//Procesa la creacion y conclusion de partidos; el formulario vive en editor/partidos/editor.php.
//Uso en partidos.php: llamar procesarPartidos() antes de generar HTML.
require_once __DIR__ . "/../../inicio.php";

//Valida un entero dentro de un rango. Devuelve el entero o null
function enteroEnRango($valor, $min, $max)
{
    $n = filter_var($valor, FILTER_VALIDATE_INT);
    return $n !== false && $n >= $min && $n <= $max ? $n : null;
}

function existeClub($conn, $id)
{
    $stmt = $conn->prepare("SELECT 1 FROM club WHERE id_club = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

//Crear y concluir partidos. Debe llamarse antes de generar HTML (termina con una redireccion)
function procesarPartidos()
{
    global $conn;

    //Los unicos POST de la pagina son los de este panel
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }
    requerirAdmin();
    verificarCsrf();

    $accion =
        isset($_POST["accion"]) && is_string($_POST["accion"])
            ? $_POST["accion"]
            : "";
    $campo = fn($nombre) => isset($_POST[$nombre]) && is_string($_POST[$nombre])
        ? trim($_POST[$nombre])
        : "";

    //Guarda el mensaje en la sesion y vuelve a la pagina (se muestra una sola vez)
    $volver = function ($ok, $texto, $datos = null, $concluir = "") {
        $_SESSION["flash"] = [
            "ok" => $ok,
            "texto" => $texto,
            "datos" => $datos,
        ];
        header(
            "Location: partidos.php" .
                ($concluir !== ""
                    ? "?concluir=" . urlencode($concluir) . "#sel"
                    : ""),
        );
        exit();
    };

    try {
        if ($accion === "crear") {
            $datos = [
                "id_club_local" => $campo("id_club_local"),
                "id_club_visitante" => $campo("id_club_visitante"),
                "fecha" => $campo("fecha"),
                "estadio" => $campo("estadio"),
            ];

            $local = enteroEnRango($datos["id_club_local"], 1, PHP_INT_MAX);
            $visitante = enteroEnRango(
                $datos["id_club_visitante"],
                1,
                PHP_INT_MAX,
            );
            if (
                $local === null ||
                $visitante === null ||
                !existeClub($conn, $local) ||
                !existeClub($conn, $visitante)
            ) {
                $volver(
                    false,
                    "Elegí un club local y un club visitante válidos",
                    $datos,
                );
            }
            if ($local === $visitante) {
                $volver(
                    false,
                    "Un club no puede jugar contra sí mismo",
                    $datos,
                );
            }

            $fecha = DateTime::createFromFormat("Y-m-d", $datos["fecha"]);
            if (!$fecha || $fecha->format("Y-m-d") !== $datos["fecha"]) {
                $volver(false, "La fecha no es válida", $datos);
            }
            if ($datos["estadio"] === "" || mb_strlen($datos["estadio"]) > 50) {
                $volver(
                    false,
                    "El estadio es obligatorio (hasta 50 caracteres)",
                    $datos,
                );
            }

            //Un partido sin goles cargados (NULL) esta programado; al concluirlo se cargan ambos goles
            $stmt = $conn->prepare(
                "INSERT INTO partidos (id_club_local, id_club_visitante, fecha, estadio) VALUES (?, ?, ?, ?)",
            );
            $stmt->bind_param(
                "iiss",
                $local,
                $visitante,
                $datos["fecha"],
                $datos["estadio"],
            );
            $stmt->execute();
            $volver(true, "Partido creado");
        }

        if ($accion === "concluir") {
            $id = enteroEnRango($campo("id"), 1, PHP_INT_MAX);
            $datos = [
                "goles_local" => $campo("goles_local"),
                "goles_visitante" => $campo("goles_visitante"),
            ];
            if ($id === null) {
                $volver(false, "Partido no indicado");
            }
            $golesLocal = enteroEnRango($datos["goles_local"], 0, 99);
            $golesVisitante = enteroEnRango($datos["goles_visitante"], 0, 99);
            if ($golesLocal === null || $golesVisitante === null) {
                $volver(
                    false,
                    "Los goles deben ser números enteros entre 0 y 99",
                    $datos,
                    (string) $id,
                );
            }

            //Solo se concluye un partido que sigue programado (sin goles cargados)
            $stmt = $conn->prepare(
                "UPDATE partidos SET goles_local = ?, goles_visitante = ? WHERE id_partido = ? AND goles_local IS NULL AND goles_visitante IS NULL",
            );
            $stmt->bind_param("iii", $golesLocal, $golesVisitante, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $volver(true, "Resultado guardado");
            }
            $volver(false, "El partido no existe o ya fue concluido");
        }

        $volver(false, "Accion invalida");
    } catch (mysqli_sql_exception $e) {
        error_log("procesar_partidos.php: " . $e->getMessage());
        $volver(false, "No se pudo guardar el partido");
    }
}
