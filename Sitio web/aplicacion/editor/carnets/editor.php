<?php
require_once __DIR__ . "/../../modulos/carnets/procesar_carnets.php";

//Muestra el formulario para subir carnets a jugadores del club actual.
function mostrarFormularioCarnets()
{
    global $conn;

    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);

    if (esAdmin()) {
        return;
    }

    $datos = $flash["datos"] ?? [];
    $idClub = clubDelUsuario();
    $jugadores = [];
    if ($idClub !== null) {
        $stmt = $conn->prepare(
            "SELECT ci, CONCAT(ci, ' - ', nombre, ' ', apellido) AS t FROM jugadores WHERE id_club = ? ORDER BY apellido, nombre",
        );
        $stmt->bind_param("i", $idClub);
        $stmt->execute();
        $jugadores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    ?>
    <section id="editor">
        <h2>Subir carnet de salud</h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status">
                <?= htmlspecialchars($flash["texto"]) ?>
            </p>
        <?php endif; ?>

        <?php if ($idClub === null): ?>
            <p class="mensaje error">Tu usuario no tiene un club asignado. Pedile al administrador que lo configure.</p>
        <?php elseif (count($jugadores) === 0): ?>
            <p class="mensaje error">Tu club todavía no tiene jugadores registrados.</p>
        <?php else: ?>
            <form action="carnets.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_CARNET ?>">
                <div class="editor-campos">
                    <label>
                        <span>Jugador</span>
                        <select name="ci" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($jugadores as $jugador): ?>
                                <option value="<?= htmlspecialchars((string) $jugador["ci"]) ?>"<?= (string) $jugador["ci"] === ($datos["ci"] ?? "") ? " selected" : "" ?>>
                                    <?= htmlspecialchars($jugador["t"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Imagen del carnet (JPG, PNG o WEBP, máx. 5 MB)</span>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                    <label>
                        <span>Inicio de validez</span>
                        <input type="date" name="fecha_inicio" min="<?= date("Y-m-d") ?>" value="<?= htmlspecialchars($datos["fecha_inicio"] ?? "") ?>" required>
                    </label>
                    <label>
                        <span>Fecha de vencimiento</span>
                        <input type="date" name="fecha_vencimiento" value="<?= htmlspecialchars($datos["fecha_vencimiento"] ?? "") ?>" required>
                    </label>
                </div>
                <div class="editor-acciones">
                    <button type="submit" class="btn-guardar">Subir carnet</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
    <?php
}
?>