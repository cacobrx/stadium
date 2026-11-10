<?php
// api/tablerosmarcadores/tablas.php
// API para entregar listas/tablas de la sección Tableros y Marcadores

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
// Buscamos la posición de /api/ para obtener la raíz del sitio de forma genérica
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

// Manejo GET / POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        global $base_url;

        // Opcional: sección solicitada
        $section = isset($_GET['section']) ? $_GET['section'] : 'all';

        $resultData = [];
        // $resultData['base_url'] = $base_url;

        // Hero
        if ($section === 'all' || $section === 'hero') {
            $res = $mysqli->query("SELECT * FROM tableros_marcadores_hero WHERE id = 1 LIMIT 1");
            $hero = $res->fetch_assoc();
            if ($hero) {
                // Debug: mostrar nombre real en BD
                // $hero['imagen_fondo_db'] = $hero['imagen_fondo'];

                // Verificar si existe la imagen estandarizada tablero_{id}.webp
                $stdImgPath = 'uploads/tableros_marcadores/tablero_' . $hero['id'] . '.webp';
                $sysPath = __DIR__ . '/../../' . $stdImgPath;
                
                if (file_exists($sysPath)) {
                    $hero['imagen_fondo'] = buildImageUrl($stdImgPath, $base_url) . '?v=' . filemtime($sysPath);
                } elseif (!empty($hero['imagen_fondo'])) {
                    $hero['imagen_fondo'] = buildImageUrl($hero['imagen_fondo'], $base_url);
                }
                $hero['badges'] = !empty($hero['badges']) ? explode(',', $hero['badges']) : [];
            }
            $resultData['hero'] = $hero ?: null;
        }

        // Slides
        if ($section === 'all' || $section === 'slides') {
            $rows = [];
            $res = $mysqli->query("SELECT id, etiqueta, titulo, titulo_destacado, descripcion, imagen, estadisticas, orden, esta_activo FROM tableros_marcadores_slides WHERE config_id = 1 ORDER BY orden ASC, id ASC");
            while ($r = $res->fetch_assoc()) {
                $r['estadisticas'] = json_decode($r['estadisticas'], true) ?: [];
                // Construir URL completa para la imagen
                if (!empty($r['imagen'])) {
                    $r['imagen'] = buildImageUrl($r['imagen'], $base_url);
                }
                $rows[] = $r;
            }
            $resultData['slides'] = $rows;
        }

        // Beneficios
        if ($section === 'all' || $section === 'beneficios') {
            $rows = [];
            $res = $mysqli->query("SELECT id, titulo, descripcion, orden, esta_activo FROM tableros_marcadores_beneficios WHERE config_id = 1 ORDER BY orden ASC, id ASC");
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $resultData['beneficios'] = $rows;
        }

        // Proceso
        if ($section === 'all' || $section === 'proceso') {
            $rows = [];
            $res = $mysqli->query("SELECT id, numero, titulo, descripcion, duracion, orden, esta_activo FROM tableros_marcadores_proceso WHERE config_id = 1 ORDER BY orden ASC, numero ASC");
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $resultData['proceso'] = $rows;
        }

        // Galería
        if ($section === 'all' || $section === 'galeria') {
            $rows = [];
            $res = $mysqli->query("SELECT id, titulo, categoria, url, alt, orden, esta_activo FROM tableros_marcadores_galeria WHERE config_id = 1 ORDER BY orden ASC, id ASC");
            while ($r = $res->fetch_assoc()) {
                // Construir URL completa para la imagen
                if (!empty($r['url'])) {
                    $r['url'] = buildImageUrl($r['url'], $base_url);
                }
                $rows[] = $r;
            }
            $resultData['galeria'] = $rows;

            // categorías dinámicas
            $cats = ['Todos'];
            $res2 = $mysqli->query("SELECT DISTINCT categoria FROM tableros_marcadores_galeria WHERE config_id = 1 AND esta_activo = 1 ORDER BY categoria ASC");
            while ($c = $res2->fetch_assoc()) {
                $cats[] = $c['categoria'];
            }
            $resultData['categoriasGaleria'] = $cats;
        }

        echo json_encode(['success' => true, 'data' => $resultData]);
        $mysqli->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}

?>
