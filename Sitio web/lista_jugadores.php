<?php 
    require "baseDeDatos.php"; 
 
    // Consulta de jugadores
    $stmt = $conn->prepare("SELECT * FROM jugadores"); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
 
    // Consulta de clubes
    $stmt_idclub = $conn->prepare("SELECT * FROM club"); 
    $stmt_idclub->execute(); 
    $result_idclub = $stmt_idclub->get_result(); 

    // Consulta de categorias
    $stmt_idcategoria = $conn->prepare("SELECT * FROM categorias"); 
    $stmt_idcategoria->execute(); 
    $result_idcategoria = $stmt_idcategoria->get_result(); 

    $clubes = [];

    while ($club = $result_idclub->fetch_assoc()) {
        $clubes[$club["id_club"]] = $club["nombre"];
    }

    $categorias = [];

    while ($categoria = $result_idcategoria->fetch_assoc()) {
        $categorias[$categoria["id_categoria"]] = $categoria["nombre"];
    }
 
    while ($jugador = $result->fetch_assoc()) { 

        $nombre_club = $clubes[$jugador["id_club"]] ?? "Sin club";
        $nombre_categoria = $categorias[$jugador["id_categoria"]] ?? "Sin categoria";

        echo "<tr>"; 
        echo "<td>" . htmlspecialchars($jugador["ci"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["nombre"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["apellido"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["fecha_nacimiento"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["posicion"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["dorsal"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($nombre_club) . "</td>"; 
        echo "<td>" . htmlspecialchars($nombre_categoria) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["masa"]) . "</td>"; 
        echo "<td>" . htmlspecialchars($jugador["altura"]) . "</td>"; 
        
        $fuerza_peso = $jugador["masa"] * 10;
        echo "<td>" . htmlspecialchars($fuerza_peso) . "</td>"; 
        echo "</tr>"; 
    } 
 
    exit(); 
?>