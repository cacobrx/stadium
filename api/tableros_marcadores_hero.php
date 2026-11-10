<?php
// api/tableros_marcadores_hero.php
// Endpoint para obtener/actualizar la sección Hero de Tableros y Marcadores

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
        $res = $mysqli->query("SELECT * FROM tableros_marcadores_hero WHERE id = 1");
        $hero = $res->fetch_assoc();
        if (!$hero) {
            // crear registro inicial
            $mysqli->query("INSERT INTO tableros_marcadores_hero (id, config_id, etiqueta, titulo, titulo_destacado, descripcion, imagen_fondo, boton_principal, boton_principal_link, boton_secundario, boton_secundario_link, badges) VALUES (1,1,'','', '', '', '', '', '', '', '', '') ON DUPLICATE KEY UPDATE id=1");
            $res = $mysqli->query("SELECT * FROM tableros_marcadores_hero WHERE id = 1");
            $hero = $res->fetch_assoc();
        }
        echo json_encode(['success' => true, 'data' => $hero]);
        $mysqli->close();
        exit(0);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido: ' . json_last_error_msg());

        $h = $input['hero'] ?? $input;
        $etiqueta = $h['etiqueta'] ?? '';
        $titulo = $h['titulo'] ?? '';
        $titulo_destacado = $h['titulo_destacado'] ?? '';
        $descripcion = $h['descripcion'] ?? '';
        $imagen_fondo = $h['imagen_fondo'] ?? '';
        $boton_principal = $h['boton_principal'] ?? '';
        $boton_principal_link = $h['boton_principal_link'] ?? '';
        $boton_secundario = $h['boton_secundario'] ?? '';
        $boton_secundario_link = $h['boton_secundario_link'] ?? '';
        $badges = $h['badges'] ?? '';

        $stmt = $mysqli->prepare("INSERT INTO tableros_marcadores_hero (id, config_id, etiqueta, titulo, titulo_destacado, descripcion, imagen_fondo, boton_principal, boton_principal_link, boton_secundario, boton_secundario_link, badges, esta_activo) VALUES (1,1,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), titulo = VALUES(titulo), titulo_destacado = VALUES(titulo_destacado), descripcion = VALUES(descripcion), imagen_fondo = VALUES(imagen_fondo), boton_principal = VALUES(boton_principal), boton_principal_link = VALUES(boton_principal_link), boton_secundario = VALUES(boton_secundario), boton_secundario_link = VALUES(boton_secundario_link), badges = VALUES(badges)");
        $stmt->bind_param('ssssssssss', $etiqueta, $titulo, $titulo_destacado, $descripcion, $imagen_fondo, $boton_principal, $boton_principal_link, $boton_secundario, $boton_secundario_link, $badges);
        $ok = $stmt->execute();
        if (!$ok) throw new Exception('Error al guardar Hero: ' . $stmt->error);

        echo json_encode(['success' => true, 'message' => 'Hero guardado']);
        $mysqli->close();
        exit(0);
    }

    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
