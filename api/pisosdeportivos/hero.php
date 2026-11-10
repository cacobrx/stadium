<?php
// api/pisosdeportivos/hero.php
// API para entregar información del elemento Hero de Pisos Deportivos

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getConnection() {
    return get_database_connection();
}

// Determinar URL base dinámicamente
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'stadium.planbsistemas.com.ar';
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
$api_pos = strpos($script_path, '/api/');
$base_folder = ($api_pos !== false) ? substr($script_path, 0, $api_pos) : '';
$base_url = $proto . '://' . $host . $base_folder . '/';

// Función para construir URL completa de imagen
function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return '';
    // Si ya es absoluta, devolverla tal cual
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
    // Quitar slash inicial si existe para evitar doble slash
    $relativeUrl = ltrim($relativeUrl, '/');
    return $baseUrl . $relativeUrl;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        $heroData = null;
        $res = $mysqli->query("SELECT * FROM pisos_deportivos_hero WHERE id = 1");
        
        if ($row = $res->fetch_assoc()) {
            // Construir URL completa para la imagen
            if (!empty($row['imagen_fondo'])) {
                $row['imagen_fondo'] = buildImageUrl($row['imagen_fondo'], $base_url);
            }
            $heroData = $row;
        }

        echo json_encode(['success' => true, 'data' => $heroData]);
        $mysqli->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
