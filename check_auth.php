<?php
require_once __DIR__ . '/config/session.php';

// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// 2. Verificar inactividad (30 minutos)
$inactivity = 1800; // 30 min en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// 3. Verificar rol (ejemplo para ruta de admin)
function requireRole($requiredRole) {
    if ($_SESSION['user_role'] !== $requiredRole) {
        header('HTTP/1.0 403 Forbidden');
        die("Acceso denegado. Se requiere rol: " . htmlspecialchars($requiredRole));
    }
}

// Uso en páginas protegidas:
// requireRole('admin');
?>