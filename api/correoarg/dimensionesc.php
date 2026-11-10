<?php
/*
 * api/correoarg/dimensiones.php
 * Calcula las dimensiones consolidadas de un carrito para cotizar con /rates.
 *
 * Método: POST  |  Content-Type: application/json
 *
 * Body:
 * {
 *   "items": [
 *     { "producto_sku": "STMOL-2079", "cantidad": 1 },
 *     { "producto_sku": "STPRO-629",  "cantidad": 5 }
 *     // también acepta producto_id en lugar de producto_sku
 *   ],
 *
 *   // Opcionales: si se envían, también llama a rates.php y devuelve cotización
 *   "customerId":            "0000550137",
 *   "postalCodeOrigin":      "1757",
 *   "postalCodeDestination": "6720",
 *   "deliveredType":         "D",   // D | S  (omitir = ambas)
 *   "produccion":            0      // 0 = test, 1 = producción
 * }
 *
 * Estrategia de empaque (caja única):
 *   weight  = SUM(peso   × cantidad)   [gramos, int, máx 25000]
 *   height  = MAX(alto)                [cm, int, máx 150]
 *   width   = MAX(ancho)               [cm, int, máx 150]
 *   length  = SUM(profundidad × cant.) [cm, int, máx 150]
 *
 * Respuesta 200:
 * {
 *   "dimensiones": { "weight":7500, "height":30, "width":40, "length":50 },
 *   "items":       [ { "producto_id":1, "nombre":"...", "cantidad":2, ... } ],
 *   "advertencias": [],
 *   "rates":       { ... }   // solo si se enviaron postalCodes
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../config/database.php';

// ─── Entrada (acepta cualquier método HTTP) ───────────────────────────────────
// Prioridad: 1) body JSON (php://input)  2) parámetro ?json=  3) parámetros sueltos
$input = json_decode('{
    "items": [
        {
            "producto_sku": "Red-Lite-B-BASE",
            "cantidad": 1
        }
    ],
    "produccion": 1,
    "customerId": "0000550137",
    "postalCodeOrigin": "1757",
    "postalCodeDestination": "1663"
}', true);

if (!is_array($input) || empty($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron datos. Enviar JSON en el body, en ?json={...} o parámetros sueltos.']);
    exit;
}

if (empty($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere el campo "items" con al menos un producto.']);
    exit;
}

// Detectar si se pasó el objeto completo como valor de "items" (error común)
if (array_keys($input['items']) !== array_keys(array_values($input['items']))) {
    http_response_code(400);
    echo json_encode([
        'error' => 'El campo "items" debe ser un array de productos, no un objeto. '
                 . 'Si querés enviar el JSON completo, envialo en el body directamente (sin envolverlo en {"items":...}).'
    ]);
    exit;
}

// ─── Validar y normalizar items ───────────────────────────────────────────────
$items_validos = [];
$use_sku       = false;   // detectar si el carrito usa sku o id

foreach ($input['items'] as $i => $item) {
    $tiene_sku = !empty($item['producto_sku']);
    $tiene_id  = !empty($item['producto_id']);

    if (!$tiene_sku && !$tiene_id) {
        http_response_code(400);
        echo json_encode(['error' => "Item $i: se requiere 'producto_sku' o 'producto_id'."]);
        exit;
    }
    if (!isset($item['cantidad'])) {
        http_response_code(400);
        echo json_encode(['error' => "Item $i: falta el campo 'cantidad'."]);
        exit;
    }

    if ($tiene_sku) $use_sku = true;

    $items_validos[] = [
        'producto_id'  => $tiene_id  ? (int)$item['producto_id']       : null,
        'producto_sku' => $tiene_sku ? trim($item['producto_sku'])      : null,
        'cantidad'     => max(1, (int)$item['cantidad']),
    ];
}

// ─── Consultar dimensiones en BD ─────────────────────────────────────────────
$db = get_database_connection();

/**
 * Busca en producto_variantes (JOIN productos) y devuelve filas
 * indexadas por la clave de búsqueda ($col_pv = columna de la variante).
 */
function buscar_en_variantes(mysqli $db, array $claves, string $col_pv, string $type): array {
    if (empty($claves)) return [];
    $placeholders = implode(',', array_fill(0, count($claves), '?'));
    $types        = str_repeat($type, count($claves));
    $stmt = $db->prepare(
        "SELECT p.id, p.sku, p.nombre, p.peso, p.alto, p.ancho, p.profundidad,
                pv.id AS variante_id, pv.sku_variante
           FROM producto_variantes pv
           JOIN productos p ON p.id = pv.producto_id
          WHERE pv.$col_pv IN ($placeholders)"
    );
    $stmt->bind_param($types, ...$claves);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $mapa = [];
    foreach ($rows as $row) {
        $row['fuente'] = 'variante';
        // Indexar por la clave de búsqueda real
        $clave_real = ($col_pv === 'sku_variante') ? $row['sku_variante'] : $row['variante_id'];
        $mapa[$clave_real] = $row;
    }
    return $mapa;
}

$productos = [];  // Indexado por la clave de búsqueda (sku o id original del item)

if ($use_sku) {
    // 1. Buscar por SKU en productos
    $skus         = array_unique(array_filter(array_column($items_validos, 'producto_sku')));
    $placeholders = implode(',', array_fill(0, count($skus), '?'));
    $types        = str_repeat('s', count($skus));
    $stmt = $db->prepare(
        "SELECT id, sku, nombre, peso, alto, ancho, profundidad
           FROM productos WHERE sku IN ($placeholders)"
    );
    $stmt->bind_param($types, ...$skus);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $row['fuente'] = 'producto';
        $productos[$row['sku']] = $row;
    }
    $stmt->close();

    // 2. Fallback a variantes para los SKUs no encontrados
    $faltantes = array_values(array_diff($skus, array_keys($productos)));
    if ($faltantes) {
        $productos += buscar_en_variantes($db, $faltantes, 'sku_variante', 's');
    }

    // Rellenar producto_id en los items para el detalle
    foreach ($items_validos as &$it) {
        $sku = $it['producto_sku'];
        if ($sku && isset($productos[$sku])) {
            $it['producto_id'] = $productos[$sku]['id'];
        }
    }
    unset($it);

} else {
    // 1. Buscar por ID en productos
    $ids          = array_unique(array_filter(array_column($items_validos, 'producto_id')));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));
    $stmt = $db->prepare(
        "SELECT id, sku, nombre, peso, alto, ancho, profundidad
           FROM productos WHERE id IN ($placeholders)"
    );
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $row['fuente'] = 'producto';
        $productos[$row['id']] = $row;
    }
    $stmt->close();

    // 2. Fallback a variantes (buscar por producto_variantes.id) para los IDs no encontrados
    $faltantes = array_values(array_diff($ids, array_keys($productos)));
    if ($faltantes) {
        foreach (buscar_en_variantes($db, $faltantes, 'id', 'i') as $vid => $row) {
            // Indexar por el variante_id original para que el lookup funcione
            $productos[$vid] = $row;
        }
    }
}

// ─── Calcular dimensiones consolidadas ───────────────────────────────────────
$total_weight = 0;
$max_height   = 0;
$max_width    = 0;
$total_length = 0;

$detalle      = [];
$advertencias = [];

foreach ($items_validos as $item) {
    // Resolver clave de búsqueda (sku o id)
    $clave = $use_sku ? $item['producto_sku'] : $item['producto_id'];
    $pid   = $item['producto_id'];
    $psku  = $item['producto_sku'] ?? null;
    $qty   = $item['cantidad'];

    if (!isset($productos[$clave])) {
        $ref = $use_sku ? "SKU '$clave'" : "ID $clave";
        $advertencias[] = "Producto/variante con $ref no encontrado en la base de datos.";
        continue;
    }

    $p = $productos[$clave];
    $sin_dim = [];

    $peso        = (float)($p['peso']        ?? 0);  // kg en BD → convertir a g
    $alto        = (float)($p['alto']        ?? 0);
    $ancho       = (float)($p['ancho']       ?? 0);
    $profundidad = (float)($p['profundidad'] ?? 0);

    if ($peso        <= 0) $sin_dim[] = 'peso';
    if ($alto        <= 0) $sin_dim[] = 'alto';
    if ($ancho       <= 0) $sin_dim[] = 'ancho';
    if ($profundidad <= 0) $sin_dim[] = 'profundidad';

    if ($sin_dim) {
        $ref_var = !empty($p['sku_variante']) ? " [variante {$p['sku_variante']}]" : '';
        $advertencias[] = "Producto {$p['sku']} (ID {$p['id']}, «{$p['nombre']}»)$ref_var: sin datos de " . implode(', ', $sin_dim) . ".";
    }

    $peso_g = $peso * 1000;   // convertir kg → gramos

    // Acumular
    $total_weight += $peso_g      * $qty;
    $max_height    = max($max_height, $alto);
    $max_width     = max($max_width,  $ancho);
    $total_length += $profundidad * $qty;

    $detalle[] = [
        'producto_id'     => $p['id'],
        'producto_sku'    => $p['sku'],
        'variante_id'     => $p['variante_id']  ?? null,
        'variante_sku'    => $p['sku_variante']  ?? null,
        'fuente'          => $p['fuente']        ?? 'producto',
        'nombre'          => $p['nombre'],
        'cantidad'        => $qty,
        'peso_unit_kg'    => round($peso, 4),
        'peso_unit_g'     => round($peso_g, 2),
        'peso_total_g'    => round($peso_g * $qty, 2),
        'alto_cm'         => $alto,
        'ancho_cm'        => $ancho,
        'profundidad_cm'  => $profundidad,
    ];
}

// Convertir a enteros y aplicar límites de la API de CA
$dim = [
    'weight' => min(25000, (int)round($total_weight)),
    'height' => min(150,   (int)ceil($max_height)),
    'width'  => min(150,   (int)ceil($max_width)),
    'length' => min(150,   (int)ceil($total_length)),
];

// Advertir si se recortaron por límites
if ($total_weight > 25000)  $advertencias[] = "Peso total (" . round($total_weight) . "g / " . round($total_weight/1000,3) . "kg) supera el máximo de 25000g — se recortó.";
if ($max_height   > 150)    $advertencias[] = "Alto ({$max_height}cm) supera el máximo de 150cm — se recortó.";
if ($max_width    > 150)    $advertencias[] = "Ancho ({$max_width}cm) supera el máximo de 150cm — se recortó.";
if ($total_length > 150)    $advertencias[] = "Largo total ({$total_length}cm) supera el máximo de 150cm — se recortó.";

// ─── Respuesta base ───────────────────────────────────────────────────────────
$respuesta = [
    'dimensiones'  => $dim,
    'items'        => $detalle,
    'advertencias' => $advertencias,
];

// ─── Cotización opcional: llamar a rates.php si vienen postalCodes ────────────
$tiene_postal = !empty($input['customerId'])
             && !empty($input['postalCodeOrigin'])
             && !empty($input['postalCodeDestination']);

if ($tiene_postal && $dim['weight'] > 0) {
    $rates_body = [
        'produccion'            => (int)($input['produccion'] ?? 0),
        'customerId'            => (string)$input['customerId'],
        'postalCodeOrigin'      => (string)$input['postalCodeOrigin'],
        'postalCodeDestination' => (string)$input['postalCodeDestination'],
        'dimensions'            => $dim,
    ];
    if (!empty($input['deliveredType'])) {
        $rates_body['deliveredType'] = strtoupper($input['deliveredType']);
    }

    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = dirname($_SERVER['SCRIPT_NAME']);
    $rates_url = $scheme . '://' . $host . $base . '/rates.php';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $rates_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($rates_body),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
    $rates_response = curl_exec($curl);
    $rates_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $rates_decoded = json_decode($rates_response);
    $respuesta['rates'] = [
        'http_code' => $rates_code,
        'data'      => $rates_decoded ?? $rates_response,
    ];
}

http_response_code(200);
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
