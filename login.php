<?php
require_once __DIR__ . '/config/database.php'; // Conexión MySQLi
require_once __DIR__ . '/config/security.php'; // Funciones de seguridad

// Redirigir si ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // 1. Buscar usuario en BD
        $stmt = $mysqli->prepare("SELECT id, password_hash, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Credenciales incorrectas');
        }

        $user = $result->fetch_assoc();

        // 2. Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Credenciales incorrectas');
        }

        // 3. Crear sesión segura
        session_regenerate_id(true); // Prevención de fixation
        
        $_SESSION = [
            'user_id' => $user['id'],
            'user_role' => $user['role'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'last_activity' => time()
        ];

        // 4. Redirigir según rol
        $redirect = match($user['role']) {
            'admin' => '/admin/dashboard.php',
            'mediator' => '/mediator/cases.php',
            default => '/user/dashboard.php'
        };

        header("Location: $redirect");
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Disputas</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
        .login-box { max-width: 400px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>
        
        <?php if (!empty($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Ingresar</button>
        </form>
        
        <p style="margin-top: 15px;">
            ¿No tienes cuenta? <a href="/register.php">Regístrate aquí</a><br>
            <a href="/forgot-password.php">¿Olvidaste tu contraseña?</a>
        </p>
    </div>
</body>
</html>