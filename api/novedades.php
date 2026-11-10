<?php
// api/novedades.php
// API REST para gestión de novedades

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación para todas las operaciones
requireAuthAPI();

// Función de compatibilidad (mantener nombre original)
function get_db_connection() {
    return get_database_connection();
}

// Directorio para subir imágenes de novedades
define('UPLOAD_DIR_NOVEDADES', __DIR__ . '/../uploads/novedades/');

/**
 * Convierte una imagen a formato WebP
 */
function convertirAWebP($archivoTemporal, $mimeType, $rutaDestino, $calidad = 80) {
    $imagen = null;
    
    switch ($mimeType) {
        case 'image/jpeg':
            $imagen = imagecreatefromjpeg($archivoTemporal);
            break;
        case 'image/png':
            $imagen = imagecreatefrompng($archivoTemporal);
            imagepalettetotruecolor($imagen);
            imagealphablending($imagen, true);
            imagesavealpha($imagen, true);
            break;
        case 'image/gif':
            $imagen = imagecreatefromgif($archivoTemporal);
            imagepalettetotruecolor($imagen);
            imagealphablending($imagen, true);
            imagesavealpha($imagen, true);
            break;
        case 'image/webp':
            return move_uploaded_file($archivoTemporal, $rutaDestino);
        default:
            return false;
    }
    
    if (!$imagen) {
        return false;
    }
    
    $resultado = imagewebp($imagen, $rutaDestino, $calidad);
    imagedestroy($imagen);
    
    return $resultado;
}

// Conexión a la base de datos usando configuración centralizada
function getConnection() {
    try {
        return get_db_connection();
    } catch (Exception $e) {
        error_log("Error en getConnection: " . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos');
    }
}

/**
 * Genera un slug único a partir del título
 */
function generarSlug($titulo, $mysqli, $idExcluir = null) {
    // Convertir a minúsculas y reemplazar caracteres especiales
    $slug = strtolower($titulo);
    $slug = preg_replace('/[áàâãä]/u', 'a', $slug);
    $slug = preg_replace('/[éèêë]/u', 'e', $slug);
    $slug = preg_replace('/[íìîï]/u', 'i', $slug);
    $slug = preg_replace('/[óòôõö]/u', 'o', $slug);
    $slug = preg_replace('/[úùûü]/u', 'u', $slug);
    $slug = preg_replace('/[ñ]/u', 'n', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Verificar si el slug ya existe
    $slugOriginal = $slug;
    $contador = 1;
    
    while (true) {
        if ($idExcluir) {
            $stmt = $mysqli->prepare('SELECT id FROM novedades WHERE slug = ? AND id != ?');
            $stmt->bind_param('si', $slug, $idExcluir);
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM novedades WHERE slug = ?');
            $stmt->bind_param('s', $slug);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            break;
        }
        
        $stmt->close();
        $slug = $slugOriginal . '-' . $contador;
        $contador++;
    }
    
    return $slug;
}

// GET - Obtener novedades
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita una novedad específica por ID o SLUG
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM novedades WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($novedad = $result->fetch_assoc()) {
                // Decodificar JSON arrays
                if ($novedad['imagenes']) {
                    $novedad['imagenes'] = json_decode($novedad['imagenes'], true);
                }
                if ($novedad['etiquetas']) {
                    $novedad['etiquetas'] = json_decode($novedad['etiquetas'], true);
                }
                
                echo json_encode(['success' => true, 'novedad' => $novedad]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Novedad no encontrada']);
            }
            $stmt->close();
        } elseif (isset($_GET['slug'])) {
            $slug = $_GET['slug'];
            $stmt = $mysqli->prepare('SELECT * FROM novedades WHERE slug = ?');
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($novedad = $result->fetch_assoc()) {
                // Decodificar JSON arrays
                if ($novedad['imagenes']) {
                    $novedad['imagenes'] = json_decode($novedad['imagenes'], true);
                }
                if ($novedad['etiquetas']) {
                    $novedad['etiquetas'] = json_decode($novedad['etiquetas'], true);
                }
                
                echo json_encode(['success' => true, 'novedad' => $novedad]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Novedad no encontrada']);
            }
            $stmt->close();
        } else {
            // Obtener todas las novedades con filtros opcionales
            $where = ['1=1'];
            $params = [];
            $types = '';
            
            // Filtros
            if (isset($_GET['tipo'])) {
                $where[] = 'tipo = ?';
                $params[] = $_GET['tipo'];
                $types .= 's';
            }
            
            if (isset($_GET['destacado'])) {
                $where[] = 'destacado = ?';
                $params[] = intval($_GET['destacado']);
                $types .= 'i';
            }
            
            if (isset($_GET['activo'])) {
                $where[] = 'activo = ?';
                $params[] = intval($_GET['activo']);
                $types .= 'i';
            }
            
            // Ordenamiento
            $orderBy = 'fecha DESC, fecha_creacion DESC';
            if (isset($_GET['order'])) {
                $validOrders = ['fecha ASC', 'fecha DESC', 'titulo ASC', 'titulo DESC'];
                if (in_array($_GET['order'], $validOrders)) {
                    $orderBy = $_GET['order'];
                }
            }
            
            $sql = 'SELECT * FROM novedades WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy;
            
            if (!empty($params)) {
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $mysqli->query($sql);
            }
            
            $novedades = [];
            while ($row = $result->fetch_assoc()) {
                // Decodificar JSON arrays
                if ($row['imagenes']) {
                    $row['imagenes'] = json_decode($row['imagenes'], true);
                }
                if ($row['etiquetas']) {
                    $row['etiquetas'] = json_decode($row['etiquetas'], true);
                }
                $novedades[] = $row;
            }
            
            echo json_encode(['success' => true, 'novedades' => $novedades]);
            
            if (!empty($params)) {
                $stmt->close();
            }
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear novedad o subir imagen
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar si es una solicitud de upload de imagen
        if (isset($_GET['action']) && $_GET['action'] === 'upload') {
            // Crear directorio si no existe
            if (!file_exists(UPLOAD_DIR_NOVEDADES)) {
                if (!mkdir(UPLOAD_DIR_NOVEDADES, 0777, true)) {
                    throw new Exception('No se pudo crear el directorio de uploads');
                }
            }
            
            if (!is_writable(UPLOAD_DIR_NOVEDADES)) {
                throw new Exception('El directorio de uploads no tiene permisos de escritura');
            }
            
            if (empty($_FILES) || !isset($_FILES['imagen'])) {
                throw new Exception('No se recibió ningún archivo');
            }
            
            $file = $_FILES['imagen'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errores = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño del formulario',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo',
                    UPLOAD_ERR_EXTENSION => 'Extensión no permitida'
                ];
                throw new Exception($errores[$file['error']] ?? 'Error desconocido al subir archivo');
            }
            
            // Validar tipo de archivo
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $tiposPermitidos)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP');
            }
            
            // Validar tamaño (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 5MB');
            }
            
            // Limpiar nombre de archivo
            $nombreLimpio = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
            $nombreLimpio = trim($nombreLimpio, '-');
            if ($nombreLimpio === '') {
                $nombreLimpio = 'novedad';
            }
            
            // Generar nombre único
            $nombreArchivo = $nombreLimpio . '_' . time() . '_' . uniqid() . '.webp';
            $rutaDestino = UPLOAD_DIR_NOVEDADES . $nombreArchivo;
            
            // Convertir a WebP
            if (!convertirAWebP($file['tmp_name'], $mimeType, $rutaDestino)) {
                throw new Exception('Error al procesar la imagen');
            }
            
            // Establecer permisos
            @chmod($rutaDestino, 0664);
            
            echo json_encode([
                'success' => true,
                'filename' => $nombreArchivo
            ]);
            exit;
        }
        
        // Crear nueva novedad
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Datos inválidos');
        }
        
        // Validar campos requeridos
        $required = ['tipo', 'titulo', 'descripcion', 'fecha'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new Exception("El campo '$field' es requerido");
            }
        }
        
        // Validar tipo
        $tiposValidos = ['evento', 'noticia', 'logro', 'promocion', 'proyecto'];
        if (!in_array($data['tipo'], $tiposValidos)) {
            throw new Exception('Tipo de novedad inválido');
        }
        
        $mysqli = getConnection();
        
        // Generar slug si no se proporciona
        $slug = isset($data['slug']) && trim($data['slug']) !== '' 
            ? $data['slug'] 
            : generarSlug($data['titulo'], $mysqli);
        
        // Preparar datos
        $tipo = $data['tipo'];
        $titulo = $data['titulo'];
        $subtitulo = $data['subtitulo'] ?? null;
        $descripcion = $data['descripcion'];
        $contenido = $data['contenido'] ?? null;
        $imagen = $data['imagen'] ?? null;
        $imagenes = isset($data['imagenes']) ? json_encode($data['imagenes']) : null;
        $fecha = $data['fecha'];
        $autor = $data['autor'] ?? null;
        $ubicacion = $data['ubicacion'] ?? null;
        $destacado = isset($data['destacado']) ? intval($data['destacado']) : 0;
        $etiquetas = isset($data['etiquetas']) ? json_encode($data['etiquetas']) : null;
        $activo = isset($data['activo']) ? intval($data['activo']) : 1;
        
        $stmt = $mysqli->prepare('
            INSERT INTO novedades 
            (tipo, titulo, subtitulo, descripcion, contenido, imagen, imagenes, fecha, autor, ubicacion, destacado, etiquetas, slug, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->bind_param(
            'sssssssssssssi',
            $tipo, $titulo, $subtitulo, $descripcion, $contenido,
            $imagen, $imagenes, $fecha, $autor, $ubicacion,
            $destacado, $etiquetas, $slug, $activo
        );
        
        if ($stmt->execute()) {
            $id = $mysqli->insert_id;
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Novedad creada exitosamente',
                'id' => $id,
                'slug' => $slug
            ]);
        } else {
            throw new Exception('Error al crear la novedad: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar novedad
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de novedad no proporcionado');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar que la novedad existe
        $stmt = $mysqli->prepare('SELECT id FROM novedades WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Novedad no encontrada');
        }
        $stmt->close();
        
        // Preparar campos para actualizar
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['tipo'])) {
            $tiposValidos = ['evento', 'noticia', 'logro', 'promocion', 'proyecto'];
            if (!in_array($data['tipo'], $tiposValidos)) {
                throw new Exception('Tipo de novedad inválido');
            }
            $updates[] = 'tipo = ?';
            $params[] = $data['tipo'];
            $types .= 's';
        }
        
        if (isset($data['titulo'])) {
            $updates[] = 'titulo = ?';
            $params[] = $data['titulo'];
            $types .= 's';
            
            // Regenerar slug si cambia el título
            if (!isset($data['slug'])) {
                $slug = generarSlug($data['titulo'], $mysqli, $id);
                $updates[] = 'slug = ?';
                $params[] = $slug;
                $types .= 's';
            }
        }
        
        if (isset($data['subtitulo'])) {
            $updates[] = 'subtitulo = ?';
            $params[] = $data['subtitulo'];
            $types .= 's';
        }
        
        if (isset($data['descripcion'])) {
            $updates[] = 'descripcion = ?';
            $params[] = $data['descripcion'];
            $types .= 's';
        }
        
        if (isset($data['contenido'])) {
            $updates[] = 'contenido = ?';
            $params[] = $data['contenido'];
            $types .= 's';
        }
        
        if (isset($data['imagen'])) {
            $updates[] = 'imagen = ?';
            $params[] = $data['imagen'];
            $types .= 's';
        }
        
        if (isset($data['imagenes'])) {
            $updates[] = 'imagenes = ?';
            $params[] = json_encode($data['imagenes']);
            $types .= 's';
        }
        
        if (isset($data['fecha'])) {
            $updates[] = 'fecha = ?';
            $params[] = $data['fecha'];
            $types .= 's';
        }
        
        if (isset($data['autor'])) {
            $updates[] = 'autor = ?';
            $params[] = $data['autor'];
            $types .= 's';
        }
        
        if (isset($data['ubicacion'])) {
            $updates[] = 'ubicacion = ?';
            $params[] = $data['ubicacion'];
            $types .= 's';
        }
        
        if (isset($data['destacado'])) {
            $updates[] = 'destacado = ?';
            $params[] = intval($data['destacado']);
            $types .= 'i';
        }
        
        if (isset($data['etiquetas'])) {
            $updates[] = 'etiquetas = ?';
            $params[] = json_encode($data['etiquetas']);
            $types .= 's';
        }
        
        if (isset($data['slug'])) {
            $slug = generarSlug($data['slug'], $mysqli, $id);
            $updates[] = 'slug = ?';
            $params[] = $slug;
            $types .= 's';
        }
        
        if (isset($data['activo'])) {
            $updates[] = 'activo = ?';
            $params[] = intval($data['activo']);
            $types .= 'i';
        }
        
        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        // Agregar ID al final
        $params[] = $id;
        $types .= 'i';
        
        $sql = 'UPDATE novedades SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Novedad actualizada exitosamente'
            ]);
        } else {
            throw new Exception('Error al actualizar la novedad: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar novedad
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Obtener ID desde la query string
        if (!isset($_GET['id'])) {
            throw new Exception('ID de novedad no proporcionado');
        }
        
        $id = intval($_GET['id']);
        $mysqli = getConnection();
        
        // Obtener información de la novedad antes de eliminar (para eliminar imágenes)
        $stmt = $mysqli->prepare('SELECT imagen, imagenes FROM novedades WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Novedad no encontrada');
        }
        
        $novedad = $result->fetch_assoc();
        $stmt->close();
        
        // Eliminar la novedad
        $stmt = $mysqli->prepare('DELETE FROM novedades WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            // Intentar eliminar imágenes del servidor
            if ($novedad['imagen'] && strpos($novedad['imagen'], '/uploads/novedades/') !== false) {
                $rutaImagen = __DIR__ . '/../' . $novedad['imagen'];
                if (file_exists($rutaImagen)) {
                    @unlink($rutaImagen);
                }
            }
            
            if ($novedad['imagenes']) {
                $imagenes = json_decode($novedad['imagenes'], true);
                if (is_array($imagenes)) {
                    foreach ($imagenes as $img) {
                        if (strpos($img, '/uploads/novedades/') !== false) {
                            $rutaImg = __DIR__ . '/../' . $img;
                            if (file_exists($rutaImg)) {
                                @unlink($rutaImg);
                            }
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Novedad eliminada exitosamente'
            ]);
        } else {
            throw new Exception('Error al eliminar la novedad: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
