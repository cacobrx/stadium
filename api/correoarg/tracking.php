<?php
/*
 * api/correoarg/tracking.php
 * Consulta el seguimiento de un envío en Correo Argentino.
 *
 * Método: GET o POST  |  Content-Type: application/json (solo POST)
 *
 * GET  (query string):
 *   ?pedido_id=123           — busca tracking_id en tabla pedidos
 *   ?id=123                  — alias de pedido_id
 *   ?shippingId=XX&produccion=1  — directo
 *
 * POST (body JSON):
 * {
 *   "pedido_id": 123,      // busca tracking_id en la tabla pedidos
 *   "produccion": 0
 * }
 *
 * Body (opción B — directo):
 * {
 *   "shippingId": "1627673L5832C9C501",
 *   "produccion": 0
 * }
 *
 * Si el pedido no tiene tracking_id asignado, devuelve 422.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Capturar errores fatales y devolverlos como JSON en lugar de 500 en blanco
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error'   => 'Error interno del servidor.',
            'detalle' => $err['message'] . ' en ' . $err['file'] . ':' . $err['line'],
        ]);
    }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use GET o POST.']);
    exit;
}

require_once __DIR__ . '/token.php';
require_once __DIR__ . '/token_manager.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/correoargentino.php';

// ─── Log ──────────────────────────────────────────────────────────────────────
$log_dir  = __DIR__ . '/../../log';
$log_file = $log_dir . '/tracking.log';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

function tlog(string $msg): void {
    global $log_file;
    file_put_contents($log_file,
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n",
        FILE_APPEND | LOCK_EX
    );
}

// ─── Leer y validar entrada ───────────────────────────────────────────────────
// GET: parámetros por query string (?pedido_id=123 o ?shippingId=XXX&produccion=1)
// POST: parámetros en el body JSON
$raw_input = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input     = json_decode($raw_input, true);

    $log_entry = '[' . date('Y-m-d H:i:s') . '] '
               . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' POST' . "\n"
               . (json_decode($raw_input) !== null
                   ? json_encode(json_decode($raw_input), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                   : $raw_input)
               . "\n" . str_repeat('-', 60) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido o vacío.']);
        exit;
    }
} else {
    // GET: tomar parámetros del query string
    $input = [
        'pedido_id'  => $_GET['pedido_id']  ?? $_GET['id']         ?? null,
        'shippingId' => $_GET['shippingId'] ?? $_GET['shipping_id'] ?? null,
        'produccion' => $_GET['produccion'] ?? null,
    ];
    // Limpiar nulls y strings vacíos (compatible PHP 7.3+)
    $input = array_filter($input, function($v) { return $v !== null && $v !== ''; });

    $log_entry = '[' . date('Y-m-d H:i:s') . '] '
               . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' GET '
               . ($_SERVER['QUERY_STRING'] ?? '') . "\n"
               . str_repeat('-', 60) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

if (empty($input['shippingId']) && empty($input['pedido_id'])) {
    http_response_code(400);
    echo json_encode(['error' => "Se requiere 'shippingId' o 'pedido_id'."]);
    exit;
}

$produccion = isset($input['produccion']) ? (int)$input['produccion'] : 1;
$pedidoId   = !empty($input['pedido_id']) ? (int)$input['pedido_id'] : null;

// ─── Resolver shippingId desde pedido (si viene pedido_id) ───────────────────
try {
if ($pedidoId !== null) {
    $db = get_database_connection();
    $stmt = $db->prepare("SELECT idtracking FROM pedidos WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $pedidoId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // No cerrar $db: es un singleton compartido con token_manager.php

    if (!$row) {
        tlog("ERROR: Pedido #$pedidoId no encontrado en BD");
        http_response_code(404);
        echo json_encode(['error' => "Pedido #$pedidoId no encontrado."]);
        exit;
    }

    if (empty($row['idtracking'])) {
        tlog("INFO: Pedido #$pedidoId no tiene tracking asignado");
        http_response_code(422);
        echo json_encode([
            'error'     => 'El pedido no tiene un ID de tracking asignado.',
            'pedido_id' => $pedidoId,
            'hint'      => 'Asigná el tracking en la edición del pedido antes de consultar el seguimiento.',
        ]);
        exit;
    }

    $shippingId = trim($row['idtracking']);
    tlog("Pedido #$pedidoId → idtracking=$shippingId");
} else {
    $shippingId = trim((string)$input['shippingId']);
}

// ─── Obtener token ────────────────────────────────────────────────────────────
try {
    $token_info = getTokenVigente($produccion);
} catch (Throwable $e) {
    tlog("ERROR token: " . $e->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'No se pudo obtener token: ' . $e->getMessage()]);
    exit;
}

if (empty($token_info['token'])) {
    tlog("ERROR: token vacío para produccion=$produccion");
    http_response_code(503);
    echo json_encode(['error' => 'Token vacío. Verificar credenciales de Correo Argentino.']);
    exit;
}

tlog("Token obtenido (nuevo=" . ($token_info['nuevo'] ? 'sí' : 'no') . ")  shippingId=$shippingId  produccion=$produccion");

// ─── Llamada a GET /shipping/tracking ────────────────────────────────────────
$base_url = $produccion ? CA_URL_PRODUCCION : CA_URL_TEST;
$endpoint = rtrim($base_url, '/') . '/shipping/tracking';
$body     = json_encode(['shippingId' => $shippingId]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token_info['token'],
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);

$response  = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($curl);
curl_close($curl);

tlog("CA /shipping/tracking → HTTP $http_code  body=" . mb_substr($response, 0, 500));

if ($curl_err) {
    tlog("ERROR curl: $curl_err");
    http_response_code(502);
    echo json_encode(['error' => 'Error de conexión con Correo Argentino: ' . $curl_err]);
    exit;
}

// ─── Respuesta ────────────────────────────────────────────────────────────────
$decoded = json_decode($response, true);

$base = ['shippingId' => $shippingId];
if ($pedidoId !== null) {
    $base['pedido_id'] = $pedidoId;
}

if ($decoded !== null) {
    $respuesta = array_merge($base, $decoded);
} else {
    $respuesta = array_merge($base, ['raw' => $response]);
}

http_response_code($http_code ?: 502);
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    $msg = $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine();
    error_log('[tracking.php] ' . $msg);
    if (isset($log_file)) {
        file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] EXCEPCION: ' . $msg . "\n", FILE_APPEND | LOCK_EX);
    }
    if (!headers_sent()) http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
