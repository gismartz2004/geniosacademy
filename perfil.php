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

// Obtener información del usuario actual
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Verificar si el email ya existe (excluyendo el usuario actual)
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :user_id");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = "El email ya está en uso por otro usuario";
    } else {
        // Actualizar información del usuario
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :user_id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Actualizar sesión
            $_SESSION['user_name'] = $nombre;
            
            $success = "Perfil actualizado correctamente";
            
            // Actualizar datos locales
            $usuario['nombre'] = $nombre;
            $usuario['email'] = $email;
        } else {
            $error = "Error al actualizar el perfil";
        }
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $nuevo_password = $_POST['nuevo_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    // Verificar contraseña actual
    if (password_verify($password_actual, $usuario['password'])) {
        if ($nuevo_password === $confirmar_password) {
            // Actualizar contraseña
            $hashed_password = password_hash($nuevo_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $success_pass = "Contraseña actualizada correctamente";
            } else {
                $error_pass = "Error al actualizar la contraseña";
            }
        } else {
            $error_pass = "Las contraseñas nuevas no coinciden";
        }
    } else {
        $error_pass = "La contraseña actual es incorrecta";
    }
}

// Procesar subida de avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_avatar']) && isset($_FILES['avatar'])) {
    require_once 'includes/upload.php';
    
    $upload = new Upload();
    $avatar = $upload->processAvatar($_FILES['avatar']);
    
    if ($avatar) {
        // Eliminar avatar anterior si existe
        if ($usuario['avatar'] && file_exists('uploads/avatares/' . $usuario['avatar'])) {
            unlink('uploads/avatares/' . $usuario['avatar']);
        }
        
        // Actualizar base de datos
        $stmt = $conn->prepare("UPDATE usuarios SET avatar = :avatar WHERE id = :user_id");
        $stmt->bindParam(':avatar', $avatar);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Actualizar sesión
            $_SESSION['user_avatar'] = $avatar;
            $usuario['avatar'] = $avatar;
            
            $success_avatar = "Avatar actualizado correctamente";
        } else {
            $error_avatar = "Error al guardar el avatar en la base de datos";
        }
    } else {
        $error_avatar = $upload->getError();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - GeiosBot Academy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="welcome-banner">
                <h1>Mi Perfil</h1>
                <p>Gestiona tu información personal en GeiosBot Academy</p>
            </div>
            
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="avatar-container">
                        <?php if ($usuario['avatar']): ?>
                            <img src="uploads/avatares/<?php echo $usuario['avatar']; ?>" alt="Avatar" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar-initials">
                                <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="avatar-form">
                            <div class="form-group">
                                <label for="avatar" class="avatar-label">Cambiar avatar</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                            </div>
                            <button type="submit" name="subir_avatar" class="btn btn-secondary">Subir</button>
                            
                            <?php if (isset($success_avatar)): ?>
                                <div class="alert success"><?php echo $success_avatar; ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_avatar)): ?>
                                <div class="alert error"><?php echo $error_avatar; ?></div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="user-info">
                        <h3><?php echo $usuario['nombre']; ?></h3>
                        <p><?php echo $usuario['email']; ?></p>
                        <p class="user-role"><?php echo ($usuario['tipo'] == 'profesor') ? 'Profesor' : 'Estudiante'; ?></p>
                        <p class="user-since">Miembro desde: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></p>
                    </div>
                </div>
                
                <div class="profile-content">
                    <div class="form-container">
                        <h2 class="form-title">Información Personal</h2>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert error"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nombre">Nombre completo</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo">Tipo de usuario</label>
                                <input type="text" id="tipo" value="<?php echo ($usuario['tipo'] == 'profesor') ? 'Profesor' : 'Estudiante'; ?>" disabled>
                                <small>El tipo de usuario no puede ser modificado</small>
                            </div>
                            
                            <button type="submit" name="actualizar_perfil" class="btn btn-primary">Actualizar Información</button>
                        </form>
                    </div>
                    
                    <div class="form-container">
                        <h2 class="form-title">Cambiar Contraseña</h2>
                        
                        <?php if (isset($success_pass)): ?>
                            <div class="alert success"><?php echo $success_pass; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_pass)): ?>
                            <div class="alert error"><?php echo $error_pass; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="password_actual">Contraseña actual</label>
                                <input type="password" id="password_actual" name="password_actual" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="nuevo_password">Nueva contraseña</label>
                                <input type="password" id="nuevo_password" name="nuevo_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmar_password">Confirmar nueva contraseña</label>
                                <input type="password" id="confirmar_password" name="confirmar_password" required>
                            </div>
                            
                            <button type="submit" name="cambiar_password" class="btn btn-primary">Cambiar Contraseña</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- <?php include 'includes/footer.php'; ?> -->
    
    <script>
    // Mostrar vista previa del avatar
    document.getElementById('avatar').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                // Si ya existe una imagen de avatar, reemplazarla
                var avatarImg = document.querySelector('.profile-avatar');
                if (avatarImg) {
                    avatarImg.src = e.target.result;
                } else {
                    // Si no existe, crear una
                    var avatarContainer = document.querySelector('.avatar-container');
                    var initialsDiv = document.querySelector('.profile-avatar-initials');
                    
                    if (initialsDiv) {
                        initialsDiv.style.display = 'none';
                    }
                    
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-avatar';
                    avatarContainer.insertBefore(img, avatarContainer.firstChild);
                }
            }
            
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    </script>
</body>
</html>