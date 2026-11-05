<?php
require_once __DIR__ . "/aplicacion/inicio.php";
requerirLogin(true);
require_once __DIR__ . "/aplicacion/modulos/boletines.php";
procesarBoletines();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title> MG Fut </title>
		<link href="recursos/css/CSSmenuInicio.css" type="text/css" rel="stylesheet">
	</head>
	<body>
		<?php menu(); ?>
		<main>
			<?php mostrarBoletines(esAdmin() ? "todos" : "clubes", esAdmin()); ?>
		</main>
		<?php pie(); ?>
	</body>
</html>
