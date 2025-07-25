<?php
// formulario_mensaje.php
session_start();
$disputa_id = (int)($_GET['disputa_id'] ?? 0);

if ($disputa_id < 1) {
  die("ID de disputa inválido");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Mensaje</title>
    <style>

  iframe[name^="glasp"], 
  div[id*="glasp"], 
  div[id*="yt_article"] {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
  }

        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea { width: 100%; padding: 10px; margin-bottom: 10px; min-height: 100px; }
        button { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; }
    </style>
    </head>
<body>
    <h2>Enviar Mensaje</h2>
    
    
    <!-- FORMULARIO CORREGIDO -->
<form id="formMensaje" action="mediacion_chat.php" method="POST" 
      onsubmit="return validarForm()">
  <input type="hidden" name="disputa_id" value="<?= $disputa_id ?>">
  <input type="hidden" name="csrf_token" value="<?= bin2hex(random_bytes(32)) ?>">
  
  <textarea name="mensaje" required></textarea>
  <button type="submit">Enviar</button>

</form>

<script>
function validarForm() {
  const form = document.getElementById('formMensaje');
  const data = new FormData(form);
  
  // Debug crucial
  console.log('Datos a enviar:', {
    disputa_id: data.get('disputa_id'),
    mensaje: data.get('mensaje'),
    csrf_token: data.get('csrf_token')
  });
  
  return true;
}
</script>
</body>
</html>