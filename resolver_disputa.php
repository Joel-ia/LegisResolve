<?php
session_start();
require_once 'includes/conexion.php';

// 1. Validación de seguridad reforzada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: auth_login.php");
    exit;
}

// 2. Verificar que el usuario es mediador
if ($_SESSION['user_role'] !== 'mediador') {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// 3. Validar y sanitizar el input
$disputa_id = filter_input(INPUT_POST, 'disputa_id', FILTER_VALIDATE_INT);
if (!$disputa_id || $disputa_id < 1) {
    die("ID de disputa no válido");
}

try {
    // 4. Consulta SQL segura con verificación de asignación
    $sql = "UPDATE disputas 
            SET estado = 'resuelta', 
                fecha_resolucion = NOW()
            WHERE id = ? 
            AND mediador_id = ? 
            AND estado != 'resuelta'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$disputa_id, $_SESSION['user_id']]);

    // 5. Manejo de resultados
    if ($stmt->rowCount() > 0) {
        // Éxito - redirigir al chat (sin caracteres especiales en la URL)
        header("Location: mediacion_chat.php?disputa_id=" . urlencode($disputa_id));
        exit;
    } else {
        // Posibles causas del fallo
        $error_info = $stmt->errorInfo();
        error_log("Error al resolver disputa: " . print_r($error_info, true));
        
        // Verificar si la disputa existe y está asignada
        $sql_check = "SELECT 1 FROM disputas 
                     WHERE id = ? AND mediador_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$disputa_id, $_SESSION['user_id']]);
        
        if (!$stmt_check->fetch()) {
            die("No tienes permisos sobre esta disputa o no existe");
        } else {
            die("La disputa ya estaba resuelta");
        }
    }
} catch (PDOException $e) {
    // 6. Manejo de errores de base de datos
    error_log("Error PDO: " . $e->getMessage());
    die("Ocurrió un error al procesar la solicitud. Por favor intente más tarde.");
}