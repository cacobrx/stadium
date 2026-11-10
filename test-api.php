<?php
// test-api.php - Prueba directa del API

// Simular usuario logueado
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_nombre'] = 'Usuario Test';
$_SESSION['login_time'] = time();

echo "<h1>Prueba del API de Productos</h1>";

try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    include 'api/productos.php';
    $response = ob_get_clean();
    
    echo "<h2>Respuesta del API:</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<h2>✅ API funcionando correctamente</h2>";
        echo "<p>Productos encontrados: " . count($data['productos']) . "</p>";
    } else {
        echo "<h2>❌ Error en API</h2>";
        if ($data && isset($data['error'])) {
            echo "<p>Error: " . $data['error'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Excepción</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Prueba del API de Marcas</h2>";

try {
    ob_start();
    include 'api/marcas.php';
    $response = ob_get_clean();
    
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>