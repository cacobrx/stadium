<?php
// API para gestión de inventario de variantes de productos
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticación para API (devuelve JSON)
requireAuthAPI();

// Obtener conexión a la base de datos
$mysqli = get_database_connection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener un registro específico de inventario
                $id = intval($_GET['id']);
                $stmt = $mysqli->prepare("
                    SELECT 
                        pv.id,
                        pv.producto_id,
                        pv.sku_variante,
                        pv.stock,
                        pv.precio,
                        pv.activa,
                        p.nombre as producto_nombre,
                        p.sku as producto_sku,
                        p.precio as precio_producto,
                        p.descripcion as producto_descripcion,
                        m.nombre as marca_nombre,
                        pi.url as imagen_producto,
                        vi.url as imagen_variante
                    FROM producto_variantes pv
                    INNER JOIN productos p ON pv.producto_id = p.id
                    LEFT JOIN marcas m ON p.marca_id = m.id
                    LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
                    LEFT JOIN variante_imagenes vi ON pv.id = vi.variante_id AND vi.es_principal = 1
                    WHERE pv.id = ?
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Obtener atributos de la variante
                    $stmt_attr = $mysqli->prepare("
                        SELECT nombre, valor 
                        FROM variante_atributos 
                        WHERE variante_id = ?
                        ORDER BY nombre
                    ");
                    $stmt_attr->bind_param('i', $id);
                    $stmt_attr->execute();
                    $atributos_result = $stmt_attr->get_result();
                    $row['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
                    
                    echo json_encode(['success' => true, 'inventario' => $row]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
                }
            } else {
                // Obtener lista completa de inventario con filtros opcionales
                $where = [];
                $params = [];
                $types = '';
                
                // Filtro por búsqueda
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $where[] = "(p.nombre LIKE ? OR p.sku LIKE ? OR pv.sku_variante LIKE ? OR pv.nombre LIKE ?)";
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search;
                    $types .= 'ssss';
                }
                
                // Filtro por producto
                if (isset($_GET['producto_id']) && !empty($_GET['producto_id'])) {
                    $where[] = "pv.producto_id = ?";
                    $params[] = intval($_GET['producto_id']);
                    $types .= 'i';
                }
                
                // Filtro por stock bajo
                if (isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1') {
                    $where[] = "pv.stock < 10";
                }
                
                // Filtro por activa
                if (isset($_GET['activa']) && $_GET['activa'] !== '') {
                    $where[] = "pv.activa = ?";
                    $params[] = intval($_GET['activa']);
                    $types .= 'i';
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                $query = "
                    SELECT 
                        pv.id,
                        pv.producto_id,
                        pv.sku_variante,
                        pv.stock,
                        pv.precio,
                        pv.activa,
                        pv.fecha_actualizacion,
                        p.nombre as producto_nombre,
                        p.sku as producto_sku,
                        p.precio as precio_producto,
                        m.nombre as marca_nombre,
                        pi.url as imagen_producto,
                        vi.url as imagen_variante
                    FROM producto_variantes pv
                    INNER JOIN productos p ON pv.producto_id = p.id
                    LEFT JOIN marcas m ON p.marca_id = m.id
                    LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
                    LEFT JOIN variante_imagenes vi ON pv.id = vi.variante_id AND vi.es_principal = 1
                    $whereClause
                    ORDER BY p.nombre, pv.sku_variante
                ";
                
                if (!empty($params)) {
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $mysqli->query($query);
                }
                
                $inventario = $result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener atributos de cada variante
                foreach ($inventario as &$item) {
                    $stmt = $mysqli->prepare("
                        SELECT nombre, valor 
                        FROM variante_atributos 
                        WHERE variante_id = ?
                        ORDER BY nombre
                    ");
                    $stmt->bind_param('i', $item['id']);
                    $stmt->execute();
                    $atributos_result = $stmt->get_result();
                    $item['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
                }
                
                echo json_encode(['success' => true, 'inventario' => $inventario]);
            }
            break;
            
        case 'POST':
            // Crear nuevo registro de inventario (nueva variante)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['producto_id']) || empty($data['sku_variante'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos (producto_id, sku_variante)']);
                exit;
            }
            
            // Verificar que el SKU no exista
            $stmt = $mysqli->prepare("SELECT id FROM producto_variantes WHERE sku_variante = ?");
            $stmt->bind_param('s', $data['sku_variante']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'El SKU de variante ya existe']);
                exit;
            }
            
            $stock = intval($data['stock'] ?? 0);
            $precio = !empty($data['precio']) ? floatval($data['precio']) : null;
            $activa = isset($data['activa']) ? intval($data['activa']) : 1;
            
            $stmt = $mysqli->prepare("
                INSERT INTO producto_variantes 
                (producto_id, sku_variante, stock, precio, activa) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                'isidi',
                $data['producto_id'],
                $data['sku_variante'],
                $stock,
                $precio,
                $activa
            );
            
            if ($stmt->execute()) {
                $newId = $mysqli->insert_id;
                
                // Insertar atributos si existen
                if (isset($data['atributos']) && is_array($data['atributos'])) {
                    $stmt_attr = $mysqli->prepare("
                        INSERT INTO variante_atributos (variante_id, nombre, valor) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($data['atributos'] as $attr) {
                        if (!empty($attr['nombre']) && !empty($attr['valor'])) {
                            $stmt_attr->bind_param('iss', $newId, $attr['nombre'], $attr['valor']);
                            $stmt_attr->execute();
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Variante creada exitosamente',
                    'id' => $newId
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            break;
            
        case 'PUT':
            // Actualizar registro de inventario
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
                exit;
            }
            
            $id = intval($data['id']);
            
            // Verificar que el registro existe
            $stmt = $mysqli->prepare("SELECT id FROM producto_variantes WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows == 0) {
                echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
                exit;
            }
            
            // Si se actualiza el SKU, verificar que no exista otro con el mismo
            if (isset($data['sku_variante'])) {
                $stmt = $mysqli->prepare("SELECT id FROM producto_variantes WHERE sku_variante = ? AND id != ?");
                $stmt->bind_param('si', $data['sku_variante'], $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    echo json_encode(['success' => false, 'error' => 'El SKU de variante ya existe']);
                    exit;
                }
            }
            
            $updates = [];
            $params = [];
            $types = '';
            
            if (isset($data['sku_variante'])) {
                $updates[] = 'sku_variante = ?';
                $params[] = $data['sku_variante'];
                $types .= 's';
            }
            
            if (isset($data['stock'])) {
                $updates[] = 'stock = ?';
                $params[] = intval($data['stock']);
                $types .= 'i';
            }
            
            if (isset($data['precio'])) {
                $updates[] = 'precio = ?';
                $params[] = $data['precio'] !== null ? floatval($data['precio']) : null;
                $types .= 'd';
            }
            
            if (isset($data['activa'])) {
                $updates[] = 'activa = ?';
                $params[] = intval($data['activa']);
                $types .= 'i';
            }
            
            // Actualizar producto_variantes
            if (!empty($updates)) {
                $params[] = $id;
                $types .= 'i';
                
                $query = "UPDATE producto_variantes SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if (!$stmt->execute()) {
                    echo json_encode(['success' => false, 'error' => $stmt->error]);
                    exit;
                }
            }
            
            // Actualizar atributos si se enviaron
            if (isset($data['atributos'])) {
                // Eliminar atributos existentes
                $mysqli->query("DELETE FROM variante_atributos WHERE variante_id = $id");
                
                // Insertar nuevos atributos
                if (is_array($data['atributos']) && count($data['atributos']) > 0) {
                    $stmt_attr = $mysqli->prepare("
                        INSERT INTO variante_atributos (variante_id, nombre, valor) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($data['atributos'] as $attr) {
                        if (!empty($attr['nombre']) && !empty($attr['valor'])) {
                            $stmt_attr->bind_param('iss', $id, $attr['nombre'], $attr['valor']);
                            $stmt_attr->execute();
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Inventario actualizado exitosamente']);
            break;
            
        case 'DELETE':
            // Eliminar registro de inventario
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
                exit;
            }
            
            $id = intval($data['id']);
            
            $stmt = $mysqli->prepare("DELETE FROM producto_variantes WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
