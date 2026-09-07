<?php
    require "baseDeDatos.php";
    //carga la tabla de la bd
    $peticion = $conn->query("SELECT * FROM usuario");
    
    //establece los datos en POST (Asignar despuos)
    $email = $_POST["email"];
    $password = $_POST["password"];

    //prepara la conexion con el select y la ejecuta
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    //asigna a una variable el resultado
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        //verifica si existe una contraseña asignada a ese user
        $user = $result->fetch_assoc();
        if ($password == $user["contraseña"]) {

            // Funciono el login jaja
            header("Location: menuInicio.html");
            exit();
        }
    }

    // Si falla el login
    header("Location: index.html");
    exit();
?>