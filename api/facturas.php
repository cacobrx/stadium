<?php
// api/facturas.php — CRUD comprobantes fiscales (tablas facturas + facturas_det)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireAuthAPI();

function getConn() {
    $m = get_database_connection();
    if ($m->connect_errno) throw new Exception('Error de conexión: ' . $m->connect_error);
    return $m;
}

function nullOrInt($val) {
    return ($val === '' || $val === null) ? null : intval($val);
}

function nullOrStr($val) {
    return ($val === '' || $val === null) ? null : trim($val);
}

// ── GET ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = getConn();

        if (isset($_GET['id'])) {
            // --- Factura individual con su detalle ---
            $id   = intval($_GET['id']);
            $stmt = $db->prepare(
                'SELECT f.*,
                        CONCAT(COALESCE(c.apellido,""), " ", COALESCE(c.nombre,"")) AS cliente_nombre,
                        d.razonsocial AS cuit_razonsocial
                 FROM facturas f
                 LEFT JOIN clientes c ON c.id = f.idcli
                 LEFT JOIN datoscuit d ON d.id = f.idcuit
                 WHERE f.id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $factura = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$factura) {
                echo json_encode(['success' => false, 'error' => 'Factura no encontrada']);
                exit;
            }

            // Detalle
            $stmt2 = $db->prepare(
                'SELECT fd.*, pv.nombre AS art_nombre, pv.sku_variante AS art_sku,
                        p.nombre AS prod_nombre
                 FROM facturas_det fd
                 LEFT JOIN producto_variantes pv ON pv.id = fd.idart
                 LEFT JOIN productos p ON p.id = pv.producto_id
                 WHERE fd.idfis = ?
                 ORDER BY fd.id ASC'
            );
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $detalles = [];
            $res2 = $stmt2->get_result();
            while ($row = $res2->fetch_assoc()) $detalles[] = $row;
            $stmt2->close();

            $factura['detalles'] = $detalles;
            echo json_encode(['success' => true, 'factura' => $factura]);

        } elseif (isset($_GET['clientes'])) {
            // --- Lista de clientes para selector ---
            $res   = $db->query("SELECT id, CONCAT(COALESCE(apellido,''), ' ', COALESCE(nombre,'')) AS nombre FROM clientes ORDER BY apellido, nombre");
            $lista = [];
            while ($row = $res->fetch_assoc()) $lista[] = $row;
            echo json_encode(['success' => true, 'clientes' => $lista]);

        } elseif (isset($_GET['cuits'])) {
            // --- Lista de datoscuit para selector ---
            $res  = $db->query("SELECT id, razonsocial, cuit, puntoventa AS ptovta, pordefecto FROM datoscuit ORDER BY pordefecto DESC, razonsocial");
            $list = [];
            while ($row = $res->fetch_assoc()) $list[] = $row;
            echo json_encode(['success' => true, 'cuits' => $list]);

        } elseif (isset($_GET['articulos'])) {
            // --- Lista de variantes para selector ---
            $res  = $db->query(
                "SELECT pv.id, pv.sku_variante AS sku, CONCAT(p.nombre, ' — ', pv.nombre) AS nombre, pv.precio
                 FROM producto_variantes pv
                 JOIN productos p ON p.id = pv.producto_id
                 WHERE pv.activa = 1
                 ORDER BY p.nombre, pv.nombre"
            );
            $list = [];
            while ($row = $res->fetch_assoc()) $list[] = $row;
            echo json_encode(['success' => true, 'articulos' => $list]);

        } else {
            // --- Lista de facturas con totales ---
            $res      = $db->query(
                'SELECT f.id, f.fecha, f.tipocom, f.letra, f.ptovta, f.numero,
                        f.numerocae, f.fechacae, f.docreferencia, f.idped, f.idcuit, f.fechacreate,
                        CONCAT(COALESCE(c.apellido,""), " ", COALESCE(c.nombre,"")) AS cliente_nombre,
                        d.razonsocial AS cuit_razonsocial,
                        COALESCE(SUM(fd.cantidad * fd.precio), 0) AS total_neto,
                        COALESCE(SUM(fd.cantidad * fd.precio * (1 + fd.alicuota/100)), 0) AS total_con_iva
                 FROM facturas f
                 LEFT JOIN clientes c    ON c.id  = f.idcli
                 LEFT JOIN datoscuit d   ON d.id  = f.idcuit
                 LEFT JOIN facturas_det fd ON fd.idfis = f.id
                 GROUP BY f.id
                 ORDER BY f.id DESC'
            );
            $facturas = [];
            while ($row = $res->fetch_assoc()) $facturas[] = $row;
            echo json_encode(['success' => true, 'facturas' => $facturas]);
        }

        $db->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ── POST (crear) ─────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Datos JSON inválidos');

        if (empty($data['tipocom'])) throw new Exception('El tipo de comprobante es requerido');

        $db = getConn();
        $db->begin_transaction();

        $fecha       = nullOrStr($data['fecha']       ?? null);
        $idcli       = nullOrInt($data['idcli']       ?? null);
        $tipocom     = trim($data['tipocom']);
        $letra       = trim($data['letra']       ?? '');
        $ptovta      = intval($data['ptovta']     ?? 1);
        $numero      = intval($data['numero']     ?? 0);
        $numerocae   = trim($data['numerocae']   ?? '');
        $fechacae    = nullOrStr($data['fechacae']    ?? null);
        $docreferencia = trim($data['docreferencia'] ?? '');
        $idped       = nullOrInt($data['idped']       ?? null);
        $idcuit      = nullOrInt($data['idcuit']      ?? null);

        $stmt = $db->prepare(
            'INSERT INTO facturas (fecha, idcli, tipocom, letra, ptovta, numero, numerocae, fechacae, docreferencia, idped, idcuit)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('siississsii',
            $fecha, $idcli, $tipocom, $letra, $ptovta, $numero,
            $numerocae, $fechacae, $docreferencia, $idped, $idcuit
        );
        if (!$stmt->execute()) throw new Exception('Error al crear factura: ' . $stmt->error);
        $newId = $db->insert_id;
        $stmt->close();

        // Insertar detalles
        $detalles = $data['detalles'] ?? [];
        if (!empty($detalles)) {
            $stmtD = $db->prepare(
                'INSERT INTO facturas_det (idfis, cantidad, descripcion, idart, precio, alicuota)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($detalles as $d) {
                $cantidad    = floatval($d['cantidad']   ?? 1);
                $desc        = trim($d['descripcion']    ?? '');
                $idart       = nullOrInt($d['idart']     ?? null);
                $precio      = floatval($d['precio']     ?? 0);
                $alicuota    = floatval($d['alicuota']   ?? 21);
                $stmtD->bind_param('idsidd', $newId, $cantidad, $desc, $idart, $precio, $alicuota);
                if (!$stmtD->execute()) throw new Exception('Error en detalle: ' . $stmtD->error);
            }
            $stmtD->close();
        }

        $db->commit();
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Comprobante creado correctamente']);
        $db->close();
    } catch (Exception $e) {
        if (isset($db)) { try { $db->rollback(); } catch(Exception $ex){} }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ── PUT (actualizar) ──────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) throw new Exception('ID requerido');

        $id  = intval($data['id']);
        $db  = getConn();

        $stmt = $db->prepare('SELECT id FROM facturas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Factura no encontrada');
        $stmt->close();

        $db->begin_transaction();

        $fecha       = nullOrStr($data['fecha']       ?? null);
        $idcli       = nullOrInt($data['idcli']       ?? null);
        $tipocom     = trim($data['tipocom']     ?? '');
        $letra       = trim($data['letra']       ?? '');
        $ptovta      = intval($data['ptovta']    ?? 1);
        $numero      = intval($data['numero']    ?? 0);
        $numerocae   = trim($data['numerocae']   ?? '');
        $fechacae    = nullOrStr($data['fechacae']    ?? null);
        $docreferencia = trim($data['docreferencia'] ?? '');
        $idped       = nullOrInt($data['idped']       ?? null);
        $idcuit      = nullOrInt($data['idcuit']      ?? null);

        $stmt = $db->prepare(
            'UPDATE facturas
             SET fecha=?, idcli=?, tipocom=?, letra=?, ptovta=?, numero=?,
                 numerocae=?, fechacae=?, docreferencia=?, idped=?, idcuit=?
             WHERE id=?'
        );
        $stmt->bind_param('siissisiisii',
            $fecha, $idcli, $tipocom, $letra, $ptovta, $numero,
            $numerocae, $fechacae, $docreferencia, $idped, $idcuit, $id
        );
        if (!$stmt->execute()) throw new Exception('Error al actualizar factura: ' . $stmt->error);
        $stmt->close();

        // Reemplazar detalles
        $db->query("DELETE FROM facturas_det WHERE idfis = $id");

        $detalles = $data['detalles'] ?? [];
        if (!empty($detalles)) {
            $stmtD = $db->prepare(
                'INSERT INTO facturas_det (idfis, cantidad, descripcion, idart, precio, alicuota)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($detalles as $d) {
                $cantidad = floatval($d['cantidad']  ?? 1);
                $desc     = trim($d['descripcion']   ?? '');
                $idart    = nullOrInt($d['idart']    ?? null);
                $precio   = floatval($d['precio']    ?? 0);
                $alicuota = floatval($d['alicuota']  ?? 21);
                $stmtD->bind_param('idsidd', $id, $cantidad, $desc, $idart, $precio, $alicuota);
                if (!$stmtD->execute()) throw new Exception('Error en detalle: ' . $stmtD->error);
            }
            $stmtD->close();
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Comprobante actualizado correctamente']);
        $db->close();
    } catch (Exception $e) {
        if (isset($db)) { try { $db->rollback(); } catch(Exception $ex){} }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) throw new Exception('ID requerido');

        $id = intval($data['id']);
        $db = getConn();

        $stmt = $db->prepare('SELECT id FROM facturas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new Exception('Factura no encontrada');
        $stmt->close();

        $db->query("DELETE FROM facturas_det WHERE idfis = $id");

        $stmt = $db->prepare('DELETE FROM facturas WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) throw new Exception('Error al eliminar: ' . $stmt->error);
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Comprobante eliminado correctamente']);
        $db->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
