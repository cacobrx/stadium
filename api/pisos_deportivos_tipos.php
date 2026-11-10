<?php
// api/pisos_deportivos_tipos.php
// GET/POST para tipos de pisos (config_id = 1)

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
        $res = $mysqli->query("SELECT * FROM pisos_deportivos_tipos WHERE config_id = 1 ORDER BY orden ASC, id ASC");
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $r['caracteristicas'] = json_decode($r['caracteristicas'], true) ?: [];
            $r['aplicaciones'] = json_decode($r['aplicaciones'], true) ?: [];
            $r['certificaciones'] = json_decode($r['certificaciones'], true) ?: [];
            $r['especificaciones'] = json_decode($r['especificaciones'], true) ?: [];
            $rows[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        $mysqli->close();
        exit(0);
    }

    $raw = file_get_contents('php://input');
    // DEBUG: Guardar raw input para debug
    file_put_contents(__DIR__.'/debug_tipos_input.txt', date('Y-m-d H:i:s')." RAW: ".$raw."\n", FILE_APPEND);

    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido: ' . json_last_error_msg());
    $items = $input['tiposPisos'] ?? $input;
    
    // DEBUG:
    file_put_contents(__DIR__.'/debug_tipos_input.txt', "ITEMS COUNT: ".(is_array($items)?count($items):'not array')."\n", FILE_APPEND);

    if (!is_array($items)) $items = [];

    $mysqli->begin_transaction();
    try {
        $mysqli->query("DELETE FROM pisos_deportivos_tipos WHERE config_id = 1");
        if (!empty($items)) {
            $stmt = $mysqli->prepare("INSERT INTO pisos_deportivos_tipos (config_id, nombre, slug, etiqueta, descripcion, imagen, caracteristicas, aplicaciones, certificaciones, especificaciones, orden, esta_activo) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $i => $it) {
                $nombre = $it['nombre'] ?? '';
                $slug = $it['slug'] ?? '';
                $etiqueta = $it['etiqueta'] ?? '';
                $descripcion = $it['descripcion'] ?? '';
                $imagen = $it['imagen'] ?? '';
                $caracteristicas = json_encode($it['caracteristicas'] ?? []);
                $aplicaciones = json_encode($it['aplicaciones'] ?? []);
                $certificaciones = json_encode($it['certificaciones'] ?? []);
                $especificaciones = json_encode($it['especificaciones'] ?? []);
                $orden = $it['orden'] ?? ($i + 1);
                $activo = isset($it['esta_activo']) ? intval($it['esta_activo']) : 1;
                $stmt->bind_param('sssssssssii', $nombre, $slug, $etiqueta, $descripcion, $imagen, $caracteristicas, $aplicaciones, $certificaciones, $especificaciones, $orden, $activo);
                $stmt->execute();
            }
        }
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Tipos actualizados']);
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
