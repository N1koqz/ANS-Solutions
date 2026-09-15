<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sanciones';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0750, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); requireRole('admin');
    $ci = filter_input(INPUT_POST, 'ci_jugador', FILTER_VALIDATE_INT);
    if (!$ci || empty($_POST['motivo']) || empty($_POST['fecha_inicio']) || empty($_POST['fecha_fin'])) exit('Datos de sanción inválidos.');
    $archivo = null;
    if (!empty($_FILES['boletin']['name'])) {
        $file = $_FILES['boletin'];
        $mime = $file['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
        if ($mime !== 'application/pdf' || $file['size'] > 5 * 1024 * 1024) exit('El boletín debe ser un PDF de hasta 5 MB.');
        $archivo = bin2hex(random_bytes(16)) . '.pdf';
        move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $archivo);
    }
    $stmt = $conn->prepare('INSERT INTO sanciones (ci_jugador,motivo,fecha_inicio,fecha_fin,boletin_pdf,creada_por) VALUES (?,?,?,?,?,?)');
    $userId = currentUserId();
    $stmt->bind_param('issssi', $ci, $_POST['motivo'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $archivo, $userId);
    $stmt->execute(); audit($conn, 'sanciones', $stmt->insert_id, 'INSERT'); $stmt->close();
    header('Location: sanciones.php'); exit();
}
$jugadores = $conn->query('SELECT ci, CONCAT(nombre, " ", apellido) AS jugador FROM jugadores ORDER BY apellido, nombre');
$sanciones = $conn->query('SELECT s.*, CONCAT(j.nombre, " ", j.apellido) AS jugador FROM sanciones s JOIN jugadores j ON j.ci=s.ci_jugador ORDER BY s.fecha_fin DESC');
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sanciones</title><link href="CSSmenuInicio.css" rel="stylesheet"><style>main{padding:20px}.panel{padding:20px;background:#f4f4f9;max-width:500px}input,select,textarea,button{padding:9px;margin:5px 0;width:100%;box-sizing:border-box}button{background:#c0392b;color:#fff;border:0}table{border-collapse:collapse;width:100%;margin-top:25px}th,td{padding:8px;border:1px solid #ddd}th{background:#2c3e50;color:#fff}.vigente{color:#b42318;font-weight:bold}</style></head><body><header><div class="MGfut"><h2>MG Fut</h2></div></header><main><p><a href="menuInicio.php">Volver al menú</a></p><?php if($esAdmin): ?><div class="panel"><h3>Registrar sanción y boletín</h3><form method="post" enctype="multipart/form-data"><?php echo csrfField(); ?><select name="ci_jugador" required><option value="">Jugador</option><?php while($j=$jugadores->fetch_assoc()): ?><option value="<?php echo $j['ci']; ?>"><?php echo htmlspecialchars($j['jugador']); ?></option><?php endwhile; ?></select><textarea name="motivo" placeholder="Motivo" required></textarea><label>Desde</label><input type="date" name="fecha_inicio" required><label>Hasta</label><input type="date" name="fecha_fin" required><input type="file" name="boletin" accept="application/pdf"><button>Registrar sanción</button></form></div><?php endif; ?><table><tr><th>Jugador</th><th>Motivo</th><th>Desde</th><th>Hasta</th><th>Estado</th><th>Boletín</th></tr><?php while($s=$sanciones->fetch_assoc()): $vigente=$s['fecha_inicio']<=date('Y-m-d')&&$s['fecha_fin']>=date('Y-m-d'); ?><tr><td><?php echo htmlspecialchars($s['jugador']); ?></td><td><?php echo htmlspecialchars($s['motivo']); ?></td><td><?php echo $s['fecha_inicio']; ?></td><td><?php echo $s['fecha_fin']; ?></td><td class="<?php echo $vigente?'vigente':''; ?>"><?php echo $vigente?'Vigente':'Finalizada'; ?></td><td><?php if($s['boletin_pdf']): ?><a href="uploads/sanciones/<?php echo rawurlencode($s['boletin_pdf']); ?>" target="_blank">Ver PDF</a><?php endif; ?></td></tr><?php endwhile; ?></table></main></body></html>
