<?php
require_once 'config.php';

class Database {
    private $conn;

    public function getConnection() {
        // Si ya existe la conexión, devolverla
        if ($this->conn) {
            return $this->conn;
        }

        try {
            // Usar la conexión PDO definida en config.php
            global $pdo;
            $this->conn = $pdo;
        } catch (PDOException $exception) {
            die("Error de conexión a la base de datos: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
