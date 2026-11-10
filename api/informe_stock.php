<?php
// api/informe_stock.php
// Informe de movimientos de stock por variante

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../config/database.php';

function get_db() { return get_database_connection(); }

// ── Lista de variantes para el selector ─────────────────────────────────────

function listar_variantes() {
    try {
        $conn = get_db();
        $sql  = "SELECT
                    pv.id,
                    pv.producto_id,
                    pv.sku_variante,
                    pv.nombre          AS variante_nombre,
                    pv.stock           AS stock_actual,
                    p.nombre           AS producto_nombre,
                    GROUP_CONCAT(
                        CONCAT(va.nombre, ': ', va.valor)
                        ORDER BY va.nombre SEPARATOR ', '
                    ) AS atributos
                 FROM producto_variantes pv
                 JOIN productos p ON pv.producto_id = p.id
                 LEFT JOIN variante_atributos va ON va.variante_id = pv.id
                 WHERE pv.activa = 1
                 GROUP BY pv.id, pv.producto_id, pv.sku_variante, pv.nombre, pv.stock, p.nombre
                 ORDER BY p.nombre, pv.nombre";
        $result   = $conn->query($sql);
        $variantes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $variantes[] = $row;
            }
        }
        $conn->close();
        return $variantes;
    } catch (Exception $e) {
        error_log("Error en listar_variantes: " . $e->getMessage());
        return [];
    }
}

// ── Movimientos de una variante ──────────────────────────────────────────────

function movimientos_variante($variante_id) {
    try {
        $conn = get_db();

        // Datos de la variante y su producto
        $stmt = $conn->prepare(
            "SELECT pv.id, pv.producto_id, pv.sku_variante, pv.nombre AS variante_nombre,
                    pv.stock AS stock_actual, p.nombre AS producto_nombre
             FROM producto_variantes pv
             JOIN productos p ON pv.producto_id = p.id
             WHERE pv.id = ?"
        );
        $stmt->bind_param("i", $variante_id);
        $stmt->execute();
        $variante = $stmt->get_result()->fetch_assoc();

        if (!$variante) {
            return ['error' => 'Variante no encontrada'];
        }

        $producto_id = $variante['producto_id'];
        $movimientos = [];

        // ── ENTRADAS (entradas_det.idart = variante_id) ──────────────────────
        $stmt_ent = $conn->prepare(
            "SELECT
                e.fecha,
                ed.cantidad,
                e.id         AS referencia_id,
                e.proveedor,
                e.notas
             FROM entradas_det ed
             JOIN entradas e ON e.id = ed.identrada
             WHERE ed.idart = ?
             ORDER BY e.fecha, e.id"
        );
        $stmt_ent->bind_param("i", $variante_id);
        $stmt_ent->execute();
        $res_ent = $stmt_ent->get_result();
        while ($row = $res_ent->fetch_assoc()) {
            $movimientos[] = [
                'tipo'          => 'entrada',
                'fecha'         => $row['fecha'],
                'cantidad'      => (int)$row['cantidad'],
                'referencia_id' => $row['referencia_id'],
                'detalle'       => 'Entrada #' . $row['referencia_id'] . ($row['notas'] ? ' — ' . $row['notas'] : ''),
                'proveedor_id'  => $row['proveedor'],
            ];
        }

        // ── SALIDAS (pedidos_det.idart = producto_id) ─────────────────────────
        $stmt_sal = $conn->prepare(
            "SELECT
                p.fecha,
                pd.cantidad,
                p.id            AS referencia_id,
                p.idcli,
                p.estado,
                pd.detalle      AS item_detalle
             FROM pedidos_det pd
             JOIN pedidos p ON p.id = pd.idped
             WHERE pd.idart = ?
             ORDER BY p.fecha, p.id"
        );
        $stmt_sal->bind_param("i", $producto_id);
        $stmt_sal->execute();
        $res_sal = $stmt_sal->get_result();
        while ($row = $res_sal->fetch_assoc()) {
            $movimientos[] = [
                'tipo'          => 'salida',
                'fecha'         => $row['fecha'],
                'cantidad'      => (int)$row['cantidad'],
                'referencia_id' => $row['referencia_id'],
                'detalle'       => 'Pedido #' . $row['referencia_id'] . ($row['item_detalle'] ? ' — ' . $row['item_detalle'] : ''),
                'estado'        => $row['estado'],
            ];
        }

        // Ordenar por fecha, luego entradas antes que salidas (para mismo día)
        usort($movimientos, function ($a, $b) {
            $cmp = strcmp($a['fecha'], $b['fecha']);
            if ($cmp !== 0) return $cmp;
            // entradas primero en mismo día
            if ($a['tipo'] === $b['tipo']) return 0;
            return $a['tipo'] === 'entrada' ? -1 : 1;
        });

        // Calcular saldo acumulado
        // El stock actual es el resultado de todos los movimientos.
        // Calculamos el saldo desde el principio asumiendo stock inicial 0
        // y luego ajustamos.
        $saldo_final = (int)$variante['stock_actual'];
        $suma_neta   = 0;
        foreach ($movimientos as $m) {
            $suma_neta += ($m['tipo'] === 'entrada') ? $m['cantidad'] : -$m['cantidad'];
        }
        // stock_inicial = stock_final - suma_neta
        $stock_inicial = $saldo_final - $suma_neta;

        $saldo = $stock_inicial;
        foreach ($movimientos as &$m) {
            if ($m['tipo'] === 'entrada') {
                $saldo += $m['cantidad'];
            } else {
                $saldo -= $m['cantidad'];
            }
            $m['saldo'] = $saldo;
        }
        unset($m);

        $conn->close();

        return [
            'variante'      => $variante,
            'stock_inicial' => $stock_inicial,
            'movimientos'   => $movimientos,
        ];

    } catch (Exception $e) {
        error_log("Error en movimientos_variante: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// ── Router ───────────────────────────────────────────────────────────────────

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    if (isset($_GET['variante'])) {
        echo json_encode(movimientos_variante(intval($_GET['variante'])));
    } else {
        echo json_encode(listar_variantes());
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
