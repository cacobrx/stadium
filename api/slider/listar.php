<?php
// api/slider/listar.php
// API REST para listar los elementos del slider para el sitio público

// ========== DEBUG MODE ==========
// Activar errores para ver qué está fallando
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers de debug - SE VEN EN PESTAÑA NETWORK > HEADERS
header('X-Debug-Step: 1-Inicio');
header('X-Debug-Server-Name: ' . ($_SERVER['SERVER_NAME'] ?? 'NULL'));
header('X-Debug-HTTP-Host: ' . ($_SERVER['HTTP_HOST'] ?? 'NULL'));
header('X-Debug-PHP-Version: ' . phpversion());

// Incluir configuración ANTES de los otros headers para capturar datos
require_once __DIR__ . '/../../config/database.php';

// Ahora enviamos los datos de conexión como headers
header('X-Debug-Step: 2-Config-Loaded');
header('X-Debug-DB-Host: ' . (defined('DB_HOST') ? DB_HOST : 'NOT_DEFINED'));
header('X-Debug-DB-User: ' . (defined('DB_USER') ? DB_USER : 'NOT_DEFINED'));
header('X-Debug-DB-Name: ' . (defined('DB_NAME') ? DB_NAME : 'NOT_DEFINED'));
header('X-Debug-Base-URL: ' . (defined('BASE_URL_SLIDER') ? BASE_URL_SLIDER : 'NOT_DEFINED'));
header('X-Debug-Is-Local: ' . (isset($is_local) ? ($is_local ? 'true' : 'false') : 'NOT_SET'));

// ========== FIN DEBUG ==========

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Conexión a la base de datos
    $mysqli = get_database_connection();
    
    // Consultar solo los sliders activos ordenados por el campo orden
    $query = "SELECT * FROM slider WHERE activo = 1 ORDER BY orden ASC";
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }
    
    $sliders = [];
    while ($row = $result->fetch_assoc()) {
        // Generar el link completo de la imagen
        if (!empty($row['imagen'])) {
            $row['imagen'] = BASE_URL_SLIDER . $row['imagen'];
        }
        
        // Generar el link completo de la imagen mobile
        if (!empty($row['imagen_mobile'])) {
            $row['imagen_mobile'] = BASE_URL_SLIDER . $row['imagen_mobile'];
        }
        
        // Convertir etiquetas de string separado por comas a array
        if (!empty($row['etiquetas'])) {
            $row['etiquetas'] = array_map('trim', explode(',', $row['etiquetas']));
        } else {
            $row['etiquetas'] = [];
        }
        
        $sliders[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $sliders
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $mysqli->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
