<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}



if (!isset($_GET['tipo']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$tipo = $_GET['tipo'];
$contenido_id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener información del contenido
$stmt = $conn->prepare("SELECT c.*, cur.profesor_id, cur.nombre as curso_nombre 
                       FROM contenido c 
                       INNER JOIN cursos cur ON c.curso_id = cur.id 
                       WHERE c.id = :contenido_id");
$stmt->bindParam(':contenido_id', $contenido_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: dashboard.php");
    exit();
}

$contenido = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar permisos (profesor del curso o estudiante inscrito)
if ($_SESSION['user_type'] == 'profesor' && $contenido['profesor_id'] != $user_id) {
    header("Location: dashboard.php");
    exit();
} elseif ($_SESSION['user_type'] == 'estudiante') {
    $stmt = $conn->prepare("SELECT * FROM estudiantes_cursos 
                           WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id");
    $stmt->bindParam(':estudiante_id', $user_id);
    $stmt->bindParam(':curso_id', $contenido['curso_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: dashboard.php");
        exit();
    }
}

// Obtener diapositivas si es una presentación
if ($tipo == 'diapositivas') {
    $stmt = $conn->prepare("SELECT * FROM diapositivas WHERE contenido_id = :contenido_id ORDER BY orden");
    $stmt->bindParam(':contenido_id', $contenido_id);
    $stmt->execute();
    $diapositivas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay diapositivas adicionales, usarlas en lugar del archivo principal
    if (count($diapositivas) > 0) {
        $contenido['tiene_diapositivas'] = true;
    }
}

// Parsear metadata si existe
if ($contenido['metadata']) {
    $metadata = json_decode($contenido['metadata'], true);
}

// Función para extraer el ID de un proyecto Scratch desde la URL
function extractScratchId($url) {
    $pattern = '/scratch\.mit\.edu\/projects\/(\d+)/';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : '';
}

// Función para obtener la ruta correcta del archivo según su tipo
function getFilePath($tipo, $archivo) {
    $base_path = UPLOAD_DIR;
    
    switch ($tipo) {
        case 'video':
            return $base_path . 'videos/' . $archivo;
        case 'diapositivas':
            return $base_path . 'diapositivas/' . $archivo;
        case 'scratch':
            return $base_path . 'scratch/' . $archivo;
        case 'simulador':
            return $base_path . 'simuladores/' . $archivo;
        default:
            return $base_path . $archivo;
    }
}
#validar que el estudiante ha visto el contenido
if ($user_type == 'estudiante' && isset($_GET['id'])) {
    $contenido_id = intval($_GET['id']);
    $stmt = $conn->prepare("INSERT IGNORE INTO vistas_contenido (contenido_id, estudiante_id) 
                            VALUES (:contenido_id, :estudiante_id)");
    $stmt->bindParam(':contenido_id', $contenido_id);
    $stmt->bindParam(':estudiante_id', $user_id);
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Contenido - GeiosBot Academy</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if ($tipo == 'scratch'): ?>
    <!-- Cargar el visualizador de Scratch -->
    <script src="https://cdn.jsdelivr.net/gh/scratchfoundation/scratch-gui@develop/dist/webpack/scratch-gui.js"></script>
    <?php endif; ?>
</head>
<body>
    <?php 
    // Incluir header correctamente
    $page_title = "Ver Contenido";
    include 'includes/header.php'; 
    ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="content-viewer">
                <div class="viewer-header">
                    <h1><?php echo htmlspecialchars($contenido['titulo']); ?></h1>
                    <p>Curso: <?php echo htmlspecialchars($contenido['curso_nombre']); ?></p>
                    <a href="contenido.php?curso_id=<?php echo $contenido['curso_id']; ?>" class="btn btn-secondary">Volver al contenido</a>
                </div>
                
                <div class="viewer-body">
                    <?php if ($tipo == 'video'): ?>
                        <!-- Visualizador de Video -->
                        <div class="video-container">
                            <video controls width="100%" style="max-height: 70vh;">
                                <source src="<?php echo getFilePath('video', $contenido['archivo']); ?>" type="video/mp4">
                                Tu navegador no soporta el elemento de video.
                            </video>
                        </div>
                        
                    <?php elseif ($tipo == 'diapositivas'): ?>
                        <!-- Visualizador de Diapositivas -->
                        <div class="slideshow-container">
                            <?php if (isset($contenido['tiene_diapositivas']) && $contenido['tiene_diapositivas']): ?>
                                <!-- Presentación con múltiples diapositivas -->
                                <div class="slideshow-controls">
                                    <button id="prevSlide" class="btn btn-secondary">Anterior</button>
                                    <span id="slideCounter">1 / <?php echo count($diapositivas); ?></span>
                                    <button id="nextSlide" class="btn btn-secondary">Siguiente</button>
                                </div>
                                
                                <div class="slideshow">
                                    <?php foreach ($diapositivas as $index => $diapositiva): ?>
                                        <div class="slide <?php echo $index == 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                                            <?php 
                                            $extension = strtolower(pathinfo($diapositiva['archivo'], PATHINFO_EXTENSION));
                                            if ($extension === 'pdf'): 
                                            ?>
                                                <embed src="<?php echo getFilePath('diapositivas', $diapositiva['archivo']); ?>" type="application/pdf" width="100%" height="600px">
                                            <?php else: ?>
                                                <img src="<?php echo getFilePath('diapositivas', $diapositiva['archivo']); ?>" alt="<?php echo htmlspecialchars($diapositiva['titulo']); ?>" style="max-width: 100%; max-height: 70vh;">
                                            <?php endif; ?>
                                            <div class="slide-caption"><?php echo htmlspecialchars($diapositiva['titulo']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php else: ?>
                                <!-- Diapositiva única -->
                                <?php 
                                $extension = strtolower(pathinfo($contenido['archivo'], PATHINFO_EXTENSION));
                                if ($extension === 'pdf'): 
                                ?>
                                    <embed src="<?php echo getFilePath('diapositivas', $contenido['archivo']); ?>" type="application/pdf" width="100%" height="800px">
                                <?php else: ?>
                                    <div class="single-slide">
                                        <img src="<?php echo getFilePath('diapositivas', $contenido['archivo']); ?>" alt="<?php echo htmlspecialchars($contenido['titulo']); ?>" style="max-width: 100%; max-height: 70vh;">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                    <?php elseif ($tipo == 'scratch'): ?>
                        <!-- Visualizador de Proyectos Scratch -->
                        <div class="scratch-container">
                            <?php if (isset($metadata) && $metadata['tipo'] == 'url'): ?>
                                <!-- Proyecto Scratch desde URL -->
                                <div class="scratch-embed">
                                    <iframe src="https://scratch.mit.edu/projects/<?php echo extractScratchId($metadata['url']); ?>/embed" 
                                            allowtransparency="true" width="100%" height="600" frameborder="0" scrolling="no" allowfullscreen>
                                    </iframe>
                                </div>
                                <div class="scratch-actions">
                                    <a href="<?php echo htmlspecialchars($metadata['url']); ?>" target="_blank" class="btn btn-primary">Abrir en Scratch</a>
                                </div>
                            <?php else: ?>
                                <!-- Proyecto Scratch desde archivo .sb3 -->
                                <div class="scratch-local">
                                    <p>Proyecto Scratch cargado desde archivo. Descarga el proyecto para abrirlo en Scratch.</p>
                                    <a href="<?php echo getFilePath('scratch', $contenido['archivo']); ?>" download class="btn btn-primary">Descargar Proyecto (.sb3)</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    <?php elseif ($tipo == 'simulador'): ?>
                        <!-- Visualizador de Simuladores -->
                        <div class="simulator-container">
                            <?php
                            $extension = strtolower(pathinfo($contenido['archivo'], PATHINFO_EXTENSION));
                            if ($extension == 'zip'): ?>
                                <p>Simulador empaquetado. Descarga y descomprime para ejecutar.</p>
                                <a href="<?php echo getFilePath('simulador', $contenido['archivo']); ?>" download class="btn btn-primary">Descargar Simulador</a>
                            <?php elseif ($extension == 'html' || $extension == 'htm'): ?>
                                <iframe src="<?php echo getFilePath('simulador', $contenido['archivo']); ?>" width="100%" height="600" frameborder="0" style="border: 1px solid #ccc;"></iframe>
                            <?php else: ?>
                                <p>Formato de simulador no compatible. Formato recibido: <?php echo $extension; ?></p>
                                <a href="<?php echo getFilePath('simulador', $contenido['archivo']); ?>" download class="btn btn-primary">Descargar Archivo</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert error">
                            <p>Tipo de contenido no reconocido: <?php echo htmlspecialchars($tipo); ?></p>
                            <a href="contenido.php?curso_id=<?php echo $contenido['curso_id']; ?>" class="btn btn-secondary">Volver al contenido</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($tipo == 'diapositivas' && isset($contenido['tiene_diapositivas']) && $contenido['tiene_diapositivas']): ?>
    <script>
    // Navegación de diapositivas
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;
    const slideCounter = document.getElementById('slideCounter');
    
    function showSlide(index) {
        // Ocultar todas las diapositivas
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Mostrar la diapositiva actual
        slides[index].classList.add('active');
        
        // Actualizar contador
        slideCounter.textContent = `${index + 1} / ${totalSlides}`;
        
        // Actualizar estado de botones
        document.getElementById('prevSlide').disabled = (index === 0);
        document.getElementById('nextSlide').disabled = (index === totalSlides - 1);
        
        currentSlide = index;
    }
    
    document.getElementById('prevSlide').addEventListener('click', () => {
        if (currentSlide > 0) {
            showSlide(currentSlide - 1);
        }
    });
    
    document.getElementById('nextSlide').addEventListener('click', () => {
        if (currentSlide < totalSlides - 1) {
            showSlide(currentSlide + 1);
        }
    });
    
    // Teclado navegación
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            document.getElementById('prevSlide').click();
        } else if (e.key === 'ArrowRight') {
            document.getElementById('nextSlide').click();
        }
    });
    
    // Inicializar
    showSlide(0);
    </script>
    <?php endif; ?>
</body>
</html>