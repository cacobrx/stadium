<?php
// api/cupones/cupon.php
// Valida un cupón de descuento y devuelve los importes resultantes.
// Requiere: POST con JSON { "codigo": "...", "subtotal": 12345.67 }
// Auth: Bearer token (token del cliente en tabla clientes)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// ── Leer y validar JSON ────────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

$codigo   = strtoupper(trim($data['codigo']   ?? ''));
$subtotal = floatval($data['subtotal'] ?? 0);

if ($codigo === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El campo codigo es obligatorio']);
    exit;
}
if ($subtotal <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El subtotal debe ser mayor a 0']);
    exit;
}

try {
    $conn = get_database_connection();

    // ── Buscar el cupón ────────────────────────────────────────────────────────
    $hoy = date('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT id, codigo, cantidad, porcentaje_descuento, tope_descuento
         FROM cupones
         WHERE codigo = ?
           AND activo = 1
           AND cantidad > 0
           AND fecha_inicio <= ?
           AND fecha_fin >= ?
         LIMIT 1"
    );
    $stmt->bind_param("sss", $codigo, $hoy, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Cupón no válido, vencido o sin usos disponibles']);
        exit;
    }

    $cupon = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    // ── Calcular descuento ─────────────────────────────────────────────────────
    $porcentaje       = floatval($cupon['porcentaje_descuento']);
    $tope             = $cupon['tope_descuento'] !== null ? floatval($cupon['tope_descuento']) : null;

    $descuento = $subtotal * ($porcentaje / 100);
    if ($tope !== null && $descuento > $tope) {
        $descuento = $tope;
    }
    $importe_final = $subtotal - $descuento;

    echo json_encode([
        'success'             => true,
        'codigo'              => $cupon['codigo'],
        'porcentaje'          => $porcentaje,
        'tope_descuento'      => $tope,
        'descuento_aplicado'  => round($descuento, 2),
        'importe_inicial'     => round($subtotal, 2),
        'importe_final'       => round($importe_final, 2),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/cupones/cupon.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
