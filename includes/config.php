<?php
// ==============================================
// Configuración de conexión a Cloud SQL (MySQL)
// ==============================================

// Variables de entorno definidas en Cloud Run
$cloud_sql_connection_name = getenv('CLOUD_SQL_CONNECTION_NAME'); // proyecto:region:instancia
$db_name = getenv('DB_NAME');       
$db_user = getenv('DB_USER');       
$db_password = getenv('DB_PASSWORD'); 

if (!$cloud_sql_connection_name || !$db_name || !$db_user || !$db_password) {
    die("❌ Faltan variables de entorno necesarias para la conexión a la base de datos.");
}

try {
    // Conexión mediante Unix Socket (segura en Cloud Run)
    $dsn = sprintf(
        'mysql:unix_socket=/cloudsql/%s;dbname=%s;charset=utf8mb4',
        $cloud_sql_connection_name,
        $db_name
    );

    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Modo errores con excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch asociativo
        PDO::ATTR_EMULATE_PREPARES => false,           // Prepared statements nativos
    ]);

} catch (PDOException $e) {
    die("❌ Error de conexión a la base de datos: " . $e->getMessage());
}

// ==============================================
// Configuración general de la aplicación
// ==============================================

// Nombre de la app
define('APP_NAME', 'GeniosBot Academy');

// Directorios de subida
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('AVATAR_DIR', UPLOAD_DIR . 'avatares/');
define('SLIDES_DIR', UPLOAD_DIR . 'diapositivas/');
define('SIMULATOR_DIR', UPLOAD_DIR . 'simuladores/');
define('VIDEO_DIR', UPLOAD_DIR . 'videos/');

// Tamaño máximo de archivo (50MB)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Tipos permitidos
$allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv'];
$allowedSlideTypes = ['pdf', 'ppt', 'pptx'];

// Sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Errores (activar solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
