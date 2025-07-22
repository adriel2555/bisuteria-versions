<?php
// Mostrar errores (útil para depurar)
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Configuración de la conexión
$host = "localhost";
$user = "root";
$password = ""; 
$dbname = "bisuteria";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
} /*else {
    echo "Conexión exitosa <br>";
}*/

// Configurar para que lance excepciones
$conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

// Establecer charset
$conn->set_charset("utf8");
?>
