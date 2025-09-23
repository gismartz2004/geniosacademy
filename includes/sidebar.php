<?php
// Determinar la página actual para resaltar el enlace activo
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar-menu">
    <ul>
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                <span class="text">Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="cursos.php" class="<?php echo ($current_page == 'cursos.php') ? 'active' : ''; ?>">
                <span class="icon">📚</span>
                <span class="text">Mis Cursos</span>
            </a>
        </li>
        
        <?php if ($_SESSION['user_type'] == 'profesor'): ?>
        <li>
            <a href="contenido.php" class="<?php echo ($current_page == 'contenido.php') ? 'active' : ''; ?>">
                <span class="icon">🎬</span>
                <span class="text">Contenido</span>
            </a>
        </li>
        
        <li>
            <a href="estudiantes.php" class="<?php echo ($current_page == 'estudiantes.php') ? 'active' : ''; ?>">
                <span class="icon">👥</span>
                <span class="text">Estudiantes</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li>
            <a href="perfil.php" class="<?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?>">
                <span class="icon">👤</span>
                <span class="text">Mi Perfil</span>
            </a>
        </li>
        
        <?php if ($_SESSION['user_type'] == 'profesor'): ?>
        <li class="divider"></li>
        <li>
            <a href="admin.php" class="<?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                <span class="icon">⚙️</span>
                <span class="text">Administración</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="divider"></li>
        <li>
            <a href="logout.php" class="logout">
                <span class="icon">🚪</span>
                <span class="text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <p class="version">v1.0.0</p>
        <p class="copyright">© 2024 GeiosBot Academy</p>
    </div>
</nav>