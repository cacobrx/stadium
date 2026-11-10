<?php
// api/pisos_deportivos_config.php
// Endpoint para obtener/actualizar la configuración principal de Pisos Deportivos

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireAuthAPI();

function getConnection() {
    return get_database_connection();
}

try {
    $mysqli = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $res = $mysqli->query("SELECT * FROM pisos_deportivos_config WHERE id = 1");
        $cfg = $res->fetch_assoc();
        if (!$cfg) {
            // Crear registro por defecto
            $mysqli->query("INSERT INTO pisos_deportivos_config (id, titulo_pagina, descripcion_pagina, esta_activo) VALUES (1, 'Pisos Deportivos Profesionales', '', 1) ON DUPLICATE KEY UPDATE id=1");
            $res = $mysqli->query("SELECT * FROM pisos_deportivos_config WHERE id = 1");
            $cfg = $res->fetch_assoc();
        }
        echo json_encode(['success' => true, 'data' => $cfg]);
        $mysqli->close();
        exit(0);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido: ' . json_last_error_msg());

        $config = $input['config'] ?? $input;
        $titulo = $config['titulo_pagina'] ?? '';
        $descripcion = $config['descripcion_pagina'] ?? '';
        $activo = isset($config['esta_activo']) ? intval($config['esta_activo']) : 1;

        $stmt = $mysqli->prepare("INSERT INTO pisos_deportivos_config (id, titulo_pagina, descripcion_pagina, esta_activo) VALUES (1, ?, ?, ?) ON DUPLICATE KEY UPDATE titulo_pagina = VALUES(titulo_pagina), descripcion_pagina = VALUES(descripcion_pagina), esta_activo = VALUES(esta_activo)");
        $stmt->bind_param('ssi', $titulo, $descripcion, $activo);
        $ok = $stmt->execute();
        if (!$ok) throw new Exception('Error al actualizar config: ' . $stmt->error);

        echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
        $mysqli->close();
        exit(0);
    }

    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
