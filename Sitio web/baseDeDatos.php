<?php
//Se establecen los parametros de la base de datos
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "mgfut";
$port = "3306";

//Crea la conexion
$conn = new mysqli($servername, $username, $password, $dbname, $port);

//Verifica la conexion
if ($conn->connect_error) {
  die("La conexion fallo: " . $conn->connect_error);
}
?>