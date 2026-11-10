<?php
// api/proyectos_det.php
// API para obtener detalles de proyectos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Función de compatibilidad (mantener nombre original)
function get_db_connection() {
    return get_database_connection();
}

// Obtener detalles completos de un proyecto
function obtener_detalles_proyecto($proyecto_id) {
    try {
        $conn = get_db_connection();
        
        $detalles = [];
        
        // Obtener categorías
        $sql = "SELECT 'categoria' as tipo, categoria as valor, '' as extra FROM proyecto_categorias WHERE proyecto_id = ? ORDER BY id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        // Obtener especificaciones
        $sql = "SELECT 'especificacion' as tipo, CONCAT(label, ': ', value) as valor, label as extra FROM proyecto_especificaciones WHERE proyecto_id = ? ORDER BY orden, id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        // Obtener features
        $sql = "SELECT 'feature' as tipo, CONCAT(title, ': ', description) as valor, icon as extra FROM proyecto_features WHERE proyecto_id = ? ORDER BY orden, id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        // Obtener galería
        $sql = "SELECT 'imagen' as tipo, COALESCE(alt_text, '') as valor, url as extra FROM proyectos_images WHERE proyecto_id = ? ORDER BY orden, id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $proyecto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        $conn->close();
        return $detalles;
    } catch (Exception $e) {
        error_log("Error en obtener_detalles_proyecto: " . $e->getMessage());
        return [];
    }
}

// Manejar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de proyecto requerido']);
        exit;
    }
    
    $proyecto_id = (int)$_GET['id'];
    $detalles = obtener_detalles_proyecto($proyecto_id);
    
    echo json_encode($detalles);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>