<?php
session_start();
require "baseDeDatos.php";
requireAuthentication();

$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    requireRole('admin');
}

// --- 1. LÓGICA DE CREAR (INSERT) ---
if (isset($_POST['guardar']) && empty($_POST['id_editar'])) {
    $stmt = $conn->prepare("INSERT INTO club (nombre, ciudad, fundacion) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $_POST['nombre'], $_POST['ciudad'], $_POST['fundacion']);
    $stmt->execute();
    audit($conn, 'club', $stmt->insert_id, 'INSERT');
    $stmt->close();
    header("Location: clubes.php");
    exit();
}

// --- 2. LÓGICA DE ACTUALIZAR (UPDATE) ---
if (isset($_POST['guardar']) && !empty($_POST['id_editar'])) {
    $stmt = $conn->prepare("UPDATE club SET nombre = ?, ciudad = ?, fundacion = ? WHERE id_club = ?");
    $stmt->bind_param("ssii", $_POST['nombre'], $_POST['ciudad'], $_POST['fundacion'], $_POST['id_editar']);
    $stmt->execute();
    audit($conn, 'club', (int) $_POST['id_editar'], 'UPDATE');
    $stmt->close();
    header("Location: clubes.php");
    exit();
}

// --- 3. LÓGICA DE ELIMINAR (DELETE) ---
if (isset($_POST['eliminar'])) {
    $stmt = $conn->prepare("DELETE FROM club WHERE id_club = ?");
    $idEliminar = filter_input(INPUT_POST, 'eliminar', FILTER_VALIDATE_INT);
    if (!$idEliminar) {
        http_response_code(400);
        exit('Identificador inválido.');
    }
    $stmt->bind_param("i", $idEliminar);
    $stmt->execute();
    audit($conn, 'club', $idEliminar, 'DELETE');
    $stmt->close();
    header("Location: clubes.php");
    exit();
}

// --- 4. CARGAR DATOS PARA EDITAR ---
$v_nombre = ""; $v_ciudad = ""; $v_fundacion = ""; $v_id = "";
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM club WHERE id_club = ?");
    $stmt->bind_param("i", $_GET['editar']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($c = $resultado->fetch_assoc()) {
        $v_id = $c['id_club'];
        $v_nombre = $c['nombre'];
        $v_ciudad = $c['ciudad'];
        $v_fundacion = $c['fundacion'];
    }
    $stmt->close();
}

// --- 5. OBTENER TODOS LOS CLUBES ---
$resultado_clubes = $conn->query("SELECT * FROM club ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> MG Fut </title>
    <link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
    <style>
        .crud-container { display: flex; gap: 30px; margin: 20px; flex-wrap: wrap; }
        form.form-crud { background: #f4f4f9; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        form.form-crud input { width: 100%; padding: 8px; margin: 6px 0; box-sizing: border-box; }
        form.form-crud button { width: 100%; padding: 10px; background: #27ae60; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .tabla-container { flex-grow: 1; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #2c3e50; color: white; }
        .btn-editar { background: #f39c12; color: white; padding: 5px 8px; text-decoration: none; border-radius: 3px; font-size: 12px; }
        .btn-eliminar { background: #e74c3c; color: white; padding: 5px 8px; text-decoration: none; border-radius: 3px; font-size: 12px; }
    </style>
</head>

<body>
    <header>
        <div class="MGfut">
            <h2> MG Fut</h2>
        </div>
    </header>

    <main>
        <div class="botonesMenu">
            <button class="btnClubes"><a href="clubes.php" style="color: #8BA3C5">Clubes</a></button>
            <button class="btnJugadores"><a href="jugadores.php" style="color: #8BA3C5">Jugadores</a></button>
            <button class="btnFixture"><a href="fixture.php" style="color: #8BA3C5">Fixture</a></button>
            <button class="btnEstadisticas"><a href="estadisticas.php" style="color: #8BA3C5">Estadísticas</a></button>
        </div>

        <div class="crud-container">
            <?php if ($esAdmin): ?><form class="form-crud" action="clubes.php" method="POST">
                <?php echo csrfField(); ?>
                <h3><?php echo $v_id ? "Editar Club" : "Agregar Club"; ?></h3>
                <input type="hidden" name="id_editar" value="<?php echo $v_id; ?>">
                
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($v_nombre); ?>" required>
                
                <label>Ciudad:</label>
                <input type="text" name="ciudad" value="<?php echo htmlspecialchars($v_ciudad); ?>" required>
                
                <label>Año de Fundación:</label>
                <input type="number" name="fundacion" min="1800" max="2100" value="<?php echo htmlspecialchars($v_fundacion); ?>" required>

                <button type="submit" name="guardar"><?php echo $v_id ? "Actualizar Club" : "Guardar Club"; ?></button>
                
                <?php if ($v_id): ?>
                    <a href="clubes.php" style="display:block; text-align:center; margin-top:10px; color:#555; text-decoration:none;">Cancelar edición</a>
                <?php endif; ?>
            </form><?php else: ?><p>Solo el administrador puede registrar o editar clubes.</p><?php endif; ?>

            <div class="tabla-container">
                <table>
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Ciudad</th>
                            <th>Año de Fundación</th>
                            <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($c = $resultado_clubes->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($c['nombre']) . "</td>";
                            echo "<td>" . htmlspecialchars($c['ciudad']) . "</td>";
                            echo "<td>" . htmlspecialchars($c['fundacion']) . "</td>";
                            if ($esAdmin) echo "<td>
                                                                        <a class='btn-editar' href='clubes.php?editar=" . $c['id_club'] . "'>Editar</a> 
                                                                        <form method='post' action='clubes.php' style='display:inline'>" . csrfField() . "<button class='btn-eliminar' type='submit' name='eliminar' value='" . (int) $c['id_club'] . "' onclick='return confirm(\"¿Estás seguro?\")'>Borrar</button></form>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>      
    </main>

    <footer>
        <br><hr>
        <h2 align="center"> ¿Quiénes somos?</h2>
        <div class="grupo_bloques_footer">
            <div class="bloques_footer">
                <p> Somos ANS Solutions, un grupo de desarrollo de software independiente localizado en Rivera, Uruguay.</p>
            </div>
            <div class="bloques_footer">
                <h3 align="center">Contactos: </h3>
                <ul>
                    <li>Email: contacto@ANSsolutions.com</li>
                    <li>Teléfono: +598 091-981-722</li>
                </ul>
            </div>
            <div class="bloques_footer">
                <p>© Copyright 2026 ANS Solutions. Todos los derechos reservados.</p>
            </div>
        </div>
        <br><br>
    </footer>
</body>
</html>
