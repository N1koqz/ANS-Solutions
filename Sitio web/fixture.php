<?php
session_start();
require 'baseDeDatos.php';
requireAuthentication();
$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';

// Carpeta donde se guardarán las imágenes subidas
$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Lógica para subir o actualizar la imagen del fixture
if (isset($_POST['subir_fixture']) && isset($_FILES['imagen_fixture'])) {
    verifyCsrf();
    requireRole('admin');
    $file = $_FILES['imagen_fixture'];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 5 * 1024 * 1024) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowedTypes[$mime])) {
            $_SESSION['fixture_error'] = 'El archivo debe ser una imagen JPG, PNG o WebP.';
            header("Location: fixture.php");
            exit();
        }
        $file_name = "fixture_" . bin2hex(random_bytes(12)) . "." . $allowedTypes[$mime];
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Guardamos la ruta en la sesión (en un entorno real se guardaría en base de datos)
            $_SESSION['fixture_img'] = 'uploads/' . $file_name;
        }
    } else {
        $_SESSION['fixture_error'] = 'No se pudo subir la imagen. El tamaño máximo es de 5 MB.';
    }
    header("Location: fixture.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> MG Fut </title>
    <link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
    <style>
        .fixture-container { text-align: center; margin: 30px auto; padding: 20px; }
        .fixture-img { max-width: 100%; height: auto; max-height: 600px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .form-upload { background: #f4f4f9; padding: 20px; border-radius: 8px; width: 350px; margin: 20px auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: left; }
        .form-upload input { width: 100%; margin: 10px 0; }
        .form-upload button { width: 100%; padding: 8px; background: #27ae60; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; }
        .form-upload button:hover { background: #219653; }
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
            <button class="btnClubes">
                <a href="clubes.php" style="color: #8BA3C5">Clubes</a>
            </button>

            <button class="btnJugadores">
                <a href="jugadores.php" style="color: #8BA3C5">Jugadores</a>
            </button>

            <button class="btnFixture">
                <a href="fixture.php" style="color: #8BA3C5">Fixture</a>
            </button>

            <button class="btnEstadisticas">
                <a href="estadisticas.php" style="color: #8BA3C5">Estadísticas</a>
            </button>
        </div>

        <div class="fixture-container">
            <h2>Fixture del Torneo</h2>
            
            <!-- Panel para que el Administrador suba/actualice la imagen -->
            <?php if ($esAdmin): ?><div class="form-upload">
                <h4>Panel de Administración</h4>
                <form action="fixture.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <label>Subir nueva imagen de fixture:</label>
                    <input type="file" name="imagen_fixture" accept="image/*" required>
                    <button type="submit" name="subir_fixture">Subir / Actualizar Imagen</button>
                </form>
            </div><?php endif; ?>

            <br>

            <!-- Visualización de la imagen cargada -->
            <?php if (!empty($_SESSION['fixture_error'])): ?>
                <p style="color: #b42318;"><?php echo htmlspecialchars($_SESSION['fixture_error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['fixture_error']); ?></p>
            <?php endif; ?>
            <?php if (isset($_SESSION['fixture_img']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $_SESSION['fixture_img'])): ?>
                <div>
                    <img src="<?php echo htmlspecialchars($_SESSION['fixture_img'], ENT_QUOTES, 'UTF-8'); ?>" alt="Fixture MG Fut" class="fixture-img">
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">Aún no se ha cargado ninguna imagen de fixture. Utiliza el panel superior para subir una.</p>
            <?php endif; ?>
        </div>      

    </main>

    <footer>
        <br>
        <hr>
        <h2 align="center"> ¿Quiénes somos?</h2>
        <div class="grupo_bloques_footer">
            <div class="bloques_footer">
                <p> Somos ANS Solutions, un grupo de desarrollo de software independiente localizado en Rivera, Uruguay.
                    Estamos especializados en la creacion de sitios webs y bases de datos para negocios y empresas grandes.
                </p>
            </div>
            <div class="bloques_footer">
                <h3 align="center">Contactos: </h3>
                <ul>
                    <li>Email: contacto@ANSsolutions.com</li>
                    <li>Teléfono: +598 091-981-722</li>
                </ul>
            </div>
            <div class="bloques_footer">
                <p>© Copyright 2026 ANS Solutions. Todos los derechos reservados. 
                    Ninguna parte de este sistema o sitio web será reproducido, de ninguna forma, 
                    incluyendo pero no limitando al host de un sitio web no autorizado por ANS Solutions.
                </p>
            </div>
        </div>
        <br>
        <br>
    </footer>

</body>
</html>
