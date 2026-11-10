<?php
// api/productos-formato-test.php - Versión sin autenticación para pruebas

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Función de conexión
function getConnection() {
    try {
        $mysqli = get_database_connection();
        if ($mysqli->connect_errno) {
            throw new Exception('Error de conexión: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8');
        return $mysqli;
    } catch (Exception $e) {
        throw new Exception('Error conectando a BD: ' . $e->getMessage());
    }
}

// Función para formatear producto según el formato solicitado
function formatearProducto($producto, $mysqli) {
    // Obtener marca
    $marca = null;
    if ($producto['marca_id']) {
        $stmt_marca = $mysqli->prepare("SELECT id, nombre FROM marcas WHERE id = ?");
        $stmt_marca->bind_param('i', $producto['marca_id']);
        $stmt_marca->execute();
        $result_marca = $stmt_marca->get_result();
        if ($marca_data = $result_marca->fetch_assoc()) {
            $marca = [
                "id" => "marca-" . $marca_data['id'],
                "nombre" => $marca_data['nombre'],
                "slug" => strtolower(str_replace(' ', '-', $marca_data['nombre']))
            ];
        }
    }

    // Obtener categorías
    $stmt_cat = $mysqli->prepare("
        SELECT c.id, c.nombre, c.slug 
        FROM categorias c 
        INNER JOIN producto_categorias pc ON c.id = pc.categoria_id 
        WHERE pc.producto_id = ?
    ");
    $stmt_cat->bind_param('i', $producto['id']);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    $categorias = [];
    while ($cat = $result_cat->fetch_assoc()) {
        $categorias[] = [
            "id" => "cat-" . $cat['id'],
            "nombre" => $cat['nombre'],
            "slug" => $cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['nombre']))
        ];
    }

    // Obtener imágenes
    $stmt_img = $mysqli->prepare("
        SELECT id, url, alt_text 
        FROM producto_imagenes 
        WHERE producto_id = ? 
        ORDER BY es_principal DESC, orden ASC, id ASC
    ");
    $stmt_img->bind_param('i', $producto['id']);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $imagenes = [];
    while ($img = $result_img->fetch_assoc()) {
        $imagenes[] = [
            "id" => "img-" . $img['id'],
            "url" => $img['url'],
            "textoAlternativo" => $img['alt_text'] ?? $producto['nombre']
        ];
    }

    // Obtener variantes
    $stmt_var = $mysqli->prepare("
        SELECT id, sku_variante, stock, precio 
        FROM producto_variantes 
        WHERE producto_id = ? AND activa = 1
        ORDER BY id ASC
    ");
    $stmt_var->bind_param('i', $producto['id']);
    $stmt_var->execute();
    $result_var = $stmt_var->get_result();
    $variantes = [];
    while ($var = $result_var->fetch_assoc()) {
        // Obtener opciones de la variante
        $stmt_opt = $mysqli->prepare("
            SELECT nombre, valor 
            FROM variante_opciones 
            WHERE variante_id = ?
        ");
        $stmt_opt->bind_param('i', $var['id']);
        $stmt_opt->execute();
        $result_opt = $stmt_opt->get_result();
        $opciones = [];
        while ($opt = $result_opt->fetch_assoc()) {
            $opciones[] = [
                "nombre" => $opt['nombre'],
                "valor" => $opt['valor']
            ];
        }

        $variantes[] = [
            "id" => "var-" . $var['id'],
            "sku" => $var['sku_variante'],
            "stock" => intval($var['stock']),
            "precio" => floatval($var['precio'] ?? $producto['precio']),
            "opciones" => $opciones,
            "imagenes" => []
        ];
    }

    // Obtener atributos
    $stmt_attr = $mysqli->prepare("
        SELECT nombre, valor 
        FROM producto_atributos 
        WHERE producto_id = ? 
        ORDER BY id ASC
    ");
    $stmt_attr->bind_param('i', $producto['id']);
    $stmt_attr->execute();
    $result_attr = $stmt_attr->get_result();
    $atributos = [];
    while ($attr = $result_attr->fetch_assoc()) {
        $atributos[] = [
            "nombre" => $attr['nombre'],
            "valor" => $attr['valor']
        ];
    }

    // Formatear el producto según el esquema solicitado
    $producto_formateado = [
        "id" => strtolower(str_replace(' ', '-', $producto['nombre'])) . "-" . $producto['id'],
        "sku" => $producto['sku'],
        "nombre" => $producto['nombre'],
        "descripcion" => $producto['descripcion'] ?? "",
        "precio" => floatval($producto['precio']),
        "stock" => intval($producto['stock']),
        "estaActivo" => boolval($producto['esta_activo']),
        "destacado" => boolval($producto['destacado']),
        "deporte" => $producto['deporte'] ?? "",
        "imagenes" => $imagenes,
        "categorias" => $categorias,
        "variantes" => $variantes,
        "atributos" => $atributos,
        "fechaDeCreacion" => date('c', strtotime($producto['fecha_creacion'])),
        "fechaDeActualizacion" => date('c', strtotime($producto['fecha_actualizacion']))
    ];

    // Agregar marca si existe
    if ($marca) {
        $producto_formateado["marca"] = $marca;
    }

    // Agregar precio de descuento si existe
    if ($producto['precio_descuento']) {
        $producto_formateado["precioDeDescuento"] = floatval($producto['precio_descuento']);
    }

    return $producto_formateado;
}

try {
    $mysqli = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Parámetros de paginación
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;

        // Filtros
        $where_conditions = ["p.esta_activo = 1"];
        $params = [];
        $param_types = "";

        if (isset($_GET['deporte']) && !empty($_GET['deporte'])) {
            $where_conditions[] = "p.deporte = ?";
            $params[] = $_GET['deporte'];
            $param_types .= "s";
        }

        if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
            $where_conditions[] = "EXISTS (SELECT 1 FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id AND (c.slug = ? OR c.nombre = ?))";
            $params[] = $_GET['categoria'];
            $params[] = $_GET['categoria'];
            $param_types .= "ss";
        }

        if (isset($_GET['marca']) && !empty($_GET['marca'])) {
            $where_conditions[] = "EXISTS (SELECT 1 FROM marcas m WHERE m.id = p.marca_id AND (m.nombre = ? OR LOWER(REPLACE(m.nombre, ' ', '-')) = ?))";
            $params[] = $_GET['marca'];
            $params[] = strtolower($_GET['marca']);
            $param_types .= "ss";
        }

        if (isset($_GET['destacado']) && $_GET['destacado'] !== '') {
            $where_conditions[] = "p.destacado = ?";
            $params[] = intval($_GET['destacado']);
            $param_types .= "i";
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Consulta para contar total
        $count_query = "
            SELECT COUNT(*) as total 
            FROM productos p 
            WHERE $where_clause
        ";
        
        if (!empty($params)) {
            $stmt_count = $mysqli->prepare($count_query);
            $stmt_count->bind_param($param_types, ...$params);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $total_count = $result_count->fetch_assoc()['total'];
        } else {
            $result_count = $mysqli->query($count_query);
            $total_count = $result_count->fetch_assoc()['total'];
        }

        // Consulta principal
        $main_query = "
            SELECT p.* 
            FROM productos p 
            WHERE $where_clause
            ORDER BY p.destacado DESC, p.nombre ASC
            LIMIT ? OFFSET ?
        ";
        
        // Agregar parámetros de paginación
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= "ii";

        $stmt = $mysqli->prepare($main_query);
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $productos_formateados = [];
        while ($producto = $result->fetch_assoc()) {
            $productos_formateados[] = formatearProducto($producto, $mysqli);
        }

        // Calcular paginación
        $total_pages = ceil($total_count / $limit);

        // Respuesta en el formato solicitado
        $response = [
            "data" => $productos_formateados,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => intval($total_count),
                "totalPages" => $total_pages
            ]
        ];

        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Método no permitido en modo test']);
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>