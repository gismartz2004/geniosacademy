<?php
// -----------------------------
// Configuración de la base de datos (Cloud SQL IP pública)
// -----------------------------

// Variables de entorno definidas en Cloud Run:
// DB_NAME, DB_USER, DB_PASSWORD
$db_host = '35.232.71.57';  // IP pública de tu instancia
$db_name = getenv('geniosacademy');       // Nombre de la base de datos
$db_user = getenv('gismar');       // Usuario de la DB
$db_password = getenv('1234'); // Contraseña

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Error de conexión
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// -----------------------------
// Configuración de la aplicación
// -----------------------------
define('APP_NAME', 'GeniosBot Academy');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

$allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv'];
$allowedSlideTypes = ['pdf', 'ppt', 'pptx'];

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Mostrar errores (solo desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
