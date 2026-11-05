<?php

//Quien es el usuario y que puede hacer: sesion, roles y club del usuario

//Devuelve true solo si el rol guardado en la sesion es exactamente "admin"
function esAdmin()
{
    return isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin";
}

//Devuelve true si hay un usuario con sesion iniciada
function estaLogueado()
{
    return isset($_SESSION["rol"]);
}

//Para paginas: redirige al login. Para listados incluidos: responde 401
function requerirLogin($redirigir = false)
{
    if (!estaLogueado()) {
        if ($redirigir == true) {
            header("Location: index.php");
        } else {
            http_response_code(401);
            echo "Sesion requerida";
        }
        exit();
    }
}

//Para usar al inicio de todo archivo que modifique datos o muestre editores
function requerirAdmin()
{
    if (!esAdmin()) {
        http_response_code(403);
        exit("Acceso denegado");
    }
}

//Club al que pertenece el usuario. La columna usuario.club guarda el id del club;
//el JOIN con la tabla club ademas confirma que ese club existe.
//Devuelve null para administradores, sin sesion o usuarios sin club valido asignado
function clubDelUsuario()
{
    global $conn;
    static $club = false;
    if ($club !== false) {
        return $club;
    }
    $club = null;
    if (estaLogueado() && !esAdmin() && isset($_SESSION["email"])) {
        $stmt = $conn->prepare(
            "SELECT c.id_club FROM usuario u INNER JOIN club c ON c.id_club = u.club WHERE u.email = ?",
        );
        $stmt->bind_param("s", $_SESSION["email"]);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        if ($fila && $fila["id_club"] !== null) {
            $club = (int) $fila["id_club"];
        }
    }
    return $club;
}

//Para acciones exclusivas de clubes: corta si el usuario es admin o no tiene club. Devuelve el id del club
function requerirClub()
{
    $id = clubDelUsuario();
    if ($id === null) {
        http_response_code(403);
        exit("Acceso denegado");
    }
    return $id;
}
