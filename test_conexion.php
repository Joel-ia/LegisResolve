<?php
// test_conexion.php
$mysqli = new mysqli('localhost', 'root', '');
if ($mysqli->connect_error) {
    die("Error: " . $mysqli->connect_error);
} else {
    echo "¡Conexión exitosa!";
}
?>