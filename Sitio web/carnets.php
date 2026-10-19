<?php
	require_once "plantilla.php";
	requerirLogin(true);
	require_once "procesar_carnets.php";
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
<?php require "listar_carnets.php"; ?>
				</tbody>
			</table>
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
