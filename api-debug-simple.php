<?php
// api-debug-simple.php - Para debuggear el error específico
session_start();

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        echo json_encode(['success' => false, 'error' => 'Solo PUT permitido']);
        exit;
    }
    
    // Verificar sesión (simple)
    $_SESSION['logged_in'] = true;
    
    // Leer input
    $input_raw = file_get_contents('php://input');
    echo json_encode([
        'success' => true, 
        'debug' => [
            'input_raw_length' => strlen($input_raw),
            'input_raw_preview' => substr($input_raw, 0, 100)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'PHP Error: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
?>