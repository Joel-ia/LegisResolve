<?php 
session_start();

require_once 'includes/head.php';

$abogados = [
    [
        "nombre" => "Lic. Juan Pérez", 
        "especialidad" => "Derecho Penal", 
        "imagen" => "https://th.bing.com/th/id/OIP.Ku86RFxg5i9PlfR-uV5YOgHaLG?w=156&h=180&c=7&r=0&o=5&pid=1.7",
        "telefono" => "+50245678901",
        "facebook" => "https://facebook.com/lic.juan.perez",
        "instagram" => "https://instagram.com/lic.juan.perez",
        "cv" => "cv_juan_perez.php",
        "correo" => "juanperez@example.com"
    ],
    [
        "nombre" => "Licda. María López", 
        "especialidad" => "Derecho Civil", 
        "imagen" => "https://www.umaslp.edu.mx/wp-content/uploads/2024/12/Maestria-derecho-penal-ind.png",
        "telefono" => "+50256789012",
        "facebook" => "https://facebook.com/lic.maria.lopez",
        "instagram" => "https://instagram.com/lic.maria.lopez",
        "cv" => "cv_maria_lopez.php",
        "correo" => "marialopez@example.com"
    ],
    [
        "nombre" => "Lic. Carlos Gómez", 
        "especialidad" => "Derecho Laboral", 
        "imagen" => "https://divorciosdeguatemaltecos.com/images/quienessomos1.jpg",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ],
      [
        "nombre" => "Lic. Inés Castillo", 
        "especialidad" => "Derecho Consitucional", 
        "imagen" => "https://hidalgo.quadratin.com.mx/www/wp-content/uploads/2020/10/WhatsApp-Image-2020-10-21-at-16.28.10-1160x700.jpeg",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ],
        [
        "nombre" => "Cristian Alvárez", 
        "especialidad" => "Derecho Penal", 
        "imagen" => "https://tse1.mm.bing.net/th/id/OIP.Ot4fIntUhLYcBd0ongbJLQHaE8?pid=ImgDet&w=182&h=121&c=7&o=7&rm=3",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ],
        [
        "nombre" => "Licda. María Casanova", 
        "especialidad" => "Derecho Civil", 
        "imagen" => "https://tse4.mm.bing.net/th/id/OIP.4oXfanzwHJwEjo7i0bPY1gHaE8?rs=1&pid=ImgDetMain&o=7&rm=3",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ],
        [
        "nombre" => "Licda. Norma Guevara", 
        "especialidad" => "Derecho Civil", 
        "imagen" => "https://th.bing.com/th?q=Diputada+Frente+Amplio&w=120&h=120&c=1&rs=1&qlt=70&o=7&cb=1&pid=InlineBlock&rm=3&mkt=es-XL&cc=GT&setlang=es&adlt=moderate&t=1&mw=247",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ],
        [
        "nombre" => "Licda. Julia Hernández", 
        "especialidad" => "Derecho Laboral", 
        "imagen" => "https://tse2.mm.bing.net/th/id/OIP.jKF-Q2K4Rn-sIDq9ai8TEwHaEN?w=580&h=330&rs=1&pid=ImgDetMain&o=7&rm=3",
        "telefono" => "+50267890123",
        "facebook" => "https://facebook.com/lic.carlos.gomez",
        "instagram" => "https://instagram.com/lic.carlos.gomez",
        "cv" => "cv_carlos_gomez.php",
        "correo" => "carlosgomez@example.com"
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesionales Legales | LegisResolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== PALETA DE COLORES ===== */
        :root {
            --primary: #150CE1;
            --primary-light: #4299e1;
            --primary-dark: #0D2142;
            --accent: #CC3800;
            --success: #38a169;
            --danger: #e53e3e;
            --light: #f8f9fa;
            --light-gray: #e2e8f0;
            --dark: #1a202c;
            --gray-200: #e2e8f0;
            --gray-500: #718096;
            --gray-700: #4a5568;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* ===== ESTILOS BASE ===== */
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        /* ===== CONTENIDO PRINCIPAL CENTRADO ===== */
        .content-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* ===== ENCABEZADO ===== */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--gray-700);
            max-width: 700px;
            margin: 0 auto;
        }

        /* ===== FILTROS ===== */
        .filters-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background-color: white;
            border: 1px solid var(--light-gray);
            color: var(--dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* ===== TARJETAS DE PROFESIONALES ===== */
        .professionals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .professional-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--light-gray);
            text-align: center;
        }

        .professional-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .professional-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin: 0 auto 1rem;
        }

        .professional-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .professional-specialty {
            display: inline-block;
            background-color: rgba(21, 12, 225, 0.1);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .contact-options {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .contact-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            color: white;
        }

        .contact-btn i {
            margin-right: 0.5rem;
        }

        .btn-whatsapp { background-color: #25D366; }
        .btn-facebook { background-color: #3b5998; }
        .btn-instagram { background-color: #E1306C; }
        .btn-cv { background-color: var(--primary); }

        /* ===== BOTÓN DE RETROCESO ===== */
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
            border: 2px solid white;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: scale(1.1) translateY(-3px);
            background-color: var(--primary-dark);
        }

        /* ===== TEMA OSCURO ===== */
        body.dark-mode {
            --light: #121212;
            --dark: #e1e1e1;
            --light-gray: #333;
            --gray-200: #2d2d2d;
            --gray-500: #a0a0a0;
            --gray-700: #e1e1e1;
            background-color: #121212;
        }

        body.dark-mode .professional-card {
            background-color: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .filter-btn {
            background-color: #2d2d2d;
            border-color: #333;
            color: var(--dark);
        }

        body.dark-mode .filter-btn:hover, 
        body.dark-mode .filter-btn.active {
            background-color: var(--primary);
            color: white;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .content-center {
                padding: 1rem;
            }
            
            .professionals-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-container">
        <div class="content-center">
            <div class="page-header">
                <h1>Profesionales Legales</h1>
                <p>Contacta directamente con abogados especializados en diferentes áreas del derecho</p>
            </div>

            <div class="filters-container">
                <div class="filters">
                    <button class="filter-btn active">Todos</button>
                    <button class="filter-btn">Derecho Penal</button>
                    <button class="filter-btn">Derecho Civil</button>
                    <button class="filter-btn">Derecho Laboral</button>
                    <button class="filter-btn">Derecho Constitucional</button>
                </div>
            </div>

            <div class="professionals-grid">
                <!-- Ejemplo de tarjeta de profesional -->
                <div class="professional-card" data-specialty="derecho-civil">
                    <img src="https://www.umaslp.edu.mx/wp-content/uploads/2024/12/Maestria-derecho-penal-ind.png" alt="Licda. María López" class="professional-img">
                    <h3 class="professional-name">Licda. María López</h3>
                    <span class="professional-specialty">Derecho Civil</span>
                    <div class="contact-options">
                        <a href="#" class="contact-btn btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="#" class="contact-btn btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="#" class="contact-btn btn-instagram">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        <a href="#" class="contact-btn btn-cv">
                            <i class="fas fa-file-alt"></i> CV
                        </a>
                    </div>
                </div>

                <!-- Segunda tarjeta de ejemplo -->
                <div class="professional-card" data-specialty="derecho-laboral">
                    <img src="https://divorciosdeguatemaltecos.com/images/quienessomos1.jpg" alt="Lic. Carlos Gómez" class="professional-img">
                    <h3 class="professional-name">Lic. Carlos Gómez</h3>
                    <span class="professional-specialty">Derecho Laboral</span>
                    <div class="contact-options">
                        <a href="#" class="contact-btn btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="#" class="contact-btn btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="#" class="contact-btn btn-instagram">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        <a href="#" class="contact-btn btn-cv">
                            <i class="fas fa-file-alt"></i> CV
                        </a>
                    </div>
                </div>

                <!-- Puedes agregar más tarjetas aquí -->
            </div>
        </div>
    </div>

    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>

    <script>
        // Filtrado por especialidad
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelector('.filter-btn.active').classList.remove('active');
                this.classList.add('active');
                
                const filter = this.textContent.toLowerCase();
                const professionals = document.querySelectorAll('.professional-card');
                
                professionals.forEach(professional => {
                    if (filter === 'todos') {
                        professional.style.display = 'block';
                    } else {
                        const specialty = professional.getAttribute('data-specialty');
                        if (specialty.includes(filter.replace(' ', '-'))) {
                            professional.style.display = 'block';
                        } else {
                            professional.style.display = 'none';
                        }
                    }
                });
            });
        });

        // Toggle tema oscuro/claro
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.createElement('button');
            themeToggle.className = 'theme-toggle';
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            themeToggle.style.position = 'fixed';
            themeToggle.style.bottom = '2rem';
            themeToggle.style.right = '2rem';
            themeToggle.style.width = '56px';
            themeToggle.style.height = '56px';
            themeToggle.style.borderRadius = '50%';
            themeToggle.style.backgroundColor = 'var(--primary)';
            themeToggle.style.color = 'white';
            themeToggle.style.display = 'flex';
            themeToggle.style.alignItems = 'center';
            themeToggle.style.justifyContent = 'center';
            themeToggle.style.boxShadow = 'var(--shadow-lg)';
            themeToggle.style.zIndex = '1000';
            themeToggle.style.border = '2px solid white';
            themeToggle.style.cursor = 'pointer';
            themeToggle.style.transition = 'all 0.3s ease';
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                if (document.body.classList.contains('dark-mode')) {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                }
            });
            
            document.body.appendChild(themeToggle);
        });
    </script>
</body>
</html>