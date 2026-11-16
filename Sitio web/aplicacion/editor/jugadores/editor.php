<?php
require_once __DIR__ . "/../../inicio.php";

const CAMPOS_JUGADOR = [
	"nombre" => "Nombre",
	"apellido" => "Apellido",
	"fecha_nacimiento" => "Fecha de nacimiento",
	"posicion" => "Posición",
];

//Procesa alta, edición y eliminación de jugadores.
function procesarJugadores()
{
	global $conn;

	//Solo acepta acciones de este formulario y exige sesión administrativa.
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
	$busqueda = isset($_POST["busqueda"]) && is_string($_POST["busqueda"]) ? trim($_POST["busqueda"]) : "";
	$volver = function ($ok, $mensaje, $registroId = "", $datos = null) use ($busqueda) {
		$_SESSION["flash"] = ["ok" => $ok, "texto" => $mensaje, "datos" => $datos];
		$parametros = $busqueda === "" ? [] : ["busqueda" => $busqueda];
		if ($registroId !== "") {
			$parametros["id"] = $registroId;
		}
		$query = $parametros ? "?" . http_build_query($parametros) : "";
		header("Location: jugadores.php" . $query . ($registroId !== "" ? "#sel" : ""));
		exit();
	};

	try {
		if ($accion === "eliminar") {
			if ($id === "") {
				$volver(false, "Jugador no indicado");
			}
			$stmt = $conn->prepare("DELETE FROM jugadores WHERE ci = ?");
			$stmt->bind_param("s", $id);
			$stmt->execute();
			if ($stmt->affected_rows === 0) {
				$volver(false, "El jugador no existe");
			}
			$volver(true, "Jugador eliminado");
		}

		//Las reglas del jugador se validan antes del INSERT o UPDATE.
		$datos = leerDatosJugador();
		$error = validarDatosJugador($conn, $datos);
		if ($error !== null) {
			$volver(false, $error, $accion === "editar" ? $id : "", $datos);
		}
		if ($accion === "editar" && $id === "") {
			$volver(false, "Jugador no indicado", "", $datos);
		}

		if ($accion === "crear") {
			$stmt = $conn->prepare("INSERT INTO jugadores (ci, nombre, apellido, fecha_nacimiento, posicion, dorsal, id_club, id_categoria, masa, altura) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssssiiidd", $datos["ci"], $datos["nombre"], $datos["apellido"], $datos["fecha_nacimiento"], $datos["posicion"], $datos["dorsal"], $datos["id_club"], $datos["id_categoria"], $datos["masa"], $datos["altura"]);
			$stmt->execute();
			$volver(true, "Jugador creado", $datos["ci"]);
		}

		$stmt = $conn->prepare("UPDATE jugadores SET nombre = ?, apellido = ?, fecha_nacimiento = ?, posicion = ?, dorsal = ?, id_club = ?, id_categoria = ?, masa = ?, altura = ? WHERE ci = ?");
		$stmt->bind_param("ssssiiidds", $datos["nombre"], $datos["apellido"], $datos["fecha_nacimiento"], $datos["posicion"], $datos["dorsal"], $datos["id_club"], $datos["id_categoria"], $datos["masa"], $datos["altura"], $id);
		$stmt->execute();
		$volver(true, "Cambios guardados", $id);
	} catch (mysqli_sql_exception $e) {
		error_log("editor/jugadores/editor.php: " . $e->getMessage());
		$mensaje = $e->getCode() === 1062 ? "Ya existe un jugador con esa cédula" : "No se pudo guardar el jugador";
		$volver(false, $mensaje, $accion === "editar" ? $id : "", $datos ?? null);
	}
}

//Lee los campos del formulario y normaliza sus espacios.
function leerDatosJugador()
{
	$datos = [];
	foreach (["ci", "nombre", "apellido", "fecha_nacimiento", "posicion", "dorsal", "id_club", "id_categoria", "masa", "altura"] as $campo) {
		$datos[$campo] = isset($_POST[$campo]) && is_string($_POST[$campo]) ? trim($_POST[$campo]) : "";
	}
	return $datos;
}

//Valida datos básicos y comprueba el club y año de nacimiento elegidos.
function validarDatosJugador($conn, $datos)
{
	//Valida identidad y campos básicos del formulario.
	if (cedulaUruguayaValida($datos["ci"]) !== true) {
		return "Ingresá una cédula uruguaya válida, solo con números";
	}
	foreach (CAMPOS_JUGADOR as $campo => $etiqueta) {
		if ($datos[$campo] === "") {
			return $etiqueta . " es obligatorio";
		}
	}
	if (mb_strlen($datos["nombre"]) > 50 || mb_strlen($datos["apellido"]) > 50 || mb_strlen($datos["posicion"]) > 30) {
		return "Revisá la longitud del nombre, apellido o posición";
	}

	$fecha = DateTime::createFromFormat("Y-m-d", $datos["fecha_nacimiento"]);
	if (!$fecha || $fecha->format("Y-m-d") !== $datos["fecha_nacimiento"] || $fecha > new DateTime("today")) {
		return "La fecha de nacimiento no es válida";
	}
	if (!ctype_digit($datos["dorsal"]) || (int) $datos["dorsal"] < 1 || (int) $datos["dorsal"] > 99) {
		return "El número de camiseta debe estar entre 1 y 99";
	}
	foreach (["id_club", "id_categoria"] as $campo) {
		if (!ctype_digit($datos[$campo]) || (int) $datos[$campo] < 1) {
			return "Elegí un club y un año de nacimiento válidos";
		}
	}
	foreach (["masa" => [1, 300], "altura" => [0.5, 250]] as $campo => [$min, $max]) {
		if (!is_numeric($datos[$campo]) || $datos[$campo] < $min || $datos[$campo] > $max) {
			return "Revisá la masa y la altura del jugador";
		}
	}

	//Confirma que las referencias seleccionadas existen en la base.
	$stmt = $conn->prepare("SELECT 1 FROM club WHERE id_club = ?");
	$stmt->bind_param("i", $datos["id_club"]);
	$stmt->execute();
	if ($stmt->get_result()->num_rows === 0) {
		return "El club seleccionado no existe";
	}
	$stmt = $conn->prepare("SELECT nombre FROM categorias WHERE id_categoria = ?");
	$stmt->bind_param("i", $datos["id_categoria"]);
	$stmt->execute();
	$categoria = $stmt->get_result()->fetch_assoc();
	if ($categoria === null) {
		return "El año de nacimiento seleccionado no existe";
	}

	$esFemenina = strcasecmp(trim((string) $categoria["nombre"]), "Femenina") === 0;
	if (!$esFemenina) {
		if (!ctype_digit($categoria["nombre"]) || strlen($categoria["nombre"]) !== 4) {
			return "El año de nacimiento seleccionado no existe";
		}

		//Compara el año del jugador con los años de categoría disponibles.
		$anios = $conn->query("SELECT nombre FROM categorias")->fetch_all(MYSQLI_ASSOC);
		$anios = array_map(fn($fila) => (int) $fila["nombre"], $anios);
		$anio = (int) $fecha->format("Y");
		if ($anio < min($anios) || $anio > max($anios)) {
			return "El año del jugador debe estar dentro de los años de nacimiento registrados";
		}
	}
	return null;
}

//Muestra el formulario de Jugadores y carga las opciones de club/categoría.
function mostrarEditorJugadores()
{
	global $conn;
	if (esAdmin() !== true) {
		return;
	}

	//Recupera el jugador y los valores fallidos para rellenar el formulario.
	$busqueda = textoBusqueda();
	$patron = patronBusqueda();
	$id = isset($_GET["id"]) && is_string($_GET["id"]) ? $_GET["id"] : "";
	$flash = $_SESSION["flash"] ?? null;
	unset($_SESSION["flash"]);
	$seleccionado = null;
	if ($id !== "") {
		$stmt = $conn->prepare("SELECT * FROM jugadores WHERE ci = ?");
		$stmt->bind_param("s", $id);
		$stmt->execute();
		$seleccionado = $stmt->get_result()->fetch_assoc();
	}
	$valores = $flash["datos"] ?? ($seleccionado ?? []);
	$clubes = $conn->query("SELECT id_club, nombre FROM club ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
	$categorias = $conn->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre DESC")->fetch_all(MYSQLI_ASSOC);
	?>
	<section id="editor">
		<h2>Editor de jugadores</h2>
		<?php if ($flash !== null): ?>
			<p class="mensaje <?= $flash["ok"] === true ? "ok" : "error" ?>" role="status"><?= htmlspecialchars($flash["texto"]) ?></p>
		<?php endif; ?>
		<form action="jugadores.php" method="POST" autocomplete="off">
			<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
			<input type="hidden" name="accion" value="<?= $seleccionado ? "editar" : "crear" ?>">
			<input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
			<?php if ($seleccionado): ?><input type="hidden" name="id" value="<?= htmlspecialchars($seleccionado["ci"]) ?>"><?php endif; ?>
			<div class="editor-campos">
				<label>Cédula <input type="text" name="ci" maxlength="8" inputmode="numeric" value="<?= htmlspecialchars((string) ($valores["ci"] ?? "")) ?>" <?= $seleccionado ? "readonly" : "" ?> required></label>
				<label>Nombre <input type="text" name="nombre" maxlength="50" value="<?= htmlspecialchars((string) ($valores["nombre"] ?? "")) ?>" required></label>
				<label>Apellido <input type="text" name="apellido" maxlength="50" value="<?= htmlspecialchars((string) ($valores["apellido"] ?? "")) ?>" required></label>
				<label>Fecha de nacimiento <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars((string) ($valores["fecha_nacimiento"] ?? "")) ?>" required></label>
				<label>Posición <input type="text" name="posicion" maxlength="30" value="<?= htmlspecialchars((string) ($valores["posicion"] ?? "")) ?>" required></label>
				<label>Número de camiseta <input type="number" name="dorsal" min="1" max="99" value="<?= htmlspecialchars((string) ($valores["dorsal"] ?? "")) ?>" required></label>
				<label>Club
					<select name="id_club" required>
						<option value="">Seleccionar...</option>
						<?php foreach ($clubes as $club): ?>
							<option value="<?= (int) $club["id_club"] ?>"<?= (string) $club["id_club"] === (string) ($valores["id_club"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($club["nombre"]) ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Año de nacimiento
					<select name="id_categoria" required>
						<option value="">Seleccionar...</option>
						<?php foreach ($categorias as $categoria): ?>
							<option value="<?= (int) $categoria["id_categoria"] ?>"<?= (string) $categoria["id_categoria"] === (string) ($valores["id_categoria"] ?? "") ? " selected" : "" ?>><?= htmlspecialchars($categoria["nombre"]) ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Masa <input type="number" name="masa" min="1" max="300" step="any" value="<?= htmlspecialchars((string) ($valores["masa"] ?? "")) ?>" required></label>
				<label>Altura <input type="number" name="altura" min="0.5" max="250" step="any" value="<?= htmlspecialchars((string) ($valores["altura"] ?? "")) ?>" required></label>
			</div>
			<div class="editor-acciones">
				<button type="submit" class="btn-guardar">Guardar jugador</button>
				<a class="btn-nuevo" href="jugadores.php">Nuevo jugador</a>
			</div>
		</form>
		<?php if ($seleccionado): ?>
			<form action="jugadores.php" method="POST">
				<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
				<input type="hidden" name="accion" value="eliminar">
				<input type="hidden" name="id" value="<?= htmlspecialchars($seleccionado["ci"]) ?>">
				<button class="btn-eliminar" type="submit">Eliminar jugador</button>
			</form>
		<?php endif; ?>
	</section>
	<?php
}