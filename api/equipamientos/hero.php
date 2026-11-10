<?php
// api/equipamientos/hero.php
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

// Determinar URL base
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'stadium.planbsistemas.com.ar';
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
$api_pos = strpos($script_path, '/api/');
$base_folder = ($api_pos !== false) ? substr($script_path, 0, $api_pos) : '';
$base_url = $proto . '://' . $host . $base_folder . '/';

function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return '';
    if (strpos($relativeUrl, 'http') === 0) return $relativeUrl;
    return $baseUrl . ltrim($relativeUrl, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        $res = $mysqli->query("SELECT * FROM equipamiento_profesional_hero WHERE id = 1 LIMIT 1");
        $hero = $res->fetch_assoc();
        
        $response = null;

        if ($hero) {
            // Lógica de imagen: priorizar uploads/equipamiento/equi_1.webp
            $stdImgPath = 'uploads/equipamiento/equi_' . $hero['id'] . '.webp';
            $sysPath = __DIR__ . '/../../' . $stdImgPath;
            
            $finalImage = '';
            if (file_exists($sysPath)) {
                $finalImage = buildImageUrl($stdImgPath, $base_url) . '?v=' . filemtime($sysPath);
            } elseif (!empty($hero['imagen_fondo'])) {
                $finalImage = buildImageUrl($hero['imagen_fondo'], $base_url);
            }

            // Badges list
            $badgesList = !empty($hero['badges']) ? explode(',', $hero['badges']) : [];
            $badgesList = array_map('trim', $badgesList);
            $badgesList = array_filter($badgesList);
            $badgesList = array_values($badgesList);

            // Mapeo a estructura JSON solicitada (camelCase)
            $response = [
                'id' => (string)$hero['id'],
                'etiqueta' => $hero['etiqueta'],
                'titulo' => $hero['titulo'],
                'tituloDestacado' => $hero['titulo_destacado'],
                'descripcion' => $hero['descripcion'],
                'imagenFondo' => $finalImage,
                'botonPrincipal' => $hero['boton_principal'],
                'botonSecundario' => $hero['boton_secundario'],
                'badges' => $badgesList,
                'estaActivo' => (string)$hero['esta_activo']
            ];
        }

        echo json_encode($response);
        $mysqli->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
}
?>