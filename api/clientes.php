<?php
// api/clientes.php
// API CRUD para clientes

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

// Obtener todos los clientes (incluye dirección principal si existe)
function obtener_clientes() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT c.id, c.apellido, c.nombre, c.telefono, c.email, c.condicioniva, c.cuit,
                       c.observaciones, c.fechacreate, c.saldoini, c.actualizaprecio,
                       c.recibirOfertas, c.recibirWhatsApp, c.newsletter, c.idioma,
                       COALESCE(dp.direccion, '') AS direccion,
                       COALESCE(dp.ciudad,    '') AS ciudad,
                       COALESCE(dp.provincia, '') AS provincia
                FROM clientes c
                LEFT JOIN clientes_direcciones dp
                       ON dp.cliente_id = c.id AND dp.principal = 1
                ORDER BY c.apellido, c.nombre";
        $result = $conn->query($sql);
        $clientes = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
        }
        $conn->close();
        return $clientes;
    } catch (Exception $e) {
        error_log("Error en obtener_clientes: " . $e->getMessage());
        return [];
    }
}

// Obtener cliente por ID (incluye sus direcciones)
function obtener_cliente($id) {
    try {
        $conn = get_db_connection();

        $stmt = $conn->prepare("SELECT id, apellido, nombre, direccion, ciudad, provincia, telefono, email, condicioniva, cuit, observaciones, fechacreate, saldoini, actualizaprecio, recibirOfertas, recibirWhatsApp, newsletter, idioma, token FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();

        if ($cliente) {
            // Si no tiene token, generar uno y guardarlo
            if (empty($cliente['token'])) {
                $nuevoToken = md5(uniqid($cliente['id'] . $cliente['email'], true));
                $stmtTok = $conn->prepare("UPDATE clientes SET token = ? WHERE id = ?");
                $stmtTok->bind_param("si", $nuevoToken, $id);
                $stmtTok->execute();
                $stmtTok->close();
                $cliente['token'] = $nuevoToken;
            }

            // Cargar múltiples direcciones
            $stmt2 = $conn->prepare(
                "SELECT id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal
                 FROM clientes_direcciones
                 WHERE cliente_id = ?
                 ORDER BY principal DESC, id ASC"
            );
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $dirs = [];
            while ($row = $res2->fetch_assoc()) {
                $row['principal'] = (int)$row['principal'];
                $dirs[] = $row;
            }
            $cliente['direcciones'] = $dirs;
        }

        $conn->close();
        return $cliente;
    } catch (Exception $e) {
        error_log("Error en obtener_cliente: " . $e->getMessage());
        // Intentar devolver solo el cliente sin direcciones si la tabla falla
        try {
            $conn2 = get_db_connection();
            $stmt3 = $conn2->prepare("SELECT id, apellido, nombre, direccion, ciudad, provincia, telefono, email, condicioniva, cuit, observaciones, fechacreate, saldoini, actualizaprecio, recibirOfertas, recibirWhatsApp, newsletter, idioma, token FROM clientes WHERE id = ?");
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
            $cliente2 = $stmt3->get_result()->fetch_assoc();
            $conn2->close();
            if ($cliente2) {
                if (empty($cliente2['token'])) {
                    try {
                        $conn3 = get_db_connection();
                        $nuevoToken2 = md5(uniqid($cliente2['id'] . $cliente2['email'], true));
                        $stmtTok2 = $conn3->prepare("UPDATE clientes SET token = ? WHERE id = ?");
                        $stmtTok2->bind_param("si", $nuevoToken2, $id);
                        $stmtTok2->execute();
                        $stmtTok2->close();
                        $conn3->close();
                        $cliente2['token'] = $nuevoToken2;
                    } catch (Exception $e3) {}
                }
                $cliente2['direcciones'] = [];
                return $cliente2;
            }
        } catch (Exception $e2) {}
        return null;
    }
}

// Crear nuevo cliente
function crear_cliente($datos) {
    $conn = get_db_connection();
    $apellido       = $datos['apellido']       ?? '';
    $nombre         = $datos['nombre']         ?? '';
    $telefono       = $datos['telefono']       ?? '';
    $email          = $datos['email']          ?? '';
    $condicioniva   = isset($datos['condicioniva']) ? (int)$datos['condicioniva'] : 0;
    $cuit           = $datos['cuit']           ?? '';
    $observaciones  = $datos['observaciones']  ?? '';
    $saldoini       = isset($datos['saldoini']) ? (float)$datos['saldoini'] : 0.00;
    $actualizaprecio  = isset($datos['actualizaprecio'])  ? (int)$datos['actualizaprecio']  : 1;
    $recibirOfertas   = isset($datos['recibirOfertas'])   ? (int)$datos['recibirOfertas']   : 0;
    $recibirWhatsApp  = isset($datos['recibirWhatsApp'])  ? (int)$datos['recibirWhatsApp']  : 0;
    $newsletter       = isset($datos['newsletter'])       ? (int)$datos['newsletter']       : 0;
    $idioma           = $datos['idioma'] ?? 'es';
    // Mantener columnas legacy vacías; las direcciones van en clientes_direcciones
    $direccion = '';
    $ciudad    = '';
    $provincia = '';
    $stmt = $conn->prepare("INSERT INTO clientes (apellido, nombre, direccion, ciudad, provincia, telefono, email, condicioniva, cuit, observaciones, saldoini, actualizaprecio, recibirOfertas, recibirWhatsApp, newsletter, idioma) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssissdiiiis",
        $apellido, $nombre, $direccion, $ciudad, $provincia,
        $telefono, $email, $condicioniva, $cuit, $observaciones,
        $saldoini, $actualizaprecio, $recibirOfertas, $recibirWhatsApp,
        $newsletter, $idioma
    );
    $success = $stmt->execute();
    $id = $conn->insert_id;
    $conn->close();
    return $success ? $id : false;
}

// Actualizar cliente
function actualizar_cliente($id, $datos) {
    $conn = get_db_connection();
    $apellido       = $datos['apellido']       ?? '';
    $nombre         = $datos['nombre']         ?? '';
    $telefono       = $datos['telefono']       ?? '';
    $email          = $datos['email']          ?? '';
    $condicioniva   = isset($datos['condicioniva']) ? (int)$datos['condicioniva'] : 0;
    $cuit           = $datos['cuit']           ?? '';
    $observaciones  = $datos['observaciones']  ?? '';
    $saldoini       = isset($datos['saldoini']) ? (float)$datos['saldoini'] : 0.00;
    $actualizaprecio  = isset($datos['actualizaprecio'])  ? (int)$datos['actualizaprecio']  : 1;
    $recibirOfertas   = isset($datos['recibirOfertas'])   ? (int)$datos['recibirOfertas']   : 0;
    $recibirWhatsApp  = isset($datos['recibirWhatsApp'])  ? (int)$datos['recibirWhatsApp']  : 0;
    $newsletter       = isset($datos['newsletter'])       ? (int)$datos['newsletter']       : 0;
    $idioma           = $datos['idioma'] ?? 'es';
    $stmt = $conn->prepare("UPDATE clientes SET apellido=?, nombre=?, telefono=?, email=?, condicioniva=?, cuit=?, observaciones=?, saldoini=?, actualizaprecio=?, recibirOfertas=?, recibirWhatsApp=?, newsletter=?, idioma=? WHERE id=?");
    $stmt->bind_param("ssssissdiiiisi",
        $apellido, $nombre, $telefono, $email, $condicioniva,
        $cuit, $observaciones, $saldoini, $actualizaprecio,
        $recibirOfertas, $recibirWhatsApp, $newsletter, $idioma, $id
    );
    $success = $stmt->execute();
    $conn->close();
    return $success;
}

// Eliminar cliente
function eliminar_cliente($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
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
                $cliente = obtener_cliente($_GET['id']);
                if ($cliente) {
                    echo json_encode($cliente);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Cliente no encontrado']);
                }
            } else {
                $clientes = obtener_clientes();
                echo json_encode($clientes);
            }
            break;

    case 'POST':
        if ($input) {
            $id = crear_cliente($input);
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Cliente creado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear cliente']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
        }
        break;

    case 'PUT':
        if (isset($_GET['id']) && $input) {
            $success = actualizar_cliente($_GET['id'], $input);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar cliente']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID y datos requeridos']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $success = eliminar_cliente($_GET['id']);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar cliente']);
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
