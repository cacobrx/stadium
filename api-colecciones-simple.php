<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Autenticación simple
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true;
}

try {
    // Usar configuración centralizada
    require_once __DIR__ . '/config/database.php';
    $mysqli = get_database_connection();
    
    $result = $mysqli->query("SELECT id, nombre FROM colecciones WHERE activa = 1 ORDER BY nombre");
    if (!$result) {
        throw new Exception('Error en consulta: ' . $mysqli->error);
    }
    
    $colecciones = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'colecciones' => $colecciones]);
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>