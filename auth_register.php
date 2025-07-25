<?php 
$page_title = "Panel de Usuario";
require_once 'includes/head.php'; 
?>

<?php
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO clientes (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nombre, $email, $password]);

    header("Location: auth_login.php?registro=exito");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .register-container {
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
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

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background-color: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            background-color: #e53e3e;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .login-link a {
            color: var(--primary-light);
            font-weight: 600;
            text-decoration: none;
        }

        .terms {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #718096;
            text-align: center;
        }

        .terms a {
            color: var(--primary-light);
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-balance-scale"></i>
            <h1>LegisResolve</h1>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Juan Pérez" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="tu@email.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                </div>
                <div class="password-strength">
                    <div class="strength-meter" id="password-strength-meter"></div>
                </div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Crear Cuenta
            </button>
        </form>
        
        <div class="terms">
            Al registrarte, aceptas nuestros <a href="#">Términos de Servicio</a> y <a href="#">Política de Privacidad</a>
        </div>
        
        <div class="login-link">
            ¿Ya tienes una cuenta? <a href="auth_login.php">Inicia Sesión</a>
        </div>
    </div>

    <script>
        // Validación de fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.getElementById('password-strength-meter');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Longitud mínima
            if (password.length >= 8) strength += 1;
            // Contiene números
            if (password.match(/\d/)) strength += 1;
            // Contiene mayúsculas
            if (password.match(/[A-Z]/)) strength += 1;
            // Contiene caracteres especiales
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            // Actualizar medidor visual
            const width = strength * 25;
            let color = '#e53e3e'; // Rojo
            
            if (strength >= 3) color = '#f6ad55'; // Naranja
            if (strength >= 4) color = '#48bb78'; // Verde
            
            strengthMeter.style.width = `${width}%`;
            strengthMeter.style.backgroundColor = color;
        });
    </script>
</body>
<button class="btn-back" onclick="window.history.back()" title="Volver atrás">
  <i class="fas fa-arrow-left"></i>
</button>

</html>