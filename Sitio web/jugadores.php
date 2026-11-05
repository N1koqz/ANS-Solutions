<?php
require_once __DIR__ . "/aplicacion/inicio.php";
requerirLogin(true);
require_once __DIR__ . "/aplicacion/editor/jugadores/editor.php";
procesarEditorJugadores();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title> MG Fut </title>
		<link href="recursos/css/CSSmenuInicio.css" type="text/css" rel="stylesheet">
		<?php estilosEditorJugadores(); ?>
	</head>
	<body>
		<?php menu(); ?>
		<main>
			<br>
			<?php mostrarEditorJugadores(); ?>
			<?php
			$clubesFiltro = $conn->query("SELECT id_club, nombre FROM club ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
			$categoriasFiltro = $conn->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
			$clubSeleccionado = isset($_GET["id_club"]) && is_string($_GET["id_club"]) ? $_GET["id_club"] : "";
			$categoriaSeleccionada = isset($_GET["id_categoria"]) && is_string($_GET["id_categoria"])
				? $_GET["id_categoria"]
				: "";
			?>
			<div>
				<form method="GET" class="barraBusqueda" id="filtrosJugadores">
					<input class="input-barraBusqueda"
						type="search"
						name="busqueda"
						placeholder="Buscar Jugadores"
						value="<?= htmlspecialchars(textoBusqueda()) ?>"
						<?= esAdmin() ? "" : "autofocus" ?>
					>
					<select name="id_club" aria-label="Filtrar por club" onchange="this.form.submit()">
						<option value="">Todos los clubes</option>
						<?php foreach ($clubesFiltro as $club): ?>
							<option value="<?= (int) $club["id_club"] ?>"<?= $clubSeleccionado === (string) $club["id_club"] ? " selected" : "" ?>>
								<?= htmlspecialchars($club["nombre"]) ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="id_categoria" aria-label="Filtrar por categoría" onchange="this.form.submit()">
						<option value="">Todas las categorías</option>
						<?php foreach ($categoriasFiltro as $categoria): ?>
							<option value="<?= (int) $categoria["id_categoria"] ?>"<?= $categoriaSeleccionada === (string) $categoria["id_categoria"] ? " selected" : "" ?>>
								<?= htmlspecialchars($categoria["nombre"]) ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="image" class="botonBusqueda" alt="Buscar" src="recursos/imagenes/search.svg">
					<a href="jugadores.php">Limpiar</a>
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
							<?php if (esAdmin()): ?>
								<th>Acciones</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php require __DIR__ . "/aplicacion/modulos/jugadores/listar_jugadores.php"; ?>
					</tbody>
				</table>
			</div>
		</main>
		<?php pie(); ?>
	</body>
</html>
