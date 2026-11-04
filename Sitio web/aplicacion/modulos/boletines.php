<?php
require_once __DIR__ . "/../inicio.php";

//Guarda y elimina boletines. Solo el administrador puede modificar publicaciones.
function procesarBoletines() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["boletin_accion"])) {
        return;
    }

    requerirAdmin();
    verificarCsrf();
    global $conn;

    $accion = is_string($_POST["boletin_accion"]) ? $_POST["boletin_accion"] : "";
    try {
        if ($accion === "eliminar") {
            $id = filter_var($_POST["id_boletin"] ?? "", FILTER_VALIDATE_INT);
            if ($id === false) {
                throw new InvalidArgumentException("Boletín no válido");
            }
            $stmt = $conn->prepare("DELETE FROM boletines WHERE id_boletin = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION["boletin_flash"] = ["ok" => true, "texto" => "Boletín eliminado"];
        } elseif ($accion === "crear") {
            $titulo = isset($_POST["titulo"]) && is_string($_POST["titulo"]) ? trim($_POST["titulo"]) : "";
            $contenido = isset($_POST["contenido"]) && is_string($_POST["contenido"]) ? trim($_POST["contenido"]) : "";
            $audiencia = isset($_POST["audiencia"]) && is_string($_POST["audiencia"]) ? $_POST["audiencia"] : "";

            if ($titulo === "" || mb_strlen($titulo) > 120 || $contenido === "" || mb_strlen($contenido) > 10000) {
                throw new InvalidArgumentException("Ingresá título y contenido (máximo 120 y 10.000 caracteres)");
            }
            if (!in_array($audiencia, ["clubes", "prensa", "ambos"], true)) {
                throw new InvalidArgumentException("Seleccioná una audiencia válida");
            }

            $stmt = $conn->prepare("INSERT INTO boletines (titulo, contenido, audiencia) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $titulo, $contenido, $audiencia);
            $stmt->execute();
            $_SESSION["boletin_flash"] = ["ok" => true, "texto" => "Boletín publicado"];
        } else {
            throw new InvalidArgumentException("Acción no válida");
        }
    } catch (InvalidArgumentException $e) {
        $_SESSION["boletin_flash"] = ["ok" => false, "texto" => $e->getMessage()];
    } catch (mysqli_sql_exception $e) {
        error_log("boletines.php: " . $e->getMessage());
        $_SESSION["boletin_flash"] = ["ok" => false, "texto" => "No se pudo guardar el boletín"];
    }

    header("Location: menuInicio.php#noticias");
    exit();
}

//Muestra boletines destinados a una audiencia; "todos" se reserva para el panel administrador.
function mostrarBoletines($audiencia, $administrar = false) {
    global $conn;

    if ($administrar === true && esAdmin() !== true) {
        return;
    }

    if ($audiencia === "todos") {
        $result = $conn->query("SELECT id_boletin, titulo, contenido, audiencia, creado_en FROM boletines ORDER BY creado_en DESC, id_boletin DESC");
    } else {
        $stmt = $conn->prepare("SELECT id_boletin, titulo, contenido, audiencia, creado_en FROM boletines WHERE audiencia = ? OR audiencia = 'ambos' ORDER BY creado_en DESC, id_boletin DESC");
        $stmt->bind_param("s", $audiencia);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $boletines = $result->fetch_all(MYSQLI_ASSOC);
    $flash = $administrar === true ? ($_SESSION["boletin_flash"] ?? null) : null;
    if ($administrar === true) {
        unset($_SESSION["boletin_flash"]);
    }
    $tituloSeccion = $audiencia === "prensa" && $administrar !== true ? "Boletines de prensa" : "Noticias y boletines";
?>
		<section class="seccion-boletines" id="noticias">
			<h2><?= htmlspecialchars($tituloSeccion) ?></h2>
<?php if ($flash !== null): ?>
			<p class="boletin-mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
<?php endif; ?>
<?php if ($administrar === true): ?>
			<form action="menuInicio.php" method="POST" class="formulario-boletin">
				<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
				<input type="hidden" name="boletin_accion" value="crear">
				<label>Título <input type="text" name="titulo" maxlength="120" required></label>
				<label>Contenido <textarea name="contenido" maxlength="10000" rows="5" required></textarea></label>
				<label>Destinatarios
					<select name="audiencia" required>
						<option value="clubes">Clubes</option>
						<option value="prensa">Prensa</option>
						<option value="ambos">Clubes y prensa</option>
					</select>
				</label>
				<button type="submit">Publicar boletín</button>
			</form>
<?php endif; ?>
<?php if (count($boletines) === 0): ?>
			<p class="boletin-vacio">No hay boletines publicados.</p>
<?php endif; ?>
<?php foreach ($boletines as $boletin): ?>
			<article class="boletin">
				<div class="boletin-cabecera">
					<h3><?= htmlspecialchars($boletin["titulo"]) ?></h3>
					<time datetime="<?= htmlspecialchars($boletin["creado_en"]) ?>"><?= htmlspecialchars(date("d/m/Y", strtotime($boletin["creado_en"]))) ?></time>
				</div>
				<p><?= nl2br(htmlspecialchars($boletin["contenido"])) ?></p>
				<?php if ($administrar === true): ?>
					<form action="menuInicio.php" method="POST" class="formulario-eliminar-boletin">
						<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
						<input type="hidden" name="boletin_accion" value="eliminar">
						<input type="hidden" name="id_boletin" value="<?= (int)$boletin["id_boletin"] ?>">
						<button type="submit" aria-label="Eliminar boletín">Eliminar</button>
					</form>
				<?php endif; ?>
			</article>
<?php endforeach; ?>
		</section>
<?php
}
?>