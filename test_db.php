<?php
// test-connection.php
$instance = getenv('INSTANCE_CONNECTION_NAME');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Conexión via Unix Socket
$socketPath = "/cloudsql/{$instance}";
$dsn = "mysql:unix_socket={$socketPath};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "🎉 ¡Conexión exitosa!";
    
    // Probar consulta simple
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Consulta testeada correctamente";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<br>Socket path: " . $socketPath;
}
?>