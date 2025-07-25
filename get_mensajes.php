<?php
session_start();
require_once 'includes/conexion.php';

// Obtener ID de disputa
$disputa_id = isset($_GET['disputa_id']) ? (int)$_GET['disputa_id'] : 0;

// Verificaciones
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Sesión no válida']));
}

if ($disputa_id < 1) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'ID de disputa no especificado']));
}

// Verificar acceso
try {
    $stmt = $conn->prepare("SELECT 1 FROM disputas 
                          WHERE id = ? AND (usuario_id = ? OR mediador_id = ?) 
                          LIMIT 1");
    $stmt->execute([$disputa_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        header('Content-Type: application/json');
        die(json_encode(['error' => 'No tienes acceso a esta disputa']));
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Error de base de datos']));
}

// Obtener y mostrar mensajes
try {
    $sql = "SELECT m.*, 
           COALESCE(c.nombre, 'Sistema') AS remitente_nombre,
           CASE 
               WHEN m.es_ia = 1 THEN 'IA'
               WHEN m.es_mediador = 1 THEN 'Mediador'
               WHEN m.es_sistema = 1 THEN 'Sistema'
               ELSE 'Cliente'
           END AS tipo_remitente
           FROM mensajes_disputas m
           LEFT JOIN clientes c ON m.remitente_id = c.id
           WHERE m.disputa_id = ?
           ORDER BY m.fecha_envio ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$disputa_id]);
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($mensajes)) {
        echo '<div class="no-messages">No hay mensajes todavía</div>';
        exit;
    }
    
    foreach ($mensajes as $mensaje) {
        $clase = ($mensaje['es_mediador'] || $mensaje['es_ia']) ? 'message-mediator' : 'message-user';
        echo '<div class="message '.$clase.'">';
        echo '<div class="message-content">';
        echo '<div class="message-sender">';
        
        if ($mensaje['es_ia']) {
            echo '<i class="fas fa-robot"></i> Asistente Legal IA';
        } elseif ($mensaje['es_mediador']) {
            echo '<i class="fas fa-user-tie"></i> Mediador';
        } elseif ($mensaje['es_sistema']) {
            echo '<i class="fas fa-info-circle"></i> Sistema';
        } else {
            echo '<i class="fas fa-user"></i> '.htmlspecialchars($mensaje['remitente_nombre'] ?? 'Tú');
        }
        
        echo '</div>';
        echo '<p>'.htmlspecialchars($mensaje['mensaje']).'</p>';
        echo '<span class="message-time">';
        echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio']));
        echo '</span>';
        echo '</div></div>';
    }
    
} catch (PDOException $e) {
    error_log("Error en get_messages: " . $e->getMessage());
    echo '<div class="error">Error al cargar mensajes</div>';
}
?>