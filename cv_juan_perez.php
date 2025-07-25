<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CV - Lic. Carlos Gómez</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            padding: 40px;
        }
        .cv-container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
        }
        h1, h2 {
            color: #2a4365;
        }
        ul {
            list-style: square;
            padding-left: 20px;
        }
        .foto-abogado {
            position: absolute;
            top: 30px;
            right: 30px;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="cv-container">
        <img src="https://th.bing.com/th/id/OIP.Ku86RFxg5i9PlfR-uV5YOgHaLG?w=156&h=180&c=7&r=0&o=5&pid=1.7" alt="Foto de Carlos Gómez" class="foto-abogado">

        <h1>Lic. Carlos Gómez</h1>
        <p><strong>Especialidad:</strong> Derecho Laboral</p>

        <h2>Perfil Profesional</h2>
        <p>Abogado con sólida trayectoria en derecho laboral, especializado en asesoría a empresas, sindicatos y trabajadores en conflictos colectivos e individuales.</p>

        <h2>Formación Académica</h2>
        <ul>
            <li>Licenciatura en Ciencias Jurídicas – Universidad del Istmo</li>
            <li>Especialización en Derecho Laboral – Universidad Da Vinci</li>
        </ul>

        <h2>Experiencia Laboral</h2>
        <ul>
            <li>Asesor jurídico en Ministerio de Trabajo (2015 - presente)</li>
            <li>Consultor independiente para empresas multinacionales (2011 - 2015)</li>
        </ul>

        <h2>Idiomas</h2>
        <ul>
            <li>Español (nativo)</li>
            <li>Inglés (intermedio)</li>
        </ul>

        <h2>Contacto</h2>
        <p>Correo: carlosgomez@example.com</p>
        <p>Teléfono: +502 6789 0123</p>
    </div>
    <button class="btn-back" onclick="window.history.back()" title="Volver atrás">
        <i class="fas fa-arrow-left"></i>
    </button>
</body>
</html>
