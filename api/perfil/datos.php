<?php
// api/perfil/datos.php
// Devuelve los datos completos del perfil de un cliente autenticado por token

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// --- Obtener token desde Authorization Bearer, POST, GET o JSON body ---
$token = null;

// 1) Authorization: Bearer token (prioritario)
//    Múltiples fuentes: getallheaders(), $_SERVER (para CGI/FastCGI/nginx)
$authHeader = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}
if (empty($authHeader)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
             ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
             ?? $_SERVER['HTTP_X_AUTHORIZATION']
             ?? '';
}
if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
}

// 2) POST form-urlencoded
if (!$token && !empty($_POST['token'])) {
    $token = $_POST['token'];
}

// 3) JSON body
if (!$token) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');
    if (!empty($raw) && stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['token'])) {
            $token = $decoded['token'];
        }
    }
}

// 4) GET parameter
if (!$token && !empty($_GET['token'])) {
    $token = $_GET['token'];
}

if (!$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token requerido']);
    exit;
}

try {
    $conn = get_database_connection();

    // Buscar cliente por token
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        $conn->close();
        exit;
    }

    $clienteId = (int)$cliente['id'];

    // --- Estadísticas de pedidos ---
    $stats = [
        'totalPedidos'  => 0,
        'pedidosEnCurso' => 0,
        'totalGastado'  => 0.0
    ];

    $stmtTotal = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(total), 0) AS gastado FROM pedidos WHERE idcli = ?");
    $stmtTotal->bind_param("i", $clienteId);
    $stmtTotal->execute();
    $rTotal = $stmtTotal->get_result()->fetch_assoc();
    $stats['totalPedidos'] = (int)$rTotal['total'];
    $stats['totalGastado'] = round((float)$rTotal['gastado'], 2);
    $stmtTotal->close();

    // Pedidos en curso (estado != cerrado)
    $stmtCurso = $conn->prepare("SELECT COUNT(*) AS enCurso FROM pedidos WHERE idcli = ? AND cerrado = 0");
    $stmtCurso->bind_param("i", $clienteId);
    $stmtCurso->execute();
    $rCurso = $stmtCurso->get_result()->fetch_assoc();
    $stats['pedidosEnCurso'] = (int)$rCurso['enCurso'];
    $stmtCurso->close();

    // --- Direcciones ---
    $direcciones = [];
    $stmtDir = $conn->prepare(
        "SELECT id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, principal, indicaciones
         FROM clientes_direcciones
         WHERE cliente_id = ?
         ORDER BY principal DESC, id ASC"
    );
    $stmtDir->bind_param("i", $clienteId);
    $stmtDir->execute();
    $rDir = $stmtDir->get_result();
    while ($row = $rDir->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['principal'] = (int)$row['principal'];
        $direcciones[] = $row;
    }
    $stmtDir->close();

    // --- Armar respuesta ---
    $data = [
        'id'                => $clienteId,
        'email'             => $cliente['email'] ?? '',
        'nombre'            => $cliente['nombre'] ?? '',
        'apellido'          => $cliente['apellido'] ?? '',
        'telefono'          => $cliente['telefono'] ?? '',
        'avatar'            => '',
        'fechaRegistro'     => $cliente['fechacreate'] ?? null,
        'fechaUltimoAcceso' => $cliente['modificado'] ?? null,
        'emailVerificado'   => !empty($cliente['fechavalidar']),
        'estadisticas'      => $stats,
        'direcciones'       => $direcciones,
        'preferencias'      => [
            'recibirOfertas'  => (bool)($cliente['recibirOfertas'] ?? false),
            'recibirWhatsApp' => (bool)($cliente['recibirWhatsApp'] ?? false),
            'newsletter'      => (bool)($cliente['newsletter'] ?? false),
            'idioma'          => $cliente['idioma'] ?? 'es'
        ]
    ];

    $conn->close();

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
