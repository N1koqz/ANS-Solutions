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
	$aniosNacimiento = [];
	foreach ($categorias as $categoria) {
		$nombreCategoria = trim((string) $categoria["nombre"]);
		if (!ctype_digit($nombreCategoria) || strlen($nombreCategoria) !== 4) {
			return "Todas las categorías deben tener un año de nacimiento de cuatro cifras";
		}
		$aniosNacimiento[] = (int) $nombreCategoria;
	}
	if (count($aniosNacimiento) === 0) {
		return null;
	}

	$anioNacimiento = (int) $fechaNacimiento->format("Y");
	$anioMenor = min($aniosNacimiento);
	$anioMayor = max($aniosNacimiento);
	if ($anioNacimiento < $anioMenor || $anioNacimiento > $anioMayor) {
		return "El año de nacimiento debe estar entre $anioMenor y $anioMayor";
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
		"id_categoria" => ["etiqueta" => "Año de nacimiento", "tipo" => "select", "origen" => ["categorias", "id_categoria", "nombre"]],
		"masa" => ["etiqueta" => "Masa", "tipo" => "number", "min" => 1, "max" => 300],
		"altura" => ["etiqueta" => "Altura", "tipo" => "number", "min" => 0.5, "max" => 250],
	],
]);