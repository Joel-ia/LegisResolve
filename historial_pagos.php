<?php
$page_title = "Historial de Pagos";
require_once 'includes/head.php';

session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

// Obtener historial de pagos
$sql = "SELECT id, plan, monto, fecha, estado 
        FROM pagos 
        WHERE user_id = ? 
        ORDER BY fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$pagos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | LegisResolve</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .history-container {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .payment-item {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .payment-item:last-child {
            border-bottom: none;
        }
        
        .payment-header {
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .payment-plan {
            font-weight: 600;
        }
        
        .payment-amount {
            font-weight: 700;
            color: #2b6cb0;
        }
        
        .payment-date {
            color: #718096;
        }
        
        .payment-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-completed {
            background-color: #c6f6d5;
            color: #22543d;
        }
        
        .status-pending {
            background-color: #feebc8;
            color: #7b341e;
        }
        
        .status-failed {
            background-color: #fed7d7;
            color: #9b2c2c;
        }
        
        @media (max-width: 768px) {
            .payment-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .payment-header {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-receipt"></i> Historial de Pagos</h1>
            <a href="user_dashboard.php" class="logout-btn">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <div class="history-container">
            <?php if (empty($pagos)): ?>
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; color: #a0aec0; margin-bottom: 1rem;"></i>
                <h3>No hay pagos registrados</h3>
                <p>Aún no has realizado ningún pago en nuestro sistema.</p>
            </div>
            <?php else: ?>
            <div class="payment-item payment-header">
                <div>Plan</div>
                <div>Monto</div>
                <div>Fecha</div>
                <div>Estado</div>
            </div>
            
            <?php foreach ($pagos as $pago): ?>
            <div class="payment-item">
                <div class="payment-plan"><?= ucfirst($pago['plan']) ?></div>
                <div class="payment-amount">Q<?= number_format($pago['monto'], 2) ?></div>
                <div class="payment-date"><?= date('d/m/Y', strtotime($pago['fecha'])) ?></div>
                <div class="payment-status status-<?= $pago['estado'] ?>">
                    <?= ucfirst($pago['estado']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>