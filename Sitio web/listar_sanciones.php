<?php 
    require "baseDeDatos.php"; 

    // Consulta de sanciones

    $stmt = $conn->prepare("
        SELECT sanciones.*, jugadores.nombre, jugadores.apellido
        FROM sanciones
        INNER JOIN jugadores ON sanciones.ci = jugadores.ci
    ");

    $stmt->execute(); 
    $result = $stmt->get_result(); 

    // Se muestra toda la información

    while ($sanciones = $result->fetch_assoc()) {

        echo "<tr>";
         echo "<td>" . htmlspecialchars($sanciones["id_sancion"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["ci"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["nombre"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["apellido"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["tipo_sancion"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["motivo"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($sanciones["fecha_sancion"]) . "</td>";
        echo "<td>" . htmlspecialchars($sanciones["fecha_limite"]) . "</td>"; 
        echo "</tr>"; 
    }

    exit(); 
?>
