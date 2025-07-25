<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

$plan = $_GET['plan'] ?? '';
$planes_permitidos = ['básico', 'plus', 'premium'];

if (!in_array($plan, $planes_permitidos)) {
    $_SESSION['error'] = "Plan no válido";
    header("Location: user_dashboard.php");
    exit;
}

// Verificar si es una actualización de pago
if ($plan !== 'básico') {
    $_SESSION['plan_pendiente'] = $plan;
    header("Location: procesar_pago.php");
    exit;
}

// Para cambio a plan básico (gratis)
try {
    $sql = "UPDATE clientes SET plan = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$plan, $_SESSION['user_id']]);
    
    $_SESSION['exito'] = "Plan actualizado a $plan correctamente";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al actualizar el plan: " . $e->getMessage();
}

header("Location: user_dashboard.php");
?>