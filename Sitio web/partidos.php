<?php
	require_once __DIR__ . "/aplicacion/inicio.php";
	requerirLogin(true);
	require_once __DIR__ . "/aplicacion/editor/editor.php";
	require_once __DIR__ . "/aplicacion/modulos/partidos/procesar_partidos.php";
	procesarPartidos();
?>
<html>
<head>
	<meta charset="UTF-8">
	<title> MG Fut </title>
	<link href="recursos/css/CSSmenuInicio.css" type="text/css" rel="stylesheet">
<?php estilosEditor(); ?>
</head>

<body>
<?php menu(); ?>

	<main>
		<br>
<?php mostrarFormularioPartidos(); ?>
		<div>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Partidos"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "" : "autofocus" ?>
				>
				<input type="image" class="botonBusqueda" alt="Buscar" src="recursos/imagenes/search.svg">
			</form>
		</div>
		<div>
			<table>
				<thead>
					<tr>
						<th>Fecha</th>
						<th>Estadio</th>
						<th>Local</th>
						<th>Resultado</th>
						<th>Visitante</th>
						<th>Estado</th>
						<th>Ganador</th>
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr>
				</thead>

				<tbody>
<?php require __DIR__ . "/aplicacion/modulos/partidos/listar_partidos.php"; ?>
				</tbody>
			</table>
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
