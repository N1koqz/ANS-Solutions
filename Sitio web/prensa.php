<?php
require_once __DIR__ . "/aplicacion/inicio.php";
require_once __DIR__ . "/aplicacion/modulos/boletines.php";
?>
<!DOCTYPE html>
<html lang="es">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Boletines de prensa - MG Fut</title>
		<link href="recursos/css/CSSindex.css" type="text/css" rel="stylesheet">
	</head>
	<body>
		<main>
			<p class="volver-login"><a href="index.php">Volver al inicio de sesión</a></p>
			<?php mostrarBoletines("prensa"); ?>
		</main>
	</body>
</html>