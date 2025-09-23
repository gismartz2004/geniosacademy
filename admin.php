<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Verificar acceso de administrador con clave maestra
$admin_key = isset($_POST['admin_key']) ? $_POST['admin_key'] : (isset($_SESSION['admin_access']) ? $_SESSION['admin_access'] : '');

// Clave maestra para acceso administrativo (cambia esto en producción)
define('MASTER_ADMIN_KEY', 'GeiosBot2024Admin!');

if ($admin_key !== MASTER_ADMIN_KEY && !isset($_SESSION['admin_access'])) {
    // Mostrar formulario de acceso de administrador
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $error = "Clave de administrador incorrecta";
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso de Administrador - GeiosBot Academy</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="login-body">
        <div class="login-container">
            <div class="login-header">
                <img src="https://via.placeholder.com/100x100?text=GB" alt="GeiosBot Academy Logo" class="logo">
                <h1>Acceso de Administrador</h1>
                <p>GeiosBot Academy - Panel de gestión</p>
            </div>
            
            <form method="POST" action="" class="login-form">
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="admin_key">Clave Maestra de Administración</label>
                    <input type="password" id="admin_key" name="admin_key" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Acceder al Panel</button>
                <a href="index.php" class="btn btn-secondary" style="margin-top: 10px;">Volver al Inicio</a>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
} else {
    // Acceso concedido, establecer sesión
    $_SESSION['admin_access'] = MASTER_ADMIN_KEY;
}

// Conexión a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $tipo = $_POST['tipo'];
    
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = "El email ya está registrado en el sistema";
    } else {
        // Hash de la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, tipo) VALUES (:nombre, :email, :password, :tipo)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':tipo', $tipo);
        
        if ($stmt->execute()) {
            $success = "Usuario creado exitosamente: " . htmlspecialchars($email);
        } else {
            $error = "Error al crear el usuario: " . $stmt->errorInfo()[2];
        }
    }
}

// Procesar eliminación de usuario
if (isset($_GET['eliminar_usuario'])) {
    $usuario_id = $_GET['eliminar_usuario'];
    
    // No permitir auto-eliminación
    if ($usuario_id != $_SESSION['user_id'] || !isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $usuario_id);
        
        if ($stmt->execute()) {
            $success = "Usuario eliminado correctamente";
        } else {
            $error = "Error al eliminar el usuario: " . $stmt->errorInfo()[2];
        }
    } else {
        $error = "No puedes eliminarte a ti mismo";
    }
}

// Obtener todos los usuarios
$stmt = $conn->prepare("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los cursos
$stmt = $conn->prepare("SELECT c.*, u.nombre as profesor_nombre FROM cursos c INNER JOIN usuarios u ON c.profesor_id = u.id ORDER BY c.fecha_creacion DESC");
$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'profesor'");
$stmt->execute();
$total_profesores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'estudiante'");
$stmt->execute();
$total_estudiantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cursos");
$stmt->execute();
$total_cursos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM contenido");
$stmt->execute();
$total_contenido = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - GeiosBot Academy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="brand">
            <img src="https://via.placeholder.com/40x40?text=GB" alt="Logo">
            <h1>GeiosBot Academy - Panel de Administración</h1>
        </div>
        <div class="user-menu">
            <a href="index.php" class="btn btn-secondary">Volver al Sitio</a>
            <a href="logout.php?admin=1" class="btn btn-danger">Cerrar Sesión Admin</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-menu">
                <li><a href="#dashboard" class="active">Dashboard</a></li>
                <li><a href="#usuarios">Gestión de Usuarios</a></li>
                <li><a href="#cursos">Gestión de Cursos</a></li>
                <li><a href="#estadisticas">Estadísticas</a></li>
            </div>
        </div>
        
        <div class="main-content">
            <div class="welcome-banner">
                <h1>Panel de Administración</h1>
                <p>Gestión completa de GeiosBot Academy</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="dashboard">Dashboard</button>
                    <button class="tab-button" data-tab="usuarios">Usuarios (<?php echo count($usuarios); ?>)</button>
                    <button class="tab-button" data-tab="cursos">Cursos (<?php echo count($cursos); ?>)</button>
                    <button class="tab-button" data-tab="estadisticas">Estadísticas</button>
                </div>
                
                <div class="tab-panes">
                    <!-- Dashboard -->
                    <div id="dashboard" class="tab-pane active">
                        <div class="stats-container">
                            <div class="stat-card">
                                <h3><?php echo $total_profesores; ?></h3>
                                <p>Profesores</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $total_estudiantes; ?></h3>
                                <p>Estudiantes</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $total_cursos; ?></h3>
                                <p>Cursos Activos</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $total_contenido; ?></h3>
                                <p>Contenidos Subidos</p>
                            </div>
                        </div>
                        
                        <div class="quick-actions">
                            <h2>Acciones Rápidas</h2>
                            <div class="action-buttons">
                                <button onclick="adminCreateUser('profesor')" class="btn btn-primary">Crear Profesor</button>
                                <button onclick="adminCreateUser('estudiante')" class="btn btn-primary">Crear Estudiante</button>
                                <a href="#usuarios" class="btn btn-secondary">Ver Todos los Usuarios</a>
                                <a href="#cursos" class="btn btn-secondary">Ver Todos los Cursos</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gestión de Usuarios -->
                    <div id="usuarios" class="tab-pane">
                        <div class="form-container">
                            <h2 class="form-title">Crear Nuevo Usuario</h2>
                            <form method="POST" action="" id="admin-create-user-form">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo</label>
                                    <input type="text" id="nombre" name="nombre" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Contraseña</label>
                                    <input type="password" id="password" name="password" required>
                                    <button type="button" class="toggle-password" data-target="password">Mostrar</button>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tipo">Tipo de Usuario</label>
                                    <select id="tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="profesor">Profesor</option>
                                        <option value="estudiante">Estudiante</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="crear_usuario" class="btn btn-primary">Crear Usuario</button>
                            </form>
                        </div>
                        
                        <div class="table-section">
                            <h2>Usuarios Registrados</h2>
                            <div class="table-controls">
                                <input type="text" class="table-search" placeholder="Buscar usuarios..." data-table="usuarios-table">
                            </div>
                            
                            <div class="table-container">
                                <table id="usuarios-table" class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo $usuario['id']; ?></td>
                                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <span class="badge">
                                                        <?php echo ($usuario['tipo'] == 'profesor') ? 'Profesor' : 'Estudiante'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                                <td>
                                                    <a href="?eliminar_usuario=<?php echo $usuario['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gestión de Cursos -->
                    <div id="cursos" class="tab-pane">
                        <div class="table-section">
                            <h2>Todos los Cursos</h2>
                            <div class="table-controls">
                                <input type="text" class="table-search" placeholder="Buscar cursos..." data-table="cursos-table">
                            </div>
                            
                            <div class="table-container">
                                <table id="cursos-table" class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Profesor</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cursos as $curso): ?>
                                            <tr>
                                                <td><?php echo $curso['id']; ?></td>
                                                <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($curso['profesor_nombre']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($curso['fecha_creacion'])); ?></td>
                                                <td>
                                                    <a href="cursos.php?id=<?php echo $curso['id']; ?>" class="btn btn-secondary">Ver Curso</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div id="estadisticas" class="tab-pane">
                        <div class="stats-container">
                            <div class="stat-card large">
                                <h3><?php echo $total_profesores; ?></h3>
                                <p>Profesores Registrados</p>
                            </div>
                            <div class="stat-card large">
                                <h3><?php echo $total_estudiantes; ?></h3>
                                <p>Estudiantes Registrados</p>
                            </div>
                            <div class="stat-card large">
                                <h3><?php echo $total_cursos; ?></h3>
                                <p>Cursos Activos</p>
                            </div>
                        </div>
                        
                        <div class="charts-container">
                            <h2>Distribución de Usuarios</h2>
                            <div class="chart">
                                <div class="chart-bar" style="width: <?php echo ($total_profesores / ($total_profesores + $total_estudiantes)) * 100; ?>%">
                                    <span>Profesores: <?php echo $total_profesores; ?></span>
                                </div>
                                <div class="chart-bar secondary" style="width: <?php echo ($total_estudiantes / ($total_profesores + $total_estudiantes)) * 100; ?>%">
                                    <span>Estudiantes: <?php echo $total_estudiantes; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>