<?php
// api/slider.php
// API REST para gestión del slider

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

// Verificar autenticación
requireAuthAPI();

// Directorio para subir imágenes
define('UPLOAD_DIR_SLIDER', __DIR__ . '/../uploads/slider/');

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
            break;
        case 'image/gif':
            $imagen = imagecreatefromgif($archivoTemporal);
            imagepalettetotruecolor($imagen);
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

// Conexión a la base de datos
function getConnection() {
    return get_database_connection();
}

// GET - Obtener datos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM slider WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($item = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $item]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No encontrado']);
            }
            $stmt->close();
        } else {
            $result = $mysqli->query('SELECT * FROM slider ORDER BY orden ASC');
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
        }
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear, Editar o Subir Imagen
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Manejar subida de imagen
        if (isset($_GET['action']) && $_GET['action'] === 'upload') {
            error_log("Subida de imagen iniciada");
            
            if (empty($_FILES)) {
                error_log("$_FILES está vacío. Esto puede deberse a que el tamaño del archivo excede post_max_size.");
                throw new Exception('No se recibió archivo o excede el límite permitido por el servidor');
            }

            if (!isset($_FILES['imagen'])) {
                error_log("Campo 'imagen' no encontrado en $_FILES");
                throw new Exception('No se recibió el campo de imagen');
            }
            
            $file = $_FILES['imagen'];
            error_log("Archivo recibido: " . $file['name'] . " - Error code: " . $file['error']);

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMsgs = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo es más grande de lo permitido por PHP',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo es más grande de lo permitido por el formulario',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en el disco',
                    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
                ];
                throw new Exception($errorMsgs[$file['error']] ?? 'Error desconocido al subir archivo');
            }
            
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mimeType = mime_content_type($file['tmp_name']);
            if (!in_array($mimeType, $tiposPermitidos)) {
                throw new Exception('Tipo de archivo no permitido: ' . $mimeType);
            }
            
            $nombreArchivo = 'slider_' . time() . '_' . uniqid() . '.webp';
            $rutaDestino = UPLOAD_DIR_SLIDER . $nombreArchivo;
            
            if (convertirAWebP($file['tmp_name'], $mimeType, $rutaDestino)) {
                error_log("Imagen procesada exitosamente: " . $nombreArchivo);
                echo json_encode(['success' => true, 'url' => $nombreArchivo]);
            } else {
                error_log("Error en convertirAWebP");
                throw new Exception('Error al procesar la imagen (WebP conversion)');
            }
            exit;
        }

        // Manejar Guardado (Crear o Editar)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos inválidos');
        }
        
        $mysqli = getConnection();
        $id = isset($data['id']) ? intval($data['id']) : null;
        $titulo = $data['titulo'] ?? '';
        $subtitulo = $data['subtitulo'] ?? '';
        $etiquetas = $data['etiquetas'] ?? '';
        $botontexto = $data['botontexto'] ?? '';
        $botonlink = $data['botonlink'] ?? '';
        $botontarget = intval($data['botontarget'] ?? 1);
        $orden = intval($data['orden'] ?? 0);
        $activo = intval($data['activo'] ?? 1);
        $imagen = $data['imagen'] ?? '';
        $imagen_mobile = $data['imagen_mobile'] ?? '';

        if ($id) {
            // Si se subió una nueva imagen (tiene prefijo temporal), renombrarla a img_ID.webp
            if ($imagen && strpos($imagen, 'slider_') === 0) {
                $nuevoNombre = "img_$id.webp";
                if (file_exists(UPLOAD_DIR_SLIDER . $imagen)) {
                    // Borrar imagen anterior si existe y es diferente
                    $stmtOld = $mysqli->prepare('SELECT imagen FROM slider WHERE id = ?');
                    $stmtOld->bind_param('i', $id);
                    $stmtOld->execute();
                    $rowOld = $stmtOld->get_result()->fetch_assoc();
                    if ($rowOld && $rowOld['imagen'] && $rowOld['imagen'] !== $nuevoNombre && file_exists(UPLOAD_DIR_SLIDER . $rowOld['imagen'])) {
                        unlink(UPLOAD_DIR_SLIDER . $rowOld['imagen']);
                    }
                    $stmtOld->close();

                    rename(UPLOAD_DIR_SLIDER . $imagen, UPLOAD_DIR_SLIDER . $nuevoNombre);
                    $imagen = $nuevoNombre;
                }
            }
            if ($imagen_mobile && strpos($imagen_mobile, 'slider_') === 0) {
                $nuevoNombreMob = "img_mob_$id.webp";
                if (file_exists(UPLOAD_DIR_SLIDER . $imagen_mobile)) {
                    $stmtOld = $mysqli->prepare('SELECT imagen_mobile FROM slider WHERE id = ?');
                    $stmtOld->bind_param('i', $id);
                    $stmtOld->execute();
                    $rowOld = $stmtOld->get_result()->fetch_assoc();
                    if ($rowOld && $rowOld['imagen_mobile'] && $rowOld['imagen_mobile'] !== $nuevoNombreMob && file_exists(UPLOAD_DIR_SLIDER . $rowOld['imagen_mobile'])) {
                        unlink(UPLOAD_DIR_SLIDER . $rowOld['imagen_mobile']);
                    }
                    $stmtOld->close();

                    rename(UPLOAD_DIR_SLIDER . $imagen_mobile, UPLOAD_DIR_SLIDER . $nuevoNombreMob);
                    $imagen_mobile = $nuevoNombreMob;
                }
            }

            // Update
            $stmt = $mysqli->prepare('UPDATE slider SET titulo=?, subtitulo=?, etiquetas=?, botontexto=?, botonlink=?, botontarget=?, orden=?, activo=?, imagen=?, imagen_mobile=? WHERE id=?');
            $stmt->bind_param('sssssiiissi', $titulo, $subtitulo, $etiquetas, $botontexto, $botonlink, $botontarget, $orden, $activo, $imagen, $imagen_mobile, $id);
            $success = $stmt->execute();
        } else {
            // Insert inicial
            $stmt = $mysqli->prepare('INSERT INTO slider (titulo, subtitulo, etiquetas, botontexto, botonlink, botontarget, orden, activo, imagen, imagen_mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssiiiss', $titulo, $subtitulo, $etiquetas, $botontexto, $botonlink, $botontarget, $orden, $activo, $imagen, $imagen_mobile);
            $success = $stmt->execute();
            $id = $mysqli->insert_id;

            // Si se subió una imagen temporal, renombrarla con el nuevo ID
            if ($success) {
                if ($imagen && strpos($imagen, 'slider_') === 0) {
                    $nuevoNombre = "img_$id.webp";
                    if (file_exists(UPLOAD_DIR_SLIDER . $imagen)) {
                        rename(UPLOAD_DIR_SLIDER . $imagen, UPLOAD_DIR_SLIDER . $nuevoNombre);
                        $imagen = $nuevoNombre;
                        $mysqli->query("UPDATE slider SET imagen = '$imagen' WHERE id = $id");
                    }
                }
                if ($imagen_mobile && strpos($imagen_mobile, 'slider_') === 0) {
                    $nuevoNombreMob = "img_mob_$id.webp";
                    if (file_exists(UPLOAD_DIR_SLIDER . $imagen_mobile)) {
                        rename(UPLOAD_DIR_SLIDER . $imagen_mobile, UPLOAD_DIR_SLIDER . $nuevoNombreMob);
                        $imagen_mobile = $nuevoNombreMob;
                        $mysqli->query("UPDATE slider SET imagen_mobile = '$imagen_mobile' WHERE id = $id");
                    }
                }
            }
        }

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Error al guardar: ' . $stmt->error);
        }
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        
        $mysqli = getConnection();
        
        // Obtener imagen para borrarla si existe
        $stmt = $mysqli->prepare('SELECT imagen, imagen_mobile FROM slider WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['imagen'] && file_exists(UPLOAD_DIR_SLIDER . $row['imagen'])) {
                unlink(UPLOAD_DIR_SLIDER . $row['imagen']);
            }
            if ($row['imagen_mobile'] && file_exists(UPLOAD_DIR_SLIDER . $row['imagen_mobile'])) {
                unlink(UPLOAD_DIR_SLIDER . $row['imagen_mobile']);
            }
        }
        $stmt->close();

        $stmt = $mysqli->prepare('DELETE FROM slider WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Error al eliminar');
        }
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
