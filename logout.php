<?php
require_once 'includes/config.php';

// Verificar si es un cierre de sesión de administrador
$is_admin_logout = isset($_GET['admin']);

// Destruir todas las variables de sesión
if ($is_admin_logout) {
    unset($_SESSION['admin_access']);
} else {
    session_destroy();
}

// Redirigir al lugar apropiado
if ($is_admin_logout) {
    header("Location: admin.php");
} else {
    header("Location: login.php");
}
exit();
?>