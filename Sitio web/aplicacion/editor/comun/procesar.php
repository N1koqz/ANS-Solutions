<?php
//Procesa las acciones del editor y luego redirige a la pagina de origen.
function procesarEditor($tipo)
{
    global $conn;

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    requerirAdmin();
    verificarCsrf();

    $entidad = entidadesEditor()[$tipo];
    $tabla = $entidad["tabla"];
    $pk = $entidad["pk"];
    $campos = $entidad["campos"];
    $editables = array_diff_key($campos, [$pk => true]);
    $accion = $_POST["accion"] ?? "";
    $id = isset($_POST["id"]) && is_string($_POST["id"]) ? $_POST["id"] : "";
    $busqueda = isset($_POST["busqueda"]) && is_string($_POST["busqueda"])
        ? trim($_POST["busqueda"])
        : "";

    $datos = [];
    foreach ($campos as $nombre => $campo) {
        $datos[$nombre] = isset($_POST[$nombre]) && is_string($_POST[$nombre])
            ? $_POST[$nombre]
            : "";
    }

    $volver = function ($ok, $texto, $idRegistro = "", $datosFormulario = null) use ($entidad, $busqueda) {
        $_SESSION["flash"] = [
            "ok" => $ok,
            "texto" => $texto,
            "datos" => $datosFormulario,
        ];
        $parametros = [];
        if ($busqueda !== "") {
            $parametros["busqueda"] = $busqueda;
        }
        if ($idRegistro !== "") {
            $parametros["id"] = $idRegistro;
        }

        $query = $parametros ? "?" . http_build_query($parametros) : "";
        $ancla = $idRegistro !== "" ? "#sel" : "";
        header("Location: " . $entidad["pagina"] . $query . $ancla);
        exit();
    };

    try {
        if ($tipo === "clubes" && $accion === "crear_categoria") {
            crearCategoria($conn, $volver);
        }

        if ($accion === "eliminar") {
            eliminarRegistro($conn, $tipo, $tabla, $pk, $id, $volver);
        }

        if ($accion === "resetear" && $tipo === "clubes") {
            resetearClaveClub($conn, $id, $volver);
        }

        if ($accion !== "crear" && $accion !== "editar") {
            $volver(false, "Accion invalida");
        }
        if ($accion === "editar" && $id === "") {
            $volver(false, "Registro no indicado");
        }

        $valores = validarValoresEditor(
            $conn,
            $accion === "crear" ? $campos : $editables,
            $datos,
            $accion,
            $id,
            $entidad,
            $volver,
        );

        if ($accion === "crear") {
            crearRegistro($conn, $tipo, $tabla, $entidad, $valores, $datos, $volver);
        }

        actualizarRegistro($conn, $tabla, $pk, $valores, $id);
        $volver(true, "Cambios guardados", $id);
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("editor.php: " . $e->getMessage());
        $mensajes = [
            1062 => "Ya existe un registro con esos datos",
            1451 => "No se puede eliminar: tiene registros relacionados",
            1452 => "Una de las referencias no existe",
        ];
        $volver(
            false,
            $mensajes[$e->getCode()] ?? "No se pudo guardar el registro",
            $id,
            $accion === "eliminar" ? null : $datos,
        );
    }
}

function crearCategoria($conn, $volver)
{
    $nombre = isset($_POST["nombre_categoria"]) && is_string($_POST["nombre_categoria"])
        ? trim($_POST["nombre_categoria"])
        : "";
    if ($nombre === "" || mb_strlen($nombre) > 50) {
        $volver(false, "El nombre de la categoría es obligatorio y admite hasta 50 caracteres");
    }

    $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $volver(true, "Categoría creada");
}

function eliminarRegistro($conn, $tipo, $tabla, $pk, $id, $volver)
{
    if ($id === "") {
        $volver(false, "Registro no indicado");
    }
    if ($tipo === "clubes") {
        $conn->begin_transaction();
    }

    $stmt = $conn->prepare("DELETE FROM $tabla WHERE $pk = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        $volver(false, "El registro no existe");
    }

    if ($tipo === "clubes") {
        $stmt = $conn->prepare("DELETE FROM usuario WHERE club = ? AND rol <> 'admin'");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $conn->commit();
    }

    $volver(true, "Registro eliminado");
}

function resetearClaveClub($conn, $id, $volver)
{
    if ($id === "") {
        $volver(false, "Registro no indicado");
    }

    $clave = isset($_POST["clave"]) && is_string($_POST["clave"]) ? $_POST["clave"] : "";
    if (strlen($clave) < 8 || strlen($clave) > 72) {
        $volver(false, "La contraseña debe tener entre 8 y 72 caracteres", $id);
    }

    $stmt = $conn->prepare("SELECT nombre FROM club WHERE id_club = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $club = $stmt->get_result()->fetch_assoc();
    if ($club === null) {
        $volver(false, "El club no existe");
    }

    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("SELECT 1 FROM usuario WHERE club = ? AND rol <> 'admin'");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE usuario SET `contraseña` = ? WHERE club = ? AND rol <> 'admin'");
        $stmt->bind_param("ss", $hash, $id);
        $stmt->execute();
        $volver(true, "Contraseña restablecida", $id);
    }

    $stmt = $conn->prepare("SELECT 1 FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $club["nombre"]);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $volver(false, "Ya existe otro usuario llamado " . $club["nombre"], $id);
    }

    $rol = "club";
    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, `contraseña`, rol, club) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $club["nombre"], $club["nombre"], $hash, $rol, $id);
    $stmt->execute();
    $volver(true, "El club no tenía usuario: se creó con la contraseña indicada", $id);
}

function validarValoresEditor($conn, $campos, $datos, $accion, $id, $entidad, $volver)
{
    $valores = [];
    foreach ($campos as $nombre => $campo) {
        $error = validarCampo($conn, $campo, $datos[$nombre]);
        if ($error !== null) {
            $volver(false, $error, $accion === "editar" ? $id : "", $datos);
        }

        $valores[$nombre] = trim($datos[$nombre]);
    }

    if (!empty($entidad["regla"])) {
        $error = $entidad["regla"]($conn, $valores, $accion === "editar" ? $id : "");
        if ($error !== null) {
            $volver(false, $error, $accion === "editar" ? $id : "", $datos);
        }
    }

    return $valores;
}

function crearRegistro($conn, $tipo, $tabla, $entidad, $valores, $datos, $volver)
{
    $clave = "";
    if ($tipo === "clubes") {
        $clave = isset($_POST["clave"]) && is_string($_POST["clave"]) ? $_POST["clave"] : "";
        if (strlen($clave) < 8 || strlen($clave) > 72) {
            $volver(false, "La contraseña debe tener entre 8 y 72 caracteres", "", $datos);
        }

        $stmt = $conn->prepare("SELECT 1 FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $valores["nombre"]);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $volver(false, "Ya existe un usuario con ese nombre", "", $datos);
        }
        $conn->begin_transaction();
    }

    $columnas = implode(", ", array_keys($valores));
    $marcas = implode(", ", array_fill(0, count($valores), "?"));
    $stmt = $conn->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcas)");
    $stmt->bind_param(str_repeat("s", count($valores)), ...array_values($valores));
    $stmt->execute();
    $idNuevo = (string) ($entidad["auto"] ? $conn->insert_id : $valores[$entidad["pk"]]);

    if ($tipo === "clubes") {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $rol = "club";
        $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, `contraseña`, rol, club) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $valores["nombre"], $valores["nombre"], $hash, $rol, $idNuevo);
        $stmt->execute();
        $conn->commit();
    }

    $volver(true, "Registro creado", $idNuevo);
}

function actualizarRegistro($conn, $tabla, $pk, $valores, $id)
{
    $asignaciones = implode(", ", array_map(fn($columna) => "$columna = ?", array_keys($valores)));
    $parametros = [...array_values($valores), $id];
    $stmt = $conn->prepare("UPDATE $tabla SET $asignaciones WHERE $pk = ?");
    $stmt->bind_param(str_repeat("s", count($parametros)), ...$parametros);
    $stmt->execute();
}