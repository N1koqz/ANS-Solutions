<?php
	require_once "plantilla.php";
	requerirLogin(true);
	require_once "editor.php";
	procesarEditor("estadisticas");
?>
<html>
<head>
	<meta charset="UTF-8">
	<title> MG Fut </title>
	<link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
<?php estilosEditor(); ?>
</head>

<body>
<?php menu(); ?>

	<main>
		<br>
<?php mostrarEditor("estadisticas"); ?>
		<div>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Estadísticas"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "" : "autofocus" ?>
				>
				<input type="image" class="botonBusqueda" alt="Buscar" src="search.svg">
			</form>
		</div>

		<h3 style="color:#23354D; margin: 20px 2% 0;">Estadísticas por partido</h3>
		<div>
			<table style="margin-bottom:30px">
				<thead>
					<tr>
						<th>Fecha</th>
						<th>Partido</th>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Goles</th>
						<th>Asistencias</th>
						<th>Amarillas</th>
						<th>Rojas</th>
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr>
				</thead>

				<tbody>
<?php require "listar_estadisticas.php"; ?>
				</tbody>
			</table>
		</div>

		<h3 style="color:#23354D; margin: 0 2%;">Acumulado por jugador</h3>
		<div>
			<table>
				<thead>
					<tr>
						<th>#</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Partidos</th>
						<th>Goles</th>
						<th>Asistencias</th>
						<th>Amarillas</th>
						<th>Rojas</th>
					</tr>
				</thead>

				<tbody>
<?php require "listar_acumulado.php"; ?>
				</tbody>
			</table>
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
