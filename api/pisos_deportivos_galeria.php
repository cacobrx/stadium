<?php
// api/pisos_deportivos_galeria.php
// GET/POST para galería (config_id = 1)

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
requireAuthAPI();

function getConnection() { return get_database_connection(); }

try {
    $mysqli = getConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $res = $mysqli->query("SELECT id, titulo, categoria, url, alt, orden, esta_activo FROM pisos_deportivos_galeria WHERE config_id = 1 ORDER BY orden ASC, id ASC");
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        $mysqli->close();
        exit(0);
    }

    $raw = file_get_contents('php://input');
    // Log raw payload for debugging
    error_log('[pisos_deportivos_galeria] raw payload: ' . $raw);
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido: ' . json_last_error_msg());
    $items = $input['galeria'] ?? $input;
    if (!is_array($items)) $items = [];

    $mysqli->begin_transaction();
    try {
        $mysqli->query("DELETE FROM pisos_deportivos_galeria WHERE config_id = 1");
        if (!empty($items)) {
            $stmt = $mysqli->prepare("INSERT INTO pisos_deportivos_galeria (config_id, titulo, categoria, url, alt, orden, esta_activo) VALUES (1, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $i => $it) {
                $titulo = $it['titulo'] ?? '';
                $categoria = $it['categoria'] ?? '';
                $url = $it['url'] ?? '';
                $alt = $it['alt'] ?? '';
                $orden = $it['orden'] ?? ($i + 1);
                $activo = isset($it['esta_activo']) ? intval($it['esta_activo']) : 1;
                $stmt->bind_param('ssssii', $titulo, $categoria, $url, $alt, $orden, $activo);
                $stmt->execute();
            }
        }
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Galería actualizada']);
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
