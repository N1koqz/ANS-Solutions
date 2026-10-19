<?php
    require_once "plantilla.php";
    requerirLogin();

    // Carnets de salud. El admin ve todos; un usuario de club solo los de los jugadores de su club (con busqueda opcional)

    $busqueda = patronBusqueda();
    $sql = "
        SELECT k.id_carnet, j.ci, j.nombre, j.apellido, c.nombre AS club, k.fecha_inicio, k.fecha_vencimiento
        FROM carnets_salud k
        INNER JOIN jugadores j ON k.ci = j.ci
        LEFT JOIN club c ON j.id_club = c.id_club
        WHERE CONCAT_WS(' ', j.ci, j.nombre, j.apellido, c.nombre) LIKE ?
    ";

    if (esAdmin()) {
        $stmt = $conn->prepare($sql . " ORDER BY k.fecha_vencimiento DESC");
        $stmt->bind_param("s", $busqueda);
    } else {
        $idClub = clubDelUsuario();
        if ($idClub === null) {
            return; //Sin club asignado no se lista nada
        }
        $stmt = $conn->prepare($sql . " AND j.id_club = ? ORDER BY k.fecha_vencimiento DESC");
        $stmt->bind_param("si", $busqueda, $idClub);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    //Se muestra toda la información

    $hoy = date("Y-m-d");
    while ($carnet = $result->fetch_assoc()) {
        if ($carnet["fecha_inicio"] > $hoy) {
            $estado = "Aún no vigente";
        } elseif ($carnet["fecha_vencimiento"] < $hoy) {
            $estado = "Vencido";
        } else {
            $estado = "Vigente";
        }

        echo "<tr>";
        foreach (["ci", "nombre", "apellido", "club", "fecha_inicio", "fecha_vencimiento"] as $columna) {
            echo celda($carnet[$columna] ?? "Sin club");
        }
        echo celda($estado);
        echo "<td><a href='carnet_imagen.php?id=" . (int)$carnet["id_carnet"] . "' target='_blank' rel='noopener' style='color:#ffffff'>Ver</a></td>";
        echo "</tr>";
    }
?>
