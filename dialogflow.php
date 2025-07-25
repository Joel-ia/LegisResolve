<?php
function procesarMensajeDialogflow($input) {
    $intent = $input['queryResult']['intent']['displayName'] ?? '';
    $params = $input['queryResult']['parameters'] ?? [];
    
    switch ($intent) {
        case 'Consulta_Contrato':
            $tipo = $params['tipo_contrato'] ?? 'desconocido';
            return [
                'fulfillmentText' => "He registrado tu problema con contrato de $tipo. Número de caso: " . generarNumeroCaso(),
                'source' => 'webhook'
            ];
        default:
            return ['fulfillmentText' => "Lo siento, no entendí. Por favor reformula tu pregunta."];
    }
}

function generarNumeroCaso() {
    return 'CASE-' . strtoupper(uniqid());
}
function consultarGPT4($mensaje, $contexto = "") {
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = [
        "Authorization: Bearer " . OPENAI_API_KEY,
        "Content-Type: application/json"
    ];

    $data = [
        "model" => "gpt-4",
        "messages" => [
            ["role" => "system", "content" => "Eres un asistente legal especializado en mediación. Responde de forma clara y profesional."],
            ["role" => "user", "content" => "$contexto\nPregunta: $mensaje"]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response)->choices[0]->message->content;
}
}
?>