<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/database.php';

// Autenticación simple
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Permitir acceso temporal para debug
    $_SESSION['logged_in'] = true;
}

try {
    $db = get_database_connection();

    $sql = "SELECT id, nombre FROM marcas WHERE activa = 1 ORDER BY nombre";
    $result = $db->query($sql);

    if (!$result) {
        throw new Exception('Error en consulta: ' . $db->error);
    }
    
    $marcas = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    echo json_encode(['success' => true, 'marcas' => $marcas]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>