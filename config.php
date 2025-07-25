<?php
// Configuración de entorno
define('ENVIRONMENT', 'development'); // 'production' en entorno real

// Configuración de OpenAI (usar variables de entorno en producción)
if (ENVIRONMENT === 'development') {
    define('OPENAI_API_KEY', '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');
} else {
    define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
}

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'disputas_online');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'notificaciones@legisresolve.com');
define('SMTP_PASS', 'contraseña-segura'); // Usar contraseña de aplicación
define('SMTP_FROM', 'notificaciones@legisresolve.com');

// Configuración de URLs
define('BASE_URL', 'https://tudominio.com');
define('ASSETS_URL', BASE_URL . '/assets');

// Habilitar errores solo en desarrollo
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}