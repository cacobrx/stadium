<?php
// api/proveedores.php
// API CRUD para proveedores

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Función de compatibilidad
function get_db_connection() {
    return get_database_connection();
}

// Obtener todos los proveedores
function obtener_proveedores() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT id, razonsocial, contacto, direccion, telefono, ciudad, cuit, condicioniva FROM proveedores ORDER BY razonsocial, contacto";
        $result = $conn->query($sql);
        $proveedores = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $proveedores[] = $row;
            }
        }
        $conn->close();
        return $proveedores;
    } catch (Exception $e) {
        error_log("Error en obtener_proveedores: " . $e->getMessage());
        return [];
    }
}

// Obtener proveedor por ID
function obtener_proveedor($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, razonsocial, contacto, direccion, telefono, ciudad, cuit, condicioniva FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $proveedor = $result->fetch_assoc();
    $conn->close();
    return $proveedor;
}

// Crear nuevo proveedor
function crear_proveedor($datos) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("INSERT INTO proveedores (razonsocial, contacto, direccion, telefono, ciudad, cuit, condicioniva) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $condicioniva = isset($datos['condicioniva']) ? (int)$datos['condicioniva'] : 1;
    $stmt->bind_param("ssssssi", 
        $datos['razonsocial'], 
        $datos['contacto'], 
        $datos['direccion'], 
        $datos['telefono'], 
        $datos['ciudad'], 
        $datos['cuit'],
        $condicioniva
    );
    $success = $stmt->execute();
    $id = $conn->insert_id;
    $conn->close();
    return $success ? $id : false;
}

// Actualizar proveedor
function actualizar_proveedor($id, $datos) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("UPDATE proveedores SET razonsocial=?, contacto=?, direccion=?, telefono=?, ciudad=?, cuit=?, condicioniva=? WHERE id=?");
    $condicioniva = isset($datos['condicioniva']) ? (int)$datos['condicioniva'] : 1;
    $stmt->bind_param("ssssssii", 
        $datos['razonsocial'], 
        $datos['contacto'], 
        $datos['direccion'], 
        $datos['telefono'], 
        $datos['ciudad'], 
        $datos['cuit'],
        $condicioniva,
        $id
    );
    $success = $stmt->execute();
    $conn->close();
    return $success;
}

// Eliminar proveedor
function eliminar_proveedor($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $conn->close();
    return $success;
}

// Manejo de peticiones HTTP
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $proveedor = obtener_proveedor($_GET['id']);
                if ($proveedor) {
                    echo json_encode($proveedor);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Proveedor no encontrado']);
                }
            } else {
                $proveedores = obtener_proveedores();
                echo json_encode($proveedores);
            }
            break;

        case 'POST':
            if ($input) {
                $id = crear_proveedor($input);
                if ($id) {
                    http_response_code(201);
                    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Proveedor creado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al crear proveedor']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id']) && $input) {
                $success = actualizar_proveedor($_GET['id'], $input);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Proveedor actualizado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar proveedor']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID y datos requeridos']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $success = eliminar_proveedor($_GET['id']);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Proveedor eliminado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar proveedor']);
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
    error_log("Error general en API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}