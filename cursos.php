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
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Procesar creación de curso (solo profesores)
if ($user_type == 'profesor' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_curso'])) {
    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $descripcion = filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING);
    
    $stmt = $conn->prepare("INSERT INTO cursos (nombre, descripcion, profesor_id) VALUES (:nombre, :descripcion, :profesor_id)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':profesor_id', $user_id);
    
    if ($stmt->execute()) {
        $success = "Curso creado exitosamente";
    } else {
        $error = "Error al crear el curso";
    }
}

// Obtener cursos según el tipo de usuario
if ($user_type == 'profesor') {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE profesor_id = :profesor_id ORDER BY fecha_creacion DESC");
    $stmt->bindParam(':profesor_id', $user_id);
} else {
    $stmt = $conn->prepare("SELECT c.* FROM cursos c 
                           INNER JOIN estudiantes_cursos ec ON c.id = ec.curso_id 
                           WHERE ec.estudiante_id = :estudiante_id ORDER BY ec.fecha_inscripcion DESC");
    $stmt->bindParam(':estudiante_id', $user_id);
}

$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos - GeiosBot Academy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
     <li>
    <a href="dashboard.php" class="btn btn-danger">INICIO</a>
    </li>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="welcome-banner">
                <h1><?php echo ($user_type == 'profesor') ? 'Mis Cursos' : 'Cursos Inscritos'; ?></h1>
                <p>Gestiona tus cursos en GeiosBot Academy</p>
            </div>
            
            <?php if ($user_type == 'profesor'): ?>
                <div class="form-container">
                    <h2 class="form-title">Crear Nuevo Curso</h2>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="nombre">Nombre del Curso</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" name="crear_curso" class="btn btn-primary">Crear Curso</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="courses-section">
                <h2>Lista de Cursos</h2>
                
                <?php if (count($cursos) > 0): ?>
                    <div class="courses-grid">
                        <?php foreach ($cursos as $curso): ?>
                            <div class="course-card">
                                <div class="course-image">
                                    <img src="https://via.placeholder.com/300x150?text=Curso" alt="<?php echo $curso['nombre']; ?>">
                                </div>
                                <div class="course-info">
                                    <h3><?php echo $curso['nombre']; ?></h3>
                                    <p><?php echo $curso['descripcion']; ?></p>
                                    <p><small>Creado: <?php echo date('d/m/Y', strtotime($curso['fecha_creacion'])); ?></small></p>
                                    
                                    <div class="course-actions">
                                        <a href="contenido.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-secondary">
                                            <?php echo ($user_type == 'profesor') ? 'Gestionar Contenido' : 'Ver Contenido'; ?>
                                        </a>
                                        
                                        <?php if ($user_type == 'profesor'): ?>
                                            <a href="estudiantes.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-secondary">Gestionar Estudiantes</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No tienes cursos <?php echo ($user_type == 'profesor') ? 'creados' : 'inscritos'; ?> aún.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>