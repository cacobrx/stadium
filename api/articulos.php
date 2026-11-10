<?php
// api/articulos.php  
// API para obtener productos (artículos)
// IMPORTANTE: Migrado de tabla 'articulos' a tabla 'productos' 
// - Los pedidos nuevos usan productos.id 
// - Mantener compatibilidad con pedidos existentes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Función de compatibilidad (mantener nombre original)
function get_db_connection() {
    return get_database_connection();
}

// Obtener todos los productos
function obtener_articulos() {
    try {
        $conn = get_db_connection();
        // Obtener solo productos activos con stock disponible
        $sql = "SELECT id, sku, nombre, precio, precio_descuento, descripcion, stock, deporte FROM productos WHERE esta_activo = 1 AND stock > 0 ORDER BY nombre";
        $result = $conn->query($sql);
        $articulos = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Usar precio con descuento si existe, sino el precio normal
                $precio_final = $row['precio_descuento'] ? $row['precio_descuento'] : $row['precio'];
                $articulos[] = [
                    'id' => $row['id'],
                    'sku' => $row['sku'],
                    'nombre' => $row['nombre'],
                    'precio' => $precio_final,
                    'precio_original' => $row['precio'],
                    'descripcion' => $row['descripcion'],
                    'stock' => $row['stock'],
                    'deporte' => $row['deporte']
                ];
            }
        }
        $conn->close();
        return $articulos;
    } catch (Exception $e) {
        error_log("Error en obtener_articulos: " . $e->getMessage());
        return [];
    }
}

// Obtener artículo por ID
function obtener_articulo($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, nombre, precio FROM articulos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $articulo = $result->fetch_assoc();
        $conn->close();
        return $articulo;
    } catch (Exception $e) {
        error_log("Error en obtener_articulo: " . $e->getMessage());
        return null;
    }
}

// Manejo de peticiones HTTP
try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $articulo = obtener_articulo($_GET['id']);
                if ($articulo) {
                    echo json_encode($articulo);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Artículo no encontrado']);
                }
            } else {
                echo json_encode(obtener_articulos());
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error general en API articulos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>