<?php
// test_db.php - Código mejorado para diagnóstico
echo "<h3>🔍 Diagnóstico de conexión Cloud SQL</h3>";

// Obtener variables de entorno
$instance = getenv('INSTANCE_CONNECTION_NAME');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "Instance: " . ($instance ?: 'NO DEFINIDA') . "<br>";
echo "DB Name: " . ($dbname ?: 'NO DEFINIDA') . "<br>";
echo "DB User: " . ($user ?: 'NO DEFINIDA') . "<br>";
echo "DB Pass: " . ($pass ? 'DEFINIDA' : 'NO DEFINIDA') . "<br>";

if (!$instance || !$dbname || !$user) {
    die("❌ Faltan variables de entorno esenciales");
}

$socketPath = "/cloudsql/{$instance}";
$dsn = "mysql:unix_socket={$socketPath};dbname={$dbname}";

echo "DSN: " . $dsn . "<br>";
echo "Socket path: " . $socketPath . "<br>";

// Verificar si el socket existe (solo para diagnóstico)
if (file_exists($socketPath)) {
    echo "✅ Socket encontrado<br>";
} else {
    echo "❌ Socket NO encontrado en: " . $socketPath . "<br>";
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "🎉 ¡Conexión exitosa!<br>";
    
    // Probar consulta simple
    $stmt = $pdo->query("SELECT 1 as test, NOW() as time");
    $result = $stmt->fetch();
    echo "✅ Consulta testeada: " . $result['test'] . " - Hora: " . $result['time'];
    
} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "<br>";
    echo "Código error: " . $e->getCode() . "<br>";
}
?>