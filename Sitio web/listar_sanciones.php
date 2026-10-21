<?php
    require_once "plantilla.php";

    // Lista de sanciones para sanciones.php. Se llama dentro de un <tbody>:
    //   "todos"  -> todas las sanciones (admin, con boton de editar)
    //   "propio" -> sanciones de los jugadores del club del usuario
    //   "otros"  -> sanciones de los jugadores de los demas clubes
    // Todas respetan la busqueda opcional
    function listarSanciones($modo) {
        global $conn;
        requerirLogin();

        $busqueda = patronBusqueda();
        $sql = "
            SELECT s.id_sancion, s.ci, j.nombre, j.apellido, COALESCE(c.nombre, 'Sin club') AS club,
                   s.tipo_sancion, s.motivo, s.fecha_sancion, s.fecha_limite
            FROM sanciones s
            INNER JOIN jugadores j ON s.ci = j.ci
            LEFT JOIN club c ON j.id_club = c.id_club
            WHERE CONCAT_WS(' ', s.id_sancion, s.ci, j.nombre, j.apellido, c.nombre, s.tipo_sancion, s.motivo) LIKE ?
        ";

        if ($modo === "todos") {
            if (!esAdmin()) {
                return;
            }
            $stmt = $conn->prepare($sql . " ORDER BY s.fecha_sancion DESC");
            $stmt->bind_param("s", $busqueda);
        } else {
            $idClub = clubDelUsuario();
            if ($idClub === null) {
                return; //Admin o usuario sin club asignado
            }
            $filtro = $modo === "propio" ? "j.id_club = ?" : "j.id_club <> ?";
            $stmt = $conn->prepare($sql . " AND $filtro ORDER BY s.fecha_sancion DESC");
            $stmt->bind_param("si", $busqueda, $idClub);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Se muestra toda la información

        while ($sancion = $result->fetch_assoc()) {
            echo "<tr>";
            foreach (["id_sancion", "ci", "nombre", "apellido", "club", "tipo_sancion", "motivo", "fecha_sancion", "fecha_limite"] as $columna) {
                echo celda($sancion[$columna]);
            }
            botonesAdmin("sanciones.php", $sancion["id_sancion"]);
            echo "</tr>";
        }
    }
?>
