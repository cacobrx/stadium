<?php
// api/pisosdeportivos/datos.php
// API para entregar listas/tablas de la sección Pisos Deportivos

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

        // Hero
        if ($section === 'all' || $section === 'hero') {
            $res = $mysqli->query("SELECT * FROM pisos_deportivos_hero WHERE id = 1");
            if ($row = $res->fetch_assoc()) {
                // Construir URL completa para la imagen
                if (!empty($row['imagen_fondo'])) {
                    $row['imagen_fondo'] = buildImageUrl($row['imagen_fondo'], $base_url);
                }
                $resultData['hero'] = $row;
            } else {
                $resultData['hero'] = null;
            }
        }

        // Beneficios
        if ($section === 'all' || $section === 'beneficios') {
            $rows = [];
            $res = $mysqli->query("SELECT id, titulo, descripcion, icono, orden, esta_activo FROM pisos_deportivos_beneficios WHERE config_id = 1 ORDER BY orden ASC, id ASC");
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $resultData['beneficios'] = $rows;
        }

        // Tipos de pisos
        if ($section === 'all' || $section === 'tipos') {
            $rows = [];
            $res = $mysqli->query("SELECT * FROM pisos_deportivos_tipos WHERE config_id = 1 ORDER BY orden ASC, id ASC");
            while ($r = $res->fetch_assoc()) {
                // Decodificar JSON
                $r['caracteristicas'] = json_decode($r['caracteristicas'], true) ?: [];
                $r['aplicaciones'] = json_decode($r['aplicaciones'], true) ?: [];
                $r['certificaciones'] = json_decode($r['certificaciones'], true) ?: [];
                $r['especificaciones'] = json_decode($r['especificaciones'], true) ?: [];
                // Construir URL completa para la imagen
                if (!empty($r['imagen'])) {
                    $r['imagen'] = buildImageUrl($r['imagen'], $base_url);
                }
                $rows[] = $r;
            }
            $resultData['tiposPisos'] = $rows;
        }

        // Proceso
        if ($section === 'all' || $section === 'proceso') {
            $rows = [];
            $res = $mysqli->query("SELECT id, numero, titulo, descripcion, duracion, orden, esta_activo FROM pisos_deportivos_proceso WHERE config_id = 1 ORDER BY orden ASC, numero ASC");
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $resultData['procesoInstalacion'] = $rows;
        }

        // Galería
        if ($section === 'all' || $section === 'galeria') {
            $rows = [];
            $res = $mysqli->query("SELECT id, titulo, categoria, url, alt, orden, esta_activo FROM pisos_deportivos_galeria WHERE config_id = 1 ORDER BY orden ASC, id ASC");
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
            $res2 = $mysqli->query("SELECT DISTINCT categoria FROM pisos_deportivos_galeria WHERE config_id = 1 AND esta_activo = 1 ORDER BY categoria ASC");
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
