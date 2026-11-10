<?php
// api/direcciones/crear.php
// Crea una nueva dirección para el cliente autenticado por Bearer token

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
        error_log('[direcciones/crear] Fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']);
    }
    ob_end_flush();
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// ─── Extraer Bearer token ──────────────────────────────────────────────────────
$token = null;

$authHeader = '';
if (function_exists('getallheaders')) {
    $hdrs = getallheaders();
    $authHeader = $hdrs['Authorization'] ?? $hdrs['authorization'] ?? '';
}
if (empty($authHeader)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
             ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
             ?? $_SERVER['HTTP_X_AUTHORIZATION']
             ?? '';
}
if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
    $token = $m[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Leer JSON del body ────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Validación de campos obligatorios ────────────────────────────────────────
$direccion = trim($data['direccion'] ?? '');
$ciudad    = trim($data['ciudad']    ?? '');
$provincia = trim($data['provincia'] ?? '');

if ($direccion === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El campo direccion es obligatorio'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($ciudad === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El campo ciudad es obligatorio'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($provincia === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El campo provincia es obligatorio'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Campos opcionales ─────────────────────────────────────────────────────────
$descripcion  = trim($data['descripcion']  ?? 'Principal');
$piso         = trim($data['piso']         ?? '');
$departamento = trim($data['departamento'] ?? '');
$cpostal      = trim($data['cpostal']      ?? '');
$indicaciones = trim($data['indicaciones'] ?? '');
$principal    = isset($data['principal']) ? (int)$data['principal'] : 0;

try {
    $conn = get_database_connection();

    // ─── Verificar token y obtener cliente ─────────────────────────────────────
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cliente) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    $cliente_id = (int)$cliente['id'];

    // ─── Si se marca como principal, quitar el flag en las demás ──────────────
    if ($principal) {
        $upd = $conn->prepare("UPDATE clientes_direcciones SET principal = 0 WHERE cliente_id = ?");
        $upd->bind_param("i", $cliente_id);
        $upd->execute();
        $upd->close();
    }

    // ─── Insertar nueva dirección ──────────────────────────────────────────────
    $stmt = $conn->prepare(
        "INSERT INTO clientes_direcciones
            (cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, indicaciones, principal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "issssssssi",
        $cliente_id,
        $descripcion,
        $direccion,
        $piso,
        $departamento,
        $cpostal,
        $ciudad,
        $provincia,
        $indicaciones,
        $principal
    );
    $ok     = $stmt->execute();
    $new_id = $conn->insert_id;
    $stmt->close();
    $conn->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar la dirección'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Dirección creada correctamente',
        'id'      => $new_id,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('[direcciones/crear] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}
