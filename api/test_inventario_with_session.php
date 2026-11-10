<?php
// Test con sesión simulada
session_start();

// Simular usuario logueado
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_nombre'] = 'Test User';
$_SESSION['login_time'] = time();

// Ahora incluir el API
echo "=== Probando API de inventario con sesión activa ===\n\n";

// Simular REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capturar la salida
ob_start();
include __DIR__ . '/inventario.php';
$output = ob_get_clean();

echo "Output del API:\n";
echo $output;
echo "\n\n";

// Intentar decodificar como JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "ERROR: No se pudo decodificar como JSON\n";
    echo "Error JSON: " . json_last_error_msg() . "\n";
} else {
    echo "✓ JSON válido\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    if (isset($json['inventario'])) {
        echo "Registros: " . count($json['inventario']) . "\n";
    }
    if (isset($json['error'])) {
        echo "Error: " . $json['error'] . "\n";
    }
}
