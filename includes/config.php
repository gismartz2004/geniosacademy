<?php
// -----------------------------
// Configuración de la base de datos (Cloud SQL socket)
// -----------------------------

// Variables de entorno definidas en Cloud Run:
// CLOUD_SQL_CONNECTION_NAME, DB_NAME, DB_USER, DB_PASSWORD
$cloud_sql_connection_name = getenv('CLOUD_SQL_CONNECTION_NAME'); // proyecto:region:instancia
$db_name = getenv('DB_NAME');       // Nombre de la base de datos
$db_user = getenv('DB_USER');       // Usuario de la DB
$db_password = getenv('DB_PASSWORD'); // Contraseña

if (!$cloud_sql_connection_name || !$db_name || !$db_user || !$db_password) {
    die("Faltan variables de entorno necesarias para la conexión a la base de datos.");
}

try {
    $pdo = new PDO(
        "mysql:unix_socket=/cloudsql/$cloud_sql_connection_name;dbname=$db_name;charset=utf8",
        $db_user,
        $db_password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
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

// Mostrar errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);