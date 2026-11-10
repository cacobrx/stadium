<?php
// api/compras_det.php
// API para obtener detalles de compras

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

// Obtener detalles de una compra con información de la variante
function obtener_detalles_compra($idcom) {
    try {
        $conn = get_db_connection();
        // JOIN con tabla producto_variantes y productos para obtener información completa
        $sql = "SELECT 
                    cd.id, 
                    cd.idcom, 
                    cd.cantidad, 
                    cd.idart, 
                    cd.precio, 
                    cd.total,
                    pv.sku_variante as producto_sku,
                    p.nombre as producto_nombre,
                    pv.precio as precio_variante
                FROM compras_det cd 
                LEFT JOIN producto_variantes pv ON cd.idart = pv.id 
                LEFT JOIN productos p ON pv.producto_id = p.id 
                WHERE cd.idcom = ? 
                ORDER BY cd.id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idcom);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Si no encuentra la variante, buscar en productos directamente (compatibilidad)
                if (!$row['producto_nombre']) {
                    $stmt_prod = $conn->prepare("SELECT nombre, sku FROM productos WHERE id = ?");
                    $stmt_prod->bind_param("i", $row['idart']);
                    $stmt_prod->execute();
                    $prod_result = $stmt_prod->get_result();
                    if ($prod_row = $prod_result->fetch_assoc()) {
                        $row['producto_nombre'] = $prod_row['nombre'];
                        $row['producto_sku'] = $prod_row['sku'];
                    }
                }
                
                // Si aún no encuentra, buscar en articulos (compatibilidad)
                if (!$row['producto_nombre']) {
                    $stmt_art = $conn->prepare("SELECT nombre FROM articulos WHERE id = ?");
                    $stmt_art->bind_param("i", $row['idart']);
                    $stmt_art->execute();
                    $art_result = $stmt_art->get_result();
                    if ($art_row = $art_result->fetch_assoc()) {
                        $row['producto_nombre'] = $art_row['nombre'];
                        $row['producto_sku'] = null;
                    }
                }
                $detalles[] = $row;
            }
        }
        $conn->close();
        return $detalles;
    } catch (Exception $e) {
        error_log("Error en obtener_detalles_compra: " . $e->getMessage());
        return [];
    }
}

// Obtener detalle específico por ID
function obtener_detalle($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, idcom, cantidad, idart, precio, total FROM compras_det WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalle = $result->fetch_assoc();
        $conn->close();
        return $detalle;
    } catch (Exception $e) {
        error_log("Error en obtener_detalle: " . $e->getMessage());
        return null;
    }
}

// Crear nuevo detalle
function crear_detalle($datos) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO compras_det (idcom, cantidad, idart, precio, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", 
            $datos['idcom'], 
            $datos['cantidad'], 
            $datos['idart'], 
            $datos['precio'], 
            $datos['total']
        );
        $success = $stmt->execute();
        $id = $conn->insert_id;
        $conn->close();
        return $success ? $id : false;
    } catch (Exception $e) {
        error_log("Error en crear_detalle: " . $e->getMessage());
        return false;
    }
}

// Actualizar detalle
function actualizar_detalle($id, $datos) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("UPDATE compras_det SET cantidad=?, idart=?, precio=?, total=? WHERE id=?");
        $stmt->bind_param("iiddi", 
            $datos['cantidad'], 
            $datos['idart'], 
            $datos['precio'], 
            $datos['total'],
            $id
        );
        $success = $stmt->execute();
        $conn->close();
        return $success;
    } catch (Exception $e) {
        error_log("Error en actualizar_detalle: " . $e->getMessage());
        return false;
    }
}

// Eliminar detalle
function eliminar_detalle($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("DELETE FROM compras_det WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $conn->close();
        return $success;
    } catch (Exception $e) {
        error_log("Error en eliminar_detalle: " . $e->getMessage());
        return false;
    }
}

// Manejo de peticiones HTTP
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener detalle específico por ID
                $detalle = obtener_detalle($_GET['id']);
                if ($detalle) {
                    echo json_encode($detalle);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Detalle no encontrado']);
                }
            } elseif (isset($_GET['idcom'])) {
                // Obtener todos los detalles de una compra
                echo json_encode(obtener_detalles_compra($_GET['idcom']));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID o IDCOM requerido']);
            }
            break;

        case 'POST':
            if ($input) {
                $id = crear_detalle($input);
                if ($id) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Detalle creado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al crear detalle']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id']) && $input) {
                $success = actualizar_detalle($_GET['id'], $input);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Detalle actualizado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar detalle']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID y datos requeridos']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $success = eliminar_detalle($_GET['id']);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Detalle eliminado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar detalle']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error general en API compras_det: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>