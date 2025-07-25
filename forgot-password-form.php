<?php
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
    
 <!-- Formulario de recuperación (debe estar dentro de auth_login.php o forgot-password-form.php) -->
<form method="POST">
    <div class="form-group">
        <label for="recovery_email">Correo Electrónico</label>
        <div class="input-with-icon">
            <i class="fas fa-envelope"></i>
            <!-- Cambiar name="email" por name="recovery_email" -->
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

