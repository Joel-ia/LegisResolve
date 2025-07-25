<?php
session_start();
require_once 'includes/conexion.php';

// Obtener todas las disputas del usuario
$sql = "SELECT id, titulo, estado, fecha_creacion 
        FROM disputas 
        WHERE usuario_id = ? 
        ORDER BY fecha_creacion DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$disputas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Disputas | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos generales */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaecef;
            font-size: 28px;
        }
        
        /* Estilos para el listado de disputas */
        .disputas-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .disputa-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
        }
        
        .disputa-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .disputa-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 10px;
            word-break: break-word;
        }
        
        .disputa-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meta-item i {
            font-size: 16px;
        }
        
        .disputa-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .status-asignada {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .status-en_proceso {
            background-color: #cce5ff;
            color: #004085;
            border-left-color: #007bff;
        }
        
        .status-resuelta {
            background-color: #f8f9fa;
            color: #383d41;
            border-left-color: #6c757d;
        }
        
        .disputa-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-chat {
            background-color: #3498db;
            color: white;
        }
        
        .btn-chat:hover {
            background-color: #2980b9;
        }
        
        .btn-new {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            margin-top: 20px;
        }
        
        .btn-new:hover {
            background-color: #27ae60;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border-radius: 10px;
            grid-column: 1 / -1;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 50px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .disputas-list {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-gavel"></i> Mis Casos de Mediación</h1>
        
        <div class="disputas-list">
            <?php if (empty($disputas)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No tienes disputas activas</h3>
                    <a href="nueva_disputa.php" class="btn btn-new">
                        <i class="fas fa-plus"></i> Crear nueva disputa
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($disputas as $disputa): ?>
                    <div class="disputa-card status-<?= $disputa['estado'] ?>">
                        <h3 class="disputa-title"><?= htmlspecialchars($disputa['titulo']) ?></h3>
                        
                        <div class="disputa-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?= date('d/m/Y', strtotime($disputa['fecha_creacion'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-info-circle"></i>
                                <span class="disputa-status status-<?= $disputa['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $disputa['estado'])) ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (in_array($disputa['estado'], ['asignada', 'en_proceso'])): ?>
                            <div class="disputa-actions">
                                <a href="mediacion_chat.php?disputa_id=<?= $disputa['id'] ?>" class="btn btn-chat">
                                    <i class="fas fa-comments"></i> Ingresar al chat
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>