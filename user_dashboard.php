<?php
$page_title = "Panel de Usuario";
require_once 'includes/head.php';

session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

// Obtener datos del usuario
$sql = "SELECT nombre, email, plan FROM clientes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Obtener disputas activas del usuario
$sql_disputas = "SELECT id, titulo, estado FROM disputas 
                WHERE usuario_id = ? AND estado IN ('pendiente', 'asignada', 'en_proceso')
                ORDER BY fecha_creacion DESC LIMIT 1";
$stmt_disputas = $conn->prepare($sql_disputas);
$stmt_disputas->execute([$_SESSION['user_id']]);
$disputa_activa = $stmt_disputas->fetch(PDO::FETCH_ASSOC);

// Contar documentos y disputas
$total_documentos = 0;
$total_disputas = 0;

try {
    $sql_count = "SELECT 
                 (SELECT COUNT(*) FROM documentos_disputa WHERE user_id = ?) as total_docs,
                 (SELECT COUNT(*) FROM disputas WHERE usuario_id = ? AND estado != 'resuelta') as total_disp";
    
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $counts = $stmt_count->fetch(PDO::FETCH_ASSOC);

    $total_documentos = $counts['total_docs'] ?? 0;
    $total_disputas = $counts['total_disp'] ?? 0;

} catch (PDOException $e) {
    $total_documentos = 0;
    $total_disputas = 0;
    error_log("Error al contar documentos: " . $e->getMessage());
}

// Obtener información del plan actual
$plan_actual = $user['plan'] ?? 'básico';
$limites_plan = [
    'básico' => [
        'chatbot_horas' => 2,
        'consultas_dia' => 2,
        'documentos_dia' => 3,
        'mensajes_abogados' => false,
        'mediacion_instantanea' => false
    ],
    'plus' => [
        'chatbot_horas' => 5,
        'consultas_dia' => 5,
        'documentos_dia' => 10,
        'mensajes_abogados' => true,
        'mediacion_instantanea' => false
    ],
    'premium' => [
        'chatbot_horas' => 'ilimitadas',
        'consultas_dia' => 'ilimitadas',
        'documentos_dia' => 'ilimitados',
        'mensajes_abogados' => true,
        'mediacion_instantanea' => true
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== PALETA DE COLORES ACTUALIZADA ===== */
        :root {
            --primary: #150CE1;       /* Azul corporativo principal */
            --primary-light: #4299e1; /* Azul más claro */
            --primary-dark: #0D2142;  /* Azul oscuro */
            --accent: #CC3800;        /* Naranja/accent */
            --accent-light: #f6ad55;  /* Naranja claro */
            --success: #38a169;       /* Verde */
            --danger: #e53e3e;        /* Rojo */
            --warning: #dd6b20;       /* Amarillo/naranja */
            --light: #f8f9fa;         /* Fondo claro */
            --light-gray: #e2e8f0;    /* Gris claro para bordes */
            --dark: #1a202c;          /* Texto oscuro */
            --gray-200: #e2e8f0;      /* Gris para fondos */
            --gray-500: #718096;      /* Gris medio */
            --gray-700: #4a5568;      /* Gris oscuro */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* ===== ESTILOS BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* ===== CONTENIDO PRINCIPAL ===== */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px; /* Ancho del sidebar */
            transition: margin 0.3s ease;
        }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: var(--light);
            color: var(--primary);
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #fff5f5;
            color: var(--danger);
            border-color: #fed7d7;
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        /* ===== TARJETAS ===== */
        .welcome-card {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid var(--light-gray);
        }

        .welcome-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .welcome-card p {
            color: var(--gray-700);
            margin-bottom: 1.5rem;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
            border: 1px solid var(--light-gray);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-right: 1rem;
        }

        .card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .card-footer {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        /* ===== BOTONES ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-register {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-register i {
            margin-right: 8px;
        }

        .btn-register:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-new-case {
            background-color: var(--accent);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-new-case i {
            margin-right: 8px;
        }
        
        .btn-new-case:hover {
            background-color: #e04e00;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-small i {
            font-size: 0.875rem;
        }

        .btn-chat {
            background-color: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-chat:hover {
            background-color: var(--primary-dark);
        }

        /* ===== SECCIONES ===== */
        .disputa-section {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-gray);
        }

        .disputa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .disputa-actions {
            display: flex;
            gap: 10px;
        }

        .disputa-activa {
            background-color: var(--light);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .plans-section {
            margin-top: 2rem;
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-gray);
        }

        .plans-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        /* ===== PLANES ===== */
        .plan-card {
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid var(--light-gray);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background-color: white;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .plan-card.basic {
            border-color: var(--gray-200);
        }
        
        .plan-card.plus {
            border-color: var(--primary);
        }
        
        .plan-card.premium {
            border-color: var(--accent-light);
        }
        
        .plan-card.current {
            border-width: 3px;
            box-shadow: 0 0 0 3px rgba(21, 12, 225, 0.2);
        }
        
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .plan-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .plan-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .plan-features {
            margin: 1.5rem 0;
        }
        
        .plan-feature {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
        }
        
        .plan-feature i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .plan-feature .fa-check {
            color: var(--success);
        }
        
        .plan-feature .fa-times {
            color: var(--danger);
        }
        
        .plan-feature .fa-infinity {
            color: var(--primary);
        }
        
        .plan-btn {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            display: block;
            margin-top: 1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        
        .plan-btn.current {
            background-color: var(--success);
            color: white;
        }
        
        .plan-btn.upgrade {
            background-color: var(--primary);
            color: white;
        }
        
        .plan-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .current-plan-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--success);
            color: white;
            padding: 0.25rem 1rem;
            border-bottom-left-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
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

        /* ===== BOTÓN TEMA OSCURO/CLARO ===== */
        .theme-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
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
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1) translateY(-3px);
            background-color: var(--primary-dark);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .dashboard-cards {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .disputa-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .plans-container {
                grid-template-columns: 1fr;
            }
        }

        /* ===== TEMA OSCURO ===== */
        body.dark-mode {
            --light: #121212;
            --dark: #e1e1e1;
            --light-gray: #333;
            --gray-200: #2d2d2d;
            --gray-500: #a0a0a0;
            --gray-700: #e1e1e1;
            background-color: #121212;
        }

        body.dark-mode .welcome-card,
        body.dark-mode .dashboard-card,
        body.dark-mode .disputa-section,
        body.dark-mode .plans-section,
        body.dark-mode .plan-card {
            background-color: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .disputa-activa {
            background-color: #2d2d2d;
        }

        body.dark-mode .card-header h3,
        body.dark-mode .plan-title,
        body.dark-mode .plan-feature {
            color: var(--dark);
        }

        body.dark-mode .card-footer {
            color: var(--gray-500);
        }
    </style>

</head>
<body>
      
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-balance-scale"></i>
            <h2>LegisResolve</h2>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <h3><?= htmlspecialchars($user['nombre']) ?></h3>
                <p><?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>
        <?php include 'includes/sidebar.php'; ?>
        
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Panel de Usuario</h1>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar Sesión
            </a>
        </div>
        
        <div class="welcome-card">
            <h2>Bienvenido, <?= htmlspecialchars($user['nombre']) ?></h2>
            <p>Desde aquí puedes gestionar tus documentos legales, iniciar nuevos casos de mediación y administrar tu cuenta.</p>
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <a href="user_documentos.php" class="btn-register">
                    <i class="fas fa-file-contract"></i> Crear Documento
                </a>
                <a href="nueva_disputa.php" class="btn-new-case">
                    <i class="fas fa-gavel"></i> Iniciar Nuevo Caso
                </a>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Documentos</h3>
                </div>
                <div class="card-value"><?= $total_documentos ?></div>
                <div class="card-footer">
                    <?= $total_documentos > 0 ? 'Documentos subidos' : 'No tienes documentos' ?>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-comment-dots"></i>
                    <h3>Disputas Activas</h3>
                </div>
                <div class="card-value"><?= $total_disputas ?></div>
                <div class="card-footer">
                    <?= $total_disputas > 0 ? '1 requiere tu atención' : 'No tienes disputas activas' ?>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-clock"></i>
                    <h3>Próximas Reuniones</h3>
                </div>
                <div class="card-value"><?= $total_disputas > 0 ? 1 : 0 ?></div>
                <div class="card-footer">
                    <?= $total_disputas > 0 ? 'Mañana a las 10:00 AM' : 'No hay reuniones programadas' ?>
                </div>
            </div>
        </div>

        <div class="disputa-section">
            <div class="disputa-header">
                <h3><i class="fas fa-gavel"></i> Tus Casos Legales</h3>
                <div class="disputa-actions">
                    <a href="historial_disputas.php" class="btn-small" style="background: var(--gray-200); color: var(--dark);">
                        <i class="fas fa-history"></i> Historial
                    </a>
                    <a href="nueva_disputa.php" class="btn-small btn-new-case">
                        <i class="fas fa-plus"></i> Nuevo Caso
                    </a>
                </div>
            </div>
            
            <?php if ($disputa_activa): ?>
            <div class="disputa-activa">
                <h4><i class="fas fa-exclamation-circle"></i> Caso activo</h4>
                <p><strong><?= htmlspecialchars($disputa_activa['titulo']) ?></strong> - Estado: <?= ucfirst(str_replace('_', ' ', $disputa_activa['estado'])) ?></p>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="mediacion_chat.php?disputa_id=<?= $disputa_activa['id'] ?>" class="btn-chat">
                        <i class="fas fa-comments"></i> Ir al chat
                    </a>
                    <a href="detalles_disputa.php?id=<?= $disputa_activa['id'] ?>" class="btn-small" style="background: var(--gray-200); color: var(--dark);">
                        <i class="fas fa-info-circle"></i> Detalles
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-folder-open" style="font-size: 2rem; color: var(--gray-200); margin-bottom: 10px;"></i>
                <h4>No tienes casos activos</h4>
                <p>Puedes iniciar un nuevo caso legal haciendo clic en el botón "Nuevo Caso"</p>
                <a href="nueva_disputa.php" class="btn-new-case" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Iniciar Nuevo Caso
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="plans-section">
            <div class="plans-header">
                <h3><i class="fas fa-crown"></i> Tu Plan Actual</h3>
                <div class="disputa-actions">
                    <a href="historial_pagos.php" class="btn-small" style="background: var(--gray-200); color: var(--dark);">
                        <i class="fas fa-receipt"></i> Historial de Pagos
                    </a>
                </div>
            </div>
            
            <p>Actualmente tienes el plan <strong><?= ucfirst($plan_actual) ?></strong>. 
               <?php if ($plan_actual !== 'premium'): ?>
               Actualiza tu plan para desbloquear más características.
               <?php endif; ?>
            </p>
            
            <div class="plans-container">
                <!-- Plan Básico -->
                <div class="plan-card basic <?= $plan_actual === 'básico' ? 'current' : '' ?>">
                    <?php if ($plan_actual === 'básico'): ?>
                    <div class="current-plan-badge">Actual</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <h4 class="plan-title">Básico</h4>
                        <div class="plan-price">Gratis</div>
                    </div>
                    
                    <div class="plan-features">
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['básico']['chatbot_horas'] ?> horas con chatbot</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['básico']['consultas_dia'] ?> consultas/día con mediadores</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['básico']['documentos_dia'] ?> documentos/día</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-times"></i>
                            <span>Mensajes directos con abogados</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-times"></i>
                            <span>Mediación instantánea</span>
                        </div>
                    </div>
                    
                    <?php if ($plan_actual === 'básico'): ?>
                    <button class="plan-btn current">Plan Actual</button>
                    <?php else: ?>
                    <a href="cambiar_plan.php?plan=básico" class="plan-btn">Seleccionar</a>
                    <?php endif; ?>
                </div>
                
                <!-- Plan Plus -->
                <div class="plan-card plus <?= $plan_actual === 'plus' ? 'current' : '' ?>">
                    <?php if ($plan_actual === 'plus'): ?>
                    <div class="current-plan-badge">Actual</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <h4 class="plan-title">Plus</h4>
                        <div class="plan-price">Q300/mes</div>
                    </div>
                    
                    <div class="plan-features">
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['plus']['chatbot_horas'] ?> horas con chatbot</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['plus']['consultas_dia'] ?> consultas/día con mediadores</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span><?= $limites_plan['plus']['documentos_dia'] ?> documentos/día</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span>Mensajes directos con abogados</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-times"></i>
                            <span>Mediación instantánea</span>
                        </div>
                    </div>
                    
                    <?php if ($plan_actual === 'plus'): ?>
                    <button class="plan-btn current">Plan Actual</button>
                    <?php else: ?>
                    <a href="cambiar_plan.php?plan=plus" class="plan-btn upgrade">Actualizar a Plus</a>
                    <?php endif; ?>
                </div>
                
                <!-- Plan Premium -->
                <div class="plan-card premium <?= $plan_actual === 'premium' ? 'current' : '' ?>">
                    <?php if ($plan_actual === 'premium'): ?>
                    <div class="current-plan-badge">Actual</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <h4 class="plan-title">Premium</h4>
                        <div class="plan-price">Q600/mes</div>
                    </div>
                    
                    <div class="plan-features">
                        <div class="plan-feature">
                            <i class="fas fa-infinity"></i>
                            <span>Chatbot ilimitado</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-infinity"></i>
                            <span>Consultas ilimitadas con mediadores</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-infinity"></i>
                            <span>Documentos ilimitados</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span>Mensajes directos con abogados</span>
                        </div>
                        <div class="plan-feature">
                            <i class="fas fa-check"></i>
                            <span>Mediación instantánea</span>
                        </div>
                    </div>
                    
                    <?php if ($plan_actual === 'premium'): ?>
                    <button class="plan-btn current">Plan Actual</button>
                    <?php else: ?>
                    <a href="cambiar_plan.php?plan=premium" class="plan-btn upgrade">Actualizar a Premium</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>

    <script>
        // Opcional: Toggle para tema oscuro/claro
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.createElement('button');
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            themeToggle.style.position = 'fixed';
            themeToggle.style.bottom = '2rem';
            themeToggle.style.right = '2rem';
            themeToggle.style.width = '56px';
            themeToggle.style.height = '56px';
            themeToggle.style.borderRadius = '50%';
            themeToggle.style.backgroundColor = 'var(--primary)';
            themeToggle.style.color = 'white';
            themeToggle.style.display = 'flex';
            themeToggle.style.alignItems = 'center';
            themeToggle.style.justifyContent = 'center';
            themeToggle.style.boxShadow = 'var(--shadow-lg)';
            themeToggle.style.zIndex = '1000';
            themeToggle.style.border = '2px solid white';
            themeToggle.style.cursor = 'pointer';
            themeToggle.style.transition = 'all 0.3s ease';
            
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
</body>
</html>