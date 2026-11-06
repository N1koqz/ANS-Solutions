<?php
function estilosEditor()
{
    if (esAdmin() === true) {
        ?>
        <link rel="stylesheet" href="recursos/css/CSSeditor.css">
        <?php
    }
}

function mostrarEditor($tipo)
{
    global $conn;

    if (esAdmin() !== true) {
        return;
    }

    $entidad = entidadesEditor()[$tipo];
    $pagina = $entidad["pagina"];
    $pk = $entidad["pk"];
    $campos = $entidad["campos"];
    $busqueda = textoBusqueda();
    $id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";
    $flash = $_SESSION["flash"] ?? null;
    unset($_SESSION["flash"]);

    $columnas = implode(", ", array_unique(array_merge([$pk], array_keys($campos))));
    $consulta = "SELECT $columnas, " . $entidad["etiqueta"] . " AS _etiqueta FROM " . $entidad["tabla"];
    $patron = patronBusqueda();
    $stmt = $conn->prepare(
        "$consulta WHERE " . $entidad["busqueda"] . " LIKE ? ORDER BY _etiqueta LIMIT 300",
    );
    $stmt->bind_param("s", $patron);
    $stmt->execute();
    $registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $seleccionado = null;
    foreach ($registros as $registro) {
        if ((string) $registro[$pk] === $id) {
            $seleccionado = $registro;
            break;
        }
    }
    if ($seleccionado === null && $id !== "") {
        $stmt = $conn->prepare("$consulta WHERE $pk = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $seleccionado = $stmt->get_result()->fetch_assoc();
    }

    $valores = $flash["datos"] ?? ($seleccionado ?? []);
    $confirmando = $seleccionado !== null && ($_GET["confirmar"] ?? "") === "1";

    $url = function ($idRegistro = "", $confirmar = false) use ($pagina, $busqueda) {
        $parametros = [];
        if ($busqueda !== "") {
            $parametros["busqueda"] = $busqueda;
        }
        if ($idRegistro !== "") {
            $parametros["id"] = $idRegistro;
        }
        if ($confirmar === true) {
            $parametros["confirmar"] = 1;
        }
        return htmlspecialchars($pagina . ($parametros ? "?" . http_build_query($parametros) : ""));
    };

    $ocultos = function ($accion, $idRegistro = "") use ($busqueda) {
        echo "<input type='hidden' name='csrf' value='" . tokenCsrf() . "'>";
        echo "<input type='hidden' name='accion' value='" . $accion . "'>";
        echo "<input type='hidden' name='busqueda' value='" . htmlspecialchars($busqueda) . "'>";
        if ($idRegistro !== "") {
            echo "<input type='hidden' name='id' value='" . htmlspecialchars($idRegistro) . "'>";
        }
    };
    ?>
    <section id="editor">
        <h2>Editor de <?= htmlspecialchars($entidad["titulo"]) ?></h2>
        <?php if ($flash !== null): ?>
            <p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status">
                <?= htmlspecialchars($flash["texto"]) ?>
            </p>
        <?php endif; ?>

        <?php if ($confirmando === true): ?>
            <form action="<?= htmlspecialchars($pagina) ?>" method="POST" class="confirmar">
                <?php $ocultos("eliminar", (string) $seleccionado[$pk]); ?>
                <p>¿Eliminar <strong><?= htmlspecialchars($seleccionado["_etiqueta"]) ?></strong>? Esta acción no se puede deshacer.</p>
                <button type="submit" class="btn-eliminar">Sí, eliminar</button>
                <a class="btn-nuevo" href="<?= $url((string) $seleccionado[$pk]) ?>#sel">Cancelar</a>
            </form>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($pagina) ?>" method="POST" autocomplete="off">
            <?php $ocultos($seleccionado ? "editar" : "crear", $seleccionado ? (string) $seleccionado[$pk] : ""); ?>
            <h3><?= $seleccionado ? "Editando: " . htmlspecialchars($seleccionado["_etiqueta"]) : "Nuevo registro" ?></h3>
            <div class="editor-campos">
                <?php foreach ($campos as $nombre => $campo): ?>
                    <?php $actual = (string) ($valores[$nombre] ?? ""); ?>
                    <label>
                        <span><?= htmlspecialchars($campo["etiqueta"]) ?></span>
                        <?php if ($campo["tipo"] === "select"): ?>
                            <select name="<?= $nombre ?>" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach (opcionesDe($conn, $campo) as $opcion): ?>
                                    <option value="<?= htmlspecialchars($opcion["v"]) ?>"<?= (string) $opcion["v"] === $actual ? " selected" : "" ?>>
                                        <?= htmlspecialchars($opcion["t"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($campo["tipo"] === "textarea"): ?>
                            <textarea name="<?= $nombre ?>" maxlength="<?= $campo["max"] ?>" rows="3" required><?= htmlspecialchars($actual) ?></textarea>
                        <?php else: ?>
                            <input type="<?= $campo["tipo"] ?>" name="<?= $nombre ?>" value="<?= htmlspecialchars($actual) ?>" <?= atributosCampo($campo, $nombre, $pk, $seleccionado) ?> required>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>

                <?php if ($tipo === "clubes" && !$seleccionado): ?>
                    <label>
                        <span>Contraseña de acceso del club</span>
                        <input type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </label>
                <?php endif; ?>
            </div>

            <div class="editor-acciones">
                <button type="submit" class="btn-guardar">Guardar cambios</button>
                <a class="btn-nuevo" href="<?= $url() ?>">Nuevo registro</a>
                <?php if ($seleccionado): ?>
                    <a class="btn-eliminar" href="<?= $url((string) $seleccionado[$pk], true) ?>#sel">Eliminar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($tipo === "clubes" && $seleccionado): ?>
            <form action="<?= htmlspecialchars($pagina) ?>" method="POST" autocomplete="off">
                <?php $ocultos("resetear", (string) $seleccionado[$pk]); ?>
                <h3>Contraseña de acceso</h3>
                <div class="editor-campos">
                    <label>
                        <span>Nueva contraseña</span>
                        <input type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </label>
                </div>
                <div class="editor-acciones">
                    <button type="submit" class="btn-guardar">Resetear contraseña</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
    <?php
}

function atributosCampo($campo, $nombre, $pk, $seleccionado)
{
    $atributos = "";
    if ($campo["tipo"] === "text") {
        $atributos = "maxlength='" . $campo["max"] . "'";
        if (($campo["validacion"] ?? "") === "ci") {
            $atributos .= " inputmode='numeric' pattern='[0-9]{7,8}' title='Ingresá una cédula uruguaya válida, solo con números'";
        }
    } elseif ($campo["tipo"] === "number") {
        $atributos = "min='" . $campo["min"] . "' max='" . $campo["max"] . "' step='" . ($campo["paso"] ?? "any") . "'";
    }

    if ($nombre === $pk && $seleccionado) {
        $atributos .= " readonly";
    }

    return $atributos;
}