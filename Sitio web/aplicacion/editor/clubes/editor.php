<?php
require_once __DIR__ . "/../editor.php";

registrarEntidadEditor("clubes", [
	"pagina" => "clubes.php",
	"titulo" => "clubes",
	"tabla" => "club",
	"pk" => "id_club",
	"auto" => true,
	"etiqueta" => "nombre",
	"busqueda" => "CONCAT_WS(' ', nombre, ciudad, fundacion)",
	"campos" => [
		"nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
		"ciudad" => ["etiqueta" => "Ciudad", "tipo" => "text", "max" => 50],
		"fundacion" => ["etiqueta" => "Año de Fundación", "tipo" => "number", "min" => 1800, "max" => 2100, "paso" => 1, "entero" => true],
	],
]);

function procesarCategoriasClubes()
{
	if (
		$_SERVER["REQUEST_METHOD"] !== "POST" ||
		($_POST["accion"] ?? "") !== "crear_categoria"
	) {
		return;
	}

	requerirAdmin();
	verificarCsrf();
	global $conn;

	$nombre = isset($_POST["nombre_categoria"]) && is_string($_POST["nombre_categoria"])
		? trim($_POST["nombre_categoria"])
		: "";
	$anioActual = (int) date("Y");
	if (!ctype_digit($nombre) || strlen($nombre) !== 4 || (int) $nombre < 1900 || (int) $nombre > $anioActual) {
		$_SESSION["flash"] = ["ok" => false, "texto" => "Ingresá un año de nacimiento de cuatro cifras entre 1900 y $anioActual"];
		header("Location: clubes.php");
		exit();
	}

	$stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
	$stmt->bind_param("s", $nombre);
	$stmt->execute();
	$_SESSION["flash"] = ["ok" => true, "texto" => "Categoría creada"];
	header("Location: clubes.php");
	exit();
}

function mostrarCategoriasClubes()
{
	if (esAdmin() !== true) {
		return;
	}

	global $conn;
	$categorias = $conn->query("SELECT nombre FROM categorias ORDER BY nombre DESC")->fetch_all(MYSQLI_ASSOC);
	?>
	<section class="editor-categorias">
		<h2>Categorías por año de nacimiento</h2>
		<form action="clubes.php" method="POST" autocomplete="off">
			<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
			<input type="hidden" name="accion" value="crear_categoria">
			<label>
				Año de nacimiento (4 cifras)
				<input type="number" name="nombre_categoria" min="1900" max="<?= date("Y") ?>" step="1" placeholder="2011" required>
			</label>
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