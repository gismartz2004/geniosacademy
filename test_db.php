<?php
// test_db_fixed.php
echo "<h3>🔍 Diagnóstico de conexión Cloud SQL</h3>";

// Obtener y verificar variables de entorno
$instance = getenv('INSTANCE_CONNECTION_NAME');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<strong>Variables de entorno:</strong><br>";
echo "INSTANCE_CONNECTION_NAME: " . ($instance ?: '❌ NO DEFINIDA') . "<br>";
echo "DB_NAME: " . ($dbname ?: '❌ NO DEFINIDA') . "<br>";
echo "DB_USER: " . ($user ?: '❌ NO DEFINIDA') . "<br>";
echo "DB_PASS: " . ($pass ? '✅ DEFINIDA' : '❌ NO DEFINIDA') . "<br><br>";

// Verificar que todas las variables estén definidas
if (empty($instance)) {
    die("❌ ERROR: INSTANCE_CONNECTION_NAME no está definida. Revisa las variables de entorno en Cloud Run.");
}

if (empty($dbname) || empty($user)) {
    die("❌ ERROR: Faltan variables de entorno esenciales.");
}

// Construir el socket path CORRECTAMENTE
$socketPath = "/cloudsql/" . trim($instance);
$dsn = "mysql:unix_socket={$socketPath};dbname={$dbname}";

echo "<strong>Configuración de conexión:</strong><br>";
echo "Socket path: " . $socketPath . "<br>";
echo "DSN completo: " . $dsn . "<br><br>";

try {
    // Configurar opciones de PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_PERSISTENT => false
    ];
    
    echo "🔌 Intentando conexión...<br>";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "🎉 ¡Conexión exitosa!<br><br>";
    
    // Probar consultas
    echo "<strong>Pruebas de consulta:</strong><br>";
    
    // Test 1: Consulta simple
    $stmt = $pdo->query("SELECT 1 as test, NOW() as time, DATABASE() as db");
    $result = $stmt->fetch();
    echo "✅ Test básico: " . $result['test'] . "<br>";
    echo "✅ Hora servidor: " . $result['time'] . "<br>";
    echo "✅ Base de datos: " . $result['db'] . "<br>";
    
    // Test 2: Mostrar tablas (opcional)
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tablas encontradas: " . count($tables) . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    echo "Código error: " . $e->getCode() . "<br>";
    
    // Diagnóstico adicional
    echo "<br><strong>Diagnóstico:</strong><br>";
    echo "¿Socket existe? " . (file_exists($socketPath) ? '✅ Sí' : '❌ No') . "<br>";
    
    if (file_exists($socketPath)) {
        echo "Permisos socket: " . substr(sprintf('%o', fileperms($socketPath)), -4) . "<br>";
        echo "Es socket? " . (is_dir($socketPath) ? 'Carpeta' : 'Archivo') . "<br>";
    }
}
?>