<?php
// api/novedades/detalle.php
// Endpoint para obtener detalle de una novedad por slug + relacionadas

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Solo GET.', 'code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// URL base para las imágenes en producción
$base_url = "https://stadium.planbsistemas.com.ar/";

function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return null;
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
    // Normalizar y forzar carpeta uploads/novedades si no está presente
    $relativeUrl = ltrim($relativeUrl, '/');
    if (strpos($relativeUrl, 'uploads/novedades') === false) {
        $relativeUrl = 'uploads/novedades/' . $relativeUrl;
    }
    return $baseUrl . $relativeUrl;
}

function getConnection() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function formatNovedadDetalle($row) {
    global $base_url;
    $imagen = buildImageUrl($row['imagen'], $base_url);

    $imagenes = null;
    if (!empty($row['imagenes'])) {
        $decoded = json_decode($row['imagenes'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $imagenes = array_values(array_filter(array_map(function($i) use ($base_url) {
                return buildImageUrl($i, $base_url);
            }, $decoded)));
        }
    }

    $etiquetas = [];
    if (!empty($row['etiquetas'])) {
        $decodedTags = json_decode($row['etiquetas'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTags)) {
            $etiquetas = $decodedTags;
        }
    }

    return [
        'id' => $row['id'],
        'tipo' => $row['tipo'],
        'titulo' => $row['titulo'],
        'subtitulo' => $row['subtitulo'],
        'descripcion' => $row['descripcion'],
        'contenido' => $row['contenido'],
        'imagen' => $imagen,
        'imagenes' => $imagenes,
        'fecha' => $row['fecha'],
        'autor' => $row['autor'],
        'ubicacion' => $row['ubicacion'],
        'destacado' => (bool)$row['destacado'],
        'etiquetas' => $etiquetas,
        'slug' => $row['slug']
    ];
}

try {
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetro slug es requerido', 'code' => 'MISSING_SLUG']);
        exit;
    }

    $mysqli = getConnection();

    $stmt = $mysqli->prepare("SELECT * FROM novedades WHERE slug = ? AND activo = 1 LIMIT 1");
    if (!$stmt) throw new Exception('Error preparando consulta: ' . $mysqli->error);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Novedad no encontrada', 'code' => 'NOT_FOUND']);
        exit;
    }

    $row = $res->fetch_assoc();
    $data = formatNovedadDetalle($row);

    // Obtener relacionadas (mismo tipo o compartir la primera etiqueta)
    $related = [];
    $relatedLimit = 5;

    $firstTag = null;
    if (!empty($row['etiquetas'])) {
        $tagsDecoded = json_decode($row['etiquetas'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tagsDecoded) && count($tagsDecoded) > 0) {
            $firstTag = $tagsDecoded[0];
        }
    }

    $relatedSql = "SELECT id, tipo, titulo, subtitulo, descripcion, imagen, fecha, destacado, etiquetas, slug FROM novedades WHERE activo = 1 AND id != ? AND (tipo = ?";
    $params = [$row['id'], $row['tipo']];
    $types = 'is';

    if ($firstTag) {
        $relatedSql .= " OR JSON_SEARCH(etiquetas, 'one', ?) IS NOT NULL";
        $params[] = $firstTag;
        $types .= 's';
    }

    $relatedSql .= ") ORDER BY destacado DESC, fecha DESC LIMIT ?";
    $params[] = $relatedLimit;
    $types .= 'i';

    $stmtRel = $mysqli->prepare($relatedSql);
    if ($stmtRel) {
        $stmtRel->bind_param($types, ...$params);
        $stmtRel->execute();
        $rRes = $stmtRel->get_result();
        while ($r = $rRes->fetch_assoc()) {
            // Formatear cada relacionada
            $img = buildImageUrl($r['imagen'], $base_url);
            $et = [];
            if (!empty($r['etiquetas'])) {
                $d = json_decode($r['etiquetas'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($d)) $et = $d;
            }
            $related[] = [
                'id' => $r['id'],
                'tipo' => $r['tipo'],
                'titulo' => $r['titulo'],
                'subtitulo' => $r['subtitulo'],
                'descripcion' => $r['descripcion'],
                'imagen' => $img,
                'fecha' => $r['fecha'],
                'destacado' => (bool)$r['destacado'],
                'etiquetas' => $et,
                'slug' => $r['slug']
            ];
        }
    }

    $mysqli->close();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'relacionadas' => $related,
        'message' => 'Novedad obtenida correctamente'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log('Error en API novedades detalle: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor', 'details' => $e->getMessage(), 'code' => 'INTERNAL_SERVER_ERROR']);
}

?>
