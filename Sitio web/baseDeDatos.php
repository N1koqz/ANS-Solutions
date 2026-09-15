<?php

$servername = getenv('DB_HOST') ?: "127.0.0.1";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";
$dbname = getenv('DB_NAME') ?: "mgfut";
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
  http_response_code(500);
  exit("No se pudo conectar a la base de datos.");
}

$conn->set_charset('utf8mb4');

function requireAuthentication(): void {
    if (empty($_SESSION['usuario'])) {
        header('Location: index.php');
        exit();
    }
}

function requireRole(string ...$roles): void {
    requireAuthentication();
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('No tienes permisos para realizar esta operación.');
    }
}

function currentUserId(): int {
    return (int) ($_SESSION['id_usuario'] ?? 0);
}

function audit(mysqli $conn, string $tabla, int $registro, string $accion): void {
    $stmt = $conn->prepare('INSERT INTO auditoria (id_usuario, tabla_afectada, id_registro, accion) VALUES (?, ?, ?, ?)');
    $userId = currentUserId();
    $stmt->bind_param('isis', $userId, $tabla, $registro, $accion);
    $stmt->execute();
    $stmt->close();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
}
?>
