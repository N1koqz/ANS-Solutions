<?php
	require_once "baseDeDatos.php";
	requerirLogin(true);
	require_once "editor.php";
	procesarEditor("clubes");
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
<?php mostrarEditor("clubes"); ?>
			<form method="GET" class="barraBusqueda">
				<input class="input-barraBusqueda"
					type="search"
					name="busqueda"
					placeholder="Buscar Clubes"
					value="<?= htmlspecialchars(textoBusqueda()) ?>"
					<?= esAdmin() ? "" : "autofocus" ?>
				>
				<input type="image" class="botonBusqueda" alt="Buscar" src="search.svg">
			</form>
		<div> 
			<table> 
				<thead> 
					<tr>
						<th>ID</th>
						<th>Club</th> 
						<th>Ciudad</th> 
						<th>Año de Fundación</th>  
						<?php if (esAdmin()): ?><th>Acciones</th><?php endif; ?>
					</tr> 
				</thead> 

				<tbody> 
<?php require "listar_clubes.php"; ?>
				</tbody> 
			</table> 
		</div>

	</main>

<?php pie(); ?>

</body>

</html>
