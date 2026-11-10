<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // URL base para las imágenes
    $base_url = "https://stadium.planbsistemas.com.ar/";
    
    // Usar configuración centralizada
    require_once __DIR__ . '/../../config/database.php';
    $mysqli = get_database_connection();
    
    // Parámetros de paginación y filtros
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $slug = isset($_GET['slug']) ? $_GET['slug'] : null;
    $destacado = isset($_GET['destacado']) ? (bool)$_GET['destacado'] : null;
    
    // Si se especifica un ID o slug, devolver solo ese proyecto
    if ($id || $slug) {
        $where_condition = $id ? "id = ?" : "slug = ?";
        $param_value = $id ? $id : $slug;
        $param_type = $id ? "i" : "s";
        
        $stmt = $mysqli->prepare("SELECT * FROM proyectos WHERE $where_condition AND activo = 1");
        $stmt->bind_param($param_type, $param_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Obtener imágenes del proyecto
            $img_query = "SELECT id, url, alt_text, es_principal FROM proyectos_images WHERE proyecto_id = ? ORDER BY id";
            $img_stmt = $mysqli->prepare($img_query);
            $img_stmt->bind_param("i", $row['id']);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            
            $imagenes = [];
            $imagen_principal = null;
            while ($img_row = $img_result->fetch_assoc()) {
                $url_completa = $base_url . $img_row['url'];
                $imagenes[] = [
                    "id" => $img_row['id'],
                    "url" => $url_completa,
                    "alt" => $img_row['alt_text'] ?? '',
                    "es_principal" => (bool)$img_row['es_principal']
                ];
                
                if ($img_row['es_principal']) {
                    $imagen_principal = $url_completa;
                }
            }
            $img_stmt->close();
            
            // Si no hay imagen principal, usar la primera
            if (!$imagen_principal && !empty($imagenes)) {
                $imagen_principal = $imagenes[0]['url'];
            }
            
            // Obtener características del proyecto
            $car_query = "SELECT id, title, description FROM proyecto_features WHERE proyecto_id = ? ORDER BY id";
            $car_stmt = $mysqli->prepare($car_query);
            $car_stmt->bind_param("i", $row['id']);
            $car_stmt->execute();
            $car_result = $car_stmt->get_result();
            
            $caracteristicas = [];
            while ($car_row = $car_result->fetch_assoc()) {
                $caracteristicas[] = [
                    "id" => $car_row['id'],
                    "titulo" => $car_row['title'],
                    "descripcion" => $car_row['description']
                ];
            }            
            $car_stmt->close();
            
            // Obtener especificaciones del proyecto
            $esp_query = "SELECT id, label, value FROM proyecto_especificaciones WHERE proyecto_id = ? ORDER BY id";
            $esp_stmt = $mysqli->prepare($esp_query);
            $esp_stmt->bind_param("i", $row['id']);
            $esp_stmt->execute();
            $esp_result = $esp_stmt->get_result();
            
            $especificaciones = [];
            while ($esp_row = $esp_result->fetch_assoc()) {
                $especificaciones[] = [
                    "etiqueta" => $esp_row['label'],
                    "valor" => $esp_row['value']
                ];
            }
            $esp_stmt->close();
            
            // Obtener categorías del proyecto
            $cat_query = "SELECT categoria FROM proyecto_categorias WHERE proyecto_id = ? ORDER BY id";
            $cat_stmt = $mysqli->prepare($cat_query);
            $cat_stmt->bind_param("i", $row['id']);
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();
            
            $categorias = [];
            while ($cat_row = $cat_result->fetch_assoc()) {
                $categorias[] = $cat_row['categoria'];
            }
            $cat_stmt->close();
            
            $proyecto = [
                "id" => $row['id'],
                "titulo" => $row['title'],
                "subtitulo" => $row['subtitle'] ?? '',
                "slug" => $row['slug'] ?? '',
                "descripcion" => $row['description'] ?? '',
                "ubicacion" => $row['location'] ?? '',
                "cliente" => $row['client'] ?? '',
                "area" => $row['area'] ?? '',
                "duracion" => $row['duration'] ?? '',
                "fecha_completado" => $row['completion_date'] ?? null,
                "destacado" => (bool)($row['badge'] ? true : false),
                "activo" => (bool)($row['activo'] ?? true),
                "imagen_principal" => $imagen_principal,
                "imagenes" => $imagenes,
                "caracteristicas" => $caracteristicas,
                "especificaciones" => $especificaciones,
                "categorias" => $categorias
            ];
            
            $stmt->close();
            $mysqli->close();
            
            // Respuesta para proyecto individual
            echo json_encode($proyecto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        } else {
            $stmt->close();
            $mysqli->close();
            
            // Proyecto no encontrado
            http_response_code(404);
            echo json_encode([
                "error" => true,
                "message" => "Proyecto no encontrado"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    
    // Construir consulta con filtros para listado
    $where_clause = "WHERE activo = 1";
    $params = [];
    $types = "";
    
    if ($categoria) {
        $where_clause .= " AND id IN (SELECT proyecto_id FROM proyecto_categorias WHERE categoria = ?)";
        $params[] = $categoria;
        $types .= "s";
    }
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM proyectos " . $where_clause;
    $count_stmt = $mysqli->prepare($count_query);
    if ($params) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total = $total_result->fetch_assoc()['total'];
    $count_stmt->close();
    
    // Calcular offset para paginación
    $offset = ($page - 1) * $limit;
    $totalPages = ceil($total / $limit);
    
    // Consulta principal con paginación
    $main_query = "SELECT * FROM proyectos " . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $mysqli->prepare($main_query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Obtener imágenes del proyecto
        $img_query = "SELECT id, url, alt_text, es_principal FROM proyectos_images WHERE proyecto_id = ? ORDER BY id";
        $img_stmt = $mysqli->prepare($img_query);
        $img_stmt->bind_param("i", $row['id']);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        
        $imagenes = [];
        $imagen_principal = null;
        while ($img_row = $img_result->fetch_assoc()) {
            $url_completa = $base_url . $img_row['url'];
            $imagenes[] = [
                "id" => $img_row['id'],
                "url" => $url_completa,
                "alt" => $img_row['alt_text'] ?? '',
                "es_principal" => (bool)$img_row['es_principal']
            ];
            
            if ($img_row['es_principal']) {
                $imagen_principal = $url_completa;
            }
        }
        $img_stmt->close();
        
        // Si no hay imagen principal, usar la primera
        if (!$imagen_principal && !empty($imagenes)) {
            $imagen_principal = $imagenes[0]['url'];
        }
        
        // Obtener características del proyecto
        $car_query = "SELECT id, title, description FROM proyecto_features WHERE proyecto_id = ? ORDER BY id";
        $car_stmt = $mysqli->prepare($car_query);
        $car_stmt->bind_param("i", $row['id']);
        $car_stmt->execute();
        $car_result = $car_stmt->get_result();
        
        $caracteristicas = [];
        while ($car_row = $car_result->fetch_assoc()) {
            $caracteristicas[] = [
                "id" => $car_row['id'],
                "titulo" => $car_row['title'],
                "descripcion" => $car_row['description']
            ];
        }        
        $car_stmt->close();
        
        // Obtener especificaciones del proyecto
        $esp_query = "SELECT id, label, value FROM proyecto_especificaciones WHERE proyecto_id = ? ORDER BY id";
        $esp_stmt = $mysqli->prepare($esp_query);
        $esp_stmt->bind_param("i", $row['id']);
        $esp_stmt->execute();
        $esp_result = $esp_stmt->get_result();
        
        $especificaciones = [];
        while ($esp_row = $esp_result->fetch_assoc()) {
            $especificaciones[] = [
                "etiqueta" => $esp_row['label'],
                "valor" => $esp_row['value']
            ];
        }
        $esp_stmt->close();
        
        // Obtener categorías del proyecto
        $cat_query = "SELECT categoria FROM proyecto_categorias WHERE proyecto_id = ? ORDER BY id";
        $cat_stmt = $mysqli->prepare($cat_query);
        $cat_stmt->bind_param("i", $row['id']);
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        
        $categorias = [];
        while ($cat_row = $cat_result->fetch_assoc()) {
            $categorias[] = $cat_row['categoria'];
        }
        $cat_stmt->close();
        
        $proyecto = [
            "id" => $row['id'],
            "titulo" => $row['title'],
            "subtitulo" => $row['subtitle'] ?? '',
            "slug" => $row['slug'] ?? '',
            "descripcion" => $row['description'] ?? '',
            "ubicacion" => $row['location'] ?? '',
            "cliente" => $row['client'] ?? '',
            "area" => $row['area'] ?? '',
            "duracion" => $row['duration'] ?? '',
            "fecha_completado" => $row['completion_date'] ?? null,
            "destacado" => (bool)($row['badge'] ? true : false),
            "activo" => (bool)($row['activo'] ?? true),
            "imagen_principal" => $imagen_principal,
            "imagenes" => $imagenes,
            "caracteristicas" => $caracteristicas,
            "especificaciones" => $especificaciones,
            "categorias" => $categorias
        ];
        
        $data[] = $proyecto;
    }
    
    $stmt->close();
    $mysqli->close();

    // Respuesta
    $response = [
        "data" => $data,
        "pagination" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $total,
            "totalPages" => $totalPages
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Error interno del servidor: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
