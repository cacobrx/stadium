<?php
// api/marcas.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar autenticación para todas las operaciones
requireAuthAPI();

// Conexión a la base de datos
function getConnection() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión a la base de datos: ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// Helpers para manejo de archivos
function saveUploadedLogoFile($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en subida de archivo (code ' . $file['error'] . ')');
    }

    // Tamaño máximo 5 MB
    $maxBytes = 5 * 1024 * 1024;
    if (isset($file['size']) && $file['size'] > $maxBytes) {
        throw new Exception('El archivo excede el tamaño máximo permitido de 5 MB');
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        throw new Exception('Tipo de archivo no permitido: ' . $mime);
    }

    $origExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uploadDir = __DIR__ . '/../uploads/marcas/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('No se pudo crear directorio de uploads');
        }
    }

    $srcPath = $file['tmp_name'];

    // Obtener información de imagen
    $info = @getimagesize($srcPath);
    if ($info === false) {
        // No es una imagen válida — fallback a mover
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $origExt;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($srcPath, $dest)) {
            throw new Exception('No se pudo mover archivo subido');
        }
        return 'uploads/marcas/' . $filename;
    }

    list($width, $height, $type) = $info;
    $mimeType = image_type_to_mime_type($type);

    // Decidir loader según mime
    $srcImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $srcImage = @imagecreatefromjpeg($srcPath);
            $defaultExt = 'jpg';
            break;
        case 'image/png':
            $srcImage = @imagecreatefrompng($srcPath);
            $defaultExt = 'png';
            break;
        case 'image/gif':
            $srcImage = @imagecreatefromgif($srcPath);
            $defaultExt = 'gif';
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $srcImage = @imagecreatefromwebp($srcPath);
            } else {
                // try fallback to imagecreatefromstring
                $srcImage = @imagecreatefromstring(file_get_contents($srcPath));
            }
            $defaultExt = 'webp';
            break;
        default:
            $srcImage = @imagecreatefromstring(file_get_contents($srcPath));
            $defaultExt = $origExt ?: 'bin';
    }

    if ($srcImage === false || $srcImage === null) {
        // Loader no disponible — fallback a mover
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $origExt;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($srcPath, $dest)) {
            throw new Exception('No se pudo mover archivo subido');
        }
        return 'uploads/marcas/' . $filename;
    }

    // Redimensionar si es necesario (máx ancho 1200px)
    $maxWidth = 1200;
    $newWidth = $width;
    $newHeight = $height;
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = intval(($maxWidth / $width) * $height);
    }

    // Decidir si convertimos a webp (si está disponible)
    $convertToWebp = function_exists('imagewebp');
    $targetExt = $convertToWebp ? 'webp' : $defaultExt;

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    // Mantener transparencia para PNG/GIF/WebP
    if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'])) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Copiar y redimensionar
    if (!imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        // si falla, liberar recursos y fallback a mover
        imagedestroy($srcImage);
        imagedestroy($dstImage);
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $origExt;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($srcPath, $dest)) {
            throw new Exception('No se pudo mover archivo subido');
        }
        return 'uploads/marcas/' . $filename;
    }

    // Nombre destino
    $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $targetExt;
    $dest = $uploadDir . $filename;

    // Guardar según formato
    $saved = false;
    if ($convertToWebp) {
        // Calidad 80
        $saved = @imagewebp($dstImage, $dest, 80);
    } else {
        switch ($targetExt) {
            case 'jpg': case 'jpeg':
                $saved = @imagejpeg($dstImage, $dest, 85);
                break;
            case 'png':
                // nivel compresión 0 (no) - 9 max. usar 6
                $saved = @imagepng($dstImage, $dest, 6);
                break;
            case 'gif':
                $saved = @imagegif($dstImage, $dest);
                break;
            default:
                // intentamos guardar como jpeg
                $saved = @imagejpeg($dstImage, $dest, 85);
                break;
        }
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    if (!$saved) {
        // Fallback: mover original
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $origExt;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($srcPath, $dest)) {
            throw new Exception('No se pudo guardar la imagen procesada ni mover el archivo original');
        }
        return 'uploads/marcas/' . $filename;
    }

    // Asegurar permisos
    @chmod($dest, 0644);

    return 'uploads/marcas/' . $filename;
}

function deleteLocalFileIfExists($path) {
    if (!$path) return;
    // Sólo borrar archivos dentro de uploads para evitar riesgos
    if (strpos($path, 'uploads/') === 0) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

// GET - Obtener marcas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita una marca específica
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM marcas WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($marca = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'marca' => $marca]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Marca no encontrada']);
            }
            $stmt->close();
        } else {
            // Obtener todas las marcas
            $result = $mysqli->query('SELECT * FROM marcas ORDER BY nombre');
            $marcas = [];
            
            while ($row = $result->fetch_assoc()) {
                $marcas[] = $row;
            }
            
            echo json_encode(['success' => true, 'marcas' => $marcas]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear marca
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        $stmt = $mysqli->prepare('SELECT id FROM marcas WHERE nombre = ?');
        $stmt->bind_param('s', $data['nombre']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('Ya existe una marca con ese nombre');
        }
        $stmt->close();
        
        // Preparar datos con valores por defecto
        $nombre = $data['nombre'];
        $descripcion = $data['descripcion'] ?? '';
        $logo = $data['logo'] ?? '';
        $sitio_web = $data['sitio_web'] ?? '';
        $activa = intval($data['activa'] ?? 1);
        $principal = isset($data['principal']) ? intval($data['principal']) : 0;
        
        $stmt = $mysqli->prepare('INSERT INTO marcas (nombre, descripcion, logo, sitio_web, activa, principal) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssii', $nombre, $descripcion, $logo, $sitio_web, $activa, $principal);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Marca creada correctamente']);
        } else {
            throw new Exception('Error al crear marca: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar marca
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de marca requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la marca existe
        $stmt = $mysqli->prepare('SELECT id FROM marcas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Marca no encontrada');
        }
        $stmt->close();
        
        // Verificar si el nombre ya existe en otra marca
        if (!empty($data['nombre'])) {
            $stmt = $mysqli->prepare('SELECT id FROM marcas WHERE nombre = ? AND id != ?');
            $stmt->bind_param('si', $data['nombre'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception('Ya existe otra marca con ese nombre');
            }
            $stmt->close();
        }
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['nombre', 'descripcion', 'logo', 'sitio_web', 'activa', 'principal'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'activa' || $field === 'principal') {
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
        
        $sql = 'UPDATE marcas SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Marca actualizada correctamente']);
        } else {
            throw new Exception('Error al actualizar marca: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar marca
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de marca requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la marca existe
        $stmt = $mysqli->prepare('SELECT id FROM marcas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Marca no encontrada');
        }
        $stmt->close();
        
        // Eliminar marca
        $stmt = $mysqli->prepare('DELETE FROM marcas WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Marca eliminada correctamente']);
        } else {
            throw new Exception('Error al eliminar marca: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>