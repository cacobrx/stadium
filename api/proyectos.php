<?php
// api/proyectos.php
// API REST para gestión de proyectos con transacciones

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// Evitar caché
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Función de compatibilidad (mantener nombre original)
function get_db_connection() {
    return get_database_connection();
}

/**
 * Convierte una imagen a formato WebP
 * @param string $archivoTemporal Ruta del archivo temporal subido
 * @param string $mimeType Tipo MIME de la imagen original
 * @param string $rutaDestino Ruta donde guardar el archivo WebP
 * @param int $calidad Calidad de compresión (0-100, default 80)
 * @return bool True si la conversión fue exitosa
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

// Función para subir imágenes (con conversión a WebP)
function subir_imagen($file, $proyecto_id = null) {
    $upload_dir = '../uploads/proyectos/';
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Validar archivo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes.');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB máximo
        throw new Exception('El archivo es demasiado grande. Máximo 5MB.');
    }
    
    // Generar nombre único con extensión .webp
    $filename = 'proyecto_' . ($proyecto_id ? $proyecto_id . '_' : '') . uniqid() . '.webp';
    $filepath = $upload_dir . $filename;
    
    // Convertir a WebP
    if (!convertirAWebP($file['tmp_name'], $file_type, $filepath)) {
        throw new Exception('Error al convertir la imagen a WebP.');
    }
    
    // Retornar ruta relativa
    return 'uploads/proyectos/' . $filename;
}

// Endpoint para subir imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload') {
    try {
        if (!isset($_FILES['image'])) {
            throw new Exception('No se encontró archivo de imagen.');
        }
        
        $proyecto_id = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : null;
        $url = subir_imagen($_FILES['image'], $proyecto_id);
        
        echo json_encode([
            'success' => true,
            'url' => $url,
            'message' => 'Imagen subida exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Obtener todos los proyectos con resumen
function obtener_proyectos() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT 
                    p.id, 
                    p.slug,
                    p.title, 
                    p.subtitle,
                    p.badge,
                    p.hero_image,
                    p.description,
                    p.location,
                    p.client,
                    p.completion_date,
                    p.area,
                    p.duration,
                    p.fecha_creacion,
                    p.activo,
                    COUNT(DISTINCT pc.id) as cantidad_categorias,
                    COUNT(DISTINCT pe.id) as cantidad_especificaciones,
                    COUNT(DISTINCT pf.id) as cantidad_features,
                    COUNT(DISTINCT pi.id) as cantidad_galeria
                FROM proyectos p 
                LEFT JOIN proyecto_categorias pc ON p.id = pc.proyecto_id
                LEFT JOIN proyecto_especificaciones pe ON p.id = pe.proyecto_id
                LEFT JOIN proyecto_features pf ON p.id = pf.proyecto_id
                LEFT JOIN proyectos_images pi ON p.id = pi.proyecto_id
                WHERE p.activo = 1
                GROUP BY p.id, p.slug, p.title, p.subtitle, p.badge, p.hero_image, p.description, p.location, p.client, p.completion_date, p.area, p.duration, p.fecha_creacion, p.activo
                ORDER BY p.fecha_creacion DESC, p.id DESC";
        $result = $conn->query($sql);
        $proyectos = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $proyectos[] = $row;
            }
        }
        $conn->close();
        return $proyectos;
    } catch (Exception $e) {
        error_log("Error en obtener_proyectos: " . $e->getMessage());
        return [];
    }
}

// Obtener proyecto por ID con todos los detalles
function obtener_proyecto($id) {
    try {
        $conn = get_db_connection();
        
        // Obtener datos principales del proyecto
        $stmt = $conn->prepare("SELECT * FROM proyectos WHERE id = ? AND activo = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $proyecto = $result->fetch_assoc();
        
        if (!$proyecto) {
            $conn->close();
            return null;
        }
        
        // Obtener categorías
        $stmt = $conn->prepare("SELECT categoria FROM proyecto_categorias WHERE proyecto_id = ? ORDER BY id");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categorias = [];
        while($row = $result->fetch_assoc()) {
            $categorias[] = $row['categoria'];
        }
        $proyecto['categories'] = $categorias;
        
        // Obtener especificaciones
        $stmt = $conn->prepare("SELECT label, value FROM proyecto_especificaciones WHERE proyecto_id = ? ORDER BY orden, id");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $especificaciones = [];
        while($row = $result->fetch_assoc()) {
            $especificaciones[] = $row;
        }
        $proyecto['specifications'] = $especificaciones;
        
        // Obtener features
        $stmt = $conn->prepare("SELECT icon, title, description FROM proyecto_features WHERE proyecto_id = ? ORDER BY orden, id");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $features = [];
        while($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
        $proyecto['features'] = $features;
        
        // Obtener galería (ahora desde proyectos_images)
        $stmt = $conn->prepare("SELECT id as gallery_id, url, alt_text as alt, es_principal FROM proyectos_images WHERE proyecto_id = ? ORDER BY orden, id");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $galeria = [];
        while($row = $result->fetch_assoc()) {
            // Convertir es_principal a entero para que JavaScript lo maneje correctamente
            $row['es_principal'] = (int)$row['es_principal'];
            $galeria[] = $row;
        }
        $proyecto['gallery'] = $galeria;
        
        $conn->close();
        return $proyecto;
    } catch (Exception $e) {
        error_log("Error en obtener_proyecto: " . $e->getMessage());
        return null;
    }
}

// Crear nuevo proyecto
function crear_proyecto($datos) {
    $conn = null;
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();
        
        // Validar datos obligatorios
        if (!isset($datos['title']) || !isset($datos['slug'])) {
            throw new Exception("Faltan datos obligatorios: title y slug");
        }
        
        // Crear el proyecto principal
        $stmt = $conn->prepare("INSERT INTO proyectos (slug, title, subtitle, badge, hero_image, description, location, client, completion_date, area, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Preparar variables para bind_param (no se pueden usar expresiones directamente)
        $slug = $datos['slug'];
        $title = $datos['title'];
        $subtitle = $datos['subtitle'] ?? '';
        $badge = $datos['badge'] ?? '';
        $hero_image = $datos['hero_image'] ?? '';
        $description = $datos['description'] ?? '';
        $location = $datos['location'] ?? '';
        $client = $datos['client'] ?? '';
        $completion_date = $datos['completion_date'] ?? '';
        $area = $datos['area'] ?? '';
        $duration = $datos['duration'] ?? '';
        
        $stmt->bind_param("sssssssssss", 
            $slug,
            $title,
            $subtitle,
            $badge,
            $hero_image,
            $description,
            $location,
            $client,
            $completion_date,
            $area,
            $duration
        );
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error;
            error_log("Error MySQL en crear_proyecto: " . $error);
            throw new Exception("Error al crear proyecto: " . $error);
        }
        
        $proyecto_id = $conn->insert_id;
        error_log("Proyecto creado con ID: " . $proyecto_id);
        
        // Crear categorías si las hay
        if (isset($datos['categories']) && is_array($datos['categories'])) {
            $stmt_cat = $conn->prepare("INSERT INTO proyecto_categorias (proyecto_id, categoria) VALUES (?, ?)");
            foreach ($datos['categories'] as $categoria) {
                if (!empty(trim($categoria))) {
                    $stmt_cat->bind_param("is", $proyecto_id, trim($categoria));
                    $stmt_cat->execute();
                }
            }
        }
        
        // Crear especificaciones si las hay
        if (isset($datos['specifications']) && is_array($datos['specifications'])) {
            $stmt_spec = $conn->prepare("INSERT INTO proyecto_especificaciones (proyecto_id, label, value, orden) VALUES (?, ?, ?, ?)");
            foreach ($datos['specifications'] as $index => $spec) {
                if (!empty(trim($spec['label'])) && !empty(trim($spec['value']))) {
                    $orden = $index + 1;
                    $stmt_spec->bind_param("issi", $proyecto_id, trim($spec['label']), trim($spec['value']), $orden);
                    $stmt_spec->execute();
                }
            }
        }
        
        // Crear features si las hay
        if (isset($datos['features']) && is_array($datos['features'])) {
            $stmt_feat = $conn->prepare("INSERT INTO proyecto_features (proyecto_id, icon, title, description, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($datos['features'] as $index => $feature) {
                if (!empty(trim($feature['title'])) && !empty(trim($feature['description']))) {
                    $orden = $index + 1;
                    $icon = isset($feature['icon']) ? trim($feature['icon']) : '';
                    $stmt_feat->bind_param("isssi", $proyecto_id, $icon, trim($feature['title']), trim($feature['description']), $orden);
                    $stmt_feat->execute();
                }
            }
        }
        
        // Crear galería si la hay
        if (isset($datos['gallery']) && is_array($datos['gallery'])) {
            $stmt_gal = $conn->prepare("INSERT INTO proyectos_images (proyecto_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($datos['gallery'] as $index => $image) {
                if (!empty(trim($image['url']))) {
                    $orden = $index + 1;
                    $alt_text = isset($image['alt']) ? trim($image['alt']) : '';
                    // Usar el valor es_principal que viene del frontend
                    $es_principal = (isset($image['es_principal']) && $image['es_principal']) ? 1 : 0;
                    $url = trim($image['url']);
                    $stmt_gal->bind_param("issii", $proyecto_id, $url, $alt_text, $es_principal, $orden);
                    $stmt_gal->execute();
                }
            }
        }
        
        $conn->commit();
        $conn->close();
        
        return ['success' => true, 'id' => $proyecto_id];
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
            $conn->close();
        }
        error_log("Error en crear_proyecto: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Actualizar proyecto
function actualizar_proyecto($id, $datos) {
    $conn = null;
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();
        
        // Actualizar datos principales del proyecto
        $stmt = $conn->prepare("UPDATE proyectos SET slug=?, title=?, subtitle=?, badge=?, hero_image=?, description=?, location=?, client=?, completion_date=?, area=?, duration=? WHERE id=? AND activo=1");
        
        // Preparar variables para bind_param (no se pueden usar expresiones directamente)
        $slug = $datos['slug'];
        $title = $datos['title'];
        $subtitle = $datos['subtitle'] ?? '';
        $badge = $datos['badge'] ?? '';
        $hero_image = $datos['hero_image'] ?? '';
        $description = $datos['description'] ?? '';
        $location = $datos['location'] ?? '';
        $client = $datos['client'] ?? '';
        $completion_date = $datos['completion_date'] ?? '';
        $area = $datos['area'] ?? '';
        $duration = $datos['duration'] ?? '';
        
        $stmt->bind_param("sssssssssssi", 
            $slug,
            $title,
            $subtitle,
            $badge,
            $hero_image,
            $description,
            $location,
            $client,
            $completion_date,
            $area,
            $duration,
            $id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            throw new Exception("Error al actualizar proyecto: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            // Verificar que el proyecto existe
            $stmt_check = $conn->prepare("SELECT id FROM proyectos WHERE id=? AND activo=1");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows === 0) {
                throw new Exception("Proyecto no encontrado");
            }
        }
        
        // Limpiar relaciones existentes
        $conn->query("DELETE FROM proyecto_categorias WHERE proyecto_id = $id");
        $conn->query("DELETE FROM proyecto_especificaciones WHERE proyecto_id = $id");
        $conn->query("DELETE FROM proyecto_features WHERE proyecto_id = $id");
        $conn->query("DELETE FROM proyectos_images WHERE proyecto_id = $id");
        
        // Recrear categorías
        if (isset($datos['categories']) && is_array($datos['categories'])) {
            $stmt_cat = $conn->prepare("INSERT INTO proyecto_categorias (proyecto_id, categoria) VALUES (?, ?)");
            foreach ($datos['categories'] as $categoria) {
                if (!empty(trim($categoria))) {
                    $stmt_cat->bind_param("is", $id, trim($categoria));
                    $stmt_cat->execute();
                }
            }
        }
        
        // Recrear especificaciones
        if (isset($datos['specifications']) && is_array($datos['specifications'])) {
            $stmt_spec = $conn->prepare("INSERT INTO proyecto_especificaciones (proyecto_id, label, value, orden) VALUES (?, ?, ?, ?)");
            foreach ($datos['specifications'] as $index => $spec) {
                if (!empty(trim($spec['label'])) && !empty(trim($spec['value']))) {
                    $orden = $index + 1;
                    $stmt_spec->bind_param("issi", $id, trim($spec['label']), trim($spec['value']), $orden);
                    $stmt_spec->execute();
                }
            }
        }
        
        // Recrear features
        if (isset($datos['features']) && is_array($datos['features'])) {
            $stmt_feat = $conn->prepare("INSERT INTO proyecto_features (proyecto_id, icon, title, description, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($datos['features'] as $index => $feature) {
                if (!empty(trim($feature['title'])) && !empty(trim($feature['description']))) {
                    $orden = $index + 1;
                    $icon = isset($feature['icon']) ? trim($feature['icon']) : '';
                    $stmt_feat->bind_param("isssi", $id, $icon, trim($feature['title']), trim($feature['description']), $orden);
                    $stmt_feat->execute();
                }
            }
        }
        
        // Recrear galería
        if (isset($datos['gallery']) && is_array($datos['gallery'])) {
            $stmt_gal = $conn->prepare("INSERT INTO proyectos_images (proyecto_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($datos['gallery'] as $index => $image) {
                if (!empty(trim($image['url']))) {
                    $orden = $index + 1;
                    $alt_text = isset($image['alt']) ? trim($image['alt']) : '';
                    // Usar el valor es_principal que viene del frontend
                    $es_principal = (isset($image['es_principal']) && $image['es_principal']) ? 1 : 0;
                    $url = trim($image['url']);
                    $stmt_gal->bind_param("issii", $id, $url, $alt_text, $es_principal, $orden);
                    $stmt_gal->execute();
                }
            }
        }
        
        $conn->commit();
        $conn->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
            $conn->close();
        }
        error_log("Error en actualizar_proyecto: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Eliminar proyecto (soft delete)
function eliminar_proyecto($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("UPDATE proyectos SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        if ($success && $stmt->affected_rows > 0) {
            $conn->close();
            return ['success' => true];
        } else {
            $conn->close();
            return ['success' => false, 'error' => 'Proyecto no encontrado o ya eliminado'];
        }
    } catch (Exception $e) {
        error_log("Error en eliminar_proyecto: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Manejar solicitudes
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $proyecto = obtener_proyecto($_GET['id']);
                if ($proyecto) {
                    echo json_encode($proyecto);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Proyecto no encontrado']);
                }
            } else {
                $proyectos = obtener_proyectos();
                echo json_encode($proyectos);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Datos JSON no válidos']);
                break;
            }
            $resultado = crear_proyecto($input);
            if ($resultado['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            echo json_encode($resultado);
            break;
            
        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID requerido para actualizar']);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Datos JSON no válidos']);
                break;
            }
            $resultado = actualizar_proyecto($_GET['id'], $input);
            if ($resultado['success']) {
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID requerido para eliminar']);
                break;
            }
            $resultado = eliminar_proyecto($_GET['id']);
            if ($resultado['success']) {
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en API proyectos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>