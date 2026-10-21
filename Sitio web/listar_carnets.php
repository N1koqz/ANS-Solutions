<?php
    require_once "plantilla.php";

    // Lista de carnets de salud para carnets.php. Se llama dentro de un <tbody>:
    //   "todos"  -> todos los carnets (solo admin)
    //   "propio" -> carnets de los jugadores del club del usuario (con enlace a la imagen)
    //   "otros"  -> carnets de los demas clubes (solo datos de validez, sin imagen)
    // Todas respetan la busqueda opcional
    function listarCarnets($modo) {
        global $conn;
        requerirLogin();

        $busqueda = patronBusqueda();
        $sql = "
            SELECT k.id_carnet, j.ci, j.nombre, j.apellido, COALESCE(c.nombre, 'Sin club') AS club,
                   k.fecha_inicio, k.fecha_vencimiento
            FROM carnets_salud k
            INNER JOIN jugadores j ON k.ci = j.ci
            LEFT JOIN club c ON j.id_club = c.id_club
            WHERE CONCAT_WS(' ', j.ci, j.nombre, j.apellido, c.nombre) LIKE ?
        ";

        if ($modo === "todos") {
            if (!esAdmin()) {
                return;
            }
            $stmt = $conn->prepare($sql . " ORDER BY c.nombre, j.apellido, k.fecha_vencimiento DESC");
            $stmt->bind_param("s", $busqueda);
        } else {
            $idClub = clubDelUsuario();
            if ($idClub === null) {
                return; //Admin o usuario sin club asignado
            }
            $filtro = $modo === "propio" ? "j.id_club = ?" : "j.id_club <> ?";
            $stmt = $conn->prepare($sql . " AND $filtro ORDER BY c.nombre, j.apellido, k.fecha_vencimiento DESC");
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
                echo celda($carnet[$columna]);
            }
            echo celda($estado);
            if ($modo !== "otros") {
                echo "<td><a href='carnet_imagen.php?id=" . (int)$carnet["id_carnet"] . "' target='_blank' rel='noopener' style='color:#ffffff'>Ver</a></td>";
            }
            echo "</tr>";
        }
    }
?>
