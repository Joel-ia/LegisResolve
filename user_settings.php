<?php 
$page_title = "Panel de Usuario";
require_once 'includes/head.php'; 
?>

<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit;
}

// Obtener datos del usuario
$sql = "SELECT nombre, email, telefono FROM clientes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Procesar cambio de tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_theme'])) {
    $currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
    $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
    setcookie('theme', $newTheme, time() + (86400 * 30), "/"); // 30 días
    header("Refresh:0");
    exit;
}

// Procesar actualización de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    
    $sql = "UPDATE clientes SET nombre = ?, telefono = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nombre, $telefono, $_SESSION['user_id']]);
    
    $success = "Perfil actualizado correctamente";
    header("Refresh:2");
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verificar contraseña actual
    $sql = "SELECT password FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $db_password = $stmt->fetchColumn();
    
    if (password_verify($current_password, $db_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE clientes SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $success = "Contraseña cambiada correctamente";
        } else {
            $error = "Las nuevas contraseñas no coinciden";
        }
    } else {
        $error = "La contraseña actual es incorrecta";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
        <style>
        :root {
            --primary: #2a4365;
            --primary-light: #4299e1;
            --text-color: #1a202c;
            --text-secondary: #4a5568;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body.dark-mode {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e2e8f0;
            --text-secondary: #a0aec0;
            --border-color: #2d3748;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        .main-container {
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .settings-wrapper {
            width: 100%;
            max-width: 800px;
        }

        .settings-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .settings-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .settings-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: var(--card-bg);
            padding: 2rem 1rem;
            border-right: 1px solid var(--border-color);
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-item.active {
            background: rgba(66, 153, 225, 0.1);
            color: var(--primary);
        }

        .menu-item i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 100%;
                position: static;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
        }
    </style>
 </head>
  <body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : '' ?>">
    <div class="main-container">
      <div class="settings-container">
        <div class="settings-header">
          <h1><i class="fas fa-cog"></i> Configuración de Cuenta</h1>
          <form method="POST" class="theme-toggle">
            <input type="hidden" name="toggle_theme">
            <button type="submit" class="theme-toggle-btn <?= (!isset($_COOKIE['theme']) || $_COOKIE['theme'] == 'light' ? 'active' : '') ?>" title="Modo claro">
              <i class="fas fa-sun"></i>
            </button>
            <button type="submit" class="theme-toggle-btn <?= isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'active' : '' ?>" title="Modo oscuro">
              <i class="fas fa-moon"></i>
            </button>          </form>
        </div>
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Información del perfil -->
            <div class="card">
                <h2><i class="fas fa-user"></i> Información del Perfil</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        <small style="color: var(--text-secondary);">Para cambiar tu correo electrónico, por favor contacta al soporte.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </form>
            </div>

            <!-- Cambio de contraseña -->
            <div class="card">
                <h2><i class="fas fa-lock"></i> Cambiar Contraseña</h2>
                <form method="POST">
                    <div class="form-group password-toggle">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" name="current_password" class="form-control" required>
                        <i class="fas fa-eye" onclick="togglePassword(this)"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                        <i class="fas fa-eye" onclick="togglePassword(this)"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                        <i class="fas fa-eye" onclick="togglePassword(this)"></i>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Cambiar contraseña
                    </button>
                </form>
            </div>

            <!-- Preferencias de notificaciones -->
            <div class="card">
                <h2><i class="fas fa-bell"></i> Preferencias de Notificaciones</h2>
                <form>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" checked> Notificaciones por correo electrónico
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" checked> Notificaciones sobre mis disputas
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox"> Notificaciones promocionales
                        </label>
                    </div>
                    
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar preferencias
                    </button>
                </form>
            </div>

            <!-- Configuración avanzada -->
            <div class="card">
                <h2><i class="fas fa-sliders-h"></i> Configuración Avanzada</h2>
                <div class="form-group">
                    <label class="form-label">Idioma</label>
                    <select class="form-control">
                        <option selected>Español</option>
                        <option>English</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Zona horaria</label>
                    <select class="form-control">
                        <option selected>Guatemala (UTC-6)</option>
                        <option>UTC-5</option>
                        <option>UTC-4</option>
                    </select>
                </div>
                
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar configuración
                </button>
            </div>

            <!-- Cuenta -->
            <div class="card">
                <h2><i class="fas fa-user-cog"></i> Cuenta</h2>
                <div style="margin-bottom: 1.5rem;">
                    <button type="button" class="btn" style="background-color: rgba(229, 62, 62, 0.1); color: var(--danger);">
                        <i class="fas fa-file-download"></i> Exportar mis datos
                    </button>
                    <small style="display: block; margin-top: 0.5rem; color: var(--text-secondary);">
                        Descarga un archivo con toda la información que tenemos almacenada sobre ti.
                    </small>
                </div>
                
                <div>
                    <button type="button" class="btn" style="background-color: rgba(229, 62, 62, 0.1); color: var(--danger);">
                        <i class="fas fa-user-times"></i> Eliminar mi cuenta
                    </button>
                    <small style="display: block; margin-top: 0.5rem; color: var(--text-secondary);">
                        Esta acción es permanente y no se puede deshacer. Todos tus datos serán eliminados.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle para mostrar/ocultar contraseña
        function togglePassword(icon) {
            const input = icon.previousElementSibling;
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        // Verificar fortaleza de contraseña
        document.querySelector('input[name="new_password"]').addEventListener('input', function(e) {
            // Implementar lógica de verificación de fortaleza si es necesario
        });

        // Inicializar tema según cookie
        function applyTheme() {
            const theme = getCookie('theme') || 'light';
            document.body.className = theme === 'dark' ? 'dark-mode' : '';
        }

        // Función auxiliar para obtener cookies
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        // Aplicar tema al cargar
        applyTheme();
    </script>

    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>

        <?php include 'includes/sidebar.php'; ?>
    
</body>
</html>