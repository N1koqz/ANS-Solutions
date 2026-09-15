<?php
session_start();
require "baseDeDatos.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';

// Prepara la conexión con el select y la ejecuta
$stmt = $conn->prepare("SELECT id_usuario, email, contraseña, rol, club FROM usuario WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $storedPassword = $user['contraseña'];
    $validPassword = password_verify($password, $storedPassword);
    // Compatibilidad temporal con registros antiguos en texto plano: se migran al iniciar sesión.
    if (!$validPassword && hash_equals($storedPassword, $password)) {
        $validPassword = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE usuario SET contraseña = ? WHERE email = ?');
        $update->bind_param('ss', $newHash, $user['email']);
        $update->execute();
        $update->close();
    }
    if ($validPassword) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $user['email'];
        $_SESSION['id_usuario'] = (int) $user['id_usuario'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['club'] = $user['club'];
        // Redirigimos al menú principal en formato PHP
        header('Location: ./menuInicio.php', true, 303);
        exit();
    }
}

$_SESSION['login_error'] = 'Correo o contraseña incorrectos.';
header('Location: ./index.php', true, 303);
exit();
?>
