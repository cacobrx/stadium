<?php
// api/entradas.php
// API REST para Entrada de Artículos (sin precios)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

function get_db() {
    return get_database_connection();
}

// ── Listar ──────────────────────────────────────────────────────────────────

function obtener_entradas() {
    try {
        $conn = get_db();
        $sql  = "SELECT
                    e.id,
                    e.fecha,
                    e.proveedor,
                    e.notas,
                    e.created_at,
                    COUNT(d.id)       AS cantidad_items,
                    SUM(d.cantidad)   AS total_unidades
                 FROM entradas e
                 LEFT JOIN entradas_det d ON e.id = d.identrada
                 GROUP BY e.id, e.fecha, e.proveedor, e.notas, e.created_at
                 ORDER BY e.fecha DESC, e.id DESC";
        $result  = $conn->query($sql);
        $entradas = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $entradas[] = $row;
            }
        }
        $conn->close();
        return $entradas;
    } catch (Exception $e) {
        error_log("Error en obtener_entradas: " . $e->getMessage());
        return [];
    }
}

// ── Obtener una ─────────────────────────────────────────────────────────────

function obtener_entrada($id) {
    try {
        $conn = get_db();
        $stmt = $conn->prepare("SELECT id, fecha, proveedor, notas FROM entradas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $entrada = $stmt->get_result()->fetch_assoc();
        $conn->close();
        return $entrada;
    } catch (Exception $e) {
        error_log("Error en obtener_entrada: " . $e->getMessage());
        return null;
    }
}

// ── Obtener detalles de una entrada ─────────────────────────────────────────

function obtener_detalle($identrada) {
    try {
        $conn = get_db();
        $stmt = $conn->prepare("SELECT id, identrada, idart, cantidad FROM entradas_det WHERE identrada = ?");
        $stmt->bind_param("i", $identrada);
        $stmt->execute();
        $result   = $stmt->get_result();
        $detalles = [];
        while ($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        $conn->close();
        return $detalles;
    } catch (Exception $e) {
        error_log("Error en obtener_detalle: " . $e->getMessage());
        return [];
    }
}

// ── Crear ────────────────────────────────────────────────────────────────────

function crear_entrada($datos) {
    $conn = null;
    try {
        $conn = get_db();
        $conn->begin_transaction();

        if (!isset($datos['fecha']) || !isset($datos['proveedor'])) {
            throw new Exception("Faltan datos obligatorios: fecha y proveedor");
        }

        $notas = $datos['notas'] ?? null;

        $stmt = $conn->prepare("INSERT INTO entradas (fecha, proveedor, notas) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $datos['fecha'], $datos['proveedor'], $notas);

        if (!$stmt->execute()) {
            throw new Exception("Error al crear entrada: " . $stmt->error);
        }

        $entrada_id = $conn->insert_id;

        // Insertar detalles y sumar stock
        if (isset($datos['articulos']) && is_array($datos['articulos'])) {
            $stmt_det   = $conn->prepare("INSERT INTO entradas_det (identrada, idart, cantidad) VALUES (?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?");

            foreach ($datos['articulos'] as $art) {
                if (!isset($art['idart']) || !isset($art['cantidad'])) {
                    throw new Exception("Faltan campos en artículo");
                }
                $stmt_det->bind_param("iii", $entrada_id, $art['idart'], $art['cantidad']);
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al insertar detalle: " . $stmt_det->error);
                }

                $stmt_stock->bind_param("ii", $art['cantidad'], $art['idart']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al actualizar stock de variante id=" . $art['idart']);
                }
            }
        }

        $conn->commit();
        $conn->close();
        return $entrada_id;
    } catch (Exception $e) {
        if ($conn) { $conn->rollback(); $conn->close(); }
        error_log("Error en crear_entrada: " . $e->getMessage());
        return false;
    }
}

// ── Actualizar ───────────────────────────────────────────────────────────────

function actualizar_entrada($id, $datos) {
    $conn = null;
    try {
        $conn = get_db();
        $conn->begin_transaction();

        $notas = $datos['notas'] ?? null;

        $stmt = $conn->prepare("UPDATE entradas SET fecha=?, proveedor=?, notas=? WHERE id=?");
        $stmt->bind_param("sisi", $datos['fecha'], $datos['proveedor'], $notas, $id);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar entrada: " . $stmt->error);
        }

        if (isset($datos['articulos']) && is_array($datos['articulos'])) {
            // Revertir stock anterior
            $stmt_old = $conn->prepare("SELECT idart, cantidad FROM entradas_det WHERE identrada = ?");
            $stmt_old->bind_param("i", $id);
            $stmt_old->execute();
            $old_result  = $stmt_old->get_result();
            $stmt_revert = $conn->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?");
            while ($old = $old_result->fetch_assoc()) {
                $stmt_revert->bind_param("ii", $old['cantidad'], $old['idart']);
                $stmt_revert->execute();
            }

            // Borrar detalles anteriores
            $stmt_del = $conn->prepare("DELETE FROM entradas_det WHERE identrada = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();

            // Insertar nuevos detalles y sumar stock
            $stmt_det   = $conn->prepare("INSERT INTO entradas_det (identrada, idart, cantidad) VALUES (?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?");

            foreach ($datos['articulos'] as $art) {
                $stmt_det->bind_param("iii", $id, $art['idart'], $art['cantidad']);
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al insertar detalle: " . $stmt_det->error);
                }
                $stmt_stock->bind_param("ii", $art['cantidad'], $art['idart']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al actualizar stock de variante id=" . $art['idart']);
                }
            }
        }

        $conn->commit();
        $conn->close();
        return true;
    } catch (Exception $e) {
        if ($conn) { $conn->rollback(); $conn->close(); }
        error_log("Error en actualizar_entrada: " . $e->getMessage());
        return false;
    }
}

// ── Eliminar ─────────────────────────────────────────────────────────────────

function eliminar_entrada($id) {
    $conn = null;
    try {
        $conn = get_db();
        $conn->begin_transaction();

        // Revertir stock
        $stmt_old = $conn->prepare("SELECT idart, cantidad FROM entradas_det WHERE identrada = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old_result  = $stmt_old->get_result();
        $stmt_revert = $conn->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?");
        while ($old = $old_result->fetch_assoc()) {
            $stmt_revert->bind_param("ii", $old['cantidad'], $old['idart']);
            $stmt_revert->execute();
        }

        // ON DELETE CASCADE elimina los detalles automáticamente
        $stmt = $conn->prepare("DELETE FROM entradas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();

        $conn->commit();
        $conn->close();
        return $ok;
    } catch (Exception $e) {
        if ($conn) { $conn->rollback(); $conn->close(); }
        error_log("Error en eliminar_entrada: " . $e->getMessage());
        return false;
    }
}

// ── Router HTTP ──────────────────────────────────────────────────────────────

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input  = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            if (isset($_GET['detalle'])) {
                echo json_encode(obtener_detalle(intval($_GET['detalle'])));
            } elseif (isset($_GET['id'])) {
                $e = obtener_entrada(intval($_GET['id']));
                if ($e) { echo json_encode($e); }
                else    { http_response_code(404); echo json_encode(['error' => 'Entrada no encontrada']); }
            } else {
                echo json_encode(obtener_entradas());
            }
            break;

        case 'POST':
            $id = crear_entrada($input);
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Entrada creada exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear entrada']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                $ok = actualizar_entrada(intval($_GET['id']), $input);
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Entrada actualizada']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $ok = eliminar_entrada(intval($_GET['id']));
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Entrada eliminada']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
