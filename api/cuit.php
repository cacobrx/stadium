<?php
// api/cuit.php — CRUD para datos fiscales (tabla datoscuit)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireAuthAPI();

function getConn() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// ─── GET ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConn();

        if (isset($_GET['id'])) {
            $id   = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM datoscuit WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row  = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                echo json_encode(['success' => true, 'registro' => $row]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
            }
        } else {
            $result   = $mysqli->query('SELECT * FROM datoscuit ORDER BY id ASC');
            $registros = [];
            while ($row = $result->fetch_assoc()) {
                $registros[] = $row;
            }
            echo json_encode(['success' => true, 'registros' => $registros]);
        }

        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ─── POST (crear) ────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }

        if (empty($data['razonsocial'])) {
            throw new Exception('La razón social es requerida');
        }
        if (empty($data['cuit'])) {
            throw new Exception('El CUIT es requerido');
        }

        $razonsocial  = trim($data['razonsocial']);
        $fantasia     = trim($data['fantasia']     ?? '');
        $cuit         = trim($data['cuit']);
        $condicioniva = trim($data['condicioniva'] ?? '');
        $direccion    = trim($data['direccion']    ?? '');
        $ciudad       = trim($data['ciudad']       ?? '');
        $puntoventa   = intval($data['puntoventa'] ?? 1);
        $fechainicio  = !empty($data['fechainicio']) ? $data['fechainicio'] : null;

        $mysqli = getConn();

        $test         = isset($data['test'])       ? intval($data['test'])       : 0;
        $pordefecto   = isset($data['pordefecto']) ? intval($data['pordefecto']) : 0;

        $stmt = $mysqli->prepare(
            'INSERT INTO datoscuit
                (razonsocial, fantasia, cuit, condicioniva, direccion, ciudad, puntoventa, fechainicio, test, pordefecto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssssisii',
            $razonsocial, $fantasia, $cuit, $condicioniva,
            $direccion, $ciudad, $puntoventa, $fechainicio, $test, $pordefecto
        );

        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Datos fiscales creados correctamente']);
        } else {
            throw new Exception('Error al crear registro: ' . $stmt->error);
        }

        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ─── PUT (actualizar) ────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de registro requerido');
        }

        $id = intval($data['id']);
        $mysqli = getConn();

        // Verificar existencia
        $stmt = $mysqli->prepare('SELECT id FROM datoscuit WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Registro no encontrado');
        }
        $stmt->close();

        $allowedFields = ['razonsocial', 'fantasia', 'cuit', 'condicioniva', 'direccion', 'ciudad', 'puntoventa', 'fechainicio', 'test', 'pordefecto'];
        $intFields     = ['puntoventa', 'test', 'pordefecto'];

        $updates = [];
        $types   = '';
        $values  = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                if (in_array($field, $intFields)) {
                    $types   .= 'i';
                    $values[] = intval($data[$field]);
                } else {
                    $types   .= 's';
                    $values[] = ($field === 'fechainicio' && empty($data[$field])) ? null : trim($data[$field]);
                }
            }
        }

        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }

        $types   .= 'i';
        $values[] = $id;

        $sql  = 'UPDATE datoscuit SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Datos fiscales actualizados correctamente']);
        } else {
            throw new Exception('Error al actualizar: ' . $stmt->error);
        }

        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ─── DELETE ──────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de registro requerido');
        }

        $id     = intval($data['id']);
        $mysqli = getConn();

        $stmt = $mysqli->prepare('SELECT id FROM datoscuit WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Registro no encontrado');
        }
        $stmt->close();

        $stmt = $mysqli->prepare('DELETE FROM datoscuit WHERE id = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
        } else {
            throw new Exception('Error al eliminar: ' . $stmt->error);
        }

        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
