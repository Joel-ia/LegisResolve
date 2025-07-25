<?php
require_once 'config.php';

class LegisResolveAI {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4-turbo'; // Modelo más reciente
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
    }
    
    public function generarRespuestaChat($disputa_id, $mensaje_usuario, $conn) {
        // Obtener contexto de la disputa
        $contexto = $this->obtenerContextoDisputa($disputa_id, $conn);
        
        // Crear prompt estructurado
        $prompt = $this->crearPromptLegal($contexto, $mensaje_usuario);
        
        // Llamar a la API
        $respuesta = $this->llamarAPI($prompt);
        
        return $this->procesarRespuesta($respuesta);
    }
    
    private function obtenerContextoDisputa($disputa_id, $conn) {
        $sql = "SELECT d.titulo, d.descripcion, d.categoria, d.fecha_creacion,
                c.nombre AS nombre_cliente
                FROM disputas d
                JOIN clientes c ON d.usuario_id = c.id
                WHERE d.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$disputa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function crearPromptLegal($contexto, $mensaje_usuario) {
        return [
            [
                'role' => 'system',
                'content' => "Eres LegisResolve AI, un asistente legal experto. Analiza el mensaje del usuario y responde profesionalmente. Contexto:\n" .
                              "Título: {$contexto['titulo']}\n" .
                              "Categoría: {$contexto['categoria']}\n" .
                              "Cliente: {$contexto['nombre_cliente']}\n" .
                              "Descripción: {$contexto['descripcion']}\n\n" .
                              "Si el caso requiere un mediador humano, incluye '[NECESITA_MEDIADOR]' al final."
            ],
            [
                'role' => 'user',
                'content' => $mensaje_usuario
            ]
        ];
    }
    
    private function llamarAPI($messages) {
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function procesarRespuesta($api_response) {
        if (isset($api_response['choices'][0]['message']['content'])) {
            $respuesta = $api_response['choices'][0]['message']['content'];
            $necesitaMediador = strpos($respuesta, '[NECESITA_MEDIADOR]') !== false;
            
            return [
                'mensaje' => str_replace('[NECESITA_MEDIADOR]', '', $respuesta),
                'necesita_mediador' => $necesitaMediador,
                'status' => 'success'
            ];
        }
        
        return [
            'mensaje' => "Lo siento, estoy teniendo dificultades técnicas. Por favor intenta nuevamente más tarde.",
            'necesita_mediador' => true,
            'status' => 'error'
        ];
    }
    
    // Método para generación de documentos
    public function generarDocumentoLegal($tipo, $datos, $contexto) {
        // Implementación similar pero con prompt específico para documentos
        // ...
    }
}
?>