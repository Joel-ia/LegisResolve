<?php
// Configuración de sesión SEGURA (debe ir ANTES de session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Solo si usas HTTPS
ini_set('session.use_strict_mode', 1);

// Iniciar sesión
session_start();

// Regenerar ID de sesión para prevenir fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Conexión MySQLi (Ajusta las credenciales!)
$host = 'localhost';
$user = 'root'; // Usuario por defecto en XAMPP
$pass = '';     // Contraseña vacía por defecto en XAMPP
$dbname = 'disputas_online';

$mysqli = new mysqli($host, $user, $pass, $dbname);

if ($mysqli->connect_error) {
    error_log("Error de conexión a BD: " . $mysqli->connect_error);
    // No muestres errores al usuario en producción
    die("Error en el sistema. Por favor intenta más tarde.");
}
?>