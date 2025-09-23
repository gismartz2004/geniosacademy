<?php
// Configuración de la base de datos para Hostinger
define('DB_HOST', 'mysql.hostinger.com'); // o "localhost" según te diga Hostinger
define('DB_NAME', 'u578800031_genios_academy'); // cambia al nombre exacto de tu DB
define('DB_USER', 'u578800031_desarrollossof'); // tu usuario MySQL
define('DB_PASS', 'Desarrollosoftware2023#');   // la contraseña que creaste

// Configuración de la aplicación
define('APP_NAME', 'GeiosBot Academy');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Tipos de archivo permitidos
$allowedVideoTypes = array('mp4', 'mov', 'avi', 'wmv');
$allowedSlideTypes = array('pdf', 'ppt', 'pptx');

// Iniciar sesión
session_start();

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Mostrar errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);