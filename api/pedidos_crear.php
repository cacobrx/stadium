<?php
// api/pedidos/crear.php
// API para crear pedidos con su detalle en una sola operación

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Limpiar cualquier salida previa que pudiera romper el JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Requerir archivos necesarios
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
requireAuthAPI();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// El JSON puede venir con la clave "pedido" envolviendo todo, o los datos directos
$pedidoData = isset($data['pedido']) ? $data['pedido'] : $data;

if (!$pedidoData || !isset($pedidoData['idcli'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de pedido incompletos o inválidos']);
    exit;
}

$conn = get_database_connection();

try {
    // Iniciar transacción
    if (method_exists($conn, 'begin_transaction')) {
        $conn->begin_transaction();
    } else {
        $conn->autocommit(false);
    }

    // 1. Preparar datos de cabecera
    $fecha = $pedidoData['fecha'] ?? date('Y-m-d');
    $idcli = intval($pedidoData['idcli']);
    $estado = intval($pedidoData['estado'] ?? 0);
    $total = floatval($pedidoData['total'] ?? 0);
    $nroremito = !empty($pedidoData['nroremito']) ? intval($pedidoData['nroremito']) : 0;
    $bultos = intval($pedidoData['bultos'] ?? 0);
    $retiro = intval($pedidoData['retiro'] ?? 0);
    $fechaentrega = !empty($pedidoData['fechaentrega']) ? $pedidoData['fechaentrega'] : null;
    $observaciones = $pedidoData['observaciones'] ?? '';
    $ubicacion = $pedidoData['ubicacion'] ?? '';
    $fechacreate = $pedidoData['fechacreate'] ?? date('Y-m-d H:i:s');
    
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : 0;

    // Insertar Pedido
    $sql_cab = "INSERT INTO pedidos (fecha, idcli, estado, total, nroremito, bultos, retiro, fechaentrega, observaciones, ubicacion, usuario, fechacreate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_cab = $conn->prepare($sql_cab);
    if (!$stmt_cab) {
        throw new Exception("Error preparando cabecera: " . $conn->error);
    }

    // s=string, i=int, d=double
    $stmt_cab->bind_param("siidiiisssis", 
        $fecha, 
        $idcli, 
        $estado, 
        $total, 
        $nroremito, 
        $bultos, 
        $retiro, 
        $fechaentrega, 
        $observaciones, 
        $ubicacion, 
        $userId,
        $fechacreate
    );

    if (!$stmt_cab->execute()) {
        throw new Exception("Error al insertar cabecera: " . $stmt_cab->error);
    }

    $pedidoId = $conn->insert_id;

    // 2. Insertar Detalles si existen
    $detalles = isset($pedidoData['detalles']) ? $pedidoData['detalles'] : [];
    
    if (!empty($detalles) && is_array($detalles)) {
        $sql_det = "INSERT INTO pedidos_det (idped, idart, detalle, cantidad, precio, total, terminado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_det = $conn->prepare($sql_det);
        if (!$stmt_det) {
            throw new Exception("Error preparando detalle: " . $conn->error);
        }

        foreach ($detalles as $det) {
            $idart = intval($det['idart']);
            $detalle_text = $det['detalle'] ?? '';
            $cantidad = intval($det['cantidad'] ?? 0);
            $precio = floatval($det['precio'] ?? 0);
            $total_det = floatval($det['total'] ?? 0);
            $terminado = intval($det['terminado'] ?? 0);

            $stmt_det->bind_param("iisiidi", 
                $pedidoId, 
                $idart, 
                $detalle_text, 
                $cantidad, 
                $precio, 
                $total_det, 
                $terminado
            );

            if (!$stmt_det->execute()) {
                throw new Exception("Error al insertar detalle para artículo ID $idart: " . $stmt_det->error);
            }
        }
    }

    // Confirmar cambios
    if (method_exists($conn, 'commit')) {
        $conn->commit();
    } else {
        $conn->autocommit(true);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Pedido #' . $pedidoId . ' creado correctamente',
        'id' => $pedidoId
    ]);

} catch (Exception $e) {
    // Revertir cambios en caso de error
    if (method_exists($conn, 'rollback')) {
        $conn->rollback();
    } else {
        try { $conn->rollback(); } catch (Exception $re) {}
        $conn->autocommit(true);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt_cab)) $stmt_cab->close();
    if (isset($stmt_det)) $stmt_det->close();
    $conn->close();
}
