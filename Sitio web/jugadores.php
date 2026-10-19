<?php
	require_once "plantilla.php";
	requerirLogin(true);
	require_once "editor.php";
	procesarEditor("jugadores");
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
<?php mostrarEditor("jugadores"); ?>
		<div>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Jugadores"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "" : "autofocus" ?>
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
						<th>Fecha de Nacimiento</th> 
						<th>Posición</th> 
						<th>N° de camiseta</th> 
						<th>Club</th> 
						<th>Categoría</th>
						<th>Masa</th> 
						<th>Altura</th>
						<th>Fuerza Peso</th> 
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr> 
				</thead> 

				<tbody> 
<?php require "listar_jugadores.php"; ?>
				</tbody> 
			</table> 
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
