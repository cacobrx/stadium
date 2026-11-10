<?php
// api/novedades/destacadas.php
// Endpoint: GET /api/novedades/destacadas.php?limit={limit}

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
		'ubicacion' => $novedad['ubicacion'] ?? null,
		'destacado' => (bool)$novedad['destacado'],
		'etiquetas' => $etiquetas,
		'slug' => $novedad['slug']
	];
}

try {
	$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
	if ($limit < 1) $limit = 1;
	if ($limit > 100) $limit = 100;

	$mysqli = getConnection();

	$sql = "SELECT id, tipo, titulo, subtitulo, descripcion, imagen, fecha, ubicacion, destacado, etiquetas, slug FROM novedades WHERE activo = 1 AND destacado = 1 ORDER BY fecha DESC LIMIT ?";
	$stmt = $mysqli->prepare($sql);
	if (!$stmt) throw new Exception('Error preparando consulta: ' . $mysqli->error);
	$stmt->bind_param('i', $limit);
	$stmt->execute();
	$result = $stmt->get_result();

	$data = [];
	while ($row = $result->fetch_assoc()) {
		$data[] = formatearNovedadSimple($row);
	}

	$mysqli->close();

	echo json_encode([
		'success' => true,
		'data' => $data,
		'message' => 'Novedades destacadas obtenidas correctamente'
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
	error_log('Error en API novedades destacadas: ' . $e->getMessage());
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Error interno del servidor', 'details' => $e->getMessage(), 'code' => 'INTERNAL_SERVER_ERROR']);
}

?>
