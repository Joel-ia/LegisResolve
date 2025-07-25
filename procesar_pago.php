<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['plan_pendiente'])) {
    header("Location: user_dashboard.php");
    exit;
}

$plan = $_SESSION['plan_pendiente'];
$precios = [
    'plus' => 300,
    'premium' => 600
];

$page_title = "Procesar Pago";
require_once 'includes/head.php';
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
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .payment-plan {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .payment-methods {
            margin-top: 2rem;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #4299e1;
            background-color: #ebf8ff;
        }
        
        .payment-method i {
            font-size: 1.5rem;
            margin-right: 1rem;
            width: 40px;
            text-align: center;
        }
        
        .payment-method.active {
            border-color: #4299e1;
            background-color: #ebf8ff;
            border-width: 2px;
        }
        
        .btn-confirm {
            width: 100%;
            padding: 1rem;
            background-color: #38a169;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm:hover {
            background-color: #2f855a;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="payment-container">
            <div class="payment-header">
                <h2><i class="fas fa-credit-card"></i> Procesar Pago</h2>
                <p>Estás actualizando a <strong>Plan <?= ucfirst($plan) ?></strong></p>
            </div>
            
            <div class="payment-plan">
                <div>
                    <h3>Plan <?= ucfirst($plan) ?></h3>
                    <p>Facturación mensual recurrente</p>
                </div>
                <div class="plan-price">Q<?= $precios[$plan] ?></div>
            </div>
            
            <form id="payment-form" action="confirmar_pago.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Información de Pago</label>
                    <div class="payment-methods">
                        <div class="payment-method active">
                            <i class="fab fa-cc-visa"></i>
                            <div>
                                <h4>Tarjeta de Crédito/Débito</h4>
                                <p>Visa, Mastercard, American Express</p>
                            </div>
                        </div>
                        <div class="payment-method">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <h4>Transferencia Bancaria</h4>
                                <p>BAC, G&T, BI</p>
                            </div>
                        </div>
                        <div class="payment-method">
                            <i class="fab fa-paypal"></i>
                            <div>
                                <h4>PayPal</h4>
                                <p>Pago con cuenta PayPal</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Número de Tarjeta</label>
                    <input type="text" class="form-control" placeholder="1234 5678 9012 3456" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Fecha de Expiración</label>
                        <input type="text" class="form-control" placeholder="MM/AA" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">CVV</label>
                        <input type="text" class="form-control" placeholder="123" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre en la Tarjeta</label>
                    <input type="text" class="form-control" placeholder="Nombre Completo" required>
                </div>
                
                <button type="submit" class="btn-confirm">
                    <i class="fas fa-lock"></i> Confirmar Pago de Q<?= $precios[$plan] ?>
                </button>
            </form>
        </div>
    </main>
</body>
</html>