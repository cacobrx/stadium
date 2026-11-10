<?php
// api/direcciones/detele.php
// Elimina una dirección del cliente autenticado por Bearer token

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
        error_log('[direcciones/detele] Fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']);
    }
    ob_end_flush();
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
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

$id = (int)($data['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El campo id es obligatorio'], JSON_UNESCAPED_UNICODE);
    exit;
}

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

    // ─── Verificar que la dirección pertenece al cliente ──────────────────────
    $chk = $conn->prepare("SELECT id, principal FROM clientes_direcciones WHERE id = ? AND cliente_id = ? LIMIT 1");
    $chk->bind_param("ii", $id, $cliente_id);
    $chk->execute();
    $dir = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$dir) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Dirección no encontrada'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // ─── Eliminar dirección ────────────────────────────────────────────────────
    $del = $conn->prepare("DELETE FROM clientes_direcciones WHERE id = ? AND cliente_id = ?");
    $del->bind_param("ii", $id, $cliente_id);
    $ok = $del->execute();
    $del->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la dirección'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    // ─── Si era la principal, promover la más antigua restante ────────────────
    if ((int)$dir['principal'] === 1) {
        $promo = $conn->prepare(
            "UPDATE clientes_direcciones SET principal = 1 WHERE cliente_id = ? ORDER BY id ASC LIMIT 1"
        );
        $promo->bind_param("i", $cliente_id);
        $promo->execute();
        $promo->close();
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Dirección eliminada correctamente',
        'id'      => $id,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('[direcciones/detele] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}
