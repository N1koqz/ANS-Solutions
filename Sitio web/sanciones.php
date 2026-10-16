<?php
	require_once "baseDeDatos.php";
	requerirLogin(true);
	require_once "editor.php";
	procesarEditor("sanciones");
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
		<div> 
			<table> 
				<thead> 
					<tr>
						<th>ID Sanción</th> 
						<th>Cédula</th> 
						<th>Nombre</th> 
						<th>Apellido</th> 
						<th>Tipo de Sanción</th> 
						<th>Motivo</th> 
						<th>Fecha de Suspensión</th> 
						<th>Fecha Límite</th> 
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr> 
				</thead> 

				<tbody> 
<?php require "listar_sanciones.php"; ?>
				</tbody> 
			</table> 
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
