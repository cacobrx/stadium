<?php
// api/pedidos/tracking.php
// Devuelve todos los datos de un pedido + detalle de items
// Parámetro: GET id=6 (o POST/JSON)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// --- Base URL de imágenes ---
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost', [
    'localhost', '127.0.0.1', '::1'
]) || php_sapi_name() === 'cli';

$BASE_IMG = $isLocal
    ? 'http://localhost/stadium/'
    : 'https://stadium.planbsistemas.com.ar/';

// --- Obtener id del pedido ---
$id = null;
if (!empty($_GET['id']))  $id = (int)$_GET['id'];
if (!$id && !empty($_POST['id'])) $id = (int)$_POST['id'];
if (!$id) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $body = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($body['id'])) {
            $id = (int)$body['id'];
        }
    }
}

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetro id requerido (ej: ?id=6)']);
    exit;
}

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

    // 1) Datos del pedido + cliente
    $stmt = $conn->prepare(
        "SELECT p.*,
                c.nombre     AS cli_nombre,
                c.apellido   AS cli_apellido,
                c.email      AS cli_email,
                c.telefono   AS cli_telefono
         FROM pedidos p
         LEFT JOIN clientes c ON c.id = p.idcli
         WHERE p.id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ped = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ped) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Pedido #$id no encontrado"]);
        exit;
    }

    // 2) Detalle de items
    $stmtDet = $conn->prepare(
        "SELECT pd.id,
                pd.cantidad,
                pd.idart,
                pd.precio,
                pd.total,
                pd.detalle,
                pd.terminado,
                pd.fechacreate    AS det_fechacreate,
                p.nombre          AS prod_nombre,
                p.sku             AS prod_sku,
                p.descripcion     AS prod_descripcion,
                (SELECT pi.url
                 FROM producto_imagenes pi
                 WHERE pi.producto_id = p.id AND pi.es_principal = 1
                 ORDER BY pi.orden ASC LIMIT 1) AS imagen_url
         FROM pedidos_det pd
         LEFT JOIN productos p ON p.id = pd.idart
         WHERE pd.idped = ?
         ORDER BY pd.id ASC"
    );
    $stmtDet->bind_param("i", $id);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    $stmtDet->close();

    // 3) Pagos del pedido
    $stmtPag = $conn->prepare(
        "SELECT id,
                token,
                monto,
                estado,
                mp_payment_id,
                mp_status,
                mp_status_detail,
                mp_payment_type,
                mp_external_ref,
                fechacreate   AS pago_fecha
         FROM pagos
         WHERE idped = ?
         ORDER BY fechacreate ASC"
    );
    $stmtPag->bind_param("i", $id);
    $stmtPag->execute();
    $resPag = $stmtPag->get_result();
    $stmtPag->close();

    $pagos = [];
    while ($prow = $resPag->fetch_assoc()) {
        $pagos[] = [
            'id'              => (int)$prow['id'],
            'token'           => $prow['token'],
            'monto'           => round((float)$prow['monto'], 2),
            'estado'          => $prow['estado'],
            'mpPaymentId'     => $prow['mp_payment_id'],
            'mpStatus'        => $prow['mp_status'],
            'mpStatusDetail'  => $prow['mp_status_detail'],
            'mpPaymentType'   => $prow['mp_payment_type'],
            'mpExternalRef'   => $prow['mp_external_ref'],
            'fecha'           => $prow['pago_fecha'],
        ];
    }

    $items = [];
    while ($row = $resDet->fetch_assoc()) {
        $imgUrl = !empty($row['imagen_url']) ? $BASE_IMG . $row['imagen_url'] : null;
        $items[] = [
            'id'          => (int)$row['id'],
            'idart'       => (int)$row['idart'],
            'nombre'      => $row['prod_nombre'] ?? $row['detalle'] ?? 'Producto',
            'sku'         => $row['prod_sku'] ?? null,
            'descripcion' => $row['prod_descripcion'] ?? null,
            'cantidad'    => (int)$row['cantidad'],
            'precio'      => round((float)$row['precio'], 2),
            'total'       => round((float)$row['total'], 2),
            'imagen'      => $imgUrl,
            'terminado'   => (bool)$row['terminado'],
            'fechacreate' => $row['det_fechacreate'],
        ];
    }

    $conn->close();

    // 4) Estado legible
    $estadoInt = (int)$ped['estado'];
    $cerrado   = (int)$ped['cerrado'];
    $estadoStr = ($cerrado === 1 && $estadoInt === 0)
        ? 'entregado'
        : ($estadoMap[$estadoInt] ?? 'pendiente');

    // 5) Preferencias de checkout (JSON dentro del campo)
    $prefCheckout = null;
    if (!empty($ped['preferencias_checkout'])) {
        $dec = json_decode($ped['preferencias_checkout'], true);
        $prefCheckout = (json_last_error() === JSON_ERROR_NONE) ? $dec : $ped['preferencias_checkout'];
    }

    // 6) Armar respuesta
    $response = [
        'success' => true,
        'data' => [
            'pedido' => [
                'id'                  => (int)$ped['id'],
                'numeroPedido'        => 'STD-' . date('Y', strtotime($ped['fechacreate'])) . '-' . str_pad($ped['id'], 5, '0', STR_PAD_LEFT),
                'fecha'               => $ped['fecha'],
                'fechaCreacion'       => date('c', strtotime($ped['fechacreate'])),
                'fechaModificacion'   => $ped['fechamod'] ? date('c', strtotime($ped['fechamod'])) : null,
                'fechaEntrega'        => $ped['fechaentrega'] ?? null,
                'fechaReparto'        => $ped['fechareparto'] ?? null,
                'fechaPago'           => $ped['fechapago'] ? date('c', strtotime($ped['fechapago'])) : null,
                'estado'              => $estadoStr,
                'estadoCodigo'        => $estadoInt,
                'cerrado'             => (bool)$cerrado,
                'subtotal'            => round((float)$ped['subtotal'], 2),
                'envio'               => round((float)$ped['envio'], 2),
                'total'               => round((float)$ped['total'], 2),
                'modoPago'            => $ped['modo_pago'] ?? null,
                'tipoEnvio'           => $ped['tipo_envio'] ?? null,
                'nombreEnvio'         => $ped['nombre_envio'] ?? null,
                'nroRemito'           => (int)$ped['nroremito'] ?: null,
                'observaciones'       => $ped['observaciones'] ?? null,
                'observacionesReparto'=> $ped['observarep'] ?? null,
                'bultos'              => (int)$ped['bultos'],
                'tipo'                => (int)$ped['tipo'],
                'retiro'              => (bool)$ped['retiro'],
                'preferencesCheckout' => $prefCheckout,
            ],
            'cliente' => [
                'id'       => (int)$ped['idcli'],
                'nombre'   => $ped['cli_nombre'] ?? '',
                'apellido' => $ped['cli_apellido'] ?? '',
                'email'    => $ped['cli_email'] ?? '',
                'telefono' => $ped['cli_telefono'] ?? '',
            ],
            'items'         => $items,
            'cantidadItems' => count($items),
            'pagos'         => $pagos,
            'cantidadPagos' => count($pagos),
            'totalPagado'   => round(array_sum(array_column(
                                  array_filter($pagos, fn($p) => $p['estado'] === 'approved'),
                                  'monto'
                               )), 2),
        ],
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
