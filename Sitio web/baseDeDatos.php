<?php
//Se establecen los parametros de la base de datos
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "mgfut";
$port = "3306";

//Los errores de mysqli lanzan excepciones en lugar de mostrarse al usuario
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //Crea la conexion
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    //El detalle va al log del servidor, el cliente no recibe informacion interna
    error_log("La conexion fallo: " . $e->getMessage());
    http_response_code(500);
    exit("Error interno del servidor");
}

//Configura e inicia la sesion una sola vez, antes de generar cualquier contenido
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax",
        "secure" => !empty($_SERVER["HTTPS"])
    ]);
    session_start();
}

//Las paginas con sesion no se guardan en cache
header("Cache-Control: no-store");

//Cierra la sesion tras 30 minutos sin actividad
if (isset($_SESSION["ultima_actividad"]) && time() - $_SESSION["ultima_actividad"] > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION["ultima_actividad"] = time();

//Devuelve true solo si el rol guardado en la sesion es exactamente "admin"
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

//Devuelve true si hay un usuario con sesion iniciada
function estaLogueado() {
    return isset($_SESSION['rol']);
}

//Para paginas: redirige al login. Para listados incluidos: responde 401
function requerirLogin($redirigir = false) {
    if (!estaLogueado()) {
        if ($redirigir) {
            header("Location: index.php");
        } else {
            http_response_code(401);
            echo "Sesion requerida";
        }
        exit();
    }
}

//Para usar al inicio de todo archivo que modifique datos o muestre editores
function requerirAdmin() {
    if (!esAdmin()) {
        http_response_code(403);
        exit("Acceso denegado");
    }
}

//Token anti-CSRF ligado a la sesion
function tokenCsrf() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

//El token llega en el campo "csrf" del formulario
function verificarCsrf() {
    $token = $_POST["csrf"] ?? "";
    if (!is_string($token) || !hash_equals(tokenCsrf(), $token)) {
        http_response_code(403);
        exit("Token invalido");
    }
}

//Texto de busqueda recibido por GET (siempre un string)
function textoBusqueda() {
    return isset($_GET["busqueda"]) && is_string($_GET["busqueda"]) ? trim($_GET["busqueda"]) : "";
}

//Arma el patron LIKE de la busqueda, escapando los comodines del usuario
function patronBusqueda() {
    return "%" . addcslashes(textoBusqueda(), "%_\\") . "%";
}

//Devuelve una celda de tabla con el valor escapado
function celda($valor) {
    return "<td>" . htmlspecialchars((string)$valor) . "</td>";
}

//Columna de acciones, solo se imprime si el usuario es admin
function botonesAdmin($pagina, $id) {
    if (esAdmin()) {
        echo "<td><a href='" . $pagina . "?id=" . urlencode($id) . "#sel'>Editar</a></td>";
    }
}

//Cabecera y menu compartidos por todas las paginas
function menu() {
    $botones = [
        ["btnClubes", "clubes.php", "Clubes"],
        ["btnJugadores", "jugadores.php", "Jugadores"],
        ["btnFixture", "fixture.php", "Fixture"],
        ["btnEstadisticas", "estadisticas.php", "Estadísticas"],
        ["btnSanciones", "sanciones.php", "Sanciones"]
    ];
?>
	<header>
		<div class="MGfut">
			<h2> MG Fut</h2>
			<div class="botonesMenu">
<?php foreach ($botones as [$clase, $archivo, $texto]): ?>
				<button class="<?= $clase ?>">
					<a href="<?= $archivo ?>" style="color: #8BA3C5"><?= $texto ?></a>
				</button>
<?php endforeach; ?>
				<form action="login.php" method="POST" style="display:inline">
					<input type="hidden" name="csrf" value="<?= tokenCsrf() ?>">
					<input type="hidden" name="accion" value="salir">
					<button class="btnCerrarSesion" type="submit" style="color: #8BA3C5">Cerrar sesión</button>
				</form>
			</div>
		</div>
	</header>
<?php
}

//Pie de pagina compartido
function pie() {
?>
	<footer>
		<br>
		<hr>
		<h2 align="center"> ¿Quiénes somos?</h2>
		<div class="grupo_bloques_footer">
			<div class="bloques_footer">
				<p> Somos ANS Solutions, un grupo de desarrollo de software independiente localizado en Rivera, Uruguay.
					Estamos especializados en la creacion de sitios webs y bases de datos para negocios y empresas grandes.
				</p>
			</div>
			<div class="bloques_footer">
				<h3 align="center">Contactos: </h3>
				<ul>
					<li>Email: contacto@ANSsolutions.com</li>
					<li>Teléfono: +598 091-981-722</li>
				</ul>
			</div>
			<div class="bloques_footer">
				<p>© Copyright 2026 ANS Solutions. Todos los derechos reservados. 
					Ninguna parte de este sistema o sitio web será reproducido, de ninguna forma, 
					incluyendo pero no limitando al host de un sitio web no autorizado por ANS Solutions.
				</p>
			</div>
		</div>
		<br>
		<br>
	</footer>
<?php
}
?>
