<?php
require_once __DIR__ . "/../editor.php";

function validarJugador($conn, $valores, $idActual)
{
	$hoy = new DateTimeImmutable("today");
	$fechaNacimiento = new DateTimeImmutable($valores["fecha_nacimiento"]);
	if ($fechaNacimiento > $hoy) {
		return "La fecha de nacimiento no puede estar en el futuro";
	}

	$categorias = $conn->query("SELECT nombre FROM categorias")->fetch_all(MYSQLI_ASSOC);
	$edades = [];
	foreach ($categorias as $categoria) {
		if (preg_match('/\b(?:sub[\s-]*|u[\s-]*)(\d{1,2})\b/i', $categoria["nombre"], $coincidencia)) {
			$edades[] = (int) $coincidencia[1];
		}
	}
	if (count($edades) === 0) {
		return null;
	}

	$edadMenor = min($edades);
	$edadMayor = max($edades);
	$fechaMinima = $hoy->modify("-$edadMayor years");
	$fechaMaxima = $hoy->modify("-$edadMenor years");
	if ($fechaNacimiento < $fechaMinima || $fechaNacimiento > $fechaMaxima) {
		return "La fecha de nacimiento debe estar dentro de las edades de las categorías registradas ($edadMenor a $edadMayor años)";
	}

	return null;
}

registrarEntidadEditor("jugadores", [
	"pagina" => "jugadores.php",
	"titulo" => "jugadores",
	"tabla" => "jugadores",
	"pk" => "ci",
	"auto" => false,
	"etiqueta" => "CONCAT(nombre, ' ', apellido)",
	"busqueda" => "CONCAT_WS(' ', ci, nombre, apellido, posicion)",
	"regla" => "validarJugador",
	"campos" => [
		"ci" => ["etiqueta" => "Cédula", "tipo" => "text", "max" => 8, "validacion" => "ci"],
		"nombre" => ["etiqueta" => "Nombre", "tipo" => "text", "max" => 50],
		"apellido" => ["etiqueta" => "Apellido", "tipo" => "text", "max" => 50],
		"fecha_nacimiento" => ["etiqueta" => "Fecha de Nacimiento", "tipo" => "date"],
		"posicion" => ["etiqueta" => "Posición", "tipo" => "text", "max" => 30],
		"dorsal" => ["etiqueta" => "N° de camiseta", "tipo" => "number", "min" => 1, "max" => 99, "paso" => 1, "entero" => true],
		"id_club" => ["etiqueta" => "Club", "tipo" => "select", "origen" => ["club", "id_club", "nombre"]],
		"id_categoria" => ["etiqueta" => "Categoría", "tipo" => "select", "origen" => ["categorias", "id_categoria", "nombre"]],
		"masa" => ["etiqueta" => "Masa", "tipo" => "number", "min" => 1, "max" => 300],
		"altura" => ["etiqueta" => "Altura", "tipo" => "number", "min" => 0.5, "max" => 250],
	],
]);