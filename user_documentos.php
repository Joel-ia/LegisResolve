<?php 
$page_title = "Panel de Usuario";
require_once 'includes/head.php'; 
?>

<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/generador_documentos.php'; // Archivo centralizado para generación de documentos

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que el usuario tiene disputas resueltas para generar documentos
    $sql = "SELECT id FROM disputas WHERE usuario_id = ? AND estado = 'resuelta'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $disputa_resuelta = $stmt->fetch();
    $tipo_generacion = $_POST['tipo_generacion'] ?? 'borrador';
    $datos_firma = null;
    
    if ($tipo_generacion === 'oficial') {
        $datos_firma = [
            'nombre' => $_POST['nombre_firmante'] ?? '',
            'cargo' => $_POST['cargo_firmante'] ?? ''
        ];
    }
    
    if (!$disputa_resuelta) {
        $_SESSION['error'] = "Debes tener al menos una disputa resuelta para generar documentos legales";
        header("Location: user_documentos.php");
        exit;
    }
    
    // Procesar subida de archivos
    $targetDir = "uploads/";
    $fileName = uniqid() . '_' . basename($_FILES["pruebas"]["name"]);
    $filePath = $targetDir . $fileName;
    
    // Validar archivo
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $fileType = mime_content_type($_FILES["pruebas"]["tmp_name"]);
    
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error'] = "Solo se permiten archivos PDF, JPG o PNG";
        header("Location: user_documentos.php");
        exit;
    }
    
    if ($_FILES["pruebas"]["size"] > 5242880) { // 5MB
        $_SESSION['error'] = "El archivo es demasiado grande (máximo 5MB)";
        header("Location: user_documentos.php");
        exit;
    }
    
    if (move_uploaded_file($_FILES["pruebas"]["tmp_name"], $filePath)) {
        // Preparar datos para el documento
        $datos_documento = [
            'tipo_documento' => $_POST['tipo_documento'],
            'datos' => $_POST['datos'],
            'archivos' => [$filePath],
            'user_id' => $_SESSION['user_id'],
            'disputa_id' => $disputa_resuelta['id'],
            'tipo_generacion' => $tipo_generacion,
            'datos_firma' => $datos_firma
        ];
        
        // Generar documento legal
        $documento_generado = generarDocumentoLegal($datos_documento);
        
        if ($documento_generado) {
            // Guardar en base de datos
            $sql = "INSERT INTO documentos (user_id, tipo, archivo, descripcion, disputa_id, es_borrador) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $es_borrador = ($tipo_generacion === 'borrador') ? 1 : 0;
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['tipo_documento'],
                $documento_generado['archivo'],
                $_POST['datos'],
                $disputa_resuelta['id'],
                $es_borrador
            ]);
            
            header("Location: documento_generado.php?id=" . $conn->lastInsertId());
            exit;
        } else {
            $_SESSION['error'] = "Error al generar el documento legal";
            header("Location: user_documentos.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Error al subir el archivo";
        header("Location: user_documentos.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
        /* ===== PALETA DE COLORES ===== */
        :root {
            --primary: #150CE1;       /* Azul corporativo principal */
            --primary-light: #4299e1;  /* Azul más claro */
            --primary-dark: #0D2142;   /* Azul oscuro */
            --accent: #CC3800;         /* Naranja/accent */
            --accent-light: #f6ad55;   /* Naranja claro */
            --success: #38a169;        /* Verde */
            --danger: #e53e3e;         /* Rojo */
            --warning: #dd6b20;        /* Amarillo/naranja */
            --light: #f8f9fa;          /* Fondo claro */
            --light-gray: #e2e8f0;     /* Gris claro para bordes */
            --dark: #1a202c;           /* Texto oscuro */
            --gray-200: #e2e8f0;       /* Gris para fondos */
            --gray-500: #718096;       /* Gris medio */
            --gray-700: #4a5568;       /* Gris oscuro */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* ===== ESTILOS BASE ===== */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light);
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

        /* ===== TARJETA DE GENERADOR CENTRADA ===== */
        .document-generator {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--light-gray);
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        .document-header {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .document-header h2 {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }

        .document-header h2 i {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }

        .form-container {
            padding: 1.5rem;
        }

        /* ===== PASOS DEL FORMULARIO ===== */
        .form-step {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: white;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            box-shadow: var(--shadow-sm);
        }

        .form-step h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .form-step h3 i {
            margin-right: 0.5rem;
            color: var(--primary-light);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        /* ===== CONTROLES DE FORMULARIO ===== */
        .form-control, .form-select, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-control:focus, 
        .form-select:focus, 
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        /* ===== SUBIDA DE ARCHIVOS ===== */
        .file-upload {
            position: relative;
            margin-bottom: 1rem;
        }

        .file-upload-input {
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            position: absolute;
            z-index: -1;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            border: 2px dashed var(--light-gray);
            border-radius: 8px;
            background-color: var(--light);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            flex-direction: column;
        }

        .file-upload-label:hover {
            border-color: var(--primary-light);
            background-color: rgba(66, 153, 225, 0.05);
        }

        .file-upload-label i {
            font-size: 2rem;
            color: var(--primary-light);
            margin-bottom: 0.5rem;
        }

        .file-upload-label span {
            font-weight: 500;
            color: var(--primary);
        }

        .file-upload-label small {
            color: var(--gray-500);
            margin-top: 0.5rem;
        }

        .file-preview {
            margin-top: 1rem;
            display: none;
        }

        .file-preview-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background-color: var(--light);
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border: 1px solid var(--light-gray);
        }

        .file-preview-item i {
            margin-right: 0.75rem;
            color: var(--primary-light);
        }

        .file-preview-item-name {
            flex: 1;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ===== BOTONES ===== */
        .btn-generate {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 2rem;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            width: 100%;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            opacity: 0.9;
        }

        .btn-generate i {
            margin-right: 0.75rem;
        }

        /* ===== NOTAS Y MENSAJES ===== */
        .form-note {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            color: var(--danger);
        }

        /* ===== GRUPOS DE RADIO ===== */
        .form-radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .form-radio {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .form-radio input {
            margin-right: 0.75rem;
        }

        .form-radio span {
            font-weight: 500;
            color: var(--dark);
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        /* ===== BOTÓN DE RETROCESO ===== */
        .btn-back {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            transition: all 0.3s ease;
            border: 2px solid white;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: scale(1.1) translateY(-3px);
            background-color: var(--primary-dark);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .document-header {
                padding: 1.25rem;
            }
            
            .document-header h2 {
                font-size: 1.5rem;
            }
            
            .form-container {
                padding: 1rem;
            }
            
            .form-step {
                padding: 1.25rem;
            }
            
            .btn-back {
                bottom: 1rem;
                left: 1rem;
                width: 48px;
                height: 48px;
            }
        }

        @media (max-width: 576px) {
            .document-header h2 {
                font-size: 1.3rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .document-header h2 i {
                margin-right: 0;
            }
            
            .form-step {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
   
    <div class="container">
        <div class="document-generator">
            <div class="document-header">
                <h2>
                    <i class="fas fa-file-contract"></i>
                    Generador de Documentos Legales
                </h2>
            </div>
            
            <div class="form-container">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-step">
                        <h3><i class="fas fa-list-ul"></i> Tipo de Documento</h3>
                        <div class="form-group">
                            <label class="form-label">Seleccione el tipo de documento legal que necesita:</label>
                            <select name="tipo_documento" class="form-select" required>
                                <option value="" disabled selected>Seleccione una opción</option>
                                <option value="contrato">Contrato Legal</option>
                                <option value="demanda">Demanda Civil</option>
                                <option value="carta_poder">Carta Poder</option>
                                <option value="recurso_amparo">Recurso de Amparo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-step">
                        <h3><i class="fas fa-signature"></i> Tipo de Generación</h3>
                        <div class="form-group">
                            <label class="form-label">Seleccione el tipo de documento que necesita:</label>
                            <div class="form-radio-group">
                                <label class="form-radio">
                                    <input type="radio" name="tipo_generacion" value="borrador" checked>
                                    <span>Borrador (Plantilla básica sin firmar)</span>
                                </label>
                                <label class="form-radio">
                                    <input type="radio" name="tipo_generacion" value="oficial">
                                    <span>Documento oficial (Firmado digitalmente por abogado)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-step">
                        <h3><i class="fas fa-file-signature"></i> Firma Digital</h3>
                        <div class="form-group" id="firma-group" style="display:none;">
                            <label class="form-label">Datos para firma digital:</label>
                            <input type="text" name="nombre_firmante" class="form-control" placeholder="Nombre completo del firmante">
                            <input type="text" name="cargo_firmante" class="form-control mt-2" placeholder="Cargo/Representación">
                            <p class="form-note">* Solo aplica para documentos oficiales</p>
                        </div>
                    </div>
                    
                    <div class="form-step">
                        <h3><i class="fas fa-file-alt"></i> Detalles del Documento</h3>
                        <div class="form-group">
                            <label class="form-label">Describa los detalles necesarios para el documento:</label>
                            <textarea name="datos" class="form-textarea" required placeholder="Describa los términos, condiciones, partes involucradas y cualquier detalle relevante para el documento legal..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-step">
                        <h3><i class="fas fa-paperclip"></i> Documentos de Apoyo (Opcional)</h3>
                        <div class="form-group">
                            <label class="form-label">Adjunte documentos de apoyo (máx. 5MB):</label>
                            <div class="file-upload">
                                <input type="file" name="pruebas" id="file-input" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="file-input" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Arrastre archivos aquí o haga clic para seleccionar</span>
                                    <small>Formatos aceptados: PDF, JPG, PNG (máximo 5MB)</small>
                                </label>
                                <div id="file-preview" class="file-preview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-generate">
                        <i class="fas fa-magic"></i> Generar Documento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mostrar u ocultar campos de firma según selección
        document.querySelectorAll('input[name="tipo_generacion"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('firma-group').style.display = 
                    this.value === 'oficial' ? 'block' : 'none';
            });
        });
        
        // Mostrar vista previa de archivos seleccionados
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');
        
        fileInput.addEventListener('change', function() {
            filePreview.innerHTML = '';
            
            if (this.files.length > 0) {
                filePreview.style.display = 'block';
                
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-preview-item';
                    
                    let iconClass = 'fa-file';
                    if (file.type.includes('image')) iconClass = 'fa-file-image';
                    else if (file.type.includes('pdf')) iconClass = 'fa-file-pdf';
                    
                    fileItem.innerHTML = `
                        <i class="fas ${iconClass}"></i>
                        <span class="file-preview-item-name">${file.name}</span>
                        <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    `;
                    
                    filePreview.appendChild(fileItem);
                }
            } else {
                filePreview.style.display = 'none';
            }
        });
        
        // Permitir arrastrar y soltar archivos
        const dropArea = document.querySelector('.file-upload-label');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.style.borderColor = 'var(--primary-light)';
            dropArea.style.backgroundColor = 'rgba(66, 153, 225, 0.1)';
        }
        
        function unhighlight() {
            dropArea.style.borderColor = 'var(--gray-200)';
            dropArea.style.backgroundColor = 'var(--light)';
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            fileInput.files = dt.files;
            
            // Disparar evento change manualmente
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
        
         // Toggle tema oscuro/claro
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.createElement('button');
            themeToggle.className = 'theme-toggle';
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            themeToggle.style.position = 'fixed';
            themeToggle.style.bottom = '2rem';
            themeToggle.style.right = '2rem';
            themeToggle.style.zIndex = '1000';
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                if (document.body.classList.contains('dark-mode')) {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                }
            });
            
            document.body.appendChild(themeToggle);
        });
    </script>
    </script>
    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>
</body>
</html>