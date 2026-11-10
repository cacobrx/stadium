<?php
// api/pedidos_det.php
// API para obtener detalles de pedidos

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

// Obtener detalles de un pedido con información del producto/inventario
function obtener_detalles_pedido($idped) {
    try {
        $conn = get_db_connection();
        // JOIN con tabla producto_variantes para obtener información del inventario
        $sql = "SELECT 
                    pd.id, 
                    pd.idped, 
                    pd.cantidad, 
                    pd.idart, 
                    pd.fechacreate, 
                    pd.fechamod, 
                    pd.precio, 
                    pd.total, 
                    pd.terminado, 
                    pd.detalle,
                    pv.sku_variante,
                    pv.stock as variante_stock,
                    pv.precio as variante_precio,
                    p.nombre as producto_nombre,
                    p.sku as producto_sku
                FROM pedidos_det pd 
                LEFT JOIN producto_variantes pv ON pd.idart = pv.id
                LEFT JOIN productos p ON pv.producto_id = p.id
                WHERE pd.idped = ? 
                ORDER BY pd.id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idped);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Obtener atributos de la variante si existe
                if ($row['idart']) {
                    $stmt_attr = $conn->prepare("SELECT nombre, valor FROM variante_atributos WHERE variante_id = ? ORDER BY nombre");
                    $stmt_attr->bind_param("i", $row['idart']);
                    $stmt_attr->execute();
                    $attr_result = $stmt_attr->get_result();
                    $atributos = [];
                    while ($attr = $attr_result->fetch_assoc()) {
                        $atributos[] = $attr['nombre'] . ': ' . $attr['valor'];
                    }
                    $row['atributos'] = implode(', ', $atributos);
                }
                
                // Construir nombre completo
                if ($row['producto_nombre']) {
                    $nombreCompleto = $row['producto_nombre'];
                    if (!empty($row['atributos'])) {
                        $nombreCompleto .= ' - ' . $row['atributos'];
                    }
                    if ($row['sku_variante']) {
                        $nombreCompleto .= ' [' . $row['sku_variante'] . ']';
                    }
                    $row['producto_nombre'] = $nombreCompleto;
                }
                
                // Si no encuentra en inventario, buscar en articulos (compatibilidad con datos antiguos)
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
        error_log("Error en obtener_detalles_pedido: " . $e->getMessage());
        return [];
    }
}

// Obtener detalle específico por ID
function obtener_detalle($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, idped, cantidad, idart, fechacreate, fechamod, precio, total, terminado, detalle FROM pedidos_det WHERE id = ?");
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
        $stmt = $conn->prepare("INSERT INTO pedidos_det (idped, cantidad, idart, precio, total, terminado, detalle) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddis", 
            $datos['idped'], 
            $datos['cantidad'], 
            $datos['idart'], 
            $datos['precio'], 
            $datos['total'], 
            $datos['terminado'], 
            $datos['detalle']
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
        $stmt = $conn->prepare("UPDATE pedidos_det SET cantidad=?, idart=?, precio=?, total=?, terminado=?, detalle=?, fechamod=NOW() WHERE id=?");
        $stmt->bind_param("iiddisi", 
            $datos['cantidad'], 
            $datos['idart'], 
            $datos['precio'], 
            $datos['total'], 
            $datos['terminado'], 
            $datos['detalle'],
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
        $stmt = $conn->prepare("DELETE FROM pedidos_det WHERE id = ?");
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
            } elseif (isset($_GET['idped'])) {
                // Obtener todos los detalles de un pedido
                echo json_encode(obtener_detalles_pedido($_GET['idped']));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID o IDPED requerido']);
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
    error_log("Error general en API pedidos_det: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>