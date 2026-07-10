<?php
    require "baseDeDatos.php";

    $peticion = $conn->query("SELECT * FROM usuarios");

    $correo = $_POST["email"];
    $contrasena = $_POST["password"];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        //verifica si existe un mail registrado
        $user = $result->fetch_assoc();
        if ($password == $user["password"]) {

            // Funciono el login jaja
            header("Location: menuInicio.html");
            exit();
        }
    }

    // Si falla el login
    header("Location: index.html");
    exit();
?>