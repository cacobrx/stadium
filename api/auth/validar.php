<?php
// api/auth/validar.php
// Valida el código enviado por el cliente:
// - Que el código coincida con el almacenado en clientes.codigo
// - Que no haya pasado más de 1 hora desde clientes.fechavalidar

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

// Capturar errores fatales que PHP no puede atrapar con try/catch
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $resp = ['success' => false, 'message' => 'Error interno del servidor'];
        if (($_GET['_dbg'] ?? '') === 'stadium') {
            $resp['debug'] = $err['message'] . ' en ' . $err['file'] . ':' . $err['line'];
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        error_log('[auth/validar] Fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']);
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

require_once __DIR__ . '/../../config/database.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ─── Solo POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Leer JSON ─────────────────────────────────────────────────────────────────
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$email  = trim($data['email']  ?? '');
$codigo = trim($data['codigo'] ?? '');

// ─── Validaciones básicas ───────────────────────────────────────────────────────
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido o no proporcionado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($codigo) || !ctype_digit($codigo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Código inválido o no proporcionado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Buscar cliente ─────────────────────────────────────────────────────────────
try {
    $conn = get_database_connection();

    $stmt = $conn->prepare("
        SELECT id, nombre, apellido, email, codigo, fechavalidar, token
        FROM clientes
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No existe ningún cliente con ese email'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Verificar que existe un código generado ────────────────────────────────
    if (empty($cliente['fechavalidar']) || (int)$cliente['codigo'] === 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No hay un código generado para este email'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Verificar vigencia (menos de 1 hora) ───────────────────────────────────
    $ahora        = new DateTime();
    $fechaGen     = new DateTime($cliente['fechavalidar']);
    $segundos     = $ahora->getTimestamp() - $fechaGen->getTimestamp();

    if ($segundos >= 3600) {
        $minutos = (int) floor($segundos / 60);
        http_response_code(410);
        echo json_encode([
            'success' => false,
            'message' => "El código expiró (generado hace {$minutos} min). Solicitá uno nuevo."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Verificar código ───────────────────────────────────────────────────────
    if ((int)$codigo !== (int)$cliente['codigo']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Código incorrecto'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Token: generar y guardar si es null ────────────────────────────────────
    $token = $cliente['token'];
    if (empty($token)) {
        $token = md5(uniqid((string)$cliente['id'], true));
        $stmtTok = $conn->prepare("UPDATE clientes SET token = ? WHERE id = ?");
        $stmtTok->bind_param('si', $token, $cliente['id']);
        $stmtTok->execute();
        $stmtTok->close();
    }

    // ─── Cargar datos completos del cliente ─────────────────────────────────────
    $stmtCli = $conn->prepare("
        SELECT id, token, apellido, nombre, email, cuit, telefono, fechacreate
        FROM clientes WHERE id = ? LIMIT 1
    ");
    $stmtCli->bind_param('i', $cliente['id']);
    $stmtCli->execute();
    $datosCliente = $stmtCli->get_result()->fetch_assoc();
    $stmtCli->close();

    // ─── Cargar direcciones del cliente ─────────────────────────────────────────
    $stmtDir = $conn->prepare("
        SELECT id, descripcion, direccion, ciudad, provincia, principal, cpostal
        FROM clientes_direcciones
        WHERE cliente_id = ?
        ORDER BY principal DESC, id ASC
    ");
    $stmtDir->bind_param('i', $cliente['id']);
    $stmtDir->execute();
    $resDir = $stmtDir->get_result();
    $direcciones = [];
    while ($row = $resDir->fetch_assoc()) {
        $row['principal'] = (int)$row['principal'];
        $direcciones[] = $row;
    }
    $stmtDir->close();

    echo json_encode([
        'success' => true,
        'message' => 'Código válido. Identidad verificada.',
        'data'    => [
            'cliente'    => $datosCliente,
            'direcciones'=> $direcciones,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[auth/validar] Error: ' . $e->getMessage());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Error interno del servidor'];
    if (($_GET['_dbg'] ?? '') === 'stadium') {
        $resp['debug'] = $e->getMessage();
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}
