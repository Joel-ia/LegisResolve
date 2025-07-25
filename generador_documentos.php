<?php
/**
 * Generador de Documentos Legales
 * 
 * Este archivo contiene funciones para la generación de documentos legales
 * con soporte para diferentes formatos, firmas digitales y plantillas personalizadas.
 */

// Verificar si la función ya existe para evitar redeclaración
if (!function_exists('generarDocumentoLegal')) {

    /**
     * Genera un documento legal profesional basado en parámetros
     * 
     * @param string $tipo Tipo de documento (contrato, demanda, carta_poder, etc.)
     * @param string $descripcion Descripción detallada del contenido
     * @param string|null $archivo_adjunto Ruta de archivos adjuntos relevantes
     * @param int $user_id ID del usuario solicitante
     * @param int $disputa_id ID de la disputa relacionada
     * @param string $formato Formato de salida (pdf, docx, html)
     * @param array|null $datos_firma Datos para firma digital [nombre, cargo, firma_img]
     * @return array|false Array con información del documento generado o false en error
     */
    function generarDocumentoLegal($tipo, $descripcion, $archivo_adjunto = null, $user_id, $disputa_id, $formato = 'pdf', $datos_firma = null) {
        global $conn;
        
        try {
            // 1. Validar parámetros de entrada
            if (empty($tipo) || empty($descripcion) || empty($user_id) || empty($disputa_id)) {
                throw new InvalidArgumentException("Parámetros requeridos faltantes");
            }

            // 2. Obtener datos de la disputa y usuario
            $disputa = obtenerDatosDisputa($disputa_id, $conn);
            $usuario = obtenerDatosUsuario($user_id, $conn);

            if (!$disputa || !$usuario) {
                throw new Exception("No se encontraron los datos necesarios");
            }

            // 3. Obtener plantilla base desde la base de datos
            $plantilla = obtenerPlantillaDocumento($tipo, $conn);
            
            if (!$plantilla) {
                throw new Exception("No se encontró plantilla para el tipo de documento solicitado");
            }

            // 4. Procesar plantilla con los datos
            $contenido = procesarPlantilla($plantilla['contenido'], [
                'descripcion' => $descripcion,
                'disputa' => $disputa,
                'usuario' => $usuario,
                'fecha' => date('d/m/Y'),
                'hora' => date('H:i:s')
            ]);

            // 5. Agregar referencias a archivos adjuntos si existen
            if ($archivo_adjunto) {
                $contenido .= "\n\nDocumentos adjuntos:\n- " . basename($archivo_adjunto);
            }

            // 6. Agregar firmas si se requieren
            if ($datos_firma) {
                $contenido = agregarMarcasFirma($contenido, $datos_firma);
            }

            // 7. Generar documento en el formato solicitado
            $documento = generarDocumentoFormato($contenido, $formato, $tipo, $datos_firma);

            // 8. Guardar registro en base de datos
            guardarRegistroDocumento($user_id, $disputa_id, $tipo, $documento['ruta'], $conn);

            return $documento;

        } catch (Exception $e) {
            error_log("Error al generar documento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los datos de una disputa desde la base de datos
     */
    function obtenerDatosDisputa($disputa_id, $conn) {
        $sql = "SELECT id, titulo, descripcion, fecha_resolucion, acuerdo_final 
                FROM disputas 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$disputa_id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene los datos del usuario desde la base de datos
     */
    function obtenerDatosUsuario($user_id, $conn) {
        $sql = "SELECT id, nombre, email, dni 
                FROM usuarios 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene la plantilla del documento desde la base de datos
     */
    function obtenerPlantillaDocumento($tipo, $conn) {
        $sql = "SELECT id, tipo, contenido, version 
                FROM plantillas_documentos 
                WHERE tipo = ? AND activa = 1 
                ORDER BY version DESC 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$tipo]);
        return $stmt->fetch();
    }

    /**
     * Procesa la plantilla reemplazando marcadores con datos reales
     */
    function procesarPlantilla($plantilla, $datos) {
        // Reemplazar marcadores básicos {{variable}}
        foreach ($datos as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $plantilla = str_replace("{{{$key}.{$subkey}}}", $subvalue, $plantilla);
                }
            } else {
                $plantilla = str_replace("{{{$key}}}", $value, $plantilla);
            }
        }

        // Aquí podríamos añadir procesamiento con IA para mejorar el contenido
        return $plantilla;
    }

    /**
     * Agrega marcas de firma al documento
     */
    function agregarMarcasFirma($contenido, $datos_firma) {
        $firmas = "\n\nFIRMAS:\n";
        $firmas .= "Nombre: {$datos_firma['nombre']}\n";
        $firmas .= "Cargo: {$datos_firma['cargo']}\n";
        
        if (!empty($datos_firma['firma_img'])) {
            $firmas .= "[Imagen de firma digital: {$datos_firma['firma_img']}]\n";
        }
        
        return $contenido . $firmas;
    }

    /**
     * Genera el documento en el formato especificado
     */
    function generarDocumentoFormato($contenido, $formato, $tipo_documento, $datos_firma = null) {
        $nombre_archivo = "documento_{$tipo_documento}_" . uniqid() . ".{$formato}";
        $ruta_archivo = "documentos/generados/" . $nombre_archivo;
        
        // Crear directorio si no existe
        if (!file_exists("documentos/generados")) {
            mkdir("documentos/generados", 0755, true);
        }

        switch (strtolower($formato)) {
            case 'pdf':
                // En producción usar librería como TCPDF o Dompdf
                file_put_contents($ruta_archivo, $contenido);
                break;
                
            case 'docx':
                // Usar librería como PHPWord en producción
                file_put_contents($ruta_archivo, $contenido);
                break;
                
            case 'html':
                $html = "<!DOCTYPE html><html><head><title>Documento Legal</title></head><body><pre>$contenido</pre></body></html>";
                file_put_contents($ruta_archivo, $html);
                break;
                
            default:
                throw new Exception("Formato de documento no soportado");
        }

        return [
            'archivo' => $nombre_archivo,
            'ruta' => $ruta_archivo,
            'formato' => $formato,
            'tamano' => filesize($ruta_archivo)
        ];
    }

    /**
     * Guarda el registro del documento en la base de datos
     */
    function guardarRegistroDocumento($user_id, $disputa_id, $tipo, $ruta_archivo, $conn) {
        $sql = "INSERT INTO documentos 
                (user_id, disputa_id, tipo, ruta_archivo, fecha_creacion) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$user_id, $disputa_id, $tipo, $ruta_archivo]);
    }
}