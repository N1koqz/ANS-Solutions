<?php
    //Logica de carnets de salud: subida (solo usuarios de club) y panel que se muestra en carnets.php
    require_once "baseDeDatos.php";

    //Las imagenes se guardan en una carpeta sin acceso directo desde el navegador; se sirven con carnet_imagen.php.
    const DIR_CARNETS = __DIR__ . "/carnets";
    const MAX_CARNET = 5 * 1024 * 1024; //5 MB
    const TIPOS_CARNET = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

    function fechaValida($valor) {
        $fecha = DateTime::createFromFormat("Y-m-d", $valor);
        return $fecha && $fecha->format("Y-m-d") === $valor;
    }

    //Subir un carnet. Debe llamarse antes de generar HTML (termina con una redireccion)
    function procesarCarnets() {
        global $conn;

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return;
        }

        //Si el archivo supera post_max_size, PHP descarta todo el POST (tambien el token)
        if (empty($_POST) && empty($_FILES) && (int)($_SERVER["CONTENT_LENGTH"] ?? 0) > 0) {
            $_SESSION["flash"] = ["ok" => false, "texto" => "El archivo es demasiado grande (máximo 5 MB)", "datos" => null];
            header("Location: carnets.php");
            exit();
        }

        //Solo usuarios de club con club asignado (los admin reciben 403)
        $idClub = requerirClub();
        verificarCsrf();

        $campo = fn($nombre) => isset($_POST[$nombre]) && is_string($_POST[$nombre]) ? trim($_POST[$nombre]) : "";
        $datos = ["ci" => $campo("ci"), "fecha_inicio" => $campo("fecha_inicio"), "fecha_vencimiento" => $campo("fecha_vencimiento")];

        $volver = function ($ok, $texto, $conDatos = false) use ($datos) {
            $_SESSION["flash"] = ["ok" => $ok, "texto" => $texto, "datos" => $conDatos ? $datos : null];
            header("Location: carnets.php");
            exit();
        };

        //El jugador debe pertenecer al club del usuario (no alcanza con que el desplegable solo muestre los suyos)
        $ci = filter_var($datos["ci"], FILTER_VALIDATE_INT);
        $stmt = $conn->prepare("SELECT 1 FROM jugadores WHERE ci = ? AND id_club = ?");
        $stmt->bind_param("ii", $ci, $idClub);
        $stmt->execute();
        if ($ci === false || $stmt->get_result()->num_rows === 0) {
            $volver(false, "Elegí un jugador de tu club", true);
        }

        if (!fechaValida($datos["fecha_inicio"]) || !fechaValida($datos["fecha_vencimiento"])) {
            $volver(false, "Las fechas no son válidas", true);
        }
        if ($datos["fecha_vencimiento"] <= $datos["fecha_inicio"]) {
            $volver(false, "El vencimiento debe ser posterior al inicio de validez", true);
        }

        //Validacion de la imagen: error de subida, tamaño, tipo real (no el que declara el navegador) y que sea una imagen decodificable
        $archivo = $_FILES["imagen"] ?? null;
        if (!is_array($archivo) || ($archivo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $tamano = in_array($archivo["error"] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            $volver(false, $tamano ? "El archivo es demasiado grande (máximo 5 MB)" : "Seleccioná la imagen del carnet", true);
        }
        if (!is_uploaded_file($archivo["tmp_name"]) || $archivo["size"] > MAX_CARNET) {
            $volver(false, "La imagen no es válida o supera los 5 MB", true);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo["tmp_name"]);
        if (!isset(TIPOS_CARNET[$mime]) || @getimagesize($archivo["tmp_name"]) === false) {
            $volver(false, "El carnet debe ser una imagen JPG, PNG o WEBP", true);
        }

        //Nombre aleatorio con extension decidida por el servidor
        if (!is_dir(DIR_CARNETS) && !mkdir(DIR_CARNETS, 0750, true)) {
            error_log("procesar_carnets.php: no se pudo crear " . DIR_CARNETS);
            $volver(false, "No se pudo guardar la imagen", true);
        }
        $nombreArchivo = bin2hex(random_bytes(16)) . "." . TIPOS_CARNET[$mime];
        if (!move_uploaded_file($archivo["tmp_name"], DIR_CARNETS . "/" . $nombreArchivo)) {
            error_log("procesar_carnets.php: fallo move_uploaded_file");
            $volver(false, "No se pudo guardar la imagen", true);
        }

        try {
            $stmt = $conn->prepare("INSERT INTO carnets_salud (ci, imagen, fecha_inicio, fecha_vencimiento) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $ci, $nombreArchivo, $datos["fecha_inicio"], $datos["fecha_vencimiento"]);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            //Si falla la base de datos no se deja la imagen huerfana
            unlink(DIR_CARNETS . "/" . $nombreArchivo);
            error_log("procesar_carnets.php: " . $e->getMessage());
            $volver(false, "No se pudo registrar el carnet", true);
        }
        $volver(true, "Carnet subido correctamente");
    }

    //Mensaje de la ultima operacion y formulario de subida (el formulario solo se dibuja para usuarios de club)
    function mostrarFormularioCarnets() {
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
            $stmt = $conn->prepare("SELECT ci, CONCAT(ci, ' - ', nombre, ' ', apellido) AS t FROM jugadores WHERE id_club = ? ORDER BY apellido, nombre");
            $stmt->bind_param("i", $idClub);
            $stmt->execute();
            $jugadores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
?>
		<section id="editor">
			<h2>Subir carnet de salud</h2>
<?php if ($flash): ?>
			<p class="mensaje <?= $flash["ok"] ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
<?php endif; ?>

<?php if ($idClub === null): ?>
			<p class="mensaje error">Tu usuario no tiene un club asignado. Pedile al administrador que lo configure.</p>
<?php elseif (!$jugadores): ?>
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
							<option value="<?= htmlspecialchars((string)$jugador["ci"]) ?>"<?= (string)$jugador["ci"] === ($datos["ci"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($jugador["t"]) ?></option>
<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span>Imagen del carnet (JPG, PNG o WEBP, máx. 5 MB)</span>
						<input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required>
					</label>
					<label>
						<span>Inicio de validez</span>
						<input type="date" name="fecha_inicio" value="<?= htmlspecialchars($datos["fecha_inicio"] ?? "") ?>" required>
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
