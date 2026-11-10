<?php
// api/pedidos.php
// API REST para gestión de pedidos con transacciones

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

// Obtener todos los pedidos
function obtener_pedidos() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT id, fecha, idcli, observaciones, ubicacion, nroremito, fechacreate, fechamod, estado, bultos, idrep, total, subtotal, envio, orden, fechareparto, tipo, retiro, observarep, usuario, cerrado, actualizacion, fechaentrega, fechapago, idtracking AS tracking_id FROM pedidos ORDER BY fecha DESC, id DESC";
        $result = $conn->query($sql);
        $pedidos = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $pedidos[] = $row;
            }
        }
        $conn->close();
        return $pedidos;
    } catch (Exception $e) {
        error_log("Error en obtener_pedidos: " . $e->getMessage());
        return [];
    }
}

// Obtener pedido por ID
function obtener_pedido($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, fecha, idcli, observaciones, ubicacion, nroremito, fechacreate, fechamod, estado, bultos, idrep, total, subtotal, envio, orden, fechareparto, tipo, retiro, observarep, usuario, cerrado, actualizacion, fechaentrega, fechapago, idtracking AS tracking_id FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pedido = $result->fetch_assoc();
        $conn->close();
        return $pedido;
    } catch (Exception $e) {
        error_log("Error en obtener_pedido: " . $e->getMessage());
        return null;
    }
}

// Crear nuevo pedido
function crear_pedido($datos) {
    try {
            $conn = get_db_connection();
            // Compatibilidad: si la instalación de mysqli no expone begin_transaction, usar autocommit(false)
            if (method_exists($conn, 'begin_transaction')) {
                $conn->begin_transaction();
            } else {
                // Fallback: desactivar autocommit para simular transacción
                $conn->autocommit(false);
            }
        
        // Crear el pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (fecha, idcli, observaciones, ubicacion, nroremito, estado, bultos, total, subtotal, envio, retiro, fechaentrega, usuario, idtracking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Obtener usuario actual
        $usuario_actual = 1;
        
        // Validar campos para evitar errores de tipo
        $nroremito = $datos['nroremito'] ? intval($datos['nroremito']) : 0;
        $fechaentrega = $datos['fechaentrega'] ?: null;
        $tracking_id = isset($datos['tracking_id']) && $datos['tracking_id'] !== '' ? $datos['tracking_id'] : null;
        $subtotalVal = isset($datos['subtotal']) ? floatval($datos['subtotal']) : floatval($datos['total']);
        $envioVal = isset($datos['envio']) ? floatval($datos['envio']) : 0.00;
        
        $stmt->bind_param("sisssiiddsssi", 
            $datos['fecha'], 
            $datos['idcli'], 
            $datos['observaciones'], 
            $datos['ubicacion'], 
            $nroremito, 
            $datos['estado'], 
            $datos['bultos'], 
            $datos['total'],
            $subtotalVal,
            $envioVal, 
            $datos['retiro'],
            $fechaentrega,
            $usuario_actual,
            $tracking_id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error;
            error_log("Error MySQL en crear_pedido: " . $error);
            throw new Exception("Error al crear pedido: " . $error);
        }
        
        $pedido_id = $conn->insert_id;
        
        // Crear los detalles del pedido si hay productos
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            $stmt_det = $conn->prepare("INSERT INTO pedidos_det (idped, cantidad, idart, precio, total, terminado, detalle) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($datos['productos'] as $producto) {
                $terminado = 0; // terminado = false por defecto
                $stmt_det->bind_param("iiiddis",
                    $pedido_id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total'],
                    $terminado,
                    $producto['detalle']
                );
                
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al crear detalle del pedido");
                }
            }
        }
        
        // Commit (compatibilidad con instalaciones sin begin_transaction)
        if (method_exists($conn, 'commit')) {
            $conn->commit();
        } else {
            // Restaurar autocommit
            $conn->autocommit(true);
        }
        $conn->close();
        return $pedido_id;
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            if (method_exists($conn, 'rollback')) {
                $conn->rollback();
            } else {
                try { $conn->rollback(); } catch (Exception $ex) { /* ignore */ }
                $conn->autocommit(true);
            }
            $conn->close();
        }
        error_log("Error en crear_pedido: " . $e->getMessage());
        return false;
    }
}

// Actualizar pedido
function actualizar_pedido($id, $datos) {
    try {
        $conn = get_db_connection();
        if (method_exists($conn, 'begin_transaction')) {
            $conn->begin_transaction();
        } else {
            $conn->autocommit(false);
        }
        
        // Actualizar el pedido
        $stmt = $conn->prepare("UPDATE pedidos SET fecha=?, idcli=?, observaciones=?, ubicacion=?, nroremito=?, estado=?, bultos=?, total=?, subtotal=?, envio=?, retiro=?, fechaentrega=?, idtracking=?, fechamod=NOW() WHERE id=?");
        
        // Validar campos para evitar errores de tipo
        $nroremito = $datos['nroremito'] ? intval($datos['nroremito']) : 0;
        $fechaentrega = $datos['fechaentrega'] ?: null;
        $tracking_id = isset($datos['tracking_id']) && $datos['tracking_id'] !== '' ? $datos['tracking_id'] : null;
        $subtotalVal = isset($datos['subtotal']) ? floatval($datos['subtotal']) : floatval($datos['total']);
        $envioVal = isset($datos['envio']) ? floatval($datos['envio']) : 0.00;
        
        $stmt->bind_param("sisssiiddsssii", 
            $datos['fecha'], 
            $datos['idcli'], 
            $datos['observaciones'], 
            $datos['ubicacion'], 
            $nroremito, 
            $datos['estado'], 
            $datos['bultos'], 
            $datos['total'], 
            $subtotalVal,
            $envioVal,
            $datos['retiro'],
            $fechaentrega,
            $tracking_id,
            $id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            throw new Exception("Error al actualizar pedido");
        }
        
        // Si hay productos, actualizar los detalles
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            // Eliminar detalles existentes
            $stmt_del = $conn->prepare("DELETE FROM pedidos_det WHERE idped = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();
            
            // Insertar nuevos detalles
            $stmt_det = $conn->prepare("INSERT INTO pedidos_det (idped, cantidad, idart, precio, total, terminado, detalle) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($datos['productos'] as $producto) {
                $terminado = isset($producto['id']) && isset($producto['terminado']) ? $producto['terminado'] : 0;
                
                $stmt_det->bind_param("iiiddis",
                    $id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total'],
                    $terminado,
                    $producto['detalle']
                );
                
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al actualizar detalle del pedido");
                }
            }
        }
        
        if (method_exists($conn, 'commit')) {
            $conn->commit();
        } else {
            $conn->autocommit(true);
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            if (method_exists($conn, 'rollback')) {
                $conn->rollback();
            } else {
                try { $conn->rollback(); } catch (Exception $ex) { /* ignore */ }
                $conn->autocommit(true);
            }
            $conn->close();
        }
        error_log("Error en actualizar_pedido: " . $e->getMessage());
        return false;
    }
}

// Eliminar pedido
function eliminar_pedido($id) {
    try {
        $conn = get_db_connection();
        
        // Primero eliminar los detalles
        $stmt = $conn->prepare("DELETE FROM pedidos_det WHERE idped = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Luego eliminar el pedido
        $stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        $conn->close();
        return $success;
    } catch (Exception $e) {
        error_log("Error en eliminar_pedido: " . $e->getMessage());
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
                $pedido = obtener_pedido($_GET['id']);
                if ($pedido) {
                    echo json_encode($pedido);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Pedido no encontrado']);
                }
            } else {
                echo json_encode(obtener_pedidos());
            }
            break;

        case 'POST':
            if ($input) {
                $id = crear_pedido($input);
                if ($id) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Pedido creado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al crear pedido']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id']) && $input) {
                $success = actualizar_pedido($_GET['id'], $input);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Pedido actualizado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar pedido']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID y datos requeridos']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $success = eliminar_pedido($_GET['id']);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Pedido eliminado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar pedido']);
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
    error_log("Error general en API pedidos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>