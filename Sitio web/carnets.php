<?php
	require_once "plantilla.php";
	requerirLogin(true);
	require_once "procesar_carnets.php";
	require_once "listar_carnets.php";
	procesarCarnets();
?>
<html>
<head>
	<meta charset="UTF-8">
	<title> MG Fut </title>
	<link href="CSSmenuInicio.css" type="text/css" rel="stylesheet">
	<link href="CSSeditor.css" type="text/css" rel="stylesheet">
</head>

<body>
<?php menu(); ?>

	<main>
		<br>
<?php mostrarFormularioCarnets(); ?>
		<div>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Carnets"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "autofocus" : "" ?>
				>
				<input type="image" class="botonBusqueda" alt="Buscar" src="search.svg">
			</form>
		</div>
<?php if (esAdmin()): ?>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Todos los carnets</h3>
		<div>
			<table>
				<thead>
					<tr>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Inicio de Validez</th>
						<th>Vencimiento</th>
						<th>Estado</th>
						<th>Carnet</th>
					</tr>
				</thead>

				<tbody>
<?php listarCarnets("todos"); ?>
				</tbody>
			</table>
		</div>
<?php else: ?>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Carnets de mi club</h3>
		<div>
			<table style="margin-bottom:30px">
				<thead>
					<tr>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Inicio de Validez</th>
						<th>Vencimiento</th>
						<th>Estado</th>
						<th>Carnet</th>
					</tr>
				</thead>

				<tbody>
<?php listarCarnets("propio"); ?>
				</tbody>
			</table>
		</div>
		<h3 style="color:#23354D; margin: 20px 2% 0;">Carnets de otros clubes</h3>
		<div>
			<table>
				<thead>
					<tr>
						<th>Cédula</th>
						<th>Nombre</th>
						<th>Apellido</th>
						<th>Club</th>
						<th>Inicio de Validez</th>
						<th>Vencimiento</th>
						<th>Estado</th>
					</tr>
				</thead>

				<tbody>
<?php listarCarnets("otros"); ?>
				</tbody>
			</table>
		</div>
<?php endif; ?>

	</main>

<?php pie(); ?>

</body>

</html>
