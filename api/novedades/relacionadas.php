<?php
// api/novedades/relacionadas.php
// Endpoint: GET /api/novedades/relacionadas.php?slug={slug}&limit={limit}

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

$base_url = "https://stadium.planbsistemas.com.ar/";

function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return null;
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
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

function formatearNovedadSimple($novedad) {
    global $base_url;
    $imagenUrl = buildImageUrl($novedad['imagen'] ?? null, $base_url);

    $etiquetas = [];
    if (!empty($novedad['etiquetas'])) {
        $decoded = json_decode($novedad['etiquetas'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $etiquetas = $decoded;
        }
    }

    return [
        'id' => $novedad['id'],
        'tipo' => $novedad['tipo'],
        'titulo' => $novedad['titulo'],
        'subtitulo' => $novedad['subtitulo'],
        'descripcion' => $novedad['descripcion'],
        'imagen' => $imagenUrl,
        'fecha' => $novedad['fecha'],
        'destacado' => (bool)$novedad['destacado'],
        'etiquetas' => $etiquetas,
        'slug' => $novedad['slug']
    ];
}

try {
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetro slug es requerido', 'code' => 'MISSING_SLUG']);
        exit;
    }

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    if ($limit < 1) $limit = 1;
    if ($limit > 100) $limit = 100;

    $mysqli = getConnection();

    // Obtener la novedad origen por slug
    $stmt = $mysqli->prepare("SELECT id, tipo FROM novedades WHERE slug = ? AND activo = 1 LIMIT 1");
    if (!$stmt) throw new Exception('Error preparando consulta: ' . $mysqli->error);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Novedad no encontrada', 'code' => 'NOT_FOUND']);
        exit;
    }

    $origin = $res->fetch_assoc();
    $originId = $origin['id'];
    $originTipo = $origin['tipo'];

    // Obtener relacionadas: mismas tipo, distinto id
    $sql = "SELECT id, tipo, titulo, subtitulo, descripcion, imagen, fecha, destacado, etiquetas, slug FROM novedades WHERE activo = 1 AND tipo = ? AND id != ? ORDER BY destacado DESC, fecha DESC LIMIT ?";
    $stmt2 = $mysqli->prepare($sql);
    if (!$stmt2) throw new Exception('Error preparando consulta relacionadas: ' . $mysqli->error);
    $stmt2->bind_param('sii', $originTipo, $originId, $limit);
    $stmt2->execute();
    $rRes = $stmt2->get_result();

    $data = [];
    while ($r = $rRes->fetch_assoc()) {
        $data[] = formatearNovedadSimple($r);
    }

    $mysqli->close();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'Novedades relacionadas obtenidas correctamente'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log('Error en API novedades relacionadas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor', 'details' => $e->getMessage(), 'code' => 'INTERNAL_SERVER_ERROR']);
}

?>
