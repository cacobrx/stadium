<?php
// api/cupones.php — CRUD de cupones de descuento
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireAuthAPI();

function db() {
    return get_database_connection();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $conn = db();
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT * FROM cupones WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            echo $row ? json_encode($row) : (http_response_code(404) && json_encode(['error' => 'No encontrado']));
        } else {
            $result = $conn->query("SELECT * FROM cupones ORDER BY id DESC");
            $rows = [];
            while ($r = $result->fetch_assoc()) $rows[] = $r;
            echo json_encode($rows);
        }
        $conn->close();
        exit;
    }

    // ── POST ─────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true);
        if (!$d) { http_response_code(400); echo json_encode(['error' => 'JSON inválido']); exit; }

        $codigo              = strtoupper(trim($d['codigo'] ?? ''));
        $cantidad            = intval($d['cantidad'] ?? 1);
        $porcentaje          = floatval($d['porcentaje_descuento'] ?? 0);
        $tope                = isset($d['tope_descuento']) && $d['tope_descuento'] !== '' ? floatval($d['tope_descuento']) : null;
        $fecha_inicio        = $d['fecha_inicio'] ?? '';
        $fecha_fin           = $d['fecha_fin']    ?? '';
        $activo              = isset($d['activo']) ? intval($d['activo']) : 1;

        if (!$codigo || !$fecha_inicio || !$fecha_fin) {
            http_response_code(422);
            echo json_encode(['error' => 'Código, fecha inicio y fecha fin son obligatorios']);
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare(
            "INSERT INTO cupones (codigo, cantidad, porcentaje_descuento, tope_descuento, fecha_inicio, fecha_fin, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("siidssi", $codigo, $cantidad, $porcentaje, $tope, $fecha_inicio, $fecha_fin, $activo);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            // Código duplicado
            if ($conn->errno === 1062) {
                http_response_code(409);
                echo json_encode(['error' => 'El código ya existe']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear cupón: ' . $stmt->error]);
            }
        }
        $conn->close();
        exit;
    }

    // ── PUT ──────────────────────────────────────────────────────────────────
    if ($method === 'PUT') {
        if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); exit; }
        $id = intval($_GET['id']);
        $d  = json_decode(file_get_contents('php://input'), true);
        if (!$d) { http_response_code(400); echo json_encode(['error' => 'JSON inválido']); exit; }

        $codigo     = strtoupper(trim($d['codigo'] ?? ''));
        $cantidad   = intval($d['cantidad'] ?? 1);
        $porcentaje = floatval($d['porcentaje_descuento'] ?? 0);
        $tope       = isset($d['tope_descuento']) && $d['tope_descuento'] !== '' ? floatval($d['tope_descuento']) : null;
        $fecha_inicio = $d['fecha_inicio'] ?? '';
        $fecha_fin    = $d['fecha_fin']    ?? '';
        $activo       = isset($d['activo']) ? intval($d['activo']) : 1;

        if (!$codigo || !$fecha_inicio || !$fecha_fin) {
            http_response_code(422);
            echo json_encode(['error' => 'Código, fecha inicio y fecha fin son obligatorios']);
            exit;
        }

        $conn = db();
        $stmt = $conn->prepare(
            "UPDATE cupones SET codigo=?, cantidad=?, porcentaje_descuento=?, tope_descuento=?, fecha_inicio=?, fecha_fin=?, activo=?
             WHERE id=?"
        );
        $stmt->bind_param("siidssii", $codigo, $cantidad, $porcentaje, $tope, $fecha_inicio, $fecha_fin, $activo, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            if ($conn->errno === 1062) {
                http_response_code(409);
                echo json_encode(['error' => 'El código ya existe']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar: ' . $stmt->error]);
            }
        }
        $conn->close();
        exit;
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); exit; }
        $id   = intval($_GET['id']);
        $conn = db();
        $stmt = $conn->prepare("DELETE FROM cupones WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo $stmt->execute()
            ? json_encode(['success' => true])
            : (http_response_code(500) && json_encode(['error' => 'Error al eliminar']));
        $conn->close();
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    error_log("Error en api/cupones.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
