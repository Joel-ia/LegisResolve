<?php
function generarRespuestaIA($disputa_id, $mensaje_usuario, $conn) {
    error_log("Generando respuesta para mensaje: $mensaje_usuario");
    
    $datos_disputa = obtenerDatosDisputa($disputa_id, $conn);
    
    // Verificar si ya hay mediador asignado
    if ($datos_disputa['estado'] !== 'pendiente' && !empty($datos_disputa['mediador_id'])) {
        error_log("IA no responde - Disputa ya tiene mediador asignado");
        return null;
    }
    
    $historial = obtenerHistorialConversacion($disputa_id, $conn);
    $contexto = analizarContexto($mensaje_usuario, $historial);
    
    error_log("Contexto detectado: " . print_r($contexto, true));
    
    if (!is_array($contexto)) {
        $contexto = []; 
    }
    
    $respuesta = generarRespuestaContextual($contexto, $disputa_id, $conn);
    
    error_log("Respuesta generada: " . print_r($respuesta, true));

    $mensaje = strtolower(trim($mensaje_usuario));
    
    // Patrones de conversación
    $respuesta = [
        'mensaje' => "No he entendido tu solicitud. ¿Podrías reformularla?",
        'necesita_mediador' => false
    ];
    
    if (strpos($mensaje, 'generar documento') !== false || 
        strpos($mensaje, 'crear contrato') !== false) {
        return [
            'mensaje' => "Perfecto, puedo ayudarte a generar un contrato de compraventa profesional. Para personalizarlo correctamente, necesito algunos detalles:\n\n".
                         "1. ¿Qué tipo de bien se está vendiendo (casa, coche, equipo, etc.)?\n".
                         "2. ¿Tienes algún acuerdo especial sobre pagos o plazos?\n".
                         "3. ¿Necesitas incluir cláusulas específicas?\n\n".
                         "Puedo proporcionarte una plantilla inicial y luego ajustarla según tus necesidades.",
            'necesita_mediador' => false
        ];
    }
    
    if (preg_match('/precio|cost[eo]|tarifa|honorario/', $mensaje)) {
        $respuesta = [
            'mensaje' => "Nuestros servicios de mediación tienen estas opciones:\n\n".
                         "1. Revisión básica: \Q80\n".
                         "2. Redacción completa: \Q200\n".
                         "3. Mediación presencial: \Q120/hora\n\n".
                         "¿Qué servicio te interesa conocer en detalle?",
            'necesita_mediador' => false
        ];
    } 
    elseif (preg_match('/generar|crear|hacer|contrato|documento/', $mensaje)) {
        $respuesta = [
            'mensaje' => "Para generar tu contrato, necesito saber:\n\n".
                         "1. Tipo de bien (casa, coche, etc.)\n".
                         "2. Valor aproximado\n".
                         "3. ¿Tienes acuerdos especiales?",
            'necesita_mediador' => false
        ];
    }
    elseif (preg_match('/ayuda|soporte|problema/', $mensaje)) {
        $respuesta = [
            'mensaje' => "Puedo ayudarte con:\n\n".
                         "1. Explicación de cláusulas\n".
                         "2. Revisión de documentos\n".
                         "3. Conexión con mediadores\n\n".
                         "¿En qué necesitas asistencia exactamente?",
            'necesita_mediador' => true
        ];
    }

    return $respuesta;
}


function obtenerHistorialConversacion($disputa_id, $conn) {
    try {
        $sql = "SELECT mensaje, remitente_id, es_ia, es_mediador, fecha_envio 
                FROM mensajes_disputas 
                WHERE disputa_id = ? 
                ORDER BY fecha_envio DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$disputa_id]);
        $result = $stmt->fetchAll();
        return is_array($result) ? $result : [];
    } catch (PDOException $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        return [];
    }
}

    function analizarContexto($mensaje_actual, $historial) {
    // Asegurar que los parámetros sean válidos
    if (!is_string($mensaje_actual)) {
        $mensaje_actual = '';
    }
    
    if (!is_array($historial)) {
        $historial = [];
    }
    
    $contexto = [
        'tema_principal' => detectarTemaPrincipal($mensaje_actual, $historial),
        'intencion' => detectarIntencion($mensaje_actual),
        'necesita_info_adicional' => false,
        'urgencia' => 'normal'
    ];
    
   // Detección de urgencia
if (strpos(strtolower($mensaje_actual), 'urgente') !== false) {
    $contexto['urgencia'] = 'alta';
}

// Detección de necesidad de información
if (preg_match('/\?$/', trim($mensaje_actual))) {
    $contexto['necesita_info_adicional'] = true;
}
    
    return $contexto;
}
function detectarTemaPrincipal($mensaje_actual, $historial) {
    // Palabras clave para temas
    $temas = [
        'contrato' => ['contrato', 'cláusula', 'firma', 'documento'],
        'pago' => ['pago', 'dinero', 'deuda', 'impago', 'reembolso'],
        'incumplimiento' => ['incumplimiento', 'violación', 'romper', 'no cumplió'],
        'daños' => ['daño', 'perjuicio', 'prejuicio', 'afectó']
    ];
    
   $mensaje_min = strtolower($mensaje_actual);
    
    foreach ($temas as $tema => $palabras) {
        foreach ($palabras as $palabra) {
            if (strpos($mensaje_min, strtolower($palabra)) !== false) {
                return $tema;
            }
        }
    }
    
    return 'general';
}

function generarRespuestaContextual($contexto, $disputa_id, $conn) {
    // Asegurar que $contexto tenga todas las claves necesarias
    $contexto = array_merge([
        'tema_principal' => 'general',
        'necesita_info_adicional' => false,
        'urgencia' => 'normal',
        'intencion' => 'consulta'
    ], $contexto);
    
    // Manejar saludos
    if ($contexto['intencion'] === 'saludo') {
        return [
            'mensaje' => "¡Hola! Soy el asistente virtual de LegisResolve. ¿En qué puedo ayudarte hoy?",
            'necesita_mediador' => false
        ];
    }
    
    // Manejar urgencias
    if ($contexto['urgencia'] === 'alta') {
        return [
            'mensaje' => "Entiendo que se trata de un asunto urgente. He activado el protocolo de prioridad alta y asignaré un mediador especializado inmediatamente.",
            'necesita_mediador' => true
        ];
    }
    
    // Respuestas según tema principal
    switch ($contexto['tema_principal']) {
        case 'contrato':
           return [
            'mensaje' => "Un contrato de compraventa es un acuerdo legal donde el vendedor transfiere la propiedad de un bien al comprador a cambio de un precio determinado. ¿Necesitas ayuda con algún aspecto específico de este tipo de contrato?",
            'necesita_mediador' => false
        ];
        case 'pago':
            return manejarTemaPago($contexto);
        case 'incumplimiento':
            return manejarTemaIncumplimiento($contexto);
        default:
            return manejarTemaGeneral($contexto);
    }
}

function detectarIntencion($mensaje) {
    $mensaje = strtolower(trim($mensaje));
    
    $intenciones = [
        'saludo' => ['hola', 'buenas', 'saludos', 'buenos días', 'buenas tardes'],
        'consulta' => ['pregunta', 'duda', 'consultar', 'qué', 'cómo', 'cuándo', 'asesoría', 'ayuda'],
        'problema' => ['problema', 'error', 'no funciona', 'incorrecto'],
        'urgencia' => ['urgente', 'inmediato', 'ahora', 'rápido'],
        'documento' => ['documento', 'contrato', 'firmar', 'pdf', 'archivo']
    ];
    
    foreach ($intenciones as $intencion => $palabras) {
        foreach ($palabras as $palabra) {
            if (strpos($mensaje, $palabra) !== false) {
                return $intencion;
            }
        }
    }
    
    return 'general';
}
function manejarTemaGeneral($contexto) {
    $respuestas = [
        "He recibido tu mensaje. ¿Podrías contarme más detalles sobre tu consulta?",
        "Entendido. Para ayudarte mejor, ¿podrías describir tu situación con más detalle?",
        "Gracias por contactarnos. Estamos aquí para ayudarte. ¿Qué tipo de asesoría necesitas?"
    ];
    
    return [
        'mensaje' => $respuestas[array_rand($respuestas)],
        'necesita_mediador' => false
    ];
}
function manejarTemaContrato($contexto, $disputa) {
    $respuestas = [
        "Estamos analizando los términos del contrato relacionado con tu caso '{$disputa['titulo']}'. ",
        "Para ayudarte con el contrato, necesitaré revisar las cláusulas específicas. ",
        "Sobre el tema contractual, es importante verificar las obligaciones de cada parte. "
    ];
    
    $preguntas = [
        "¿Podrías indicarme qué cláusula específica del contrato te preocupa?",
        "¿Tienes una copia del contrato que puedas compartir para analizarlo?",
        "¿Qué aspecto específico del contrato necesitas aclarar?"
    ];
    
    $mensaje = $respuestas[array_rand($respuestas)] . $preguntas[array_rand($preguntas)];
    
    return [
        'mensaje' => $mensaje,
        'necesita_mediador' => false
    ];
}

// ... (funciones similares para otros temas)

function obtenerDatosDisputa($disputa_id, $conn) {
    $sql = "SELECT titulo, categoria, estado FROM disputas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$disputa_id]);
    return $stmt->fetch();
}


function asignarMediador($disputa_id, $conn) {
    // Primero verifica si la tabla mediadores existe
    $sql_check = "SHOW TABLES LIKE 'mediadores'";
    $stmt_check = $conn->query($sql_check);
    if($stmt_check->rowCount() == 0) {
        error_log("Tabla mediadores no existe");
        return false;
    }

    // Obtener prioridad de la disputa
   // Obtener la categoría de la disputa
$sql_categoria = "SELECT categoria FROM disputas WHERE id = ?";
$stmt_categoria = $conn->prepare($sql_categoria);
$stmt_categoria->execute([$disputa_id]);
$categoria = $stmt_categoria->fetchColumn();

// Buscar mediador con especialidad coincidente
$sql = "SELECT m.id 
        FROM mediadores m
        WHERE m.disponibilidad = 1
        AND (m.especialidad = ? OR m.especialidad IS NULL)
        ORDER BY RAND() 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$categoria]);

    // Si no tiene prioridad, asignar una por defecto
    if(!isset($disputa['prioridad'])) {
        $disputa['prioridad'] = 'media';
    }

    // Buscar mediador más adecuado
    $sql = "SELECT m.id 
            FROM mediadores m
            WHERE m.disponibilidad = 1
            ORDER BY RAND() 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $mediador = $stmt->fetch();

    if ($mediador) {
        try {
            $conn->beginTransaction();
            
            // Asignar mediador a la disputa
            $sql_update = "UPDATE disputas SET mediador_id = ?, estado = 'asignada' WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([$mediador['id'], $disputa_id]);
            
            $conn->commit();
            return $mediador['id'];
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Error al asignar mediador: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

function notificarMediador($mediador_id, $disputa_id, $conn) {
    // Obtener datos del mediador
    $sql = "SELECT email FROM mediadores WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$mediador_id]);
    $mediador = $stmt->fetch();
    
    if ($mediador) {
        // Enviar correo de notificación (implementar función de envío de email)
        $asunto = "Nuevo caso asignado - LegisResolve";
        $mensaje = "Tienes un nuevo caso asignado. Por favor inicia sesión para atenderlo.";
        // enviarEmail($mediador['email'], $asunto, $mensaje);
        
        // También podrías registrar la notificación en la base de datos
        $sql_notif = "INSERT INTO notificaciones (usuario_id, tipo, contenido) VALUES (?, 'email', ?)";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->execute([$mediador_id, $mensaje]);
    }
}

function manejarTemaPago($contexto) {
    return [
        'mensaje' => "Sobre el tema de pagos, ¿podrías especificar el monto y la fecha del pago en cuestión?",
        'necesita_mediador' => false
    ];
}

function manejarTemaIncumplimiento($contexto) {
    return [
        'mensaje' => "Para abordar el incumplimiento, necesitaré más detalles sobre qué cláusula o acuerdo específico no se está cumpliendo.",
        'necesita_mediador' => true
    ];
}

function solicitarInformacionEspecifica($contexto) {
    $preguntas = [
        "Para ayudarte mejor, ¿podrías proporcionar más detalles sobre tu consulta?",
        "¿Qué información adicional necesitas sobre este tema?",
        "¿Hay algún aspecto específico que te gustaría que aclaremos?"
    ];
    
    return [
        'mensaje' => $preguntas[array_rand($preguntas)],
        'necesita_mediador' => false
    ];
}

?>