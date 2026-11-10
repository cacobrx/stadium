<?php
// api/compras.php
// API REST para gestión de compras con transacciones

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

// Obtener todas las compras
function obtener_compras() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT 
                    c.id, 
                    c.fecha, 
                    c.proveedor, 
                    c.importe,
                    COUNT(cd.id) as cantidad_articulos
                FROM compras c 
                LEFT JOIN compras_det cd ON c.id = cd.idcom 
                GROUP BY c.id, c.fecha, c.proveedor, c.importe 
                ORDER BY c.fecha DESC, c.id DESC";
        $result = $conn->query($sql);
        $compras = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $compras[] = $row;
            }
        }
        $conn->close();
        return $compras;
    } catch (Exception $e) {
        error_log("Error en obtener_compras: " . $e->getMessage());
        return [];
    }
}

// Obtener compra por ID
function obtener_compra($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, fecha, proveedor, importe FROM compras WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $compra = $result->fetch_assoc();
        $conn->close();
        return $compra;
    } catch (Exception $e) {
        error_log("Error en obtener_compra: " . $e->getMessage());
        return null;
    }
}

// Crear nueva compra
function crear_compra($datos) {
    $conn = null;
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();
        
        // Validar datos obligatorios
        if (!isset($datos['fecha']) || !isset($datos['proveedor'])) {
            throw new Exception("Faltan datos obligatorios: fecha y proveedor");
        }
        
        // Crear la compra (el campo articulo es legacy, se setea en 0)
        $stmt = $conn->prepare("INSERT INTO compras (fecha, proveedor, articulo, importe) VALUES (?, ?, 0, ?)");
        
        $stmt->bind_param("sid", 
            $datos['fecha'], 
            $datos['proveedor'], 
            $datos['importe']
        );
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error;
            error_log("Error MySQL en crear_compra: " . $error);
            throw new Exception("Error al crear compra: " . $error);
        }
        
        $compra_id = $conn->insert_id;
        error_log("Compra creada con ID: " . $compra_id);
        
        // Crear los detalles de la compra si hay productos
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            $stmt_det = $conn->prepare("INSERT INTO compras_det (idcom, cantidad, idart, precio, total) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($datos['productos'] as $index => $producto) {
                error_log("Procesando producto " . $index . ": " . json_encode($producto));
                
                // Validar datos del producto
                if (!isset($producto['idart']) || !isset($producto['cantidad']) || !isset($producto['precio'])) {
                    throw new Exception("Faltan datos en el producto " . $index);
                }
                
                $stmt_det->bind_param("iiidd",
                    $compra_id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total']
                );
                
                if (!$stmt_det->execute()) {
                    $error = $stmt_det->error;
                    error_log("Error al crear detalle de compra: " . $error);
                    throw new Exception("Error al crear detalle de la compra: " . $error);
                }

                // Sumar al stock de la variante
                $stmt_stock = $conn->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?");
                $stmt_stock->bind_param("ii", $producto['cantidad'], $producto['idart']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al actualizar stock de variante id=" . $producto['idart']);
                }
            }
        }
        
        $conn->commit();
        $conn->close();
        error_log("Compra creada exitosamente con ID: " . $compra_id);
        return $compra_id;
    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
            $conn->close();
        }
        error_log("Error en crear_compra: " . $e->getMessage());
        error_log("Datos recibidos: " . json_encode($datos));
        return false;
    }
}

// Actualizar compra
function actualizar_compra($id, $datos) {
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();
        
        // Actualizar la compra (el campo articulo se mantiene en 0)
        $stmt = $conn->prepare("UPDATE compras SET fecha=?, proveedor=?, articulo=0, importe=? WHERE id=?");
        
        $stmt->bind_param("sidi", 
            $datos['fecha'], 
            $datos['proveedor'], 
            $datos['importe'],
            $id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            throw new Exception("Error al actualizar compra");
        }
        
        // Si hay productos, actualizar los detalles
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            // Obtener detalles anteriores para descontar su stock
            $stmt_old = $conn->prepare("SELECT idart, cantidad FROM compras_det WHERE idcom = ?");
            $stmt_old->bind_param("i", $id);
            $stmt_old->execute();
            $old_result = $stmt_old->get_result();
            $stmt_revert = $conn->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?");
            while ($old = $old_result->fetch_assoc()) {
                $stmt_revert->bind_param("ii", $old['cantidad'], $old['idart']);
                $stmt_revert->execute();
            }

            // Eliminar detalles existentes
            $stmt_del = $conn->prepare("DELETE FROM compras_det WHERE idcom = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();
            
            // Insertar nuevos detalles y sumar stock
            $stmt_det   = $conn->prepare("INSERT INTO compras_det (idcom, cantidad, idart, precio, total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?");
            
            foreach ($datos['productos'] as $producto) {
                $stmt_det->bind_param("iiidd",
                    $id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total']
                );
                
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al actualizar detalle de la compra");
                }

                // Sumar al stock de la variante
                $stmt_stock->bind_param("ii", $producto['cantidad'], $producto['idart']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al actualizar stock de variante id=" . $producto['idart']);
                }
            }
        }
        
        $conn->commit();
        $conn->close();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        error_log("Error en actualizar_compra: " . $e->getMessage());
        return false;
    }
}

// Eliminar compra
function eliminar_compra($id) {
    try {
        $conn = get_db_connection();
        $conn->begin_transaction();

        // Descontar el stock de las variantes antes de borrar los detalles
        $stmt_old = $conn->prepare("SELECT idart, cantidad FROM compras_det WHERE idcom = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old_result = $stmt_old->get_result();
        $stmt_revert = $conn->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?");
        while ($old = $old_result->fetch_assoc()) {
            $stmt_revert->bind_param("ii", $old['cantidad'], $old['idart']);
            $stmt_revert->execute();
        }

        // Eliminar los detalles
        $stmt = $conn->prepare("DELETE FROM compras_det WHERE idcom = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Eliminar la compra
        $stmt = $conn->prepare("DELETE FROM compras WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();

        $conn->commit();
        $conn->close();
        return $success;
    } catch (Exception $e) {
        if (isset($conn)) { $conn->rollback(); $conn->close(); }
        error_log("Error en eliminar_compra: " . $e->getMessage());
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
                $compra = obtener_compra($_GET['id']);
                if ($compra) {
                    echo json_encode($compra);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Compra no encontrada']);
                }
            } else {
                echo json_encode(obtener_compras());
            }
            break;

        case 'POST':
            if ($input) {
                $id = crear_compra($input);
                if ($id) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Compra creada exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al crear compra']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id']) && $input) {
                $success = actualizar_compra($_GET['id'], $input);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Compra actualizada exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar compra']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID y datos requeridos']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $success = eliminar_compra($_GET['id']);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Compra eliminada exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar compra']);
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
    error_log("Error general en API compras: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>