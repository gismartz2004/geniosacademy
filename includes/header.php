<?php
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $page_title ?? 'Panel'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="brand">
            <img src="https://via.placeholder.com/40x40?text=GB" alt="GeiosBot Academy Logo" class="logo">
            <h1>GeiosBot Academy</h1>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <?php if ($_SESSION['user_avatar']): ?>
                    <img src="uploads/avatares/<?php echo $_SESSION['user_avatar']; ?>" alt="Avatar" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-initials">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                <span class="user-role">(<?php echo ($_SESSION['user_type'] == 'profesor') ? 'Profesor' : 'Estudiante'; ?>)</span>
            </div>
            <div class="dropdown-menu">
                <a href="perfil.php" class="dropdown-item">Mi Perfil</a>
                <a href="dashboard.php" class="dropdown-item">Dashboard</a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item logout">Cerrar Sesión</a>
            </div>
        </div>
    </div>