<?php
require_once 'includes/config.php';

// Carpetas necesarias
$folders = [
    UPLOAD_DIR . 'videos/',
    UPLOAD_DIR . 'diapositivas/',
    UPLOAD_DIR . 'scratch/',
    UPLOAD_DIR . 'simuladores/',
    UPLOAD_DIR . 'avatares/'
];

foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        if (mkdir($folder, 0777, true)) {
            echo "<p>✅ Carpeta creada: $folder</p>";
            // Crear archivo .htaccess para protección
            file_put_contents($folder . '.htaccess', "Deny from all");
        } else {
            echo "<p style='color: red;'>❌ Error creando carpeta: $folder</p>";
        }
    } else {
        echo "<p>✅ Carpeta ya existe: $folder</p>";
    }
}

echo "<p><strong>Proceso completado. <a href='index.php'>Volver al inicio</a></strong></p>";
?>