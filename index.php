<?php
// Iniciar sesión (si ya está logueado, redirige al dashboard)
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegisResolve | Resolución de Disputas en Línea</title>
    <link rel="icono"  href="file:///C:/Users/Administrador/Downloads/Proyecto%20de%20Seminario_files/Logo%20PersonalJoe%20(1).png" type="image/x-icono">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #080808;       /* Azul corporativo oscuro */
            --primary-light: #4299e1; /* Azul corporativo claro */
            --accent: #f6ad55;        /* Naranja/accent */
            --light: #f0e0b7;         /* Fondo claro */
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
        }

        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary), #2c5282);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3QgZmlsbD0idXJsKCNwYXR0ZXJuKSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIvPjwvc3ZnPg==');
            opacity: 0.4;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            padding: 2rem;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .hero h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--accent);
            color: var(--dark);
            padding: 0.875rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            border: none;
            cursor: pointer;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            background-color: #fbd38d;
        }

        .btn-cta i {
            margin-right: 0.5rem;
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn-cta {
                margin: 0.5rem 0;
                width: 100%;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-content {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <h1>LegisResolve</h1>
            <h2>Solución Profesional de Disputas en Línea</h2>
            <p>Mediación certificada y generación de documentos legales con tecnología avanzada</p>
            <div class="btn-group">
                <a href="auth_login.php" class="btn-cta btn-outline">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
                <a href="auth_register.php" class="btn-cta">
                    <i class="fas fa-user-plus"></i> Registrarse
                </a>
            </div>
        </div>
    </section>
</body>
</html>