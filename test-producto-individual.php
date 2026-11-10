<?php
session_start();

// Simular usuario logueado para prueba
$_SESSION['logged_in'] = true;

echo "<h2>Test de Producto Individual</h2>";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    echo "<h3>Probando producto ID: $id</h3>";
    
    // Hacer request al API
    $url = "http://localhost:8080/stadium/api-productos-simple.php?id=$id";
    
    // Inicializar contexto para mantener cookies de sesión
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: PHPSESSID=" . session_id() . "\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    echo "<h4>Respuesta del API:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "<h4>Datos del producto:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($data['producto'] as $campo => $valor) {
            echo "<tr><td><strong>$campo</strong></td><td>" . htmlspecialchars($valor ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: red;'>Error: " . ($data['error'] ?? 'Respuesta inválida') . "</div>";
    }
} else {
    echo "<p>Agrega ?id=X a la URL para probar un producto específico</p>";
    
    // Mostrar lista de productos disponibles
    $url = "http://localhost:8080/stadium/api-productos-simple.php";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Cookie: PHPSESSID=" . session_id() . "\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if ($data && $data['success'] && !empty($data['productos'])) {
        echo "<h3>Productos disponibles para probar:</h3>";
        echo "<ul>";
        foreach ($data['productos'] as $producto) {
            echo "<li><a href='?id={$producto['id']}'>{$producto['nombre']} (ID: {$producto['id']})</a></li>";
        }
        echo "</ul>";
    }
}
?>