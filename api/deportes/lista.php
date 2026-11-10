<?php
/**
 * API de Deportes - Lista pública
 * Retorna nombre, imagen y principal de todos los deportes activos
 * 
 * GET /api/deportes/lista.php - Lista todos los deportes activos
 * GET /api/deportes/lista.php?principales=1 - Solo deportes principales
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $mysqli = get_database_connection();
    
    // URL base para las imágenes
    $base_url = "https://stadium.planbsistemas.com.ar/";
    
    // Base query
    $sql = 'SELECT id, nombre, imagen, principal FROM deportes WHERE activo = 1';
    
    // Si se solicitan solo los principales
    if (isset($_GET['principales']) && $_GET['principales'] == '1') {
        $sql .= ' AND principal = 1';
    }
    
    $sql .= ' ORDER BY principal DESC, nombre ASC';
    
    $result = $mysqli->query($sql);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $mysqli->error);
    }
    
    $deportes = [];
    
    while ($row = $result->fetch_assoc()) {
        $imagen_url = null;
        if ($row['imagen']) {
            $imagen_url = $base_url . 'uploads/deportes/' . $row['imagen'];
        }
        
        $deportes[] = [
            'id' => intval($row['id']),
            'nombre' => $row['nombre'],
            'imagen' => $imagen_url,
            'principal' => intval($row['principal']) === 1
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($deportes),
        'deportes' => $deportes
    ]);
    
    $mysqli->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
