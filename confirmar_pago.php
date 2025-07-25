<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['plan_pendiente'])) {
    header("Location: user_dashboard.php");
    exit;
}

// Aquí iría la lógica real de procesamiento de pago con la pasarela de pago
// Esta es una simulación

$plan = $_SESSION['plan_pendiente'];
$user_id = $_SESSION['user_id'];

try {
    // 1. Registrar el pago
    $sql_pago = "INSERT INTO pagos (user_id, plan, monto, fecha) 
                VALUES (?, ?, ?, NOW())";
    $stmt_pago = $conn->prepare($sql_pago);
    
    $monto = ($plan === 'plus') ? 300 : 600;
    $stmt_pago->execute([$user_id, $plan, $monto]);
    
    // 2. Actualizar el plan del usuario
    $sql_plan = "UPDATE clientes SET plan = ? WHERE id = ?";
    $stmt_plan = $conn->prepare($sql_plan);
    $stmt_plan->execute([$plan, $user_id]);
    
    // 3. Registrar en el historial
    $sql_historial = "INSERT INTO historial_planes (user_id, plan_anterior, plan_nuevo, fecha) 
                     VALUES (?, (SELECT plan FROM clientes WHERE id = ?), ?, NOW())";
    $stmt_historial = $conn->prepare($sql_historial);
    $stmt_historial->execute([$user_id, $user_id, $plan]);
    
    unset($_SESSION['plan_pendiente']);
    $_SESSION['exito'] = "¡Pago procesado correctamente! Ahora tienes el plan " . ucfirst($plan);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al procesar el pago: " . $e->getMessage();
}

header("Location: user_dashboard.php");
?>