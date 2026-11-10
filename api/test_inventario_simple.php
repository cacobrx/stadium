<?php
// Test directo sin autenticación para debugging
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $mysqli = get_database_connection();
    
    $query = "
        SELECT 
            pv.id,
            pv.producto_id,
            pv.sku_variante,
            pv.nombre as variante_nombre,
            pv.stock,
            pv.precio,
            pv.activa,
            pv.fecha_actualizacion,
            p.nombre as producto_nombre,
            p.sku as producto_sku,
            m.nombre as marca_nombre,
            pi.url as imagen_producto
        FROM producto_variantes pv
        INNER JOIN productos p ON pv.producto_id = p.id
        LEFT JOIN marcas m ON p.marca_id = m.id
        LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
        ORDER BY p.nombre, pv.nombre
    ";
    
    $result = $mysqli->query($query);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Error en consulta: ' . $mysqli->error
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $inventario = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($inventario),
        'inventario' => $inventario
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
