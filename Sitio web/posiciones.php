<?php
require_once __DIR__ . "/aplicacion/inicio.php";
requerirLogin(true);
require_once __DIR__ . "/aplicacion/modulos/posiciones/calcular_posiciones.php";

$ligas = ligas_disponibles();
$liga_seleccionada = $_GET["liga"] ?? "oro";
if (!is_string($liga_seleccionada) || !array_key_exists($liga_seleccionada, $ligas)) {
    http_response_code(400);
    exit("La liga seleccionada no es válida.");
}
$error_posiciones = null;
$tablas = [];
$sin_liga = 0;
$sin_categoria = 0;
try {
    $categorias = $conn->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre DESC")->fetch_all(MYSQLI_ASSOC);
    $clubes = $conn->query("SELECT id_club, nombre FROM club ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
    $partidos = $conn->query("SELECT liga, id_categoria, id_club_local, id_club_visitante, goles_local, goles_visitante FROM partidos")->fetch_all(MYSQLI_ASSOC);
    $tablas = calcular_posiciones($categorias, $clubes, $partidos, $liga_seleccionada);
    foreach ($partidos as $partido) {
        if ($partido["goles_local"] !== null && $partido["goles_visitante"] !== null) {
            if ($partido["liga"] === null) {
                $sin_liga++;
            } elseif ($partido["liga"] === $liga_seleccionada && $partido["id_categoria"] === null) {
                $sin_categoria++;
            }
        }
    }
} catch (mysqli_sql_exception $error) {
    error_log("Error al cargar posiciones: " . $error->getMessage());
    http_response_code(500);
    $error_posiciones = "No se pudieron cargar las posiciones. Revisá la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Posiciones - MG Fut</title>
    <link href="recursos/css/CSSmenuInicio.css" rel="stylesheet">
</head>
<body>
    <?php menu(); ?>
    <main class="posiciones">
        <h1>Posiciones</h1>
        <form method="get" class="filtro-liga">
            <label for="liga">Liga</label>
            <select name="liga" id="liga">
                <?php foreach ($ligas as $valor => $nombre): ?>
                    <option value="<?= htmlspecialchars($valor) ?>"<?= $valor === $liga_seleccionada ? " selected" : "" ?>><?= htmlspecialchars($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Ver posiciones</button>
        </form>
        <h2><?= htmlspecialchars($ligas[$liga_seleccionada]) ?></h2>
        <p>Victoria: 3 puntos; empate: 1; derrota: 0. Desempate por diferencia de goles, goles a favor y nombre del club.</p>
        <?php if ($sin_liga > 0): ?>
            <p class="aviso-posiciones">Hay <?= $sin_liga ?> partido(s) finalizado(s) sin liga que no se incluyen en las tablas. Un administrador puede asignarla desde <a href="partidos.php">Partidos</a>.</p>
        <?php endif; ?>
        <?php if ($sin_categoria > 0): ?>
            <p class="aviso-posiciones">Hay <?= $sin_categoria ?> partido(s) finalizado(s) de esta liga sin categoría que no se incluyen en las tablas. Un administrador puede asignarla desde <a href="partidos.php">Partidos</a>.</p>
        <?php endif; ?>
        <?php if ($error_posiciones !== null): ?>
            <p class="aviso-posiciones" role="alert"><?= htmlspecialchars($error_posiciones) ?></p>
        <?php elseif (!$tablas): ?>
            <p>No hay categorías registradas.</p>
        <?php endif; ?>
        <?php foreach ($tablas as $tabla): ?>
            <section>
                <h2>Categoría <?= htmlspecialchars((string) $tabla["nombre"]) ?></h2>
                <?php if (!$tabla["clubes"]): ?>
                    <p>No hay clubes registrados.</p>
                <?php else: ?>
                    <div class="tabla-posiciones">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">#</th><th scope="col">Club</th><th scope="col">Partidos Jugados</th>
                                    <th scope="col">Partidos Ganados</th><th scope="col">Partidos Empatados</th><th scope="col">Partidos Perdidos</th>
                                    <th scope="col">Goles a Favor</th><th scope="col">Goles en Contra</th><th scope="col">Diferencia de Goles</th><th scope="col">Puntos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tabla["clubes"] as $indice => $fila): ?>
                                    <tr>
                                        <?= celda($indice + 1) ?>
                                        <?php foreach (["nombre", "partidos_jugados", "partidos_ganados", "partidos_empatados", "partidos_perdidos", "goles_a_favor", "goles_en_contra", "diferencia_de_goles", "puntos"] as $columna): ?>
                                            <?= celda($fila[$columna]) ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        </main>
    <?php pie(); ?>
</body>
</html>
