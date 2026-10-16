<?php
    require_once "baseDeDatos.php";

    //Solo se acepta POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: index.php");
        exit();
    }

    //Cerrar sesion (boton del menu, exige token para que no se pueda forzar desde otro sitio)
    if (($_POST["accion"] ?? "") === "salir") {
        verificarCsrf();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), "", time() - 3600, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
        header("Location: index.php");
        exit();
    }

    //Bloqueo temporal tras 5 intentos fallidos
    if (($_SESSION["intentos"] ?? 0) >= 5 && time() - ($_SESSION["ultimo_intento"] ?? 0) < 60) {
        header("Location: index.php?error=2");
        exit();
    }

    //establece los datos en POST
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $acceso = false;

    //prepara la conexion con el select y la ejecuta
    $stmt = $conn->prepare("SELECT email, `contraseña`, rol FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    //asigna a una variable el resultado
    $result = $stmt->get_result();

    if ($result->num_rows == 1 && is_string($password)) {
        $user = $result->fetch_assoc();
        $guardada = $user["contraseña"];
        $esHash = password_get_info($guardada)["algoName"] !== "unknown";

        //Las contraseñas nuevas estan hasheadas, las viejas en texto plano se aceptan una vez y se migran
        $acceso = $esHash ? password_verify($password, $guardada) : hash_equals($guardada, $password);

        if ($acceso && (!$esHash || password_needs_rehash($guardada, PASSWORD_DEFAULT))) {
            $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_hash = $conn->prepare("UPDATE usuario SET `contraseña` = ? WHERE email = ?");
            $stmt_hash->bind_param("ss", $nuevo_hash, $email);
            $stmt_hash->execute();
        }
    }

    if ($acceso) {
        //Evita la fijacion de sesion y guarda el rol que viene de la base de datos
        session_regenerate_id(true);
        unset($_SESSION["intentos"], $_SESSION["ultimo_intento"], $_SESSION["csrf"]);
        $_SESSION['rol'] = $user["rol"];
        $_SESSION["email"] = $user["email"];

        header("Location: menuInicio.php");
        exit();
    }

    // Si falla el login
    $_SESSION["intentos"] = ($_SESSION["intentos"] ?? 0) + 1;
    $_SESSION["ultimo_intento"] = time();
    header("Location: index.php?error=1");
    exit();
?>
