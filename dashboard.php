<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Consultas según el tipo de usuario
if ($user_type == 'profesor') {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE profesor_id = :profesor_id");
    $stmt->bindParam(':profesor_id', $user_id);
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar estudiantes inscritos en todos los cursos del profesor
    $total_estudiantes = 0;
    foreach ($cursos as $curso) {
        $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM estudiantes_cursos WHERE curso_id = :curso_id");
        $stmt2->bindParam(':curso_id', $curso['id']);
        $stmt2->execute();
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);
        $total_estudiantes += $result['total'];
    }
} else {
    // Para estudiantes: obtener cursos inscritos
    $stmt = $conn->prepare("SELECT c.* FROM cursos c 
                           INNER JOIN estudiantes_cursos ec ON c.id = ec.curso_id 
                           WHERE ec.estudiante_id = :estudiante_id");
    $stmt->bindParam(':estudiante_id', $user_id);
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar contenido disponible en los cursos
    $total_contenido = 0;
    foreach ($cursos as $curso) {
        $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM contenido WHERE curso_id = :curso_id");
        $stmt2->bindParam(':curso_id', $curso['id']);
        $stmt2->execute();
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);
        $total_contenido += $result['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GeiosBot Academy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <li>
    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
    </li>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div> 
        
        <div class="main-content">
            <div class="welcome-banner">
                <h1>Bienvenido, <?php echo $_SESSION['user_name']; ?></h1>
                <p>Panel principal de GeiosBot Academy</p>
            </div>
            
            <div class="stats-container">
                <?php if ($user_type == 'profesor'): ?>
                    <div class="stat-card">
                        <h3><?php echo count($cursos); ?></h3>
                        <p>Cursos creados</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_estudiantes; ?></h3>
                        <p>Estudiantes inscritos</p>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <h3><?php echo count($cursos); ?></h3>
                        <p>Cursos inscritos</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_contenido; ?></h3>
                        <p>Contenidos disponibles</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="courses-section">
                <h2><?php echo ($user_type == 'profesor') ? 'Mis Cursos' : 'Cursos Inscritos'; ?></h2>
                
                <?php if (count($cursos) > 0): ?>
                    <div class="courses-grid">
                        <?php foreach ($cursos as $curso): ?>
                            <div class="course-card">
                                <div class="course-image">
                                    <img src="https://via.placeholder.com/300x150?text=Curso" alt="<?php echo $curso['nombre']; ?>">
                                </div>
                                <div class="course-info">
                                    <h3><?php echo $curso['nombre']; ?></h3>
                                    <p><?php echo substr($curso['descripcion'], 0, 100); ?>...</p>
                                    <div class="course-actions">
                                        <a href="cursos.php?id=<?php echo $curso['id']; ?>" class="btn btn-secondary">Ver curso</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No tienes cursos <?php echo ($user_type == 'profesor') ? 'creados' : 'inscritos'; ?> aún.</p>
                        <?php if ($user_type == 'profesor'): ?>
                            <a href="cursos.php?action=create" class="btn btn-primary">Crear mi primer curso</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>