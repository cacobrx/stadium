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
    // Si no se envía limit, se asume un valor muy alto para devolver todo (sin límite por defecto)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10000;
    $categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
    $deporte = isset($_GET['deporte']) ? (int)$_GET['deporte'] : null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    // Si se especifica un ID, devolver solo ese producto
    if ($id) {
        $stmt = $mysqli->prepare("
            SELECT e.*, 
                   c.nombre as categoria_nombre,
                   d.nombre as deporte_nombre
            FROM equipamiento_profesional e
            LEFT JOIN categorias c ON e.categoria = c.id
            LEFT JOIN deportes d ON e.deporte_id = d.id
            WHERE e.id = ? AND e.esta_activo = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Obtener imágenes del equipamiento
            $img_query = "SELECT url, alt_text, es_principal, orden FROM equipamiento_imagenes WHERE equipamiento_id = ? ORDER BY orden, id";
            $img_stmt = $mysqli->prepare($img_query);
            $img_stmt->bind_param("i", $row['id']);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            
            $imagenes = [];
            while ($img_row = $img_result->fetch_assoc()) {
                $imagenes[] = $base_url . $img_row['url'];
            }
            $img_stmt->close();
            
            // Obtener especificaciones del equipamiento
            $esp_query = "SELECT etiqueta, valor FROM equipamiento_especificaciones WHERE equipamiento_id = ? ORDER BY orden, id";
            $esp_stmt = $mysqli->prepare($esp_query);
            $esp_stmt->bind_param("i", $row['id']);
            $esp_stmt->execute();
            $esp_result = $esp_stmt->get_result();
            
            $especificaciones = [];
            while ($esp_row = $esp_result->fetch_assoc()) {
                $especificaciones[] = [
                    "etiqueta" => $esp_row['etiqueta'],
                    "valor" => $esp_row['valor']
                ];
            }
            $esp_stmt->close();
            
            // Obtener características del equipamiento
            $car_query = "SELECT caracteristica FROM equipamiento_caracteristicas WHERE equipamiento_id = ? ORDER BY orden, id";
            $car_stmt = $mysqli->prepare($car_query);
            $car_stmt->bind_param("i", $row['id']);
            $car_stmt->execute();
            $car_result = $car_stmt->get_result();
            
            $caracteristicas = [];
            while ($car_row = $car_result->fetch_assoc()) {
                $caracteristicas[] = $car_row['caracteristica'];
            }
            $car_stmt->close();
            
            $equipamiento = [
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "categoria_id" => $row['categoria'],
                "categoria" => $row['categoria_nombre'] ?? null,
                "deporte_id" => $row['deporte_id'],
                "deporte" => $row['deporte_nombre'] ?? null,
                "uso" => $row['uso'] ?? null,
                "descripcion" => $row['descripcion'] ?? '',
                "especificaciones" => $especificaciones,
                "caracteristicas" => $caracteristicas,
                "imagenes" => $imagenes,
                "disponible" => (bool)($row['disponible'] ?? true),
                "destacado" => (bool)($row['destacado'] ?? false),
                "tiempoEntrega" => $row['tiempo_entrega'] ?? '',
                "garantia" => $row['garantia'] ?? '',
                "instalacion" => (bool)($row['instalacion'] ?? false),
                "financiacion" => (bool)($row['financiacion'] ?? true)
            ];
            
            $stmt->close();
            $mysqli->close();
            
            // Respuesta para producto individual
            echo json_encode($equipamiento, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        } else {
            $stmt->close();
            $mysqli->close();
            
            // Producto no encontrado
            http_response_code(404);
            echo json_encode([
                "error" => true,
                "message" => "Producto no encontrado"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    
    // Construir consulta con filtros
    $where_clause = "WHERE e.esta_activo = 1";
    $params = [];
    $types = "";
    
    if ($categoria) {
        $where_clause .= " AND e.categoria = ?";
        $params[] = $categoria;
        $types .= "i";
    }
    
    if ($deporte) {
        $where_clause .= " AND e.deporte_id = ?";
        $params[] = $deporte;
        $types .= "i";
    }
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM equipamiento_profesional e " . $where_clause;
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
    $main_query = "
        SELECT e.*, 
               c.nombre as categoria_nombre,
               d.nombre as deporte_nombre
        FROM equipamiento_profesional e
        LEFT JOIN categorias c ON e.categoria = c.id
        LEFT JOIN deportes d ON e.deporte_id = d.id
        " . $where_clause . " ORDER BY e.orden ASC LIMIT ? OFFSET ?";
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
        // Obtener imágenes del equipamiento
        $img_query = "SELECT url, alt_text, es_principal, orden FROM equipamiento_imagenes WHERE equipamiento_id = ? ORDER BY orden, id";
        $img_stmt = $mysqli->prepare($img_query);
        $img_stmt->bind_param("i", $row['id']);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        
        $imagenes = [];
        while ($img_row = $img_result->fetch_assoc()) {
            $imagenes[] = $base_url . $img_row['url'];
        }
        $img_stmt->close();
        
        // Obtener especificaciones del equipamiento
        $esp_query = "SELECT etiqueta, valor FROM equipamiento_especificaciones WHERE equipamiento_id = ? ORDER BY orden, id";
        $esp_stmt = $mysqli->prepare($esp_query);
        $esp_stmt->bind_param("i", $row['id']);
        $esp_stmt->execute();
        $esp_result = $esp_stmt->get_result();
        
        $especificaciones = [];
        while ($esp_row = $esp_result->fetch_assoc()) {
            $especificaciones[] = [
                "etiqueta" => $esp_row['etiqueta'],
                "valor" => $esp_row['valor']
            ];
        }
        $esp_stmt->close();
        
        // Obtener características del equipamiento
        $car_query = "SELECT caracteristica FROM equipamiento_caracteristicas WHERE equipamiento_id = ? ORDER BY orden, id";
        $car_stmt = $mysqli->prepare($car_query);
        $car_stmt->bind_param("i", $row['id']);
        $car_stmt->execute();
        $car_result = $car_stmt->get_result();
        
        $caracteristicas = [];
        while ($car_row = $car_result->fetch_assoc()) {
            $caracteristicas[] = $car_row['caracteristica'];
        }
        $car_stmt->close();
        
        $equipamiento = [
            "id" => $row['id'],
            "nombre" => $row['nombre'],
            "categoria_id" => $row['categoria'],
            "categoria" => $row['categoria_nombre'] ?? null,
            "deporte_id" => $row['deporte_id'],
            "deporte" => $row['deporte_nombre'] ?? null,
            "uso" => $row['uso'] ?? null,
            "descripcion" => $row['descripcion'] ?? '',
            "especificaciones" => $especificaciones,
            "caracteristicas" => $caracteristicas,
            "imagenes" => $imagenes,
            "disponible" => (bool)($row['disponible'] ?? true),
            "destacado" => (bool)($row['destacado'] ?? false),
            "tiempoEntrega" => $row['tiempo_entrega'] ?? '',
            "garantia" => $row['garantia'] ?? '',
            "instalacion" => (bool)($row['instalacion'] ?? false),
            "financiacion" => (bool)($row['financiacion'] ?? true)
        ];
        
        $data[] = $equipamiento;
    }
    
    $stmt->close();
    $mysqli->close();

    // Respuesta
    $limit_response = isset($_GET['limit']) ? $limit : (int)$total;
    $response = [
        "data" => $data,
        "pagination" => [
            "page" => $page,
            "limit" => $limit_response,
            "total" => (int)$total,
            "totalPages" => $limit_response > 0 ? ceil($total / $limit_response) : 1
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