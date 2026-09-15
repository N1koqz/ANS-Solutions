<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    requireRole('admin');
    $ci = filter_input(INPUT_POST, 'ci', FILTER_VALIDATE_INT);
    $dorsal = filter_input(INPUT_POST, 'dorsal', FILTER_VALIDATE_INT);
    $idClub = filter_input(INPUT_POST, 'id_club', FILTER_VALIDATE_INT);
    $idCategoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    if (!$ci || !$dorsal || !$idClub || !$idCategoria) exit('Datos del jugador inválidos.');
    if (!empty($_POST['ci_editar'])) {
        $stmt = $conn->prepare('UPDATE jugadores SET nombre=?, apellido=?, fecha_nacimiento=?, posicion=?, dorsal=?, id_club=?, id_categoria=?, masa=?, altura=?, fuerza_peso=? WHERE ci=?');
        $stmt->bind_param('ssssiiiiiii', $_POST['nombre'], $_POST['apellido'], $_POST['fecha_nacimiento'], $_POST['posicion'], $dorsal, $idClub, $idCategoria, $_POST['masa'], $_POST['altura'], $_POST['fuerza_peso'], $_POST['ci_editar']);
        $stmt->execute();
        audit($conn, 'jugadores', (int) $_POST['ci_editar'], 'UPDATE');
    } else {
        $stmt = $conn->prepare('INSERT INTO jugadores (ci,nombre,apellido,fecha_nacimiento,posicion,dorsal,id_club,id_categoria,masa,altura,fuerza_peso) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('issssiiiiii', $ci, $_POST['nombre'], $_POST['apellido'], $_POST['fecha_nacimiento'], $_POST['posicion'], $dorsal, $idClub, $idCategoria, $_POST['masa'], $_POST['altura'], $_POST['fuerza_peso']);
        $stmt->execute();
        audit($conn, 'jugadores', $ci, 'INSERT');
    }
    $stmt->close();
    header('Location: jugadores.php');
    exit();
}
$clubs = $conn->query('SELECT id_club, nombre FROM club ORDER BY nombre');
$categorias = $conn->query('SELECT id_categoria, nombre FROM categorias ORDER BY id_categoria');
$jugadores = $conn->query('SELECT j.*, c.nombre AS club_nombre, ca.nombre AS categoria_nombre FROM jugadores j JOIN club c ON c.id_club=j.id_club JOIN categorias ca ON ca.id_categoria=j.id_categoria ORDER BY j.apellido, j.nombre');
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Jugadores | MG Fut</title><link href="CSSmenuInicio.css" rel="stylesheet"><style>main{padding:20px}.crud-container{display:flex;gap:30px;flex-wrap:wrap}.form-crud{padding:20px;background:#f4f4f9;width:300px}.form-crud input,.form-crud select{width:100%;padding:8px;margin:5px 0;box-sizing:border-box}.form-crud button{width:100%;padding:10px;background:#27ae60;color:#fff;border:0}.tabla-container{flex:1;overflow:auto}table{border-collapse:collapse;width:100%;background:#fff}th,td{padding:8px;border:1px solid #ddd;text-align:left}th{background:#2c3e50;color:#fff}</style></head>
<body><header><div class="MGfut"><h2>MG Fut</h2></div></header><main><div class="botonesMenu"><button class="btnClubes"><a href="clubes.php">Clubes</a></button><button class="btnJugadores"><a href="jugadores.php">Jugadores</a></button><button class="btnFixture"><a href="fixture.php">Fixture</a></button><button class="btnEstadisticas"><a href="estadisticas.php">Estadísticas</a></button><button><a href="carnets.php">Carnets</a></button><button><a href="sanciones.php">Sanciones</a></button></div>
<div class="crud-container"><?php if ($esAdmin): ?><form class="form-crud" method="post"><?php echo csrfField(); ?><h3>Registrar jugador</h3><input name="ci" type="number" placeholder="Cédula" required><input name="nombre" placeholder="Nombre" required><input name="apellido" placeholder="Apellido" required><input name="fecha_nacimiento" type="date" required><input name="posicion" placeholder="Posición" required><input name="dorsal" type="number" min="1" max="99" placeholder="Dorsal" required><select name="id_club" required><option value="">Club</option><?php while($c=$clubs->fetch_assoc()): ?><option value="<?php echo $c['id_club']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endwhile; ?></select><select name="id_categoria" required><option value="">Categoría</option><?php while($c=$categorias->fetch_assoc()): ?><option value="<?php echo $c['id_categoria']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endwhile; ?></select><input name="masa" type="number" placeholder="Masa (kg)"><input name="altura" type="number" placeholder="Altura (cm)"><input name="fuerza_peso" type="number" placeholder="Fuerza/peso"><button>Guardar jugador</button></form><?php else: ?><p>Solo el administrador puede registrar o editar jugadores.</p><?php endif; ?>
<div class="tabla-container"><table><tr><th>CI</th><th>Jugador</th><th>Fecha nacimiento</th><th>Posición</th><th>Dorsal</th><th>Club</th><th>Categoría</th></tr><?php while($j=$jugadores->fetch_assoc()): ?><tr><td><?php echo (int)$j['ci']; ?></td><td><?php echo htmlspecialchars($j['nombre'].' '.$j['apellido']); ?></td><td><?php echo htmlspecialchars($j['fecha_nacimiento']); ?></td><td><?php echo htmlspecialchars($j['posicion']); ?></td><td><?php echo (int)$j['dorsal']; ?></td><td><?php echo htmlspecialchars($j['club_nombre']); ?></td><td><?php echo htmlspecialchars($j['categoria_nombre']); ?></td></tr><?php endwhile; ?></table></div></div></main></body></html>
