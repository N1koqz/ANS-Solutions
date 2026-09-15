<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'carnets';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0750, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $ci = filter_input(INPUT_POST, 'ci_jugador', FILTER_VALIDATE_INT);
    $vencimiento = $_POST['fecha_vencimiento'] ?? '';
    if (!$ci || !DateTime::createFromFormat('Y-m-d', $vencimiento) || empty($_FILES['carnet'])) exit('Datos del carnet inválidos.');
    $file = $_FILES['carnet'];
    $mime = $file['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
    if ($mime !== 'application/pdf' || $file['size'] > 5 * 1024 * 1024) exit('El carnet debe ser un PDF de hasta 5 MB.');
    $name = bin2hex(random_bytes(16)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $name)) exit('No se pudo guardar el carnet.');
    $stmt = $conn->prepare('INSERT INTO carnet_salud (ci_jugador, archivo, nombre_original, fecha_vencimiento, subido_por) VALUES (?,?,?,?,?)');
    $userId = currentUserId();
    $stmt->bind_param('isssi', $ci, $name, $file['name'], $vencimiento, $userId);
    $stmt->execute();
    audit($conn, 'carnet_salud', $stmt->insert_id, 'UPLOAD');
    $stmt->close();
    header('Location: carnets.php'); exit();
}
$jugadores = $conn->query("SELECT j.ci, CONCAT(j.nombre, ' ', j.apellido) AS jugador FROM jugadores j JOIN club c ON c.id_club=j.id_club WHERE '" . $conn->real_escape_string($_SESSION['rol'] === 'admin' ? '' : ($_SESSION['club'] ?? '')) . "' = '' OR c.nombre='" . $conn->real_escape_string($_SESSION['club'] ?? '') . "' ORDER BY jugador");
$carnets = $conn->query('SELECT cs.*, CONCAT(j.nombre, " ", j.apellido) AS jugador FROM carnet_salud cs JOIN jugadores j ON j.ci=cs.ci_jugador ORDER BY cs.fecha_subida DESC');
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Carnets de salud</title><link href="CSSmenuInicio.css" rel="stylesheet"><style>main{padding:20px}.panel{padding:20px;background:#f4f4f9;max-width:450px}input,select,button{padding:9px;margin:5px 0;width:100%;box-sizing:border-box}button{background:#27ae60;color:#fff;border:0}table{border-collapse:collapse;width:100%;margin-top:25px}th,td{padding:8px;border:1px solid #ddd}th{background:#2c3e50;color:#fff}.vencido{color:#b42318;font-weight:bold}</style></head><body><header><div class="MGfut"><h2>MG Fut</h2></div></header><main><p><a href="menuInicio.php">Volver al menú</a></p><div class="panel"><h3>Actualizar carnet de salud</h3><form method="post" enctype="multipart/form-data"><?php echo csrfField(); ?><select name="ci_jugador" required><option value="">Jugador</option><?php while($j=$jugadores->fetch_assoc()): ?><option value="<?php echo $j['ci']; ?>"><?php echo htmlspecialchars($j['jugador']); ?></option><?php endwhile; ?></select><label>Fecha de vencimiento</label><input type="date" name="fecha_vencimiento" required><input type="file" name="carnet" accept="application/pdf" required><button>Subir PDF</button></form></div><table><tr><th>Jugador</th><th>Subido</th><th>Vencimiento</th><th>Estado</th><th>Archivo</th></tr><?php while($c=$carnets->fetch_assoc()): $vigente=$c['fecha_vencimiento'] >= date('Y-m-d'); ?><tr><td><?php echo htmlspecialchars($c['jugador']); ?></td><td><?php echo htmlspecialchars($c['fecha_subida']); ?></td><td><?php echo htmlspecialchars($c['fecha_vencimiento']); ?></td><td class="<?php echo $vigente?'':'vencido'; ?>"><?php echo $vigente?'Vigente':'Vencido'; ?></td><td><a href="uploads/carnets/<?php echo rawurlencode($c['archivo']); ?>" target="_blank">Ver PDF</a></td></tr><?php endwhile; ?></table></main></body></html>
