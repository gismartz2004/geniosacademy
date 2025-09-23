<?php
// Configuración de la base de datos para XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'geiosbot_academy');
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP generalmente tiene contraseña vacía por defecto

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
?>