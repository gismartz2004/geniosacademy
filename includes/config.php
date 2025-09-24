<?php
// database.php - Configuración de conexión
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Obtener variables de entorno
        $this->host = getenv('DB_SOCKET') ?: '/cloudsql/' . getenv('INSTANCE_CONNECTION_NAME');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Conexión con socket de Unix
            $dsn = "mysql:unix_socket={$this->host};dbname={$this->db_name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            echo "✅ Conexión exitosa via Unix Socket";
            
        } catch (PDOException $exception) {
            echo "❌ Error de conexión: " . $exception->getMessage();
            echo "<br>DSN usado: mysql:unix_socket={$this->host};dbname={$this->db_name}";
        }
        
        return $this->conn;
    }
}

// Uso:
$database = new Database();
$db = $database->getConnection();
?>