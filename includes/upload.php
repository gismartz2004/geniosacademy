<?php
require_once 'config.php';

class Upload {
    private $error = '';
    
    public function processFile($file, $type) {
        // Validar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = 'Error al subir el archivo: ' . $this->getUploadError($file['error']);
            return false;
        }
        
        // Validar tamaño del archivo
        if ($file['size'] > MAX_FILE_SIZE) {
            $this->error = 'El archivo es demasiado grande. Tamaño máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            return false;
        }
        
        // Obtener extensión del archivo
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validar tipo de archivo según el tipo de contenido
        global $allowedVideoTypes, $allowedSlideTypes;
        $allowedScratchTypes = array('sb3');
        $allowedSimulatorTypes = array('zip', 'html', 'htm');
        $allowedImageTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        if ($type == 'video' && !in_array($file_ext, $allowedVideoTypes)) {
            $this->error = 'Tipo de archivo no permitido para video. Formatos permitidos: ' . implode(', ', $allowedVideoTypes);
            return false;
        }
        
        if ($type == 'diapositivas' && !in_array($file_ext, $allowedSlideTypes) && !in_array($file_ext, $allowedImageTypes)) {
            $this->error = 'Tipo de archivo no permitido para diapositivas. Formatos permitidos: ' . implode(', ', array_merge($allowedSlideTypes, $allowedImageTypes));
            return false;
        }
        
        if ($type == 'scratch' && !in_array($file_ext, $allowedScratchTypes)) {
            $this->error = 'Tipo de archivo no permitido para Scratch. Formato permitido: SB3';
            return false;
        }
        
        if ($type == 'simulador' && !in_array($file_ext, $allowedSimulatorTypes)) {
            $this->error = 'Tipo de archivo no permitido para simulador. Formatos permitidos: ' . implode(', ', $allowedSimulatorTypes);
            return false;
        }
        
        // Crear directorio si no existe
        $upload_dir = UPLOAD_DIR . $type . 's/'; // videos/, diapositivas/, scratch/, simuladores/
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        // Mover archivo subido
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $new_filename;
        } else {
            $this->error = 'Error al guardar el archivo';
            return false;
        }
    }
    
    public function processAvatar($file) {
        // Validar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = 'Error al subir el avatar: ' . $this->getUploadError($file['error']);
            return false;
        }
        
        // Validar tamaño del archivo
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB máximo para avatares
            $this->error = 'El avatar es demasiado grande. Tamaño máximo: 2MB';
            return false;
        }
        
        // Validar tipo de archivo
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $this->error = 'Tipo de archivo no permitido para avatar. Formatos permitidos: ' . implode(', ', $allowed_types);
            return false;
        }
        
        // Crear directorio si no existe
        $upload_dir = UPLOAD_DIR . 'avatares/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $new_filename = 'avatar_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        // Redimensionar imagen si es necesario
        list($width, $height) = getimagesize($file['tmp_name']);
        $new_width = 200;
        $new_height = 200;
        
        // Crear imagen según el tipo
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'png':
                $source = imagecreatefrompng($file['tmp_name']);
                break;
            case 'gif':
                $source = imagecreatefromgif($file['tmp_name']);
                break;
            default:
                $this->error = 'Formato de imagen no soportado';
                return false;
        }
        
        // Crear imagen redimensionada
        $thumb = imagecreatetruecolor($new_width, $new_height);
        
        // Preservar transparencia para PNG y GIF
        if ($file_ext == 'png' || $file_ext == 'gif') {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        // Redimensionar
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Guardar imagen
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, $destination, 90);
                break;
            case 'png':
                imagepng($thumb, $destination);
                break;
            case 'gif':
                imagegif($thumb, $destination);
                break;
        }
        
        // Liberar memoria
        imagedestroy($source);
        imagedestroy($thumb);
        
        return $new_filename;
    }
    
    public function getError() {
        return $this->error;
    }
    
    private function getUploadError($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el servidor';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el formulario';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo fue subido parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta la carpeta temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo en el disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Una extensión de PHP detuvo la subida del archivo';
            default:
                return 'Error desconocido';
        }
    }
}
?>