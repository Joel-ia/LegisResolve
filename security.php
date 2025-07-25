<?php
// Sanitizar nombres de archivo
function sanitizeFilename($filename) {
    return preg_replace('/[^A-Za-z0-9_\-.]/', '', $filename);
}

// Determinar tipo de archivo
function obtenerTipoArchivo($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'png'])) return 'imagen';
    if ($extension === 'pdf') return 'pdf';
    if ($extension === 'docx') return 'documento';
    return 'otro';
}

// Sanitizar entradas de formulario
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>