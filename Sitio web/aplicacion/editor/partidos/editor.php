<?php
require_once __DIR__ . "/../../modulos/partidos/procesar_partidos.php";

//Muestra el formulario para crear partidos y concluir encuentros programados.
function mostrarFormularioPartidos()
{
    global $conn;

    if (esAdmin() !== true) {
        return;
    }

    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);
    $datos = $flash["datos"] ?? [];

    $clubes = $conn
        ->query("SELECT id_club AS v, nombre AS t FROM club ORDER BY nombre")
        ->fetch_all(MYSQLI_ASSOC);

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

        <form action="partidos.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
            <input type="hidden" name="accion" value="crear">
            <h3>Nuevo partido</h3>
            <div class="editor-campos">
                <label>
                    <span>Club local</span>
                    <select name="id_club_local" required><?= $opciones($datos["id_club_local"] ?? "") ?></select>
                </label>
                <label>
                    <span>Club visitante</span>
                    <select name="id_club_visitante" required><?= $opciones($datos["id_club_visitante"] ?? "") ?></select>
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