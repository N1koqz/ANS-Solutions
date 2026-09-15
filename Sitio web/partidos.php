<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); requireRole('admin');
    $local = filter_input(INPUT_POST, 'id_club_local', FILTER_VALIDATE_INT);
    $visitante = filter_input(INPUT_POST, 'id_club_visitante', FILTER_VALIDATE_INT);
    $golesLocal = filter_input(INPUT_POST, 'goles_local', FILTER_VALIDATE_INT);
    $golesVisitante = filter_input(INPUT_POST, 'goles_visitante', FILTER_VALIDATE_INT);
    if (!$local || !$visitante || $local === $visitante || $golesLocal === false || $golesVisitante === false || $golesLocal < 0 || $golesVisitante < 0) exit('Datos del partido inválidos.');
    $stmt = $conn->prepare('INSERT INTO partidos (fecha,estadio,id_club_local,id_club_visitante,goles_local,goles_visitante,velocidad_pelota) VALUES (?,?,?,?,?,?,?)');
    $velocidad = $_POST['velocidad_pelota'] === '' ? null : (float) $_POST['velocidad_pelota'];
    $stmt->bind_param('ssiiiid', $_POST['fecha'], $_POST['estadio'], $local, $visitante, $golesLocal, $golesVisitante, $velocidad);
    $stmt->execute(); audit($conn, 'partidos', $stmt->insert_id, 'INSERT'); $stmt->close();
    header('Location: partidos.php'); exit();
}
$clubs = $conn->query('SELECT id_club,nombre FROM club ORDER BY nombre');
$partidos = $conn->query('SELECT p.*, l.nombre AS local_nombre, v.nombre AS visitante_nombre FROM partidos p JOIN club l ON l.id_club=p.id_club_local JOIN club v ON v.id_club=p.id_club_visitante ORDER BY p.fecha DESC');
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Partidos</title><link href="CSSmenuInicio.css" rel="stylesheet"><style>main{padding:20px}.panel{padding:20px;background:#f4f4f9;max-width:500px}input,select,button{padding:9px;margin:5px 0;width:100%;box-sizing:border-box}button{background:#27ae60;color:#fff;border:0}table{border-collapse:collapse;width:100%;margin-top:25px}th,td{padding:8px;border:1px solid #ddd}th{background:#2c3e50;color:#fff}</style></head><body><header><div class="MGfut"><h2>MG Fut</h2></div></header><main><p><a href="menuInicio.php">Volver al menú</a></p><?php if($esAdmin): ?><div class="panel"><h3>Registrar resultado</h3><form method="post"><?php echo csrfField(); ?><input type="date" name="fecha" required><input name="estadio" placeholder="Estadio" required><select name="id_club_local" required><option value="">Club local</option><?php while($c=$clubs->fetch_assoc()): ?><option value="<?php echo $c['id_club']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endwhile; ?></select><?php $clubs = $conn->query('SELECT id_club,nombre FROM club ORDER BY nombre'); ?><select name="id_club_visitante" required><option value="">Club visitante</option><?php while($c=$clubs->fetch_assoc()): ?><option value="<?php echo $c['id_club']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endwhile; ?></select><input type="number" min="0" name="goles_local" placeholder="Goles local" required><input type="number" min="0" name="goles_visitante" placeholder="Goles visitante" required><input type="number" min="0" step="0.01" name="velocidad_pelota" placeholder="Velocidad de pelota (km/h)"><button>Guardar resultado</button></form></div><?php endif; ?><table><tr><th>Fecha</th><th>Partido</th><th>Estadio</th><th>Resultado</th><th>Velocidad</th></tr><?php while($p=$partidos->fetch_assoc()): ?><tr><td><?php echo $p['fecha']; ?></td><td><?php echo htmlspecialchars($p['local_nombre'].' vs '.$p['visitante_nombre']); ?></td><td><?php echo htmlspecialchars($p['estadio']); ?></td><td><?php echo $p['goles_local'].' - '.$p['goles_visitante']; ?></td><td><?php echo $p['velocidad_pelota'] === null ? 'No registrada' : $p['velocidad_pelota'].' km/h'; ?></td></tr><?php endwhile; ?></table></main></body></html>
