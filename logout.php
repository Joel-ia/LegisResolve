<?php
session_start();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php"); // Cambia 'index.php' por la URL de tu página de inicio
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2a4365;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .logout-container {
            background: #003366;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        .btn-logout {
            background-color: #ff4136;
            color: white;
        }
        .btn-cancel {
            background-color: #0074D9;
            color: white;
        }
        .btn-back {
            background-color: #AAAAAA;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #003366;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="logout-container">
         <i class="fas fa-balance-scale"></i>
        <h2>¿Está seguro de que desea cerrar sesión?</h2>
        <button class="btn-logout" onclick="openModal()">Cerrar Sesión</button>
        <button class="btn-back" onclick="goBack()">Regresar</button>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <p>¿Está seguro que desea salir?</p>
            <form method="post">
                <button type="submit" name="logout" class="btn-logout">Sí, cerrar sesión</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
