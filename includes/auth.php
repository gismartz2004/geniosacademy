<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isProfessor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'profesor';
}

function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'estudiante';
}

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }
    
    public function register($nombre, $email, $password, $tipo) {
        // Verificar si el email ya existe
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false; // Email ya registrado
        }
        
        // Hash de la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario
        $stmt = $this->conn->prepare("INSERT INTO usuarios (nombre, email, password, tipo) VALUES (:nombre, :email, :password, :tipo)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':tipo', $tipo);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
}
?>