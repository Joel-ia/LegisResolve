<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

$disputa_id = (int)($_GET['disputa_id'] ?? $_POST['disputa_id'] ?? $_SESSION['current_disputa_id'] ?? 0);
if ($disputa_id < 1) {
    die("ID de disputa inválido. Por favor, accede a través del panel de usuario.");
}
$_SESSION['current_disputa_id'] = $disputa_id;

// Verificar rol
if (!in_array($_SESSION['user_role'], ['cliente', 'mediador'])) {
    die("No tienes permisos para acceder a esta función");
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/conexion.php';
require_once 'ia_chat.php';

// Verificar acceso a la disputa
$sql = "SELECT d.id, d.titulo, d.estado, d.usuario_id, d.mediador_id,
        c.nombre AS clientes_nombre
        FROM disputas d
        JOIN clientes c ON d.usuario_id = c.id
        WHERE d.id = ? AND (d.usuario_id = ? OR d.mediador_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$disputa_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$disputa = $stmt->fetch();

if (!$disputa) {
    die("No tienes acceso a esta disputa o no existe");
}

// Procesar mensaje nuevo (solo si es POST y tiene mensaje)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mensaje'])) {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF inválido");
    }

    // Filtrar mensaje
    $mensaje = trim(filter_var($_POST['mensaje'], FILTER_SANITIZE_STRING));
    if (empty($mensaje)) {
        die("El mensaje no puede estar vacío");
    }

   try {
    $conn->beginTransaction();
    
    // Guardar mensaje del usuario
    $stmt = $conn->prepare("INSERT INTO mensajes_disputas 
                          (disputa_id, remitente_id, es_mediador, mensaje) 
                          VALUES (?, ?, ?, ?)");
    $is_mediador = ($_SESSION['user_role'] === 'mediador') ? 1 : 0;
    $stmt->execute([$disputa_id, $_SESSION['user_id'], $is_mediador, $mensaje]);

    // Llamar a la IA solo si el mensaje no viene de un mediador
    if ($_SESSION['user_role'] !== 'mediador') {
        require_once 'ia_chat.php';
        $respuesta_ia = generarRespuestaIA($disputa_id, $mensaje, $conn);

        if ($respuesta_ia && !empty($respuesta_ia['mensaje'])) {
            $stmt_ia = $conn->prepare("INSERT INTO mensajes_disputas 
                                     (disputa_id, remitente_id, es_ia, mensaje) 
                                     VALUES (?, NULL, 1, ?)");
            if (!$stmt_ia) {
                throw new Exception("Error al preparar consulta IA: " . $conn->errorInfo()[2]);
            }
            $stmt_ia->execute([$disputa_id, $respuesta_ia['mensaje']]);
        // Asignar mediador si es necesario
        if ($respuesta_ia['necesita_mediador']) {
            $mediador_id = asignarMediador($disputa_id, $conn);
            if ($mediador_id) {
                notificarMediador($mediador_id, $disputa_id, $conn);
            }
        }
    }
}
        
        
    $conn->commit();
    
    // Si es AJAX, responder con JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
    

} catch (PDOException $e) {
    $conn->rollBack();
    die("Error al guardar mensajes: " . $e->getMessage());
}


}

$mensajes = [];

try {
    $sql_mensajes = "SELECT m.*, 
            c.nombre AS remitente_nombre,
            IF(m.es_mediador, 'Mediador', 
               IF(m.es_ia, 'IA', 'Cliente')) AS tipo_remitente
     FROM mensajes_disputas m
     LEFT JOIN clientes c ON m.remitente_id = c.id
     WHERE m.disputa_id = ?
     ORDER BY m.fecha_envio ASC";
    $stmt = $conn->prepare($sql_mensajes);
    $stmt->execute([$disputa_id]);
    $mensajes = $stmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Error al cargar mensajes: " . $e->getMessage());
    $mensajes = [];
}

$progreso_data = actualizarProgresoDisputa($disputa_id, $conn);

function actualizarProgresoDisputa($disputa_id, $conn) {
    // Valores por defecto
    $resultado = [
        'estado' => 'pendiente',
        'etapa' => 'inicial',
        'progreso' => 0
    ];

    try {
        // 1. Obtener el estado actual de la disputa
        $sql_estado = "SELECT estado FROM disputas WHERE id = ?";
        $stmt_estado = $conn->prepare($sql_estado);
        $stmt_estado->execute([$disputa_id]);
        $estado_disputa = $stmt_estado->fetchColumn();

        // 2. Contar mensajes
        $sql_mensajes = "SELECT 
                        COUNT(*) as total_mensajes,
                        SUM(CASE WHEN es_mediador = 1 THEN 1 ELSE 0 END) as mensajes_mediador
                        FROM mensajes_disputas 
                        WHERE disputa_id = ?";
        $stmt = $conn->prepare($sql_mensajes);
        $stmt->execute([$disputa_id]);
        $datos = $stmt->fetch();

        // 3. Calcular progreso
        $progreso = 0;
        $etapa = 'inicial';
        
        if ($estado_disputa === 'pendiente') {
            $progreso = ($datos['total_mensajes'] > 0) ? 10 : 0;
            $etapa = 'inicial';
        } elseif ($estado_disputa === 'asignada') {
            $progreso = 25;
            $etapa = 'revision';
        } elseif ($estado_disputa === 'en_proceso') {
            $progreso = ($datos['mensajes_mediador'] > 3) ? 75 : 50;
            $etapa = ($datos['mensajes_mediador'] > 3) ? 'resolucion' : 'negociacion';
        } elseif ($estado_disputa === 'resuelta') {
            $progreso = 100;
            $etapa = 'finalizado';
        }

        // Actualizar resultado
        $resultado = [
            'estado' => $estado_disputa ?: 'pendiente',
            'etapa' => $etapa,
            'progreso' => $progreso
        ];

        // 4. Actualizar la base de datos
        $sql_update = "UPDATE disputas SET etapa = ?, progreso = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([$etapa, $progreso, $disputa_id]);

    } catch (PDOException $e) {
        error_log("Error en actualizarProgresoDisputa: " . $e->getMessage());
    }

    return $resultado;
}

// Cargar mensajes existentes
try {
    $sql_mensajes = "SELECT m.*, 
                c.nombre AS remitente_nombre,
                IF(m.es_mediador, 'Mediador', 
                   IF(m.es_ia, 'IA', 'Cliente')) AS tipo_remitente
         FROM mensajes_disputas m
         LEFT JOIN clientes c ON m.remitente_id = c.id
         WHERE m.disputa_id = ?
         ORDER BY m.fecha_envio ASC";
    $stmt = $conn->prepare($sql_mensajes);
    $stmt->execute([$disputa_id]);
    $mensajes = $stmt->fetchAll() ?: [];
} catch (PDOException $e) {
    error_log("Error al cargar mensajes: " . $e->getMessage());
    $mensajes = [];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Mediación | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
        /* ===== PALETA DE COLORES ===== */
        :root {
            --primary: #150CE1;       /* Azul corporativo principal */
            --primary-light: #4299e1;  /* Azul más claro */
            --primary-dark: #0D2142;   /* Azul oscuro */
            --accent: #CC3800;         /* Naranja/accent */
            --success: #38a169;        /* Verde */
            --danger: #e53e3e;         /* Rojo */
            --light: #FFFFFF;          /* Fondo claro */
            --light-gray: #e2e8f0;     /* Gris claro para bordes */
            --dark: #1a202c;           /* Texto oscuro */
            --gray-200: #e2e8f0;       /* Gris para fondos */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* ===== ESTILOS BASE ===== */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            width: 100%;
        }

        /* ===== CONTENEDOR DEL CHAT CENTRADO ===== */
        .chat-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--light-gray);
        }

        /* ===== CABECERA DEL CHAT ===== */
        .chat-header {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h2 {
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .case-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
        }

        /* ===== BARRA DE PROGRESO ===== */
        .case-progress {
            margin: 15px 0;
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
            border: 1px solid var(--light-gray);
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .progress-bar {
            height: 10px;
            background: var(--gray-200);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--success);
            transition: width 0.3s;
            width: 0%;
        }

        .progress-percent {
            text-align: right;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            color: var(--gray-500);
        }

        /* ===== MENSAJES DEL CHAT ===== */
        .chat-messages {
            height: 500px;
            overflow-y: auto;
            padding: 1.5rem;
            background: var(--light);
        }

        .message {
            display: flex;
            margin-bottom: 1.25rem;
            max-width: 80%;
        }

        .message-user {
            flex-direction: row-reverse;
            margin-left: auto;
        }

        .message-mediator {
            margin-right: auto;
        }

        .message-content {
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            position: relative;
            line-height: 1.5;
            box-shadow: var(--shadow-sm);
        }

        .message-user .message-content {
            background-color: var(--primary);
            color: white;
            border-top-right-radius: 0;
        }

        .message-mediator .message-content {
            background-color: white;
            border: 1px solid var(--light-gray);
            border-top-left-radius: 0;
        }

        .message-sender {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .message-user .message-sender {
            color: rgba(255, 255, 255, 0.9);
        }

        .message-mediator .message-sender {
            color: var(--primary);
        }

        .message-sender i {
            margin-right: 0.5rem;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.5rem;
            display: block;
            text-align: right;
        }

        /* ===== ÁREA DE ENTRADA ===== */
        .chat-input {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid var(--light-gray);
            display: flex;
            gap: 1rem;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem 1.25rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            resize: none;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        .btn-send {
            background: var(--primary);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-send:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ===== BOTONES ===== */
        .btn-dashboard {
            background-color: white;
            color: var(--primary);
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-dashboard:hover {
            background-color: var(--light);
            transform: translateY(-2px);
        }

        .btn-dashboard i {
            margin-right: 5px;
        }

        /* ===== SCROLLBAR ===== */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: var(--gray-200);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content {
                padding: 0;
            }
            
            .chat-container {
                border-radius: 0;
                height: 100vh;
                max-width: 100%;
            }
            
            .chat-messages {
                height: calc(100vh - 180px);
            }
            
            .message {
                max-width: 90%;
            }
            
            .chat-header {
                padding: 1rem;
            }
            
            .chat-header h2 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .progress-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .chat-input {
                padding: 1rem;
            }
            
            .btn-send {
                width: 45px;
                height: 45px;
            }
        }
    </style>
</head>
<body>
      <?php include 'includes/sidebar.php'; ?>
      
  <div class="chat-container">
    <div class="chat-header">
        <div>
            <a href="user_dashboard.php" class="btn-dashboard">
                <i class="fas fa-arrow-left"></i> Panel
            </a>
            <h2><?= htmlspecialchars($disputa['titulo']) ?></h2>
        </div>
        <span class="case-status"><?= ucfirst(str_replace('_', ' ', $disputa['estado'])) ?></span>
    </div>

    <div class="case-progress">
        <div class="progress-info">
            <span>Estado: <?= ucfirst($progreso_data['estado'] ?? 'pendiente') ?></span>
            <span>Etapa: <?= ucfirst($progreso_data['etapa'] ?? 'inicial') ?></span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $progreso_data['progreso'] ?? 0 ?>%"></div>
        </div>
        <div class="progress-percent"><?= $progreso_data['progreso'] ?? 0 ?>% completado</div>
    </div>

    <div class="chat-messages" id="chat-messages">
        <?php foreach ($mensajes as $mensaje): ?>
            <div class="message <?= ($mensaje['es_mediador'] || $mensaje['es_ia']) ? 'message-mediator' : 'message-user' ?>">
                <div class="message-content">
                    <div class="message-sender">
                        <?php if ($mensaje['es_ia']): ?>
                            <i class="fas fa-robot"></i> Asistente Legal IA
                        <?php elseif ($mensaje['es_mediador']): ?>
                            <i class="fas fa-user-tie"></i> Mediador
                        <?php elseif ($mensaje['es_sistema']): ?>
                            <i class="fas fa-info-circle"></i> Sistema
                        <?php else: ?>
                            <i class="fas fa-user"></i> <?= htmlspecialchars($mensaje['remitente_nombre'] ?? 'Tú') ?>
                        <?php endif; ?>
                    </div>
                    <p><?= htmlspecialchars($mensaje['mensaje']) ?></p>
                    <span class="message-time">
                        <?= date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" class="chat-input" id="chat-form">
      <input type="hidden" name="disputa_id" value="<?= $disputa_id ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <textarea 
            name="mensaje" 
            class="message-input" 
            placeholder="Escribe tu mensaje..." 
            required
            rows="1"
            id="message-input"
        ></textarea>
        <button type="submit" class="btn-send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>
  </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Obtener el ID de disputa de múltiples fuentes
    var disputa_id = $('input[name="disputa_id"]').val() || <?= $disputa_id ?> || 0;
    
    if (!disputa_id || disputa_id < 1) {
        console.error('ID de disputa no válido:', disputa_id);
        $('#chat-messages').html('<div class="error">Error: Disputa no válida. Vuelve al panel.</div>');
        return;
    }

    // Configurar AJAX para mantener la sesión
    $.ajaxSetup({
        xhrFields: {
            withCredentials: true
        }
    });

    // Función mejorada para cargar mensajes
    function loadMessages() {
        console.log('Solicitando mensajes para disputa:', disputa_id);
        
        $.ajax({
            url: 'get_messages.php',
            type: 'GET',
            data: { 
                disputa_id: disputa_id,
                last_update: new Date().getTime() // Evitar caché
            },
    success: function(data) {
    if (typeof data === 'string' && data.length > 0) {
        $('#chat-messages').html(data);
        scrollToBottom();
    }
},
            error: function(xhr, status, error) {
                console.error('Error en AJAX:', status, error);
                showTemporaryError('Error al cargar mensajes. Reintentando...');
                setTimeout(loadMessages, 3000);
            }
        });
    }

    // Función para scroll al final
    function scrollToBottom() {
        var container = $('#chat-messages');
        container.scrollTop(container[0].scrollHeight);
    }

    // Función para mostrar errores temporales
    function showTemporaryError(message) {
        var errorDiv = $('#temp-error');
        if (errorDiv.length === 0) {
            errorDiv = $('<div id="temp-error" class="temp-error"></div>');
            $('#chat-messages').append(errorDiv);
        }
        errorDiv.text(message).fadeIn().delay(2000).fadeOut();
    }
    $mensaje_filtrado = filter_var(trim($_POST['mensaje']), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
if (empty($mensaje_filtrado)) {
    die("El mensaje no puede estar vacío");
}

    // Configurar el envío de mensajes
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        if (!$('#message-input').val().trim()) return;
        
        $.ajax({
            url: 'mediacion_chat.php',
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.btn-send').prop('disabled', true);
            },
            success: function() {
                $('#message-input').val('');
                loadMessages();
            },
            error: function(xhr) {
                showTemporaryError('Error al enviar: ' + xhr.responseText);
            },
            complete: function() {
                $('.btn-send').prop('disabled', false);
            }
        });
    });

    // Iniciar carga de mensajes
    loadMessages();
    setInterval(loadMessages, 3000); // Actualizar cada 3 segundos
});
</script>
</body>
</html>