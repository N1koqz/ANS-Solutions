<?php
    require_once "baseDeDatos.php";
    requerirLogin();

    // Consulta de sanciones (con busqueda opcional)

    $busqueda = patronBusqueda();
    $stmt = $conn->prepare("
        SELECT sanciones.*, jugadores.nombre, jugadores.apellido
        FROM sanciones
        INNER JOIN jugadores ON sanciones.ci = jugadores.ci
        WHERE CONCAT_WS(' ', sanciones.id_sancion, sanciones.ci, jugadores.nombre, jugadores.apellido, sanciones.tipo_sancion, sanciones.motivo) LIKE ?
        ORDER BY sanciones.fecha_sancion DESC
    ");
    $stmt->bind_param("s", $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se muestra toda la información

    while ($sanciones = $result->fetch_assoc()) {
        echo "<tr>";
        foreach (["id_sancion", "ci", "nombre", "apellido", "tipo_sancion", "motivo", "fecha_sancion", "fecha_limite"] as $columna) {
            echo celda($sanciones[$columna]);
        }
        botonesAdmin("sanciones.php", $sanciones["id_sancion"]);
        echo "</tr>";
    }
?>
