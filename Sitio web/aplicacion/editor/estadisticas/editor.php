<?php
require_once __DIR__ . "/../../inicio.php";

//Procesa alta, edición y eliminación de estadísticas de partido.
function procesarEstadisticas()
{
    global $conn;

    //Este editor solo procesa crear, editar o eliminar estadísticas.
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
    $volver = function ($ok, $mensaje, $registroId = "", $datos = null) {
        $_SESSION["flash"] = ["ok" => $ok, "texto" => $mensaje, "datos" => $datos];
        $query = $registroId !== "" ? "?id=" . urlencode($registroId) . "#sel" : "";
        header("Location: estadisticas.php" . $query);
        exit();
    };

    if ($accion === "eliminar") {
        if ($id === "") {
            $volver(false, "Estadística no indicada");
        }
        try {
            $stmt = $conn->prepare("DELETE FROM estadisticas WHERE id_estadistica = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $existe = $stmt->affected_rows > 0;
            $volver($existe, $existe ? "Estadística eliminada" : "La estadística no existe");
        } catch (mysqli_sql_exception $e) {
            error_log("editor/estadisticas/editor.php: " . $e->getMessage());
            $volver(false, "No se pudo eliminar la estadística");
        }
    }

    //Lee y valida los valores antes de escribir en la base de datos.
    $datos = [];
    foreach (["id_partido", "ci_jugador", "goles", "asistencias", "amarillas", "rojas"] as $campo) {
        $datos[$campo] = isset($_POST[$campo]) && is_string($_POST[$campo]) ? trim($_POST[$campo]) : "";
    }
    $error = validarDatosEstadistica($conn, $datos, $id);
    if ($error !== null) {
        $volver(false, $error, $accion === "editar" ? $id : "", $datos);
    }
    if ($accion === "editar" && $id === "") {
        $volver(false, "Estadística no indicada", "", $datos);
    }

    try {
        if ($accion === "crear") {
            $stmt = $conn->prepare("INSERT INTO estadisticas (id_partido, ci_jugador, goles, asistencias, amarillas, rojas) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiii", $datos["id_partido"], $datos["ci_jugador"], $datos["goles"], $datos["asistencias"], $datos["amarillas"], $datos["rojas"]);
            $stmt->execute();
            $volver(true, "Estadística creada", (string) $conn->insert_id);
        }

        $stmt = $conn->prepare("UPDATE estadisticas SET id_partido = ?, ci_jugador = ?, goles = ?, asistencias = ?, amarillas = ?, rojas = ? WHERE id_estadistica = ?");
        $stmt->bind_param("isiiiii", $datos["id_partido"], $datos["ci_jugador"], $datos["goles"], $datos["asistencias"], $datos["amarillas"], $datos["rojas"], $id);
        $stmt->execute();
        $volver(true, "Cambios guardados", $id);
    } catch (mysqli_sql_exception $e) {
        error_log("editor/estadisticas/editor.php: " . $e->getMessage());
        $volver(false, "No se pudieron guardar las estadísticas", $accion === "editar" ? $id : "", $datos);
    }
}

//Comprueba que el jugador pertenezca al partido y que sus números sean posibles.
function validarDatosEstadistica($conn, $datos, $idActual)
{
    //Comprueba formato y límites numéricos de las estadísticas.
    if (!ctype_digit($datos["id_partido"]) || !cedulaUruguayaValida($datos["ci_jugador"])) {
        return "Elegí un partido y una cédula válidos";
    }
    $limites = ["goles" => 30, "asistencias" => 30, "amarillas" => 2, "rojas" => 1];
    foreach ($limites as $campo => $maximo) {
        if (!ctype_digit($datos[$campo]) || (int) $datos[$campo] > $maximo) {
            return "$campo debe ser un número entero entre 0 y $maximo";
        }
    }

    //El jugador debe pertenecer a uno de los dos clubes del partido.
    $stmt = $conn->prepare("SELECT id_club_local, id_club_visitante, goles_local, goles_visitante FROM partidos WHERE id_partido = ?");
    $stmt->bind_param("i", $datos["id_partido"]);
    $stmt->execute();
    $partido = $stmt->get_result()->fetch_assoc();
    $stmt = $conn->prepare("SELECT id_club FROM jugadores WHERE ci = ?");
    $stmt->bind_param("s", $datos["ci_jugador"]);
    $stmt->execute();
    $jugador = $stmt->get_result()->fetch_assoc();
    if ($partido === null || $jugador === null) {
        return "El partido o el jugador no existe";
    }

    $clubJugador = (int) $jugador["id_club"];
    if ($clubJugador !== (int) $partido["id_club_local"] && $clubJugador !== (int) $partido["id_club_visitante"]) {
        return "El jugador no pertenece a los clubes de ese partido";
    }

    //No se permiten dos filas para el mismo jugador y partido.
    $stmt = $conn->prepare("SELECT 1 FROM estadisticas WHERE id_partido = ? AND ci_jugador = ? AND id_estadistica <> ?");
    $stmt->bind_param("isi", $datos["id_partido"], $datos["ci_jugador"], $idActual);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Ese jugador ya tiene estadísticas en ese partido";
    }

    $golesDelClub = $clubJugador === (int) $partido["id_club_local"]
        ? $partido["goles_local"]
        : $partido["goles_visitante"];
    if ($golesDelClub !== null && (int) $datos["goles"] > (int) $golesDelClub) {
        return "El jugador no puede tener más goles que su club en ese partido";
    }
    return null;
}

//Muestra el formulario y sus opciones de partido.
function mostrarEditorEstadisticas()
{
    global $conn;
    if (esAdmin() !== true) {
        return;
    }

    //Carga la fila editada y las opciones de partidos para los selectores.
    $id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    $estadistica = null;
    if ($id !== "") {
        $stmt = $conn->prepare("SELECT * FROM estadisticas WHERE id_estadistica = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $estadistica = $stmt->get_result()->fetch_assoc();
    }
    $datos = $flash["datos"] ?? ($estadistica ?? []);
    $partidos = $conn->query("SELECT p.id_partido, CONCAT(p.fecha, ' - ', l.nombre, ' vs ', v.nombre) AS texto FROM partidos p INNER JOIN club l ON p.id_club_local = l.id_club INNER JOIN club v ON p.id_club_visitante = v.id_club ORDER BY p.fecha DESC")->fetch_all(MYSQLI_ASSOC);
    ?>
    <section id="editor">
        <h2>Editor de estadísticas</h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
        <?php endif; ?>
        <form action="estadisticas.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="<?= $estadistica ? "editar" : "crear" ?>">
            <?php if ($estadistica): ?><input type="hidden" name="id" value="<?= (int) $estadistica["id_estadistica"] ?>"><?php endif; ?>
            <div class="editor-campos">
                <label>Partido
                    <select name="id_partido" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($partidos as $partido): ?>
                            <option value="<?= (int) $partido["id_partido"] ?>"<?= (string) $partido["id_partido"] === (string) ($datos["id_partido"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($partido["texto"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cédula del jugador <input type="text" name="ci_jugador" maxlength="8" inputmode="numeric" value="<?= htmlspecialchars((string) ($datos["ci_jugador"] ?? "")) ?>" required></label>
                <label>Goles <input type="number" name="goles" min="0" max="30" value="<?= htmlspecialchars((string) ($datos["goles"] ?? "0")) ?>" required></label>
                <label>Asistencias <input type="number" name="asistencias" min="0" max="30" value="<?= htmlspecialchars((string) ($datos["asistencias"] ?? "0")) ?>" required></label>
                <label>Amarillas <input type="number" name="amarillas" min="0" max="2" value="<?= htmlspecialchars((string) ($datos["amarillas"] ?? "0")) ?>" required></label>
                <label>Rojas <input type="number" name="rojas" min="0" max="1" value="<?= htmlspecialchars((string) ($datos["rojas"] ?? "0")) ?>" required></label>
            </div>
            <div class="editor-acciones">
                <button type="submit" class="btn-guardar">Guardar estadísticas</button>
                <a class="btn-nuevo" href="estadisticas.php">Nuevo registro</a>
                <?php if ($estadistica): ?>
                    <a class="btn-eliminar" href="estadisticas.php?id=<?= (int) $estadistica["id_estadistica"] ?>&confirmar=1#sel">Eliminar</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if ($estadistica && ($_GET["confirmar"] ?? "") === "1"): ?>
            <form action="estadisticas.php" method="POST">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $estadistica["id_estadistica"] ?>">
                <button type="submit" class="btn-eliminar">Confirmar eliminación</button>
            </form>
        <?php endif; ?>
    </section>
    <?php
}
