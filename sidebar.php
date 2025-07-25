<?php
// Verificar si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="user_dashboard.php" class="logo-link">
            <img src="https://sdmntprwestus2.oaiusercontent.com/files/00000000-6638-61f8-99cd-4e963029e38f/raw?se=2025-07-25T06%3A26%3A12Z&sp=r&sv=2024-08-04&sr=b&scid=a5ab197c-cf36-56c3-8a42-d87c27847947&skoid=0da8417a-a4c3-4a19-9b05-b82cee9d8868&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-07-24T21%3A39%3A14Z&ske=2025-07-25T21%3A39%3A14Z&sks=b&skv=2024-08-04&sig=5DiJnTLjRmOVfS/dqauDpI1sAyJJcZv22sIkaD4om50%3D" alt="LegisResolve Logo" class="logo">
            <h2>LegisResolve</h2>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="user_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="user_lawyers.php" class="nav-link">
                    <i class="fas fa-balance-scale"></i>
                    <span>Consultas</span>
                </a>
            </li>
             <li class="nav-item">
                <a href="mediacion_chat.php" class="nav-link">
                    <i class="fas fa-balance-scale"></i>
                    <span>Asesoria</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="user_documentos.php" class="nav-link">
                    <i class="fas fa-file-contract"></i>
                    <span>Generador de Documentos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="user_settings.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Mi Perfil</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="user_ayuda.php" class="nav-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Ayuda</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></span>
                    <span class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></span>
                </div>
                <a href="logout.php" class="logout-btn" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>

<style>
    :root {
        --sidebar-width: 280px;
        --sidebar-bg: #0D2142;
        --sidebar-color: #ffffff;
        --sidebar-active-bg: rgba(255, 255, 255, 0.1);
        --sidebar-hover-bg: rgba(255, 255, 255, 0.05);
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background-color: var(--sidebar-bg);
        color: var(--sidebar-color);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-link {
        display: flex;
        align-items: center;
        color: inherit;
        text-decoration: none;
    }

    .logo {
        width: 40px;
        height: 40px;
        margin-right: 10px;
    }

    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }

    .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin: 0.25rem 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: var(--sidebar-color);
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .nav-link:hover {
        background-color: var(--sidebar-hover-bg);
    }

    .nav-link.active {
        background-color: var(--sidebar-active-bg);
        font-weight: 600;
    }

    .nav-link i {
        margin-right: 1rem;
        width: 20px;
        text-align: center;
    }

    .sidebar-footer {
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-profile {
        display: flex;
        align-items: center;
        padding: 0.5rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
    }

    .user-avatar i {
        font-size: 1.5rem;
    }

    .user-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .user-email {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .logout-btn {
        color: var(--sidebar-color);
        opacity: 0.7;
        padding: 0.5rem;
        transition: all 0.2s ease;
    }

    .logout-btn:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Opcional: Puedes añadir funcionalidad para mostrar/ocultar el sidebar en móviles
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        
        // Ejemplo: Botón para toggle sidebar en móviles
        // Deberías agregar un botón en tu header para activar esto
        function toggleSidebar() {
            sidebar.classList.toggle('active');
        }
    });
</script>