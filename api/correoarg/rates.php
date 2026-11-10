<?php
/*
 * api/correoarg/rates.php
 * Endpoint interno: recibe JSON y devuelve las cotizaciones de Correo Argentino.
 *
 * Método: POST
 * Content-Type: application/json
 *
 * Body esperado:
 * {
 *   "customerId":            "0000550137",
 *   "postalCodeOrigin":      "1757",
 *   "postalCodeDestination": "6720",
 *   "deliveredType":         "D|S"   (opcional; si se omite devuelve ambas)
 *   "produccion":            0|1     (opcional; 0 = test, 1 = producción)
 *   "dimensions": {
 *     "weight": 2500,   (gramos, 1–25000)
 *     "height": 50,     (cm, máx 150)
 *     "width":  80,     (cm, máx 150)
 *     "length": 50      (cm, máx 150)
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// Pre-flight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST.']);
    exit;
}

require_once __DIR__ . '/token.php';
require_once __DIR__ . '/token_manager.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/correoargentino.php';

// ─── Leer y validar JSON de entrada ──────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido o vacío.']);
    exit;
}

// Aplicar defaults del config si no vienen en el request
$input['customerId']       = $input['customerId']       ?? CA_CUSTOMER_ID;
$input['postalCodeOrigin'] = $input['postalCodeOrigin'] ?? CA_CP_ORIGEN;

$required = ['customerId', 'postalCodeOrigin', 'postalCodeDestination'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Campo requerido faltante: '$field'."]);
        exit;
    }
}

// ─── Dimensiones: directas o calculadas desde items ───────────────────────────
if (!empty($input['dimensions'])) {
    // Vinieron dimensions explícitas — validarlas
    foreach (['weight', 'height', 'width', 'length'] as $d) {
        if (!isset($input['dimensions'][$d])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido faltante en dimensions: '$d'."]);
            exit;
        }
    }
    $dim          = $input['dimensions'];
    $dim_items    = [];
    $dim_warnings = [];

} elseif (!empty($input['items']) && is_array($input['items'])) {
    // Calcular dimensiones desde los items consultando la BD
    $db = get_database_connection();

    function _buscar_variantes_rates(mysqli $db, array $claves, string $col, string $type): array {
        if (empty($claves)) return [];
        $ph   = implode(',', array_fill(0, count($claves), '?'));
        $stmt = $db->prepare(
            "SELECT p.id, p.sku, p.nombre, p.peso, p.alto, p.ancho, p.profundidad,
                    pv.id AS variante_id, pv.sku_variante
               FROM producto_variantes pv
               JOIN productos p ON p.id = pv.producto_id
              WHERE pv.$col IN ($ph)"
        );
        $stmt->bind_param(str_repeat($type, count($claves)), ...$claves);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $mapa = [];
        foreach ($rows as $row) {
            $row['fuente'] = 'variante';
            $mapa[$col === 'sku_variante' ? $row['sku_variante'] : $row['variante_id']] = $row;
        }
        return $mapa;
    }

    $use_sku = false;
    $items_v = [];
    foreach ($input['items'] as $i => $item) {
        $tiene_sku = !empty($item['producto_sku']);
        $tiene_id  = !empty($item['producto_id']);
        if (!$tiene_sku && !$tiene_id) {
            http_response_code(400);
            echo json_encode(['error' => "Item $i: se requiere 'producto_sku' o 'producto_id'."]);
            exit;
        }
        if ($tiene_sku) $use_sku = true;
        $items_v[] = [
            'producto_id'  => $tiene_id  ? (int)$item['producto_id']  : null,
            'producto_sku' => $tiene_sku ? trim($item['producto_sku']) : null,
            'cantidad'     => max(1, (int)($item['cantidad'] ?? 1)),
        ];
    }

    $productos = [];
    if ($use_sku) {
        $skus = array_unique(array_filter(array_column($items_v, 'producto_sku')));
        $ph   = implode(',', array_fill(0, count($skus), '?'));
        $stmt = $db->prepare("SELECT id, sku, nombre, peso, alto, ancho, profundidad FROM productos WHERE sku IN ($ph)");
        $stmt->bind_param(str_repeat('s', count($skus)), ...$skus);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $row['fuente'] = 'producto';
            $productos[$row['sku']] = $row;
        }
        $stmt->close();
        $faltantes = array_values(array_diff($skus, array_keys($productos)));
        if ($faltantes) $productos += _buscar_variantes_rates($db, $faltantes, 'sku_variante', 's');
        foreach ($items_v as &$it) {
            if ($it['producto_sku'] && isset($productos[$it['producto_sku']]))
                $it['producto_id'] = $productos[$it['producto_sku']]['id'];
        }
        unset($it);
    } else {
        $ids  = array_unique(array_filter(array_column($items_v, 'producto_id')));
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id, sku, nombre, peso, alto, ancho, profundidad FROM productos WHERE id IN ($ph)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $row['fuente'] = 'producto';
            $productos[$row['id']] = $row;
        }
        $stmt->close();
        $faltantes = array_values(array_diff($ids, array_keys($productos)));
        if ($faltantes) {
            foreach (_buscar_variantes_rates($db, $faltantes, 'id', 'i') as $vid => $row)
                $productos[$vid] = $row;
        }
    }

    $total_weight = 0; $max_height = 0; $max_width = 0; $total_length = 0;
    $dim_items = []; $dim_warnings = [];
    foreach ($items_v as $item) {
        $clave = $use_sku ? $item['producto_sku'] : $item['producto_id'];
        if (!isset($productos[$clave])) {
            $dim_warnings[] = ($use_sku ? "SKU '$clave'" : "ID $clave") . ': no encontrado en BD.';
            continue;
        }
        $p    = $productos[$clave];
        $qty  = $item['cantidad'];
        $peso = (float)($p['peso'] ?? 0);
        $alto = (float)($p['alto'] ?? 0);
        $ancho= (float)($p['ancho'] ?? 0);
        $prof = (float)($p['profundidad'] ?? 0);
        $peso_g = $peso * 1000;
        $total_weight += $peso_g * $qty;
        $max_height    = max($max_height, $alto);
        $max_width     = max($max_width,  $ancho);
        $total_length += $prof * $qty;
        $dim_items[] = ['sku' => $p['sku'], 'nombre' => $p['nombre'], 'cantidad' => $qty,
                        'peso_g' => round($peso_g * $qty, 2)];
    }

    $dim = [
        'weight' => min(25000, (int)round($total_weight)),
        'height' => min(150,   (int)ceil($max_height)),
        'width'  => min(150,   (int)ceil($max_width)),
        'length' => min(150,   (int)ceil($total_length)),
    ];

} else {
    http_response_code(400);
    echo json_encode(['error' => "Se requiere 'dimensions' o 'items'."]);
    exit;
}

// ─── Parámetros ───────────────────────────────────────────────────────────────
$produccion = isset($input['produccion']) ? (int)$input['produccion'] : 0;

$body_arr = [
    'customerId'            => (string)$input['customerId'],
    'postalCodeOrigin'      => (string)$input['postalCodeOrigin'],
    'postalCodeDestination' => (string)$input['postalCodeDestination'],
    'dimensions'            => [
        'weight' => (int)$dim['weight'],
        'height' => (int)$dim['height'],
        'width'  => (int)$dim['width'],
        'length' => (int)$dim['length'],
    ],
];

if (!empty($input['deliveredType'])) {
    $body_arr['deliveredType'] = strtoupper($input['deliveredType']);
}

// ─── Token ────────────────────────────────────────────────────────────────────
try {
    $token_info = getTokenVigente($produccion);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['error' => 'No se pudo obtener token: ' . $e->getMessage()]);
    exit;
}

if (empty($token_info['token'])) {
    http_response_code(503);
    echo json_encode(['error' => 'Token vacío. Verificar credenciales de Correo Argentino.']);
    exit;
}

// ─── Llamada a /rates ─────────────────────────────────────────────────────────
$base_url = $produccion ? CA_URL_PRODUCCION : CA_URL_TEST;
$endpoint = rtrim($base_url, '/') . '/rates';
$body     = json_encode($body_arr, JSON_UNESCAPED_UNICODE);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'POST',
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

if ($curl_err) {
    http_response_code(502);
    echo json_encode(['error' => 'Error de conexión: ' . $curl_err]);
    exit;
}

// ─── Respuesta ────────────────────────────────────────────────────────────────
$decoded = json_decode($response);

$respuesta = [];
// Si se calcularon dimensiones desde items, incluirlas en la respuesta
if (!empty($dim_items)) {
    $respuesta['dimensiones']  = $dim;
    $respuesta['items']        = $dim_items;
    $respuesta['advertencias'] = $dim_warnings ?? [];
}

if ($decoded !== null) {
    foreach ((array)$decoded as $k => $v) $respuesta[$k] = $v;
} else {
    $respuesta['raw'] = $response;
}

http_response_code($http_code ?: 502);
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
