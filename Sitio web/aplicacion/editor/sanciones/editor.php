<?php
require_once __DIR__ . "/../../inicio.php";

//Procesa alta, edición y eliminación de sanciones.
function procesarSanciones()
{
    global $conn;

    //Ignora solicitudes que no sean de este formulario.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }
    $accion = $_POST["accion"] ?? "";
    if (!in_array($accion, ["crear", "editar", "eliminar"], true)) {
        return;
    }

    requerirAdmin();
    verificarCsrf();
    $id = isset($_POST["id"]) && is_string($_POST["id"]) ? $_POST["id"] : "";
    $busqueda = isset($_POST["busqueda"]) && is_string($_POST["busqueda"])
        ? trim($_POST["busqueda"])
        : "";
    $volver = function ($ok, $mensaje, $registroId = "", $datos = null) use ($busqueda) {
        $_SESSION["flash"] = ["ok" => $ok, "texto" => $mensaje, "datos" => $datos];
        $parametros = $busqueda === "" ? [] : ["busqueda" => $busqueda];
        if ($registroId !== "") {
            $parametros["id"] = $registroId;
        }
        $query = $parametros ? "?" . http_build_query($parametros) : "";
        header("Location: sanciones.php" . $query . ($registroId !== "" ? "#sel" : ""));
        exit();
    };

    if ($accion === "eliminar") {
        if ($id === "") {
            $volver(false, "Sanción no indicada");
        }
        try {
            $stmt = $conn->prepare("DELETE FROM sanciones WHERE id_sancion = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $existe = $stmt->affected_rows > 0;
            $volver($existe, $existe ? "Sanción eliminada" : "La sanción no existe");
        } catch (mysqli_sql_exception $e) {
            error_log("editor/sanciones/editor.php: " . $e->getMessage());
            $volver(false, "No se pudo eliminar la sanción");
        }
    }

    //Lee los campos y valida antes de crear o actualizar la sanción.
    $datos = [];
    foreach (["ci", "tipo_sancion", "motivo", "fecha_sancion", "fecha_limite"] as $campo) {
        $datos[$campo] = isset($_POST[$campo]) && is_string($_POST[$campo]) ? trim($_POST[$campo]) : "";
    }
    $error = validarSancion($conn, $datos);
    if ($error !== null) {
        $volver(false, $error, $accion === "editar" ? $id : "", $datos);
    }
    if ($accion === "editar" && $id === "") {
        $volver(false, "Sanción no indicada", "", $datos);
    }

    try {
        if ($accion === "crear") {
            $stmt = $conn->prepare("INSERT INTO sanciones (ci, tipo_sancion, motivo, fecha_sancion, fecha_limite) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $datos["ci"], $datos["tipo_sancion"], $datos["motivo"], $datos["fecha_sancion"], $datos["fecha_limite"]);
            $stmt->execute();
            $volver(true, "Sanción creada", (string) $conn->insert_id);
        }

        $stmt = $conn->prepare("UPDATE sanciones SET ci = ?, tipo_sancion = ?, motivo = ?, fecha_sancion = ?, fecha_limite = ? WHERE id_sancion = ?");
        $stmt->bind_param("sssssi", $datos["ci"], $datos["tipo_sancion"], $datos["motivo"], $datos["fecha_sancion"], $datos["fecha_limite"], $id);
        $stmt->execute();
        $volver(true, "Cambios guardados", $id);
    } catch (mysqli_sql_exception $e) {
        error_log("editor/sanciones/editor.php: " . $e->getMessage());
        $mensaje = $e->getCode() === 1452 ? "El jugador seleccionado no existe" : "No se pudo guardar la sanción";
        $volver(false, $mensaje, $accion === "editar" ? $id : "", $datos);
    }
}

//Comprueba cédula, jugador asociado, texto y fechas de la sanción.
function validarSancion($conn, $datos)
{
    //La cédula debe pertenecer a un jugador existente.
    if (cedulaUruguayaValida($datos["ci"]) !== true) {
        return "Ingresá una cédula uruguaya válida";
    }

    $stmt = $conn->prepare("SELECT 1 FROM jugadores WHERE ci = ?");
    $stmt->bind_param("s", $datos["ci"]);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        return "No existe ningún jugador con esa cédula";
    }
    if ($datos["tipo_sancion"] === "" || mb_strlen($datos["tipo_sancion"]) > 50) {
        return "Ingresá un tipo de sanción (máximo 50 caracteres)";
    }
    if ($datos["motivo"] === "" || mb_strlen($datos["motivo"]) > 255) {
        return "Ingresá un motivo (máximo 255 caracteres)";
    }
    //Ambas fechas deben poder guardarse en formato YYYY-MM-DD.
    foreach (["fecha_sancion", "fecha_limite"] as $campo) {
        $fecha = DateTime::createFromFormat("Y-m-d", $datos[$campo]);
        if (!$fecha || $fecha->format("Y-m-d") !== $datos[$campo]) {
            return "Las fechas de la sanción no son válidas";
        }
    }
    return null;
}

//Muestra el formulario vacío o los datos de la sanción seleccionada.
function mostrarEditorSanciones()
{
    global $conn;
    if (esAdmin() !== true) {
        return;
    }

    //Busca la sanción seleccionada y recupera datos para reponer el formulario.
    $id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    $sancion = null;
    if ($id !== "") {
        $stmt = $conn->prepare("SELECT * FROM sanciones WHERE id_sancion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $sancion = $stmt->get_result()->fetch_assoc();
    }
    $datos = $flash["datos"] ?? ($sancion ?? []);
    $busqueda = textoBusqueda();
    ?>
    <section id="editor">
        <h2>Editor de sanciones</h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
        <?php endif; ?>
        <form action="sanciones.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="<?= $sancion ? "editar" : "crear" ?>">
            <input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
            <?php if ($sancion): ?><input type="hidden" name="id" value="<?= (int) $sancion["id_sancion"] ?>"><?php endif; ?>
            <div class="editor-campos">
                <label>Cédula <input type="text" name="ci" maxlength="8" inputmode="numeric" value="<?= htmlspecialchars((string) ($datos["ci"] ?? "")) ?>" required></label>
                <label>Tipo de sanción <input type="text" name="tipo_sancion" maxlength="50" value="<?= htmlspecialchars((string) ($datos["tipo_sancion"] ?? "")) ?>" required></label>
                <label>Motivo <textarea name="motivo" maxlength="255" rows="3" required><?= htmlspecialchars((string) ($datos["motivo"] ?? "")) ?></textarea></label>
                <label>Fecha de sanción <input type="date" name="fecha_sancion" value="<?= htmlspecialchars((string) ($datos["fecha_sancion"] ?? "")) ?>" required></label>
                <label>Fecha límite <input type="date" name="fecha_limite" value="<?= htmlspecialchars((string) ($datos["fecha_limite"] ?? "")) ?>" required></label>
            </div>
            <div class="editor-acciones">
                <button type="submit" class="btn-guardar">Guardar sanción</button>
                <a class="btn-nuevo" href="sanciones.php">Nueva sanción</a>
                <?php if ($sancion): ?>
                    <a class="btn-eliminar" href="sanciones.php?id=<?= (int) $sancion["id_sancion"] ?>&confirmar=1#sel">Eliminar</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if ($sancion && ($_GET["confirmar"] ?? "") === "1"): ?>
            <form action="sanciones.php" method="POST">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $sancion["id_sancion"] ?>">
                <button type="submit" class="btn-eliminar">Confirmar eliminación</button>
            </form>
        <?php endif; ?>
    </section>
    <?php
}
