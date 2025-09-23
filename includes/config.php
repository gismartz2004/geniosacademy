<?php
// Configuración de la base de datos usando IP pública de Cloud SQL
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
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Configuración de la aplicación
define('APP_NAME', 'GeniosBot Academy');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

$allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv'];
$allowedSlideTypes = ['pdf', 'ppt', 'pptx'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

error_reporting(E_ALL);
ini_set('display_errors', 1);
