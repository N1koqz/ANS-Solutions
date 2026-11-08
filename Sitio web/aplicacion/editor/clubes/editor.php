<?php
require_once __DIR__ . "/../../inicio.php";

const CAMPOS_CLUBES = [
    "nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
    "ciudad" => ["etiqueta" => "Ciudad", "tipo" => "text", "max" => 50],
    "fundacion" => ["etiqueta" => "Año de Fundación", "tipo" => "number", "min" => 1800, "max" => 2100],
];

//Procesa las acciones que llegan desde los formularios de Clubes.
function procesarClubes()
{
    global $conn;

    //Solo procesa envíos de este formulario.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    $accion = $_POST["accion"] ?? "";
    $acciones = ["crear", "editar", "eliminar", "resetear", "crear_categoria"];
    if (!in_array($accion, $acciones, true)) {
        return;
    }

    requerirAdmin();
    verificarCsrf();

    $id = isset($_POST["id"]) && is_string($_POST["id"]) ? $_POST["id"] : "";
    $busqueda = isset($_POST["busqueda"]) && is_string($_POST["busqueda"])
        ? trim($_POST["busqueda"])
        : "";
    $transaccion = false;
    //Guarda el resultado y vuelve a la misma búsqueda o registro.
    $volver = function ($ok, $mensaje, $registroId = "", $datos = null) use ($busqueda) {
        $_SESSION["flash"] = ["ok" => $ok, "texto" => $mensaje, "datos" => $datos];
        $parametros = $busqueda === "" ? [] : ["busqueda" => $busqueda];
        if ($registroId !== "") {
            $parametros["id"] = $registroId;
        }
        $query = $parametros ? "?" . http_build_query($parametros) : "";
        header("Location: clubes.php" . $query . ($registroId !== "" ? "#sel" : ""));
        exit();
    };

    try {
        //Las acciones especiales se atienden antes del guardado normal del club.
        if ($accion === "crear_categoria") {
            crearCategoriaClubes($conn, $volver);
        }
        if ($accion === "resetear") {
            resetearClaveClub($conn, $id, $volver);
        }
        if ($accion === "eliminar") {
            eliminarClub($conn, $id, $volver, $transaccion);
        }

        //Lee y valida los campos del club antes de tocar la base.
        $datos = [];
        foreach (CAMPOS_CLUBES as $nombre => $campo) {
            $datos[$nombre] = isset($_POST[$nombre]) && is_string($_POST[$nombre])
                ? trim($_POST[$nombre])
                : "";
            if ($datos[$nombre] === "" || strlen($datos[$nombre]) > $campo["max"]) {
                $volver(false, $campo["etiqueta"] . " es obligatorio y admite hasta " . $campo["max"] . " caracteres", $accion === "editar" ? $id : "", $datos);
            }
        }
        if (!ctype_digit($datos["fundacion"]) || (int) $datos["fundacion"] < 1800 || (int) $datos["fundacion"] > 2100) {
            $volver(false, "El año de fundación debe estar entre 1800 y 2100", $accion === "editar" ? $id : "", $datos);
        }
        if ($accion === "editar" && $id === "") {
            $volver(false, "Club no indicado", "", $datos);
        }

        //Crear también registra su usuario de acceso; editar solo actualiza el club.
        if ($accion === "crear") {
            crearClub($conn, $datos, $volver, $transaccion);
        }

        $stmt = $conn->prepare("UPDATE club SET nombre = ?, ciudad = ?, fundacion = ? WHERE id_club = ?");
        $stmt->bind_param("ssii", $datos["nombre"], $datos["ciudad"], $datos["fundacion"], $id);
        $stmt->execute();
        $volver(true, "Cambios guardados", $id);
    } catch (mysqli_sql_exception $e) {
        if ($transaccion === true) {
            $conn->rollback();
        }
        error_log("editor/clubes/editor.php: " . $e->getMessage());
        $mensajes = [1062 => "Ya existe un registro con esos datos", 1451 => "No se puede eliminar: tiene registros relacionados"];
        $volver(false, $mensajes[$e->getCode()] ?? "No se pudo guardar el club", $id);
    }
}

//Crea el club y su usuario dentro de una transacción.
function crearClub($conn, $datos, $volver, &$transaccion)
{
    //Evita usuarios duplicados antes de abrir la transacción.
    $clave = isset($_POST["clave"]) && is_string($_POST["clave"]) ? $_POST["clave"] : "";
    if (strlen($clave) < 8 || strlen($clave) > 72) {
        $volver(false, "La contraseña debe tener entre 8 y 72 caracteres", "", $datos);
    }

    $stmt = $conn->prepare("SELECT 1 FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $datos["nombre"]);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $volver(false, "Ya existe un usuario con ese nombre", "", $datos);
    }

    $conn->begin_transaction();
    $transaccion = true;
    $stmt = $conn->prepare("INSERT INTO club (nombre, ciudad, fundacion) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $datos["nombre"], $datos["ciudad"], $datos["fundacion"]);
    $stmt->execute();
    $idClub = (string) $conn->insert_id;

    //La clave se guarda como hash, nunca en texto plano.
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $rol = "club";
    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, `contraseña`, rol, club) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $datos["nombre"], $datos["nombre"], $hash, $rol, $idClub);
    $stmt->execute();
    $conn->commit();
    $transaccion = false;
    $volver(true, "Club creado", $idClub);
}

//Elimina el club y su usuario; si una consulta falla, revierte ambas operaciones.
function eliminarClub($conn, $id, $volver, &$transaccion)
{
    if ($id === "") {
        $volver(false, "Club no indicado");
    }

    $conn->begin_transaction();
    $transaccion = true;
    $stmt = $conn->prepare("DELETE FROM club WHERE id_club = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        $transaccion = false;
        $volver(false, "El club no existe");
    }

    $stmt = $conn->prepare("DELETE FROM usuario WHERE club = ? AND rol <> 'admin'");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $conn->commit();
    $transaccion = false;
    $volver(true, "Club eliminado");
}

//Registra el año de nacimiento que se usará como categoría.
function crearCategoriaClubes($conn, $volver)
{
    $anio = isset($_POST["nombre_categoria"]) && is_string($_POST["nombre_categoria"])
        ? trim($_POST["nombre_categoria"])
        : "";
    $anioActual = (int) date("Y");
    if (!ctype_digit($anio) || strlen($anio) !== 4 || (int) $anio < 1900 || (int) $anio > $anioActual) {
        $volver(false, "Ingresá un año de nacimiento de cuatro cifras entre 1900 y $anioActual");
    }

    $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
    $stmt->bind_param("s", $anio);
    $stmt->execute();
    $volver(true, "Categoría creada");
}

//Cambia la contraseña del usuario del club o lo crea si falta.
function resetearClaveClub($conn, $id, $volver)
{
    if ($id === "") {
        $volver(false, "Club no indicado");
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

    //Si el club aún no tiene usuario, se crea con su nombre como acceso.
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
    $volver(true, "Usuario creado y contraseña asignada", $id);
}

//Muestra el formulario del club seleccionado o uno vacío.
function mostrarEditorClubes()
{
    global $conn;
    if (esAdmin() !== true) {
        return;
    }

    $id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    $club = null;
    if ($id !== "") {
        $stmt = $conn->prepare("SELECT id_club, nombre, ciudad, fundacion FROM club WHERE id_club = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $club = $stmt->get_result()->fetch_assoc();
    }
    $confirmarEliminar = $club !== null && ($_GET["confirmar"] ?? "") === "1";
    ?>
    <section id="editor">
        <h2>Editor de clubes</h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
        <?php endif; ?>
        <?php if ($confirmarEliminar): ?>
            <form action="clubes.php" method="POST">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $club["id_club"] ?>">
                <p>¿Eliminar el club <strong><?= htmlspecialchars($club["nombre"]) ?></strong>?</p>
                <button class="btn-eliminar" type="submit">Confirmar</button>
                <a class="btn-nuevo" href="clubes.php?id=<?= (int) $club["id_club"] ?>#sel">Cancelar</a>
            </form>
        <?php endif; ?>
        <form action="clubes.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="<?= $club ? "editar" : "crear" ?>">
            <?php if ($club): ?><input type="hidden" name="id" value="<?= (int) $club["id_club"] ?>"><?php endif; ?>
            <div class="editor-campos">
                <label><span>Nombre</span><input type="text" name="nombre" maxlength="50" value="<?= htmlspecialchars($club["nombre"] ?? "") ?>" required></label>
                <label><span>Ciudad</span><input type="text" name="ciudad" maxlength="50" value="<?= htmlspecialchars($club["ciudad"] ?? "") ?>" required></label>
                <label><span>Año de fundación</span><input type="number" name="fundacion" min="1800" max="2100" value="<?= htmlspecialchars((string) ($club["fundacion"] ?? "")) ?>" required></label>
                <?php if ($club === null): ?>
                    <label><span>Contraseña del club</span><input type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" required></label>
                <?php endif; ?>
            </div>
            <div class="editor-acciones">
                <button type="submit" class="btn-guardar">Guardar club</button>
                <a class="btn-nuevo" href="clubes.php">Nuevo club</a>
                <?php if ($club): ?><a class="btn-eliminar" href="clubes.php?id=<?= (int) $club["id_club"] ?>&confirmar=1#sel">Eliminar</a><?php endif; ?>
            </div>
        </form>
        <?php if ($club): ?>
            <form action="clubes.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="resetear">
                <input type="hidden" name="id" value="<?= (int) $club["id_club"] ?>">
                <label>Nueva contraseña <input type="password" name="clave" minlength="8" maxlength="72" required></label>
                <button type="submit">Restablecer contraseña</button>
            </form>
        <?php endif; ?>
        <?php mostrarCategoriasClubes(); ?>
    </section>
    <?php
}

//Muestra los años de nacimiento registrados para asignarlos a jugadores.
function mostrarCategoriasClubes()
{
    global $conn;
    if (esAdmin() !== true) {
        return;
    }

    $categorias = $conn->query("SELECT nombre FROM categorias ORDER BY nombre DESC")->fetch_all(MYSQLI_ASSOC);
    ?>
    <section class="editor-categorias">
        <h2>Categorías por año de nacimiento</h2>
        <form action="clubes.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="crear_categoria">
            <label>Año de nacimiento <input type="number" name="nombre_categoria" min="1900" max="<?= date("Y") ?>" step="1" placeholder="2011" required></label>
            <button type="submit" class="btn-guardar">Añadir categoría</button>
        </form>
        <?php if (count($categorias) === 0): ?>
            <p>Todavía no hay categorías.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($categorias as $categoria): ?>
                    <li><?= htmlspecialchars($categoria["nombre"]) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <?php
}