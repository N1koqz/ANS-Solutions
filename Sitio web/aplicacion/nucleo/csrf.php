<?php
//Proteccion CSRF de los formularios que modifican datos

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
