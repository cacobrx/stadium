<?php
/*
 * Archivo: api/mercadopago/search_payments.php
 * Consumo del endpoint GET /v1/payments/search de Mercado Pago
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar configuración de Mercado Pago
$config_path = __DIR__ . '/../../config/mercadopago.php';
if (file_exists($config_path)) {
    require_once $config_path;
}

// Token Sandbox por defecto
$token = defined('MP_TOKEN_SANDBOX') ? MP_TOKEN_SANDBOX : (defined('MP_TOKEN_SANDBOX_CHECKOUT') ? MP_TOKEN_SANDBOX_CHECKOUT : '');

// Permitir sobreescribir token mediante GET/POST si se proporciona
if (!empty($_REQUEST['token'])) {
    $token = trim($_REQUEST['token']);
}

// Parámetros de búsqueda
$limit = isset($_REQUEST['limit']) ? min(100, max(1, (int)$_REQUEST['limit'])) : 30;
$offset = isset($_REQUEST['offset']) ? max(0, (int)$_REQUEST['offset']) : 0;
$sort = isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], ['date_created', 'date_last_updated', 'id', 'money_release_date']) ? $_REQUEST['sort'] : 'date_created';
$criteria = isset($_REQUEST['criteria']) && strtolower($_REQUEST['criteria']) === 'asc' ? 'asc' : 'desc';

$query_params = [
    'sort' => $sort,
    'criteria' => $criteria,
    'limit' => $limit,
    'offset' => $offset
];

if (!empty($_REQUEST['status'])) {
    $query_params['status'] = trim($_REQUEST['status']);
}

if (!empty($_REQUEST['external_reference'])) {
    $query_params['external_reference'] = trim($_REQUEST['external_reference']);
}

if (!empty($_REQUEST['payment_id'])) {
    $query_params['id'] = trim($_REQUEST['payment_id']);
}

if (!empty($_REQUEST['begin_date'])) {
    $begin = trim($_REQUEST['begin_date']);
    if (strlen($begin) == 10) {
        $begin .= 'T00:00:00.000-03:00';
    }
    $query_params['begin_date'] = $begin;
}

if (!empty($_REQUEST['end_date'])) {
    $end = trim($_REQUEST['end_date']);
    if (strlen($end) == 10) {
        $end .= 'T23:59:59.999-03:00';
    }
    $query_params['end_date'] = $end;
}

// Construir la URL completa
$url = 'https://api.mercadopago.com/v1/payments/search?' . http_build_query($query_params);

// Iniciar cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión cURL: ' . $curl_error,
        'http_code' => $http_code,
        'url' => $url
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

$decoded = json_decode($response_body, true);

if ($http_code >= 400 || !is_array($decoded)) {
    echo json_encode([
        'success' => false,
        'error' => isset($decoded['message']) ? $decoded['message'] : 'Error en respuesta de Mercado Pago',
        'http_code' => $http_code,
        'raw_response' => $decoded ? $decoded : $response_body,
        'url' => $url
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Procesar estadísticas de resumen
$results = isset($decoded['results']) && is_array($decoded['results']) ? $decoded['results'] : [];
$paging = isset($decoded['paging']) ? $decoded['paging'] : ['total' => count($results), 'limit' => $limit, 'offset' => $offset];

$stats = [
    'total_in_page' => count($results),
    'total_found' => isset($paging['total']) ? (int)$paging['total'] : count($results),
    'approved_count' => 0,
    'approved_amount' => 0.0,
    'pending_count' => 0,
    'pending_amount' => 0.0,
    'rejected_count' => 0,
    'refunded_count' => 0,
    'cancelled_count' => 0
];

foreach ($results as $p) {
    $status = isset($p['status']) ? $p['status'] : '';
    $amount = isset($p['transaction_amount']) ? (float)$p['transaction_amount'] : 0.0;

    switch ($status) {
        case 'approved':
            $stats['approved_count']++;
            $stats['approved_amount'] += $amount;
            break;
        case 'pending':
        case 'in_process':
        case 'authorized':
            $stats['pending_count']++;
            $stats['pending_amount'] += $amount;
            break;
        case 'rejected':
            $stats['rejected_count']++;
            break;
        case 'refunded':
        case 'charged_back':
            $stats['refunded_count']++;
            break;
        case 'cancelled':
            $stats['cancelled_count']++;
            break;
    }
}

echo json_encode([
    'success' => true,
    'endpoint' => 'GET /v1/payments/search',
    'mode' => 'sandbox',
    'token_used' => substr($token, 0, 12) . '...',
    'http_code' => $http_code,
    'summary' => $stats,
    'paging' => $paging,
    'results' => $results,
    'raw_response' => $decoded
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
