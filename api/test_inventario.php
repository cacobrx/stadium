<?php
// Script de diagnóstico para verificar la estructura de producto_variantes
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$mysqli = get_database_connection();

try {
    // Verificar estructura de la tabla
    $result = $mysqli->query("DESCRIBE producto_variantes");
    
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'columns' => $columns,
            'message' => 'Estructura de la tabla producto_variantes'
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo describir la tabla: ' . $mysqli->error
        ], JSON_PRETTY_PRINT);
    }
    
    // Contar registros
    $result = $mysqli->query("SELECT COUNT(*) as total FROM producto_variantes");
    $count = $result->fetch_assoc();
    echo "\n\nTotal de registros: " . $count['total'];
    
    // Mostrar algunos registros
    if ($count['total'] > 0) {
        $result = $mysqli->query("SELECT * FROM producto_variantes LIMIT 5");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo "\n\nPrimeros registros:\n";
        echo json_encode($rows, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
