<?php
// api/clientes_direcciones.php
// API CRUD para múltiples direcciones de clientes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

function get_db_connection() {
    return get_database_connection();
}

// -------------------------------------------------------
// Obtener todas las direcciones de un cliente
// -------------------------------------------------------
function obtener_direcciones($cliente_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare(
        "SELECT id, cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal, fechacreate
         FROM clientes_direcciones
         WHERE cliente_id = ?
         ORDER BY principal DESC, id ASC"
    );
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['principal'] = (int)$row['principal'];
        $rows[] = $row;
    }
    $conn->close();
    return $rows;
}

// -------------------------------------------------------
// Obtener una dirección por ID
// -------------------------------------------------------
function obtener_direccion($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare(
        "SELECT id, cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal, fechacreate
         FROM clientes_direcciones WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $conn->close();
    if ($row) $row['principal'] = (int)$row['principal'];
    return $row;
}

// -------------------------------------------------------
// Crear nueva dirección
// -------------------------------------------------------
function crear_direccion($datos) {
    $conn = get_db_connection();

    $cliente_id  = (int)($datos['cliente_id'] ?? 0);
    $descripcion  = $datos['descripcion']  ?? 'Principal';
    $direccion    = $datos['direccion']    ?? '';
    $piso         = $datos['piso']         ?? '';
    $departamento = $datos['departamento'] ?? '';
    $cpostal      = $datos['cpostal']      ?? '';
    $ciudad       = $datos['ciudad']       ?? '';
    $provincia    = $datos['provincia']    ?? '';
    $principal    = isset($datos['principal']) ? (int)$datos['principal'] : 0;

    if (!$cliente_id) {
        $conn->close();
        return ['success' => false, 'message' => 'cliente_id requerido'];
    }

    // Si se marca como principal, quitar el flag de las demás
    if ($principal) {
        $upd = $conn->prepare("UPDATE clientes_direcciones SET principal=0 WHERE cliente_id=?");
        $upd->bind_param("i", $cliente_id);
        $upd->execute();
    }

    $stmt = $conn->prepare(
        "INSERT INTO clientes_direcciones (cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssssssi", $cliente_id, $descripcion, $direccion, $piso, $departamento, $cpostal, $ciudad, $provincia, $principal);
    $ok = $stmt->execute();
    $new_id = $conn->insert_id;
    $conn->close();
    return $ok
        ? ['success' => true, 'id' => $new_id, 'message' => 'Dirección creada']
        : ['success' => false, 'message' => 'Error al crear dirección'];
}

// -------------------------------------------------------
// Actualizar dirección
// -------------------------------------------------------
function actualizar_direccion($id, $datos) {
    $conn = get_db_connection();

    $descripcion  = $datos['descripcion']  ?? '';
    $direccion    = $datos['direccion']    ?? '';
    $piso         = $datos['piso']         ?? '';
    $departamento = $datos['departamento'] ?? '';
    $cpostal      = $datos['cpostal']      ?? '';
    $ciudad       = $datos['ciudad']       ?? '';
    $provincia    = $datos['provincia']    ?? '';
    $principal    = isset($datos['principal']) ? (int)$datos['principal'] : 0;

    // Necesitamos el cliente_id para quitar el flag en otras filas
    if ($principal) {
        $qcid = $conn->prepare("SELECT cliente_id FROM clientes_direcciones WHERE id=?");
        $qcid->bind_param("i", $id);
        $qcid->execute();
        $r = $qcid->get_result()->fetch_assoc();
        if ($r) {
            $upd = $conn->prepare("UPDATE clientes_direcciones SET principal=0 WHERE cliente_id=?");
            $upd->bind_param("i", $r['cliente_id']);
            $upd->execute();
        }
    }

    $stmt = $conn->prepare(
        "UPDATE clientes_direcciones
         SET descripcion=?, direccion=?, piso=?, departamento=?, cpostal=?, ciudad=?, provincia=?, principal=?
         WHERE id=?"
    );
    $stmt->bind_param("sssssssii", $descripcion, $direccion, $piso, $departamento, $cpostal, $ciudad, $provincia, $principal, $id);
    $ok = $stmt->execute();
    $conn->close();
    return $ok
        ? ['success' => true, 'message' => 'Dirección actualizada']
        : ['success' => false, 'message' => 'Error al actualizar dirección'];
}

// -------------------------------------------------------
// Eliminar dirección
// -------------------------------------------------------
function eliminar_direccion($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM clientes_direcciones WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $conn->close();
    return $ok
        ? ['success' => true, 'message' => 'Dirección eliminada']
        : ['success' => false, 'message' => 'Error al eliminar dirección'];
}

// -------------------------------------------------------
// Marcar una dirección como principal (shortcut via PATCH)
// -------------------------------------------------------
function marcar_principal($id) {
    $conn = get_db_connection();
    // Obtener cliente_id
    $q = $conn->prepare("SELECT cliente_id FROM clientes_direcciones WHERE id=?");
    $q->bind_param("i", $id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    if (!$r) {
        $conn->close();
        return ['success' => false, 'message' => 'Dirección no encontrada'];
    }
    $cliente_id = $r['cliente_id'];

    // Quitar principal a todas las del cliente
    $upd = $conn->prepare("UPDATE clientes_direcciones SET principal=0 WHERE cliente_id=?");
    $upd->bind_param("i", $cliente_id);
    $upd->execute();

    // Marcar la seleccionada
    $set = $conn->prepare("UPDATE clientes_direcciones SET principal=1 WHERE id=?");
    $set->bind_param("i", $id);
    $ok = $set->execute();
    $conn->close();
    return $ok
        ? ['success' => true, 'message' => 'Dirección principal actualizada']
        : ['success' => false, 'message' => 'Error al marcar dirección principal'];
}

// -------------------------------------------------------
// Router
// -------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $dir = obtener_direccion((int)$_GET['id']);
                if ($dir) {
                    echo json_encode($dir);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Dirección no encontrada']);
                }
            } elseif (isset($_GET['cliente_id'])) {
                echo json_encode(obtener_direcciones((int)$_GET['cliente_id']));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere id o cliente_id']);
            }
            break;

        case 'POST':
            // Acción especial: marcar principal
            if (isset($input['accion']) && $input['accion'] === 'marcar_principal' && isset($input['id'])) {
                echo json_encode(marcar_principal((int)$input['id']));
            } else {
                $res = crear_direccion($input);
                http_response_code($res['success'] ? 201 : 400);
                echo json_encode($res);
            }
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                echo json_encode(actualizar_direccion((int)$_GET['id'], $input));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                echo json_encode(eliminar_direccion((int)$_GET['id']));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Error en clientes_direcciones API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
