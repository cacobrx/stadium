<?php
// api/novedades/lista.php
// Endpoint para listar novedades con filtros y paginación

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Solo se permite GET.',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// URL base para las imágenes en producción
$base_url = "https://stadium.planbsistemas.com.ar/";

/**
 * Función para construir URL completa de imagen
 */
function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return null;
    // Si ya es absoluta, devolverla tal cual
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
    // Normalizar y forzar carpeta uploads/novedades si no está presente
    $relativeUrl = ltrim($relativeUrl, '/');
    if (strpos($relativeUrl, 'uploads/novedades') === false) {
        $relativeUrl = 'uploads/novedades/' . $relativeUrl;
    }
    return $baseUrl . $relativeUrl;
}

/**
 * Función para obtener conexión a la base de datos
 */
function getConnection() {
    try {
        $mysqli = get_database_connection();
        if ($mysqli->connect_errno) {
            throw new Exception('Error de conexión: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (Exception $e) {
        throw new Exception('Error conectando a BD: ' . $e->getMessage());
    }
}

/**
 * Función para validar parámetros de entrada
 */
function validateParameters($params) {
    $errors = [];
    
    // Validar tipo
    if (isset($params['tipo']) && !empty($params['tipo'])) {
        $tiposPermitidos = ['evento', 'noticia', 'logro', 'promocion', 'proyecto'];
        if (!in_array($params['tipo'], $tiposPermitidos)) {
            $errors[] = "Tipo debe ser uno de: " . implode(', ', $tiposPermitidos);
        }
    }
    
    // Validar destacado
    if (isset($params['destacado']) && !empty($params['destacado'])) {
        if (!in_array(strtolower($params['destacado']), ['true', 'false', '1', '0'])) {
            $errors[] = "Destacado debe ser true o false";
        }
    }
    
    // Validar page
    if (isset($params['page'])) {
        if (!is_numeric($params['page']) || intval($params['page']) < 1) {
            $errors[] = "Page debe ser un número mayor a 0";
        }
    }
    
    // Validar limit
    if (isset($params['limit'])) {
        if (!is_numeric($params['limit']) || intval($params['limit']) < 1 || intval($params['limit']) > 100) {
            $errors[] = "Limit debe ser un número entre 1 y 100";
        }
    }
    
    return $errors;
}

/**
 * Función para construir la query SQL con filtros
 */
function buildQuery($params) {
    $whereConditions = [];
    $queryParams = [];
    $types = "";
    
    // Base query
    $sql = "SELECT 
        id,
        tipo,
        titulo,
        subtitulo,
        descripcion,
        contenido,
        imagen,
        imagenes,
        fecha,
        autor,
        ubicacion,
        destacado,
        etiquetas,
        slug
    FROM novedades 
    WHERE activo = 1";
    
    // Filtro por tipo
    if (isset($params['tipo']) && !empty($params['tipo'])) {
        $whereConditions[] = "tipo = ?";
        $queryParams[] = $params['tipo'];
        $types .= "s";
    }
    
    // Filtro por etiqueta
    if (isset($params['etiqueta']) && !empty($params['etiqueta'])) {
        $whereConditions[] = "JSON_SEARCH(etiquetas, 'one', ?) IS NOT NULL";
        $queryParams[] = $params['etiqueta'];
        $types .= "s";
    }
    
    // Filtro por búsqueda de texto
    if (isset($params['busqueda']) && !empty($params['busqueda'])) {
        $busqueda = '%' . $params['busqueda'] . '%';
        $whereConditions[] = "(titulo LIKE ? OR descripcion LIKE ? OR contenido LIKE ?)";
        $queryParams[] = $busqueda;
        $queryParams[] = $busqueda;
        $queryParams[] = $busqueda;
        $types .= "sss";
    }
    
    // Filtro por destacado
    if (isset($params['destacado']) && !empty($params['destacado'])) {
        $destacado = in_array(strtolower($params['destacado']), ['true', '1']) ? 1 : 0;
        $whereConditions[] = "destacado = ?";
        $queryParams[] = $destacado;
        $types .= "i";
    }
    
    // Agregar condiciones WHERE
    if (!empty($whereConditions)) {
        $sql .= " AND " . implode(" AND ", $whereConditions);
    }
    
    return [
        'sql' => $sql,
        'params' => $queryParams,
        'types' => $types
    ];
}

/**
 * Función para formatear una novedad
 */
function formatearNovedad($novedad) {
    global $base_url;
    
    // Construir URL completa para imagen principal
    $imagenUrl = buildImageUrl($novedad['imagen'], $base_url);
    
    // Decodificar imagenes JSON y construir URLs completas
    $imagenes = null;
    if (!empty($novedad['imagenes'])) {
        $imagenesDecoded = json_decode($novedad['imagenes'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($imagenesDecoded)) {
            // Construir URLs completas para cada imagen de la galería
            $imagenes = array_map(function($img) use ($base_url) {
                return buildImageUrl($img, $base_url);
            }, $imagenesDecoded);
            // Filtrar nulls en caso de URLs vacías
            $imagenes = array_filter($imagenes);
        }
    }
    
    // Decodificar etiquetas JSON
    $etiquetas = [];
    if (!empty($novedad['etiquetas'])) {
        $etiquetasDecoded = json_decode($novedad['etiquetas'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($etiquetasDecoded)) {
            $etiquetas = $etiquetasDecoded;
        }
    }
    
    return [
        'id' => $novedad['id'],
        'tipo' => $novedad['tipo'],
        'titulo' => $novedad['titulo'],
        'subtitulo' => $novedad['subtitulo'],
        'descripcion' => $novedad['descripcion'],
        'contenido' => $novedad['contenido'],
        'imagen' => $imagenUrl,
        'imagenes' => $imagenes,
        'fecha' => $novedad['fecha'],
        'autor' => $novedad['autor'],
        'ubicacion' => $novedad['ubicacion'],
        'destacado' => (bool)$novedad['destacado'],
        'etiquetas' => $etiquetas,
        'slug' => $novedad['slug']
    ];
}

try {
    // Obtener parámetros
    $params = [
        'tipo' => $_GET['tipo'] ?? null,
        'etiqueta' => $_GET['etiqueta'] ?? null,
        'busqueda' => $_GET['busqueda'] ?? null,
        'destacado' => $_GET['destacado'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 10
    ];
    
    // Validar parámetros
    $validationErrors = validateParameters($params);
    if (!empty($validationErrors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Parámetros inválidos',
            'details' => $validationErrors,
            'code' => 'INVALID_PARAMETERS'
        ]);
        exit;
    }
    
    // Convertir parámetros de paginación a enteros
    $page = max(1, intval($params['page']));
    $limit = max(1, min(100, intval($params['limit'])));
    $offset = ($page - 1) * $limit;
    
    // Obtener conexión
    $mysqli = getConnection();
    
    // Construir query con filtros
    $queryData = buildQuery($params);
    
    // Query para contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM novedades WHERE activo = 1";
    if (!empty($queryData['params'])) {
        // Construir el WHERE para el count
        $countWhereConditions = [];
        $paramIndex = 0;
        
        if (isset($params['tipo']) && !empty($params['tipo'])) {
            $countWhereConditions[] = "tipo = ?";
        }
        
        if (isset($params['etiqueta']) && !empty($params['etiqueta'])) {
            $countWhereConditions[] = "JSON_SEARCH(etiquetas, 'one', ?) IS NOT NULL";
        }
        
        if (isset($params['busqueda']) && !empty($params['busqueda'])) {
            $countWhereConditions[] = "(titulo LIKE ? OR descripcion LIKE ? OR contenido LIKE ?)";
        }
        
        if (isset($params['destacado']) && !empty($params['destacado'])) {
            $countWhereConditions[] = "destacado = ?";
        }
        
        if (!empty($countWhereConditions)) {
            $countSql .= " AND " . implode(" AND ", $countWhereConditions);
        }
    }
    
    // Ejecutar query de conteo
    if (!empty($queryData['params'])) {
        $countStmt = $mysqli->prepare($countSql);
        if (!$countStmt) {
            throw new Exception('Error preparando consulta de conteo: ' . $mysqli->error);
        }
        $countStmt->bind_param($queryData['types'], ...$queryData['params']);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
    } else {
        $countResult = $mysqli->query($countSql);
    }
    
    if (!$countResult) {
        throw new Exception('Error ejecutando consulta de conteo: ' . $mysqli->error);
    }
    
    $totalRow = $countResult->fetch_assoc();
    $total = intval($totalRow['total']);
    
    // Query principal con paginación
    $mainSql = $queryData['sql'] . " ORDER BY destacado DESC, fecha DESC LIMIT ? OFFSET ?";
    $mainParams = $queryData['params'];
    $mainParams[] = $limit;
    $mainParams[] = $offset;
    $mainTypes = $queryData['types'] . "ii";
    
    // Ejecutar query principal
    if (!empty($queryData['params'])) {
        $stmt = $mysqli->prepare($mainSql);
        if (!$stmt) {
            throw new Exception('Error preparando consulta principal: ' . $mysqli->error);
        }
        $stmt->bind_param($mainTypes, ...$mainParams);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $stmt = $mysqli->prepare($mainSql);
        if (!$stmt) {
            throw new Exception('Error preparando consulta principal: ' . $mysqli->error);
        }
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if (!$result) {
        throw new Exception('Error ejecutando consulta principal: ' . $mysqli->error);
    }
    
    // Procesar resultados
    $novedades = [];
    while ($row = $result->fetch_assoc()) {
        $novedades[] = formatearNovedad($row);
    }
    
    // Calcular paginación
    $totalPages = ceil($total / $limit);
    
    // Cerrar conexión
    $mysqli->close();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $novedades,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => $totalPages,
        'message' => 'Novedades obtenidas correctamente'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Log del error (opcional)
    error_log("Error en API novedades: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage(),
        'code' => 'INTERNAL_SERVER_ERROR'
    ]);
}
?>
