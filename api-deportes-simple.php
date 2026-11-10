<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/database.php';

// Autenticación simple
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true;
}

try {
    $db = get_database_connection();

    $sql = "SELECT id, nombre FROM deportes WHERE activo = 1 ORDER BY nombre";
    $result = $db->query($sql);
    
    $result = $db->query("SELECT id, nombre FROM deportes WHERE activo = 1 ORDER BY nombre");
    if (!$result) {
        throw new Exception('Error en consulta: ' . $db->error);
    }
    
    $deportes = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'deportes' => $deportes]);
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>