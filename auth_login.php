<?php 
$page_title = "Panel de Usuario";
require_once 'includes/head.php';

// Iniciar sesión al principio del script
session_start();

// Generar nuevo token CSRF al iniciar sesión
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['current_disputa_id'] = 0; // Inicializar


require_once 'includes/conexion.php';

// Constantes para configuración
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 2 * 60); // 15 minutos en segundos

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es el formulario de login o de recuperación
    if (isset($_POST['password'])) {
        // FORMULARIO DE LOGIN
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Verificar intentos fallidos
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            if (time() - $_SESSION['last_login_attempt'] < LOGIN_LOCKOUT_TIME) {
                $remaining_time = ceil((LOGIN_LOCKOUT_TIME - (time() - $_SESSION['last_login_attempt'])) / 60);
                $error = "Demasiados intentos fallidos. Por favor espere $remaining_time minutos.";
            } else {
                // Resetear intentos si ha pasado el tiempo de bloqueo
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);
            }
        }
        
        if (!isset($error)) {
            $sql = "SELECT id, password FROM clientes WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

           // Después de verificar la contraseña correctamente:
// Dentro del bloque de login exitoso:
if ($user && password_verify($password, $user['password'])) {
    // Login exitoso
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_login_attempt']);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Verificación mejorada del rol
    $sql_rol = "SELECT 'mediador' AS role FROM mediadores WHERE id = ? 
                UNION 
                SELECT 'cliente' AS role FROM clientes WHERE id = ?";
    $stmt_rol = $conn->prepare($sql_rol);
    $stmt_rol->execute([$user['id'], $user['id']]);
    $rol = $stmt_rol->fetch(PDO::FETCH_ASSOC);
    
    // Establecer rol con valor por defecto 'cliente'
    $_SESSION['user_role'] = $rol['role'] ?? 'cliente';
    
    // DEBUG: Verificar datos de sesión
    error_log("Login exitoso. User ID: {$_SESSION['user_id']}, Role: {$_SESSION['user_role']}");
    
    header("Location: " . ($_SESSION['user_role'] === 'mediador' ? 'mediador_dashboard.php' : 'user_dashboard.php'));
    exit;
}
            } else {
                $error = "Email o contraseña incorrectos";
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $_SESSION['last_login_attempt'] = time();
            }
        }
    } elseif (isset($_POST['recovery_email'])) {
        // FORMULARIO DE RECUPERACIÓN DE CONTRASEÑA
        $email = filter_input(INPUT_POST, 'recovery_email', FILTER_SANITIZE_EMAIL);
        
        // Verificar si el email existe
        $sql = "SELECT id, nombre FROM clientes WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generar token de recuperación
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token en la base de datos
            $sql = "UPDATE clientes SET reset_token = ?, reset_expires = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Enviar email con el enlace de recuperación
            $reset_link = "http://".$_SERVER['HTTP_HOST']."/reset-password.php?token=$token";
            $subject = "Recuperación de contraseña - LegisResolve";
            $message = "Hola {$user['nombre']},\n\n";
            $message .= "Para restablecer tu contraseña, haz clic en el siguiente enlace:\n";
            $message .= "$reset_link\n\n";
            $message .= "Este enlace expirará en 1 hora.\n";
            $message .= "Si no solicitaste este cambio, ignora este mensaje.\n\n";
            $message .= "Atentamente,\nEl equipo de LegisResolve";
            
            // Configurar headers para el email
            $headers = "From: no-reply@legisresolve.com\r\n";
            $headers .= "Reply-To: no-reply@legisresolve.com\r\n";
            $headers .= "X-Mailer: PHP/".phpversion();
            
            // Enviar email (en producción usar PHPMailer)
            if (mail($email, $subject, $message, $headers)) {
                $success = "Se ha enviado un enlace de recuperación a tu email.";
            } else {
                $error = "Error al enviar el email. Por favor intenta más tarde.";
            }
        } else {
            $error = "No existe una cuenta con ese email.";
        }
    }


// Mostrar formulario de recuperación si se solicita
if (isset($_GET['action']) && $_GET['action'] == 'forgot-password') {
    $page_title = "Recuperar Contraseña";
    require_once 'includes/head.php';
    ?>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-key"></i>
            <h1>Recuperar Contraseña</h1>
        </div>
        
        <?php if(isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="recovery_email">Correo Electrónico</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="recovery_email" name="recovery_email" class="form-control" placeholder="tu@email.com" required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación
            </button>
        </form>
        
        <div class="signup-link">
            <a href="auth_login.php"><i class="fas fa-arrow-left"></i> Volver al login</a>
        </div>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

// Mostrar formulario de login normal
?>


<!DOCTYPE html>
<html lang="es">
<head>
     <style>

    :root {
  /* ===== PALETA DE COLORES ===== */
  --primary: #2a4365;
  --primary-light: #4299e1;
  --accent: #f6ad55;
  --success: #38a169;
  --danger: #e53e3e;
  --warning: #dd6b20;

  /* ===== MODOS DE COLOR ===== */
  /* Modo claro (default) */
  --bg-color: #f8f9fa;
  --card-bg: #ffffff;
  --text-color: #1a202c;
  --text-secondary: #4a5568;
  --border-color: #e2e8f0;
  --input-bg: #ffffff;
  
  /* Sombras */
  --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* ===== MODO OSCURO ===== */
[data-theme="dark"] {
  --bg-color: #121212;
  --card-bg: #1e1e1e;
  --text-color: #e2e8f0;
  --text-secondary: #a0aec0;
  --border-color: #2d3748;
  --input-bg: #2d3748;
}

/* ===== ESTILOS BASE ===== */
body {
  font-family: 'Inter', system-ui, sans-serif;
  background-color: var(--bg-color);
  color: var(--text-color);
  transition: all 0.3s ease;
  line-height: 1.6;
  margin: 0;
  padding: 0;
}

/* ===== COMPONENTES REUTILIZABLES ===== */
.card {
  background-color: var(--card-bg);
  border-radius: 12px;
  box-shadow: var(--shadow-md);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border: 1px solid var(--border-color);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-lg);
}

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
}

.btn-primary {
  background-color: var(--primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-light);
  transform: translateY(-2px);
}

/* ===== BOTÓN DE RETROCESO MEJORADO ===== */
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
  border: 2px solid var(--card-bg);
}

.btn-back:hover {
  transform: scale(1.1) translateY(-3px);
  background-color: var(--primary-light);
}

/* ===== FORMULARIOS ===== */
.form-control {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background-color: var(--input-bg);
  color: var(--text-color);
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-light);
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
}
        
        :root {
            --primary: #2a4365;       /* Azul corporativo oscuro */
            --primary-light: #4299e1; /* Azul corporativo claro */
            --accent: #f6ad55;        /* Naranja/accent */
            --light: #f7fafc;         /* Fondo claro */
            --dark: #1a202c;          /* Texto oscuro */
            --gray-200: #e2e8f0;      /* Bordes */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.5rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
        }

        .forgot-password a {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--gray-200);
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .signup-link a {
            color: var(--primary-light);
            font-weight: 600;
            text-decoration: none;
        }

        .error-message {
            color: #e53e3e;
            background-color: #fff5f5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #fed7d7;
            font-size: 0.875rem;
            display: <?php echo isset($error) ? 'block' : 'none'; ?>;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-balance-scale"></i>
            <h1>LegisResolve</h1>
        </div>
        
        <?php if(isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="tu@email.com" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="forgot-password">
                    <a href="forgot-password-form.php">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="signup-link">
            ¿No tienes una cuenta? <a href="auth_register.php">Regístrate</a>
        </div>
    </div>

    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>
    
    <script>
        // Validación del lado del cliente
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor completa todos los campos');
            }
        });
    </script>
</body>
</html>
