<?php
require_once __DIR__ . "/../../inicio.php";
require_once __DIR__ . "/../../nucleo/ligas.php";

//Convierte un entero válido o devuelve null si sale del rango.
function enteroEnRango($valor, $min, $max)
{
    $numero = filter_var($valor, FILTER_VALIDATE_INT);
    return $numero !== false && $numero >= $min && $numero <= $max ? $numero : null;
}

//Verifica que el club elegido siga registrado.
function existeClub($conn, $id)
{
    $stmt = $conn->prepare("SELECT 1 FROM club WHERE id_club = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

//Procesa las acciones del editor de partidos y vuelve al formulario.
function procesarPartidos()
{
    global $conn;

    //Solo acepta las acciones del editor.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    $accion = $_POST["accion"] ?? "";
    if (!in_array($accion, ["crear", "concluir", "categoria", "liga"], true)) {
        return;
    }

    requerirAdmin();
    verificarCsrf();

    $campo = fn($nombre) => isset($_POST[$nombre]) && is_string($_POST[$nombre])
        ? trim($_POST[$nombre])
        : "";
    $volver = function ($ok, $texto, $datos = null, $idConcluir = "", $idCategoria = "", $idLiga = "") {
        $_SESSION["flash"] = ["ok" => $ok, "texto" => $texto, "datos" => $datos];
        $ancla = $idConcluir !== "" ? "?concluir=" . urlencode($idConcluir) . "#sel" : "";
        if ($idCategoria !== "") {
            $ancla = "?categoria=" . urlencode($idCategoria) . "#sel-categoria";
        }
        if ($idLiga !== "") {
            $ancla = "?liga=" . urlencode($idLiga) . "#sel-liga";
        }
        header("Location: partidos.php" . $ancla);
        exit();
    };

    try {
        if ($accion === "crear") {
            crearPartido($conn, $campo, $volver);
        } elseif ($accion === "concluir") {
            concluirPartido($conn, $campo, $volver);
        } elseif ($accion === "categoria") {
            asignarCategoriaPartido($conn, $campo, $volver);
        } elseif ($accion === "liga") {
            asignarLigaPartido($conn, $campo, $volver);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("editor/partidos/editor.php: " . $e->getMessage());
        $volver(false, "No se pudo guardar el partido");
    }
}

//Valida clubes, fecha y estadio antes de programar un partido.
function crearPartido($conn, $campo, $volver)
{
    //Valida que ambos clubes existan y que los datos del encuentro sean válidos.
    $datos = [
        "id_club_local" => $campo("id_club_local"),
        "id_club_visitante" => $campo("id_club_visitante"),
        "fecha" => $campo("fecha"),
        "estadio" => $campo("estadio"),
        "id_categoria" => $campo("id_categoria"),
        "liga" => $campo("liga"),
    ];
    $local = enteroEnRango($datos["id_club_local"], 1, PHP_INT_MAX);
    $visitante = enteroEnRango($datos["id_club_visitante"], 1, PHP_INT_MAX);

    if ($local === null || $visitante === null || !existeClub($conn, $local) || !existeClub($conn, $visitante)) {
        $volver(false, "Elegí un club local y un club visitante válidos", $datos);
    }
    if ($local === $visitante) {
        $volver(false, "Un club no puede jugar contra sí mismo", $datos);
    }
    $categoria = enteroEnRango($datos["id_categoria"], 1, PHP_INT_MAX);
    if ($categoria === null || !existeCategoriaPartido($conn, $categoria)) {
        $volver(false, "Elegí una categoría válida", $datos);
    }
    if (!array_key_exists($datos["liga"], ligas_disponibles())) {
        $volver(false, "Elegí una liga válida", $datos);
    }

    $fecha = DateTime::createFromFormat("Y-m-d", $datos["fecha"]);
    if (!$fecha || $fecha->format("Y-m-d") !== $datos["fecha"]) {
        $volver(false, "La fecha no es válida", $datos);
    }
    if ($datos["estadio"] === "" || mb_strlen($datos["estadio"]) > 50) {
        $volver(false, "El estadio es obligatorio (hasta 50 caracteres)", $datos);
    }

    $stmt = $conn->prepare("INSERT INTO partidos (id_club_local, id_club_visitante, fecha, estadio, id_categoria, liga) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissis", $local, $visitante, $datos["fecha"], $datos["estadio"], $categoria, $datos["liga"]);
    $stmt->execute();
    $volver(true, "Partido creado");
}

function existeCategoriaPartido($conn, $id)
{
    $stmt = $conn->prepare("SELECT 1 FROM categorias WHERE id_categoria = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function asignarCategoriaPartido($conn, $campo, $volver)
{
    $id = enteroEnRango($campo("id"), 1, PHP_INT_MAX);
    $categoria = enteroEnRango($campo("id_categoria"), 1, PHP_INT_MAX);
    if ($id === null) {
        $volver(false, "Partido no indicado");
    }
    if ($categoria === null || !existeCategoriaPartido($conn, $categoria)) {
        $volver(false, "Elegí una categoría válida", null, "", (string) $id);
    }
    $stmt = $conn->prepare("SELECT 1 FROM partidos WHERE id_partido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $volver(false, "El partido no existe");
    }
    $stmt = $conn->prepare("UPDATE partidos SET id_categoria = ? WHERE id_partido = ?");
    $stmt->bind_param("ii", $categoria, $id);
    $stmt->execute();
    $volver(true, "Categoría del partido guardada");
}

function asignarLigaPartido($conn, $campo, $volver)
{
    $id = enteroEnRango($campo("id"), 1, PHP_INT_MAX);
    $liga = $campo("liga");
    if ($id === null) {
        $volver(false, "Partido no indicado");
    }
    if (!array_key_exists($liga, ligas_disponibles())) {
        $volver(false, "Elegí una liga válida", null, "", "", (string) $id);
    }
    $stmt = $conn->prepare("SELECT 1 FROM partidos WHERE id_partido = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $volver(false, "El partido no existe");
    }
    $stmt = $conn->prepare("UPDATE partidos SET liga = ? WHERE id_partido = ?");
    $stmt->bind_param("si", $liga, $id);
    $stmt->execute();
    $volver(true, "Liga del partido guardada");
}

//Carga el resultado solo si el partido sigue programado.
function concluirPartido($conn, $campo, $volver)
{
    //Solo carga el resultado si el partido aún no tiene goles.
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
        $volver(false, "Los goles deben ser números enteros entre 0 y 99", $datos, (string) $id);
    }

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

//Muestra el formulario para crear partidos y concluir encuentros programados.
function mostrarFormularioPartidos()
{
    global $conn;

    if (esAdmin() !== true) {
        return;
    }

    //Busca un encuentro programado si se está entrando a cargar su resultado.
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    $datos = $flash["datos"] ?? [];

    $clubes = $conn
        ->query("SELECT id_club AS v, nombre AS t FROM club ORDER BY nombre")
        ->fetch_all(MYSQLI_ASSOC);
    $categorias = $conn
        ->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre DESC")
        ->fetch_all(MYSQLI_ASSOC);
    $ligas = ligas_disponibles();

    $partido = null;
    $idConcluir = enteroEnRango($_GET["concluir"] ?? "", 1, PHP_INT_MAX);
    if ($idConcluir !== null) {
        $stmt = $conn->prepare(
            "SELECT p.id_partido, cl.nombre AS local, cv.nombre AS visitante
            FROM partidos p
            INNER JOIN club cl ON p.id_club_local = cl.id_club
            INNER JOIN club cv ON p.id_club_visitante = cv.id_club
            WHERE p.id_partido = ? AND p.goles_local IS NULL AND p.goles_visitante IS NULL",
        );
        $stmt->bind_param("i", $idConcluir);
        $stmt->execute();
        $partido = $stmt->get_result()->fetch_assoc();
    }
    $partidoCategoria = null;
    $idCategoria = enteroEnRango($_GET["categoria"] ?? "", 1, PHP_INT_MAX);
    if ($idCategoria !== null) {
        $stmt = $conn->prepare(
            "SELECT p.id_partido, p.id_categoria, cl.nombre AS local, cv.nombre AS visitante
             FROM partidos p
             INNER JOIN club cl ON p.id_club_local = cl.id_club
             INNER JOIN club cv ON p.id_club_visitante = cv.id_club
             WHERE p.id_partido = ?"
        );
        $stmt->bind_param("i", $idCategoria);
        $stmt->execute();
        $partidoCategoria = $stmt->get_result()->fetch_assoc();
    }
    $partidoLiga = null;
    $idLiga = enteroEnRango($_GET["liga"] ?? "", 1, PHP_INT_MAX);
    if ($idLiga !== null) {
        $stmt = $conn->prepare(
            "SELECT p.id_partido, p.liga, cl.nombre AS local, cv.nombre AS visitante
             FROM partidos p
             INNER JOIN club cl ON p.id_club_local = cl.id_club
             INNER JOIN club cv ON p.id_club_visitante = cv.id_club
             WHERE p.id_partido = ?"
        );
        $stmt->bind_param("i", $idLiga);
        $stmt->execute();
        $partidoLiga = $stmt->get_result()->fetch_assoc();
    }

    $opciones = function ($seleccionado) use ($clubes) {
        echo "<option value=''>Seleccionar...</option>";
        foreach ($clubes as $club) {
            $selected = (string) $club["v"] === (string) $seleccionado ? " selected" : "";
            echo "<option value='" . htmlspecialchars((string) $club["v"]) . "'$selected>" . htmlspecialchars($club["t"]) . "</option>";
        }
    };
    ?>
    <section id="editor">
        <h2>Administrar partidos</h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status">
                <?= htmlspecialchars($flash["texto"]) ?>
            </p>
        <?php endif; ?>

        <?php if ($partido !== null): ?>
            <form action="partidos.php" method="POST" autocomplete="off" id="sel">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="concluir">
                <input type="hidden" name="id" value="<?= (int) $partido["id_partido"] ?>">
                <h3>Concluir: <?= htmlspecialchars($partido["local"]) ?> vs <?= htmlspecialchars($partido["visitante"]) ?></h3>
                <div class="editor-campos">
                    <label>
                        <span>Goles de <?= htmlspecialchars($partido["local"]) ?> (local)</span>
                        <input type="number" name="goles_local" min="0" max="99" step="1" value="<?= htmlspecialchars($datos["goles_local"] ?? "") ?>" required autofocus>
                    </label>
                    <label>
                        <span>Goles de <?= htmlspecialchars($partido["visitante"]) ?> (visitante)</span>
                        <input type="number" name="goles_visitante" min="0" max="99" step="1" value="<?= htmlspecialchars($datos["goles_visitante"] ?? "") ?>" required>
                    </label>
                </div>
                <div class="editor-acciones">
                    <button type="submit" class="btn-guardar">Guardar resultado</button>
                    <a class="btn-nuevo" href="partidos.php">Cancelar</a>
                </div>
            </form>
            <hr>
        <?php endif; ?>

        <?php if ($partidoCategoria !== null): ?>
            <form action="partidos.php" method="POST" id="sel-categoria">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="categoria">
                <input type="hidden" name="id" value="<?= (int) $partidoCategoria["id_partido"] ?>">
                <h3>Categoría: <?= htmlspecialchars($partidoCategoria["local"]) ?> vs <?= htmlspecialchars($partidoCategoria["visitante"]) ?></h3>
                <div class="editor-campos">
                    <label>Categoría
                        <select name="id_categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria["id_categoria"] ?>"<?= (string) $categoria["id_categoria"] === (string) $partidoCategoria["id_categoria"] ? " selected" : "" ?>><?= htmlspecialchars($categoria["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="editor-acciones">
                    <button type="submit" class="btn-guardar">Guardar categoría</button>
                    <a class="btn-nuevo" href="partidos.php">Cancelar</a>
                </div>
            </form>
            <hr>
        <?php endif; ?>

        <?php if ($partidoLiga !== null): ?>
            <form action="partidos.php" method="POST" id="sel-liga">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="accion" value="liga">
                <input type="hidden" name="id" value="<?= (int) $partidoLiga["id_partido"] ?>">
                <h3>Liga: <?= htmlspecialchars($partidoLiga["local"]) ?> vs <?= htmlspecialchars($partidoLiga["visitante"]) ?></h3>
                <div class="editor-campos">
                    <label>Liga
                        <select name="liga" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($ligas as $valor => $nombre): ?>
                                <option value="<?= htmlspecialchars($valor) ?>"<?= $valor === $partidoLiga["liga"] ? " selected" : "" ?>><?= htmlspecialchars($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="editor-acciones">
                    <button type="submit" class="btn-guardar">Guardar liga</button>
                    <a class="btn-nuevo" href="partidos.php">Cancelar</a>
                </div>
            </form>
            <hr>
        <?php endif; ?>

        <form action="partidos.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="crear">
            <h3>Nuevo partido</h3>
            <div class="editor-campos">
                <label>Liga
                    <select name="liga" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($ligas as $valor => $nombre): ?>
                            <option value="<?= htmlspecialchars($valor) ?>"<?= $valor === ($datos["liga"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Categoría
                    <select name="id_categoria" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria["id_categoria"] ?>"<?= (string) $categoria["id_categoria"] === (string) ($datos["id_categoria"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($categoria["nombre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Club local</span>
                    <select name="id_club_local" required>
                        <?php $opciones($datos["id_club_local"] ?? ""); ?>
                    </select>
                </label>
                <label>
                    <span>Club visitante</span>
                    <select name="id_club_visitante" required>
                        <?php $opciones($datos["id_club_visitante"] ?? ""); ?>
                    </select>
                </label>
                <label>
                    <span>Fecha</span>
                    <input type="date" name="fecha" value="<?= htmlspecialchars($datos["fecha"] ?? "") ?>" required>
                </label>
                <label>
                    <span>Estadio</span>
                    <input type="text" name="estadio" maxlength="50" value="<?= htmlspecialchars($datos["estadio"] ?? "") ?>" required>
                </label>
            </div>
            <div class="editor-acciones">
                <button type="submit" class="btn-guardar">Crear partido</button>
            </div>
        </form>
    </section>
    <?php
}
?>