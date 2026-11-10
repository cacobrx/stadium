<?php
// api/pedidos/lista.php
// Lista paginada de pedidos del cliente autenticado por Bearer token

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// --- Base URL de imágenes según entorno ---
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost', [
    'localhost', '127.0.0.1', '::1'
]) || php_sapi_name() === 'cli';

$BASE_IMG = $isLocal
    ? 'http://localhost/stadium/'
    : 'https://stadium.planbsistemas.com.ar/';

// --- Obtener token (prioritario: Authorization Bearer) ---
$token = null;

$authHeader = '';
if (function_exists('getallheaders')) {
    $h = getallheaders();
    $authHeader = $h['Authorization'] ?? $h['authorization'] ?? '';
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

if (!$token && !empty($_POST['token']))  $token = $_POST['token'];
if (!$token && !empty($_GET['token']))   $token = $_GET['token'];
if (!$token) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $body = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($body['token'])) {
            $token = $body['token'];
        }
    }
}

if (!$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token requerido (Authorization: Bearer <token>)']);
    exit;
}

// --- Parámetros de paginación ---
$page  = max(1, (int)($_GET['page']  ?? $_POST['page']  ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? $_POST['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

// --- Mapa de estados ---
$estadoMap = [
    0 => 'pendiente',
    1 => 'confirmado',
    2 => 'preparando',
    3 => 'enviado',
    4 => 'entregado',
    5 => 'cancelado',
];

try {
    $conn = get_database_connection();

    // 1) Buscar cliente por token
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Token inválido o cliente no encontrado']);
        exit;
    }
    $clienteId = (int)$row['id'];

    // 2) Total de pedidos del cliente
    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE idcli = ?");
    $stmtCount->bind_param("i", $clienteId);
    $stmtCount->execute();
    $totalItems = (int)$stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $limit) : 1;

    // 3) Pedidos paginados
    $stmtPed = $conn->prepare(
        "SELECT id, fechacreate, estado, total, cerrado
         FROM pedidos
         WHERE idcli = ?
         ORDER BY fechacreate DESC, id DESC
         LIMIT ? OFFSET ?"
    );
    $stmtPed->bind_param("iii", $clienteId, $limit, $offset);
    $stmtPed->execute();
    $resPed = $stmtPed->get_result();
    $stmtPed->close();

    $pedidos = [];

    while ($ped = $resPed->fetch_assoc()) {
        $pedId = (int)$ped['id'];

        // 4) Cantidad de items del pedido
        $stmtCant = $conn->prepare("SELECT COUNT(*) AS cant, SUM(cantidad) AS total_uds FROM pedidos_det WHERE idped = ?");
        $stmtCant->bind_param("i", $pedId);
        $stmtCant->execute();
        $resCant = $stmtCant->get_result()->fetch_assoc();
        $stmtCant->close();
        $cantidadItems = (int)$resCant['total_uds'];

        // 5) Preview de productos (hasta 3): nombre + imagen principal
        $stmtPrev = $conn->prepare(
            "SELECT p.nombre,
                    (SELECT pi.url FROM producto_imagenes pi
                     WHERE pi.producto_id = p.id AND pi.es_principal = 1
                     ORDER BY pi.orden ASC LIMIT 1) AS imagen_url
             FROM pedidos_det pd
             LEFT JOIN productos p ON p.id = pd.idart
             WHERE pd.idped = ?
             GROUP BY pd.idart, p.nombre
             LIMIT 3"
        );
        $stmtPrev->bind_param("i", $pedId);
        $stmtPrev->execute();
        $resPrev = $stmtPrev->get_result();
        $stmtPrev->close();

        $productosPreview = [];
        $imagenPrincipal  = '';

        while ($pv = $resPrev->fetch_assoc()) {
            $imgUrl = !empty($pv['imagen_url'])
                ? $BASE_IMG . $pv['imagen_url']
                : '';

            if (empty($imagenPrincipal) && $imgUrl) {
                $imagenPrincipal = $imgUrl;
            }

            $productosPreview[] = [
                'nombre' => $pv['nombre'] ?? 'Producto',
                'imagen' => $imgUrl
            ];
        }

        // 6) Estado legible
        $estadoInt = (int)$ped['estado'];
        if ((int)$ped['cerrado'] === 1 && $estadoInt === 0) {
            $estadoStr = 'entregado';
        } else {
            $estadoStr = $estadoMap[$estadoInt] ?? 'pendiente';
        }

        $pedidos[] = [
            'id'              => $pedId,
            'numeroPedido'    => 'STD-' . date('Y', strtotime($ped['fechacreate'])) . '-' . str_pad($pedId, 5, '0', STR_PAD_LEFT),
            'fechaCreacion'   => date('c', strtotime($ped['fechacreate'])),
            'estado'          => $estadoStr,
            'total'           => round((float)$ped['total'], 2),
            'cantidadItems'   => $cantidadItems,
            'imagenPrincipal' => $imagenPrincipal,
            'productosPreview'=> $productosPreview,
        ];
    }

    $conn->close();

    echo json_encode([
        'success'    => true,
        'data'       => $pedidos,
        'pagination' => [
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems,
            'itemsPerPage' => $limit,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
