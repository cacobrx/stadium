<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    $mysqli = get_database_connection();
    
    // URL base para las imágenes
    $base_url = "https://stadium.planbsistemas.com.ar/";
    
    // Consulta para obtener solo las marcas principales
    $sql = "SELECT id, nombre, logo 
            FROM marcas 
            WHERE principal = 1 AND activa = 1
            ORDER BY nombre ASC";
    
    $result = $mysqli->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $mysqli->error);
    }
    
    $marcas = [];
    while ($row = $result->fetch_assoc()) {
        $logo_url = null;
        if ($row['logo']) {
            // Si ya tiene la URL completa, la dejamos como está
            if (strpos($row['logo'], 'http') === 0) {
                $logo_url = $row['logo'];
            } else {
                // Si es una ruta relativa, construimos la URL completa
                $logo_url = $base_url . $row['logo'];
            }
        }
        
        $marcas[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'logo' => $logo_url,
            'slug' => strtolower(str_replace(' ', '-', $row['nombre']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $marcas,
        'message' => 'Marcas principales obtenidas correctamente'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $mysqli->close();
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Error al obtener las marcas principales: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
