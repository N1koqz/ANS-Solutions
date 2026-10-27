<?php
//Conexion a la base de datos, sesion y cabeceras. No define funciones de pagina
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
