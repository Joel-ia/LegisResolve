<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar inputs
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
    $contraparte = filter_input(INPUT_POST, 'contraparte', FILTER_SANITIZE_STRING);
    $valor_reclamado = filter_input(INPUT_POST, 'valor_reclamado', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Validaciones básicas
    if (empty($titulo) || empty($descripcion) || empty($categoria)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } else {
        try {
            // Determinar prioridad y categoría legal
            $prioridad = determinarPrioridad($descripcion);
            $categoria_legal = determinarCategoriaLegal($categoria, $descripcion);
            
            $sql = "INSERT INTO disputas 
                    (titulo, descripcion, categoria, usuario_id, estado, prioridad, categoria_legal, contraparte, valor_reclamado) 
                    VALUES (?, ?, ?, ?, 'pendiente', ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $titulo, 
                $descripcion, 
                $categoria, 
                $_SESSION['user_id'],
                $prioridad,
                $categoria_legal,
                $contraparte,
                $valor_reclamado
            ]);
            
            $disputa_id = $conn->lastInsertId();
            $success = 'Disputa creada exitosamente. Serás redirigido al chat de mediación.';
            
            // Redirigir después de 3 segundos
            header("Refresh: 3; url=mediacion_chat.php?disputa_id=".$disputa_id);
            
        } catch (PDOException $e) {
            $error = 'Error al crear la disputa: ' . $e->getMessage();
            error_log("Error al crear disputa: " . $e->getMessage());
        }
    }
}

// Funciones de ayuda
function determinarPrioridad($descripcion) {
    $urgentes = ['violencia', 'desalojo', 'despido', 'acoso', 'emergencia', 'restricción', 'amenaza'];
    $altas = ['contrato', 'pago', 'deuda', 'daños', 'incumplimiento', 'fraude', 'despid', 'embargo'];
    
    $descripcion = strtolower($descripcion);
    
    foreach ($urgentes as $palabra) {
        if (strpos($descripcion, $palabra) !== false) return 'urgente';
    }
    
    foreach ($altas as $palabra) {
        if (strpos($descripcion, $palabra) !== false) return 'alta';
    }
    
    return 'media';
}

function determinarCategoriaLegal($categoria, $descripcion) {
    // Mapeo de categorías a áreas legales más específicas
    $mapa_categorias = [
        'ecommerce' => 'Derecho Comercial',
        'laboral' => 'Derecho Laboral',
        'arrendamiento' => 'Derecho Civil',
        'otros' => 'Derecho General'
    ];
    
    // Si la categoría existe en el mapa, usarla
    if (isset($mapa_categorias[$categoria])) {
        return $mapa_categorias[$categoria];
    }
    
    // Análisis de palabras clave para categorías no mapeadas
    $descripcion = strtolower($descripcion);
    
    if (strpos($descripcion, 'laboral') !== false || strpos($descripcion, 'empleo') !== false) {
        return 'Derecho Laboral';
    }
    
    if (strpos($descripcion, 'arrend') !== false || strpos($descripcion, 'alquiler') !== false) {
        return 'Derecho Civil';
    }
    
    if (strpos($descripcion, 'compra') !== false || strpos($descripcion, 'venta') !== false) {
        return 'Derecho Comercial';
    }
    
    return 'Derecho General';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Disputa | LegisResolve</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dispute-form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .form-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #34495e;
        }
        
        .form-label .required {
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-submit {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .category-select {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .category-option {
            position: relative;
        }
        
        .category-option input {
            position: absolute;
            opacity: 0;
        }
        
        .category-option label {
            display: block;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #eee;
        }
        
        .category-option input:checked + label {
            background: #e3f2fd;
            border-color: #3498db;
            color: #3498db;
            font-weight: 500;
        }
        
        .error-message {
            color: #e74c3c;
            margin: 1rem 0;
            padding: 12px;
            background: #fdecea;
            border-radius: 6px;
            display: none;
        }
        
        .success-message {
            color: #27ae60;
            margin: 1rem 0;
            padding: 12px;
            background: #e8f8f0;
            border-radius: 6px;
            display: none;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .currency-input {
            display: flex;
            align-items: center;
        }
        
        .currency-input span {
            background: #f8f9fa;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .currency-input input {
            border-radius: 0 8px 8px 0 !important;
        }
    </style>
</head>
<body>
       
    <div class="dispute-form-container">
        <h1 class="form-title">Iniciar Nueva Disputa Legal</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message" style="display: block;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message" style="display: block;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php else: ?>
        
        <form method="POST" id="disputeForm">
            <div class="form-group">
                <label for="titulo" class="form-label">
                    Título de la disputa <span class="required">*</span>
                </label>
                <input type="text" id="titulo" name="titulo" class="form-control" required 
                       placeholder="Ej: Incumplimiento de contrato de arrendamiento"
                       value="<?= isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : '' ?>">
                <p class="form-hint">Describe brevemente la naturaleza de la disputa</p>
            </div>
            
            <div class="form-group">
                <label for="contraparte" class="form-label">
                    Contraparte (opcional)
                </label>
                <input type="text" id="contraparte" name="contraparte" class="form-control"
                       placeholder="Nombre de la persona/empresa con la que tienes la disputa"
                       value="<?= isset($_POST['contraparte']) ? htmlspecialchars($_POST['contraparte']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="valor_reclamado" class="form-label">
                    Valor reclamado (opcional)
                </label>
                <div class="currency-input">
                    <span>Q</span>
                    <input type="number" id="valor_reclamado" name="valor_reclamado" class="form-control"
                           step="0.01" min="0" placeholder="0.00"
                           value="<?= isset($_POST['valor_reclamado']) ? htmlspecialchars($_POST['valor_reclamado']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="descripcion" class="form-label">
                    Descripción detallada <span class="required">*</span>
                </label>
                <textarea id="descripcion" name="descripcion" class="form-control" required
                          placeholder="Describe los hechos, fechas relevantes, pruebas disponibles y cualquier otro detalle importante"><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                <p class="form-hint">Incluye toda la información relevante para ayudar a nuestros mediadores</p>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    Categoría principal <span class="required">*</span>
                </label>
                <div class="category-select">
                    <div class="category-option">
                        <input type="radio" id="cat-ecommerce" name="categoria" value="ecommerce" required
                               <?= (isset($_POST['categoria']) && $_POST['categoria'] === 'ecommerce') ? 'checked' : '' ?>>
                        <label for="cat-ecommerce">
                            <i class="fas fa-shopping-cart"></i> E-commerce
                        </label>
                    </div>
                    <div class="category-option">
                        <input type="radio" id="cat-laboral" name="categoria" value="laboral"
                               <?= (isset($_POST['categoria']) && $_POST['categoria'] === 'laboral') ? 'checked' : '' ?>>
                        <label for="cat-laboral">
                            <i class="fas fa-briefcase"></i> Laboral
                        </label>
                    </div>
                    <div class="category-option">
                        <input type="radio" id="cat-arrendamiento" name="categoria" value="arrendamiento"
                               <?= (isset($_POST['categoria']) && $_POST['categoria'] === 'arrendamiento') ? 'checked' : '' ?>>
                        <label for="cat-arrendamiento">
                            <i class="fas fa-home"></i> Arrendamiento
                        </label>
                    </div>
                    <div class="category-option">
                        <input type="radio" id="cat-arrendamiento" name="categoria" value="arrendamiento"
                               <?= (isset($_POST['categoria']) && $_POST['categoria'] === 'arrendamiento') ? 'checked' : '' ?>>
                        <label for="cat-arrendamiento">
                            <i class="fas fa-home"></i> Demanda Legal
                        </label>
                    </div>
                    <div class="category-option">
                        <input type="radio" id="cat-otros" name="categoria" value="otros"
                               <?= (isset($_POST['categoria']) && $_POST['categoria'] === 'otros') ? 'checked' : '' ?>>
                        <label for="cat-otros">
                            <i class="fas fa-ellipsis-h"></i> Otros
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-gavel"></i> Iniciar Proceso Legal
                </button>
                <p class="form-hint" style="margin-top: 1rem;">
                    Al enviar esta disputa, aceptas nuestros <a href="#">Términos de Servicio</a>
                </p>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Validación mejorada del formulario
        document.getElementById('disputeForm')?.addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const categoria = document.querySelector('input[name="categoria"]:checked');
            const errorElement = document.querySelector('.error-message');
            
            // Resetear mensajes de error
            errorElement.style.display = 'none';
            
            // Validar campos obligatorios
            if (!titulo || titulo.length < 10) {
                e.preventDefault();
                errorElement.textContent = 'El título debe tener al menos 10 caracteres';
                errorElement.style.display = 'block';
                document.getElementById('titulo').focus();
                return;
            }
            
            if (!descripcion || descripcion.length < 30) {
                e.preventDefault();
                errorElement.textContent = 'La descripción debe tener al menos 30 caracteres';
                errorElement.style.display = 'block';
                document.getElementById('descripcion').focus();
                return;
            }
            
            if (!categoria) {
                e.preventDefault();
                errorElement.textContent = 'Por favor seleccione una categoría';
                errorElement.style.display = 'block';
                return;
            }
        });
    </script>
</body>
<button class="btn-back" onclick="window.history.back()" title="Volver atrás">
  <i class="fas fa-arrow-left"></i>
</button>
</html>