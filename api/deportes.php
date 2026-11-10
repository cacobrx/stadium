<?php
// api/deportes.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar autenticación para todas las operaciones
requireAuthAPI();

// Directorio para subir imágenes de deportes
define('UPLOAD_DIR_DEPORTES', __DIR__ . '/../uploads/deportes/');

/**
 * Convierte una imagen a formato WebP
 * @param string $archivoTemporal Ruta del archivo temporal subido
 * @param string $mimeType Tipo MIME de la imagen original
 * @param string $rutaDestino Ruta donde guardar el archivo WebP
 * @param int $calidad Calidad de compresión (0-100, default 80)
 * @return bool True si la conversión fue exitosa
 */
function convertirAWebP($archivoTemporal, $mimeType, $rutaDestino, $calidad = 80) {
    // Crear recurso de imagen según el tipo
    $imagen = null;
    
    switch ($mimeType) {
        case 'image/jpeg':
            $imagen = imagecreatefromjpeg($archivoTemporal);
            break;
        case 'image/png':
            $imagen = imagecreatefrompng($archivoTemporal);
            // Preservar transparencia
            imagepalettetotruecolor($imagen);
            imagealphablending($imagen, true);
            imagesavealpha($imagen, true);
            break;
        case 'image/gif':
            $imagen = imagecreatefromgif($archivoTemporal);
            // Preservar transparencia
            imagepalettetotruecolor($imagen);
            imagealphablending($imagen, true);
            imagesavealpha($imagen, true);
            break;
        case 'image/webp':
            // Ya es WebP, solo mover el archivo
            return move_uploaded_file($archivoTemporal, $rutaDestino);
        default:
            return false;
    }
    
    if (!$imagen) {
        return false;
    }
    
    // Convertir a WebP
    $resultado = imagewebp($imagen, $rutaDestino, $calidad);
    
    // Liberar memoria
    imagedestroy($imagen);
    
    return $resultado;
}

// Conexión a la base de datos
function getConnection() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión a la base de datos: ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// GET - Obtener deportes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    try {
        $mysqli = getConnection();
        
        // Si se solicita un deporte específico
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM deportes WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($deporte = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'deporte' => $deporte]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Deporte no encontrado']);
            }
            $stmt->close();
        } else {
            // Obtener todos los deportes
            $result = $mysqli->query('SELECT * FROM deportes ORDER BY nombre');
            $deportes = [];
            
            while ($row = $result->fetch_assoc()) {
                $deportes[] = $row;
            }
            
            echo json_encode(['success' => true, 'deportes' => $deportes]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear deporte o subir imagen
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        // Verificar si es una solicitud de upload de imagen
        if (isset($_GET['action']) && $_GET['action'] === 'upload') {
            // Crear directorio si no existe
            if (!file_exists(UPLOAD_DIR_DEPORTES)) {
                if (!mkdir(UPLOAD_DIR_DEPORTES, 0777, true)) {
                    throw new Exception('No se pudo crear el directorio de uploads');
                }
            }
            
            // Verificar permisos de escritura
            if (!is_writable(UPLOAD_DIR_DEPORTES)) {
                throw new Exception('El directorio de uploads no tiene permisos de escritura');
            }
            
            // Debug: verificar qué se recibió
            if (empty($_FILES)) {
                // Log para debug
                error_log('Upload debug - FILES: ' . print_r($_FILES, true));
                error_log('Upload debug - POST: ' . print_r($_POST, true));
                error_log('Upload debug - Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'no definido'));
                error_log('Upload debug - Content-Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'no definido'));
                
                // Verificar si el archivo excedió el límite
                $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? intval($_SERVER['CONTENT_LENGTH']) : 0;
                $maxPostSize = ini_get('post_max_size');
                $maxUploadSize = ini_get('upload_max_filesize');
                
                if ($contentLength > 0) {
                    throw new Exception("El archivo es demasiado grande. Tamaño enviado: " . round($contentLength/1024/1024, 2) . "MB. Límite: post_max_size=$maxPostSize, upload_max_filesize=$maxUploadSize");
                }
                
                throw new Exception('No se recibieron archivos. Intente con una imagen más pequeña (máximo ' . $maxUploadSize . ')');
            }
            
            if (!isset($_FILES['imagen'])) {
                throw new Exception('No se recibió el campo "imagen". Campos recibidos: ' . implode(', ', array_keys($_FILES)));
            }
            
            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                    UPLOAD_ERR_EXTENSION => 'Extensión de PHP detuvo la subida'
                ];
                $errorCode = $_FILES['imagen']['error'];
                $errorMsg = $uploadErrors[$errorCode] ?? 'Error desconocido: ' . $errorCode;
                throw new Exception($errorMsg);
            }
            
            $file = $_FILES['imagen'];
            
            // Validar tipo MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido. Use JPG, PNG, GIF o WEBP. Tipo recibido: ' . $mimeType);
            }
            
            // Validar tamaño (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB');
            }
            
            // Limpiar nombre de archivo
            $nombreLimpio = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
            $nombreLimpio = trim($nombreLimpio, '-');
            if ($nombreLimpio === '') {
                $nombreLimpio = 'deporte';
            }
            
            // Generar nombre único con extensión .webp
            $filename = $nombreLimpio . '_' . time() . '_' . uniqid() . '.webp';
            $destination = UPLOAD_DIR_DEPORTES . $filename;
            
            // Convertir imagen a WebP
            $imagenConvertida = convertirAWebP($file['tmp_name'], $mimeType, $destination, 80);
            
            if (!$imagenConvertida) {
                throw new Exception('Error al convertir la imagen a formato WebP');
            }
            
            // Establecer permisos
            @chmod($destination, 0664);
            
            echo json_encode(['success' => true, 'filename' => $filename]);
            exit;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }
        
        // Validar campos requeridos
        if (empty($data['nombre'])) {
            throw new Exception('El nombre es requerido');
        }
        
        $mysqli = getConnection();
        
        // Verificar si el nombre ya existe
        $stmt = $mysqli->prepare('SELECT id FROM deportes WHERE nombre = ?');
        $stmt->bind_param('s', $data['nombre']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('Ya existe un deporte con ese nombre');
        }
        $stmt->close();
        
        // Preparar datos con valores por defecto
        $nombre = $data['nombre'];
        $descripcion = $data['descripcion'] ?? '';
        $activo = intval($data['activo'] ?? 1);
        $principal = intval($data['principal'] ?? 0);
        $imagen = $data['imagen'] ?? null;
        
        $stmt = $mysqli->prepare('INSERT INTO deportes (nombre, descripcion, activo, principal, imagen) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('ssiis', $nombre, $descripcion, $activo, $principal, $imagen);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Deporte creado correctamente']);
        } else {
            throw new Exception('Error al crear deporte: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar deporte
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    header('Content-Type: application/json');
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de deporte requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si el deporte existe
        $stmt = $mysqli->prepare('SELECT id FROM deportes WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Deporte no encontrado');
        }
        $stmt->close();
        
        // Verificar si el nombre ya existe en otro deporte
        if (!empty($data['nombre'])) {
            $stmt = $mysqli->prepare('SELECT id FROM deportes WHERE nombre = ? AND id != ?');
            $stmt->bind_param('si', $data['nombre'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception('Ya existe otro deporte con ese nombre');
            }
            $stmt->close();
        }
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['nombre', 'descripcion', 'activo', 'principal', 'imagen'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'activo' || $field === 'principal') {
                    $types .= 'i';
                    $values[] = intval($data[$field]);
                } else {
                    $types .= 's';
                    $values[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $types .= 'i';
        $values[] = $id;
        
        $sql = 'UPDATE deportes SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Deporte actualizado correctamente']);
        } else {
            throw new Exception('Error al actualizar deporte: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar deporte
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de deporte requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si el deporte existe
        $stmt = $mysqli->prepare('SELECT id FROM deportes WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Deporte no encontrado');
        }
        $stmt->close();
        
        // Eliminar deporte
        $stmt = $mysqli->prepare('DELETE FROM deportes WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Deporte eliminado correctamente']);
        } else {
            throw new Exception('Error al eliminar deporte: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>