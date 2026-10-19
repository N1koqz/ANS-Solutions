<?php
date_default_timezone_set("America/Montevideo");

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

//Club al que pertenece el usuario. La columna usuario.club guarda el id del club;
//el JOIN con la tabla club ademas confirma que ese club existe.
//Devuelve null para administradores, sin sesion o usuarios sin club valido asignado
function clubDelUsuario() {
    global $conn;
    static $club = false;
    if ($club !== false) {
        return $club;
    }
    $club = null;
    if (estaLogueado() && !esAdmin() && isset($_SESSION["email"])) {
        $stmt = $conn->prepare("SELECT c.id_club FROM usuario u INNER JOIN club c ON c.id_club = u.club WHERE u.email = ?");
        $stmt->bind_param("s", $_SESSION["email"]);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        if ($fila && $fila["id_club"] !== null) {
            $club = (int)$fila["id_club"];
        }
    }
    return $club;
}

//Para acciones exclusivas de clubes: corta si el usuario es admin o no tiene club. Devuelve el id del club
function requerirClub() {
    $id = clubDelUsuario();
    if ($id === null) {
        http_response_code(403);
        exit("Acceso denegado");
    }
    return $id;
}
