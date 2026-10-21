<?php
	require_once "plantilla.php";
	requerirLogin(true);
	require_once "editor.php";
	procesarEditor("sanciones");
	require_once "listar_sanciones.php";
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
<?php mostrarEditor("sanciones"); ?>
		<div>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Sanciones"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "" : "autofocus" ?>
				>
				<input type="image" class="botonBusqueda" alt="Buscar" src="search.svg">
			</form>
		</div>
<?php if (esAdmin()): ?>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Todas las sanciones</h3>
		<div>
			<table>
				<thead>
					<tr>
						<th>ID Sanción</th>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Tipo de Sanción</th>
						<th>Motivo</th>
						<th>Fecha de Suspensión</th>
						<th>Fecha Límite</th>
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr>
				</thead>

				<tbody>
<?php listarSanciones("todos"); ?>
				</tbody>
			</table>
		</div>
<?php else: ?>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Sanciones de mi club</h3>
		<div>
			<table style="margin-bottom:30px">
				<thead>
					<tr>
						<th>ID Sanción</th>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Tipo de Sanción</th>
						<th>Motivo</th>
						<th>Fecha de Suspensión</th>
						<th>Fecha Límite</th>
					</tr>
				</thead>

				<tbody>
<?php listarSanciones("propio"); ?>
				</tbody>
			</table>
		</div>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Sanciones de otros clubes</h3>
		<div>
			<table>
				<thead>
					<tr>
						<th>ID Sanción</th>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Tipo de Sanción</th>
						<th>Motivo</th>
						<th>Fecha de Suspensión</th>
						<th>Fecha Límite</th>
					</tr>
				</thead>

				<tbody>
<?php listarSanciones("otros"); ?>
				</tbody>
			</table>
		</div>
<?php endif; ?>

	</main>

<?php pie(); ?>

</body>

</html>
