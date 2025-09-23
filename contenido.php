<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/upload.php';

if (!isLoggedIn()) {
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
$user_type = $_SESSION['user_type'];

// Verificar permisos sobre el curso
if ($user_type == 'profesor') {
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = :curso_id AND profesor_id = :profesor_id");
    $stmt->bindParam(':curso_id', $curso_id);
    $stmt->bindParam(':profesor_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: cursos.php");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Para estudiantes, verificar que esté inscrito en el curso
    $stmt = $conn->prepare("SELECT c.* FROM cursos c 
                           INNER JOIN estudiantes_cursos ec ON c.id = ec.curso_id 
                           WHERE c.id = :curso_id AND ec.estudiante_id = :estudiante_id");
    $stmt->bindParam(':curso_id', $curso_id);
    $stmt->bindParam(':estudiante_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: cursos.php");
        exit();
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================================================
   ELIMINAR CONTENIDO (SOLO PROFESORES)
   ========================================================== */
if ($user_type == 'profesor' && isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Verificar que el contenido pertenece a este curso
    $stmt = $conn->prepare("SELECT * FROM contenido WHERE id = :id AND curso_id = :curso_id");
    $stmt->bindParam(':id', $delete_id);
    $stmt->bindParam(':curso_id', $curso_id);
    $stmt->execute();
    $contenidoEliminar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($contenidoEliminar) {
        // Eliminar diapositivas asociadas
        if ($contenidoEliminar['tipo'] == 'diapositivas') {
            $stmt = $conn->prepare("DELETE FROM diapositivas WHERE contenido_id = :contenido_id");
            $stmt->bindParam(':contenido_id', $delete_id);
            $stmt->execute();
        }

        // Eliminar archivo físico si existe y no es URL
        if (!empty($contenidoEliminar['archivo']) && !filter_var($contenidoEliminar['archivo'], FILTER_VALIDATE_URL)) {
            $filePath = "uploads/" . $contenidoEliminar['archivo'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Borrar el contenido en la BD
        $stmt = $conn->prepare("DELETE FROM contenido WHERE id = :id");
        $stmt->bindParam(':id', $delete_id);
        if ($stmt->execute()) {
            $success = "Contenido eliminado correctamente.";
        } else {
            $error = "No se pudo eliminar el contenido.";
        }
    } else {
        $error = "No tienes permiso para eliminar este contenido.";
    }
}

/* ==========================================================
   SUBIR CONTENIDO (SOLO PROFESORES)
   ========================================================== */
if ($user_type == 'profesor' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_contenido'])) {
    $titulo = filter_var($_POST['titulo'], FILTER_SANITIZE_STRING);
    $tipo = $_POST['tipo'];
    
    $upload = new Upload();
    $archivo = null;
    $metadata = null;

    if ($tipo == 'scratch' && !empty($_POST['scratch_url'])) {
        // Proyecto Scratch desde URL
        $scratch_url = filter_var($_POST['scratch_url'], FILTER_SANITIZE_URL);
        if (filter_var($scratch_url, FILTER_VALIDATE_URL)) {
            $archivo = $scratch_url;
            $metadata = json_encode(['tipo' => 'url', 'url' => $scratch_url]);
        } else {
            $error = "URL de Scratch no válida";
        }
    } elseif ($tipo == 'scratch' && !empty($_FILES['scratch_file']['name'])) {
        // Proyecto Scratch como archivo .sb3
        $archivo = $upload->processFile($_FILES['scratch_file'], $tipo);
    } else {
        // Archivo normal
        $archivo = $upload->processFile($_FILES['archivo'], $tipo);
    }

    if ($archivo) {
        $stmt = $conn->prepare("INSERT INTO contenido (curso_id, titulo, tipo, archivo, metadata) 
                                VALUES (:curso_id, :titulo, :tipo, :archivo, :metadata)");
        $stmt->bindParam(':curso_id', $curso_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':archivo', $archivo);
        $stmt->bindParam(':metadata', $metadata);

        if ($stmt->execute()) {
            $nuevo_contenido_id = $conn->lastInsertId();
            
            // Guardar diapositivas múltiples
            if ($tipo == 'diapositivas' && !empty($_FILES['diapositivas_adicionales'])) {
                $diapositivas = $_FILES['diapositivas_adicionales'];
                
                for ($i = 0; $i < count($diapositivas['name']); $i++) {
                    if ($diapositivas['error'][$i] === UPLOAD_ERR_OK) {
                        $archivo_diapositiva = $upload->processFile([
                            'name' => $diapositivas['name'][$i],
                            'type' => $diapositivas['type'][$i],
                            'tmp_name' => $diapositivas['tmp_name'][$i],
                            'error' => $diapositivas['error'][$i],
                            'size' => $diapositivas['size'][$i]
                        ], 'diapositivas');
                        
                        if ($archivo_diapositiva) {
                            $stmt_diap = $conn->prepare("INSERT INTO diapositivas (contenido_id, titulo, archivo, orden) 
                                                         VALUES (:contenido_id, :titulo, :archivo, :orden)");
                            $titulo_diap = "Diapositiva " . ($i + 1);
                            $orden = $i + 1;
                            $stmt_diap->bindParam(':contenido_id', $nuevo_contenido_id);
                            $stmt_diap->bindParam(':titulo', $titulo_diap);
                            $stmt_diap->bindParam(':archivo', $archivo_diapositiva);
                            $stmt_diap->bindParam(':orden', $orden);
                            $stmt_diap->execute();
                        }
                    }
                }
            }
            
            $success = "Contenido subido exitosamente";
        } else {
            $error = "Error al guardar la información en la base de datos";
        }
    } else {
        $error = $upload->getError();
    }
}

/* ==========================================================
   OBTENER CONTENIDO DEL CURSO
   ========================================================== */
$stmt = $conn->prepare("SELECT * FROM contenido WHERE curso_id = :curso_id ORDER BY fecha_subida DESC");
$stmt->bindParam(':curso_id', $curso_id);
$stmt->execute();
$contenido = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($contenido as &$item) {
    if ($item['tipo'] == 'diapositivas') {
        $stmt_diap = $conn->prepare("SELECT * FROM diapositivas WHERE contenido_id = :contenido_id ORDER BY orden");
        $stmt_diap->bindParam(':contenido_id', $item['id']);
        $stmt_diap->execute();
        $item['diapositivas'] = $stmt_diap->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($item['metadata']) {
        $item['metadata'] = json_decode($item['metadata'], true);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenido del Curso - GeiosBot Academy</title>
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
                <h1>Contenido: <?php echo $curso['nombre']; ?></h1>
                <p><?php echo $curso['descripcion']; ?></p>
            </div>
            
            <?php if ($user_type == 'profesor'): ?>
                <div class="form-container">
                    <h2 class="form-title">Subir Nuevo Contenido</h2>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="contenidoForm">
                        <div class="form-group">
                            <label for="titulo">Título del Contenido</label>
                            <input type="text" id="titulo" name="titulo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo">Tipo de Contenido</label>
                            <select id="tipo" name="tipo" required onchange="toggleFields()">
                                <option value="">Seleccionar tipo</option>
                                <option value="video">Video</option>
                                <option value="diapositivas">Diapositivas (PDF/PPT)</option>
                                <option value="scratch">Proyecto Scratch</option>
                                <option value="simulador">Simulador</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="archivoGroup">
                            <label for="archivo">Archivo</label>
                            <input type="file" id="archivo" name="archivo">
                            <small id="fileHelp">Tamaño máximo: 50MB. Formatos permitidos: videos (MP4, MOV, AVI, WMV), diapositivas (PDF, PPT, PPTX), simuladores (ZIP, HTML)</small>
                        </div>
                        
                        <div class="form-group" id="scratchGroup" style="display: none;">
                            <label for="scratch_url">URL del Proyecto Scratch</label>
                            <input type="url" id="scratch_url" name="scratch_url" placeholder="https://scratch.mit.edu/projects/123456789/">
                            <small>Pega la URL de tu proyecto Scratch</small>
                            <div style="margin-top: 10px;"><strong>O</strong></div>
                            <label for="scratch_file">Subir proyecto Scratch (.sb3)</label>
                            <input type="file" id="scratch_file" name="scratch_file" accept=".sb3">
                        </div>
                        
                        <div class="form-group" id="diapositivasGroup" style="display: none;">
                            <label>Diapositivas adicionales (opcional)</label>
                            <input type="file" name="diapositivas_adicionales[]" multiple accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png">
                            <small>Puedes seleccionar múltiples archivos</small>
                        </div>
                        
                        <button type="submit" name="subir_contenido" class="btn btn-primary">Subir Contenido</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="content-section">
                <h2>Contenido Disponible</h2>
                
                <?php if (count($contenido) > 0): ?>
                    <div class="content-grid">
                        <?php foreach ($contenido as $item): ?>
                            <div class="content-card">
                                <div class="content-header">
                                    <h3><?php echo $item['titulo']; ?></h3>
                                    <span class="badge <?php echo $item['tipo']; ?>">
                                        <?php 
                                        $tipos = [
                                            'video' => 'Video',
                                            'diapositivas' => 'Diapositivas',
                                            'scratch' => 'Scratch',
                                            'simulador' => 'Simulador'
                                        ];
                                        echo $tipos[$item['tipo']]; 
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="content-body">
                                    <p>Subido: <?php echo date('d/m/Y H:i', strtotime($item['fecha_subida'])); ?></p>
                                    
                                    <div class="content-actions">
                                        <?php if ($item['tipo'] == 'video'): ?>
                                            <a href="ver_contenido.php?tipo=video&id=<?php echo $item['id']; ?>" class="btn btn-primary">Ver Video</a>
                                        <?php elseif ($item['tipo'] == 'diapositivas'): ?>
                                            <a href="ver_contenido.php?tipo=diapositivas&id=<?php echo $item['id']; ?>" class="btn btn-primary">Ver Diapositivas</a>
                                        <?php elseif ($item['tipo'] == 'scratch'): ?>
                                            <a href="ver_contenido.php?tipo=scratch&id=<?php echo $item['id']; ?>" class="btn btn-primary">Abrir Proyecto Scratch</a>
                                        <?php elseif ($item['tipo'] == 'simulador'): ?>
                                            <a href="ver_contenido.php?tipo=simulador&id=<?php echo $item['id']; ?>" class="btn btn-primary">Ejecutar Simulador</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user_type == 'profesor'): ?>
                                            <a href="?curso_id=<?php echo $curso_id; ?>&delete=<?php echo $item['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este contenido?')">Eliminar</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No hay contenido disponible para este curso.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleFields() {
        const tipo = document.getElementById('tipo').value;
        const archivoGroup = document.getElementById('archivoGroup');
        const scratchGroup = document.getElementById('scratchGroup');
        const diapositivasGroup = document.getElementById('diapositivasGroup');
        
        archivoGroup.style.display = (tipo === 'scratch') ? 'none' : 'block';
        scratchGroup.style.display = (tipo === 'scratch') ? 'block' : 'none';
        diapositivasGroup.style.display = (tipo === 'diapositivas') ? 'block' : 'none';
        
        const fileHelp = document.getElementById('fileHelp');
        if (tipo === 'simulador') {
            fileHelp.textContent = 'Tamaño máximo: 50MB. Formatos permitidos: ZIP, HTML';
        } else if (tipo === 'diapositivas') {
            fileHelp.textContent = 'Tamaño máximo: 50MB. Formatos permitidos: PDF, PPT, PPTX, JPG, PNG';
        } else if (tipo === 'video') {
            fileHelp.textContent = 'Tamaño máximo: 50MB. Formatos permitidos: MP4, MOV, AVI, WMV';
        }
    }
    document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
</body>
</html>
