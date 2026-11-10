<?php
// api/upload_imagen.php
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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

$previousErrorHandler = set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    requireAuthAPI();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
        exit;
    }

    if (!isset($_FILES['imagen'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No se recibió ningún archivo'
        ]);
        exit;
    }

    $archivo = $_FILES['imagen'];

    if (!is_array($archivo) || !isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $error = 'Error al subir el archivo';
        if (isset($archivo['error'])) {
            switch ($archivo['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $maxSize = ini_get('upload_max_filesize');
                    $error = "El archivo supera el tamaño máximo permitido ({$maxSize})";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'El archivo supera el tamaño máximo permitido por el formulario';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'El archivo se subió parcialmente';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = 'No se subió ningún archivo';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = 'No existe directorio temporal';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = 'No se pudo escribir el archivo en disco';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = 'Una extensión de PHP detuvo la subida del archivo';
                    break;
                default:
                    $error .= ' (código ' . $archivo['error'] . ')';
            }
        }
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
        exit;
    }

    // Verificar tamaño máximo (usar el límite del servidor)
    $maxFileSize = min(
        (int) str_replace(['K', 'M', 'G'], ['000', '000000', '000000000'], ini_get('upload_max_filesize')),
        (int) str_replace(['K', 'M', 'G'], ['000', '000000', '000000000'], ini_get('post_max_size'))
    );
    
    if ($archivo['size'] > $maxFileSize) {
        http_response_code(400);
        $maxSizeFormatted = ini_get('upload_max_filesize');
        echo json_encode([
            'success' => false,
            'error' => "El archivo supera el tamaño máximo permitido de {$maxSizeFormatted}"
        ]);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    if ($mime === false) {
        throw new RuntimeException('No se pudo determinar el tipo de archivo');
    }

    $extensionesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if (!isset($extensionesPermitidas[$mime])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Formato de imagen no permitido. Use JPG, PNG, WEBP o GIF'
        ]);
        exit;
    }

    // Determinar directorio de destino basado en el contexto
    $uploadsDir = __DIR__ . '/../uploads/productos';
    
    // Si viene de equipamiento, usar ese directorio
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'equipamiento') !== false) {
        $uploadsDir = __DIR__ . '/../uploads/equipamiento';
    }
    
    // Si viene de proyectos, usar ese directorio
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'proyectos') !== false) {
        $uploadsDir = __DIR__ . '/../uploads/proyectos';
    }
    
    // Si viene de tableros_marcadores, usar ese directorio
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tableros_marcadores') !== false) {
        $uploadsDir = __DIR__ . '/../uploads/tableros_marcadores';
    }
    
    // Si viene de pisos_deportivos, usar ese directorio
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pisos_deportivos') !== false) {
        $uploadsDir = __DIR__ . '/../uploads/pisosdeportivos';
    }
    
    // Si viene de marcas, usar ese directorio
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'marcas') !== false) {
        $uploadsDir = __DIR__ . '/../uploads/marcas';
    }
    
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true)) {
        throw new RuntimeException('No se pudo crear el directorio de destino');
    }

    if (!is_writable($uploadsDir)) {
        throw new RuntimeException('El directorio de destino no tiene permisos de escritura');
    }

    $nombreLimpio = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($archivo['name'], PATHINFO_FILENAME));
    
    // Check for custom name
    $customName = isset($_POST['custom_name']) ? $_POST['custom_name'] : '';
    $useExactName = isset($_POST['use_exact_name']) && $_POST['use_exact_name'] === 'true';

    if (!empty($customName)) {
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_-]/', '-', $customName);
    }

    $nombreLimpio = trim($nombreLimpio, '-');
    if ($nombreLimpio === '') {
        $nombreLimpio = 'imagen';
    }

    // Siempre convertir a WebP para optimizar el tamaño
    if ($useExactName) {
        $nombreArchivo = $nombreLimpio . '.webp';
    } else {
        $nombreArchivo = sprintf('%s-%s.webp', $nombreLimpio, uniqid('', true));
    }
    $rutaDestino = $uploadsDir . '/' . $nombreArchivo;

    // Convertir imagen a WebP
    $imagenConvertida = convertirAWebP($archivo['tmp_name'], $mime, $rutaDestino);
    
    if (!$imagenConvertida) {
        throw new RuntimeException('No se pudo convertir la imagen a WebP');
    }

    @chmod($rutaDestino, 0664);

    // Determinar URL pública basada en el directorio usado
    $carpeta = basename(dirname($rutaDestino));
    $urlPublica = 'uploads/' . $carpeta . '/' . $nombreArchivo;

    echo json_encode([
        'success' => true,
        'url' => $urlPublica
    ]);
} catch (Throwable $e) {
    $currentStatus = http_response_code();
    if ($currentStatus < 400) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if ($previousErrorHandler !== null) {
        set_error_handler($previousErrorHandler);
    } else {
        restore_error_handler();
    }
}
