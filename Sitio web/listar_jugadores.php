<?php
    require_once "plantilla.php";
    requerirLogin();

    // Consulta de jugadores con su club y categoria en una sola consulta (con busqueda opcional)

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT j.ci, j.nombre, j.apellido, j.fecha_nacimiento, j.posicion, j.dorsal,
               COALESCE(c.nombre, 'Sin club') AS club,
               COALESCE(ca.nombre, 'Sin categoria') AS categoria,
               j.masa, j.altura
        FROM jugadores j
        LEFT JOIN club c ON j.id_club = c.id_club
        LEFT JOIN categorias ca ON j.id_categoria = ca.id_categoria
        WHERE CONCAT_WS(' ', j.ci, j.nombre, j.apellido, j.posicion, j.dorsal, c.nombre, ca.nombre) LIKE ?
        ORDER BY j.apellido, j.nombre
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    //Se muestra toda la información

    while ($jugador = $result->fetch_assoc()) {
        echo "<tr>";
        foreach (["ci", "nombre", "apellido", "fecha_nacimiento", "posicion", "dorsal", "club", "categoria", "masa", "altura"] as $columna) {
            echo celda($jugador[$columna]);
        }
        $fuerza_peso = $jugador["masa"] * 9.8;
        echo celda($fuerza_peso);
        botonesAdmin("jugadores.php", $jugador["ci"]);
        echo "</tr>";
    }
?>
