<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn() || !isProfessor()) {
    header("Location: login.php");
    exit();
}

// Verificar si se proporcionó un ID de curso
if (!isset($_GET['curso_id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = $_GET['curso_id'];
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Verificar que el curso pertenece al profesor
$stmt = $conn->prepare("SELECT * FROM cursos WHERE id = :curso_id AND profesor_id = :profesor_id");
$stmt->bindParam(':curso_id', $curso_id);
$stmt->bindParam(':profesor_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: cursos.php");
    exit();
}

$curso = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar adición de estudiante
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_estudiante'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Buscar estudiante por email
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND tipo = 'estudiante'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        $estudiante_id = $estudiante['id'];
        
        // Verificar si ya está inscrito
        $stmt2 = $conn->prepare("SELECT id FROM estudiantes_cursos WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id");
        $stmt2->bindParam(':estudiante_id', $estudiante_id);
        $stmt2->bindParam(':curso_id', $curso_id);
        $stmt2->execute();
        
        if ($stmt2->rowCount() == 0) {
            // Inscribir estudiante
            $stmt3 = $conn->prepare("INSERT INTO estudiantes_cursos (estudiante_id, curso_id) VALUES (:estudiante_id, :curso_id)");
            $stmt3->bindParam(':estudiante_id', $estudiante_id);
            $stmt3->bindParam(':curso_id', $curso_id);
            
            if ($stmt3->execute()) {
                $success = "Estudiante agregado exitosamente";
            } else {
                $error = "Error al agregar el estudiante";
            }
        } else {
            $error = "El estudiante ya está inscrito en este curso";
        }
    } else {
        $error = "No se encontró un estudiante con ese email";
    }
}

// Procesar eliminación de estudiante
if (isset($_GET['eliminar'])) {
    $estudiante_id = $_GET['eliminar'];
    
    $stmt = $conn->prepare("DELETE FROM estudiantes_cursos WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id");
    $stmt->bindParam(':estudiante_id', $estudiante_id);
    $stmt->bindParam(':curso_id', $curso_id);
    
    if ($stmt->execute()) {
        $success = "Estudiante eliminado del curso";
    } else {
        $error = "Error al eliminar el estudiante";
    }
}

// Obtener estudiantes inscritos en el curso
$stmt = $conn->prepare("SELECT u.id, u.nombre, u.email, u.avatar, ec.fecha_inscripcion 
                       FROM usuarios u 
                       INNER JOIN estudiantes_cursos ec ON u.id = ec.estudiante_id 
                       WHERE ec.curso_id = :curso_id 
                       ORDER BY u.nombre");
$stmt->bindParam(':curso_id', $curso_id);
$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los estudiantes (para búsqueda)
$stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE tipo = 'estudiante' ORDER BY nombre");
$stmt->execute();
$todos_estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - GeiosBot Academy</title>
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
                <h1>Gestión de Estudiantes: <?php echo $curso['nombre']; ?></h1>
                <p>Administra los estudiantes inscritos en este curso</p>
            </div>
            
            <div class="form-container">
                <h2 class="form-title">Agregar Estudiante al Curso</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email del Estudiante</label>
                        <input type="email" id="email" name="email" required list="estudiantesList">
                        <datalist id="estudiantesList">
                            <?php foreach ($todos_estudiantes as $est): ?>
                                <option value="<?php echo $est['email']; ?>"><?php echo $est['nombre']; ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <button type="submit" name="agregar_estudiante" class="btn btn-primary">Agregar Estudiante</button>
                </form>
            </div>
            
            <div class="students-section">
                <h2>Estudiantes Inscritos (<?php echo count($estudiantes); ?>)</h2>
                
                <?php if (count($estudiantes) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Email</th>
                                    <th>Fecha de inscripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <?php if ($estudiante['avatar']): ?>
                                                    <img src="uploads/avatares/<?php echo $estudiante['avatar']; ?>" alt="<?php echo $estudiante['nombre']; ?>" class="user-avatar">
                                                <?php else: ?>
                                                    <div class="user-avatar"><?php echo substr($estudiante['nombre'], 0, 1); ?></div>
                                                <?php endif; ?>
                                                <span><?php echo $estudiante['nombre']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $estudiante['email']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($estudiante['fecha_inscripcion'])); ?></td>
                                        <td>
                                            <a href="?curso_id=<?php echo $curso_id; ?>&eliminar=<?php echo $estudiante['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar a este estudiante del curso?')">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No hay estudiantes inscritos en este curso.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>