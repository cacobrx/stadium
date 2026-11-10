<?php
// api/productos/productos_categorias_principales.php
// Devuelve productos con stock>0 que pertenezcan a categorías marcadas como "principal"

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getConnection() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8');
    return $mysqli;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $mysqli = getConnection();

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;

    if ($categoria_id) {
        // Usar CTE recursivo para incluir subcategorías
        $query = "
            WITH RECURSIVE cat_tree AS (
                SELECT id FROM categorias WHERE id = ?
                UNION ALL
                SELECT c.id FROM categorias c JOIN cat_tree ct ON c.categoria_padre_id = ct.id
            )
            SELECT DISTINCT p.id, p.sku, p.nombre, p.descripcion, p.precio, p.precio_descuento,
                   p.stock, p.esta_activo, p.destacado, p.deporte,
                   m.id as marca_id, m.nombre as marca_nombre,
                   pi.url as imagen_principal
            FROM productos p
            LEFT JOIN marcas m ON p.marca_id = m.id
            LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
            INNER JOIN producto_categorias pc ON p.id = pc.producto_id
            INNER JOIN categorias c ON c.id = pc.categoria_id AND c.principal = 1
            WHERE pc.categoria_id IN (SELECT id FROM cat_tree) AND p.stock > 0 AND p.esta_activo = 1
            ORDER BY p.nombre
        ";

        if ($limit && $limit > 0) {
            $query .= " LIMIT " . $limit;
        }

        $stmt = $mysqli->prepare($query);
        if ($stmt === false) {
            throw new Exception('Error preparando consulta: ' . $mysqli->error);
        }
        $stmt->bind_param('i', $categoria_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Todos los productos que estén en alguna categoría principal
        $query = "
            SELECT DISTINCT p.id, p.sku, p.nombre, p.descripcion, p.precio, p.precio_descuento,
                   p.stock, p.esta_activo, p.destacado, p.deporte,
                   m.id as marca_id, m.nombre as marca_nombre,
                   pi.url as imagen_principal
            FROM productos p
            LEFT JOIN marcas m ON p.marca_id = m.id
            LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
            INNER JOIN producto_categorias pc ON p.id = pc.producto_id
            INNER JOIN categorias c ON c.id = pc.categoria_id AND c.principal = 1
            WHERE p.stock > 0 AND p.esta_activo = 1
            ORDER BY p.nombre
        ";

        if ($limit && $limit > 0) {
            $query .= " LIMIT " . $limit;
        }

        $result = $mysqli->query($query);
        if (!$result) {
            throw new Exception('Error en consulta: ' . $mysqli->error);
        }
    }

    $productos = [];
    $base_url = "https://stadium.planbsistemas.com.ar/";
    while ($row = $result->fetch_assoc()) {
        $producto_id = intval($row['id']);

        // Obtener categorías principales asociadas a este producto
        $stmt_cat = $mysqli->prepare("SELECT c.id, c.nombre FROM categorias c INNER JOIN producto_categorias pc ON c.id = pc.categoria_id WHERE pc.producto_id = ? AND c.principal = 1 ORDER BY c.nombre");
        $stmt_cat->bind_param('i', $producto_id);
        $stmt_cat->execute();
        $res_cat = $stmt_cat->get_result();
        $row['categorias_principales'] = $res_cat->fetch_all(MYSQLI_ASSOC);
        $stmt_cat->close();

        // Construir URL absoluta de la imagen principal
        $img = isset($row['imagen_principal']) ? $row['imagen_principal'] : '';
        if ($img && strpos($img, 'http') !== 0) {
            $row['imagen_url'] = $base_url . ltrim($img, '/');
        } else {
            $row['imagen_url'] = $img;
        }

        $productos[] = $row;
    }

    echo json_encode(['success' => true, 'productos' => $productos]);
    $mysqli->close();

} catch (Exception $e) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>