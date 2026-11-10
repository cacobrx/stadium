<?php
/**
 * Test de Integración API Correo Argentino (PaqAr) v2.0
 * 
 * Este script permite probar la conectividad y validación de credenciales
 * con la API de Correo Argentino.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- CONFIGURACIÓN ---
// Sustituir con las credenciales proporcionadas por Correo Argentino
$config = [
    'api_key'      => 'TU_API_KEY_AQUI',
    'agreement_id' => 'TU_AGREEMENT_ID_AQUI',
    'base_url'     => 'https://apitest.correoargentino.com.ar/paqar/v1/' // Sandbox
];

// Permitir pasar credenciales por GET para pruebas rápidas
if (isset($_GET['api_key'])) $config['api_key'] = $_GET['api_key'];
if (isset($_GET['agreement'])) $config['agreement_id'] = $_GET['agreement'];

$action = $_GET['action'] ?? 'auth';

function call_paqar_api($endpoint, $method = 'GET', $data = null, $config) {
    $url = $config['base_url'] . $endpoint;
    $ch = curl_init();
    
    $headers = [
        "Authorization: Apikey " . $config['api_key'],
        "agreement: " . $config['agreement_id'],
        "Content-Type: application/json"
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true); // Retornar cabeceras para debug
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'url' => $url,
        'http_code' => $http_code,
        'headers' => $header,
        'body' => json_decode($body, true) ?: $body,
        'error' => $error
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test API Correo Argentino</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 1000px; margin: 20px auto; padding: 0 20px; background: #f4f4f4; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #005a9c; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status-ok { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .btn { display: inline-block; background: #005a9c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .btn:hover { background: #004070; }
        .config-info { font-size: 0.9em; color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>

    <h1>🚀 Test Enlace Correo Argentino (PaqAr)</h1>

    <div class="card config-info">
        <strong>Configuración Actual:</strong><br>
        Base URL: <?php echo $config['base_url']; ?><br>
        API Key: <?php echo $config['api_key'] === 'TU_API_KEY_AQUI' ? '<span class="status-error">NO CONFIGURADO</span>' : 'Seteada'; ?><br>
        Agreement ID: <?php echo $config['agreement_id'] === 'TU_AGREEMENT_ID_AQUI' ? '<span class="status-error">NO CONFIGURADO</span>' : $config['agreement_id']; ?>
    </div>

    <nav>
        <a href="?action=auth" class="btn">Validar Credenciales (Auth)</a>
        <a href="?action=test_order" class="btn">Simular Alta de Orden</a>
    </nav>

    <div class="card">
        <?php
        if ($action === 'auth') {
            echo "<h2>1. Pruebo endpoint de Autenticación</h2>";
            $result = call_paqar_api('auth', 'GET', null, $config);
            
            echo "<p>URL: <code>{$result['url']}</code></p>";
            echo "<p>Código HTTP: <strong>{$result['http_code']}</strong></p>";
            
            if ($result['http_code'] == 204) {
                echo "<p class='status-ok'>✅ ¡Éxito! Las credenciales son válidas.</p>";
            } else {
                echo "<p class='status-error'>❌ Fallo de autenticación o error en el servidor.</p>";
            }
            
            echo "<h3>Respuesta completa:</h3>";
            echo "<pre>" . print_r($result['body'], true) . "</pre>";
        } 
        
        elseif ($action === 'test_order') {
            echo "<h2>2. Simulación de Alta de Orden (Shipment)</h2>";
            echo "<p>Enviando datos de prueba para verificar estructura...</p>";
            
            $test_data = [
                "idSeller" => "TEST-001",
                "trackingNumber" => "TRK" . time(),
                "senderData" => [
                    "businessName" => "Tienda Stadium",
                    "address" => [
                        "streetName" => "Calle Falsa",
                        "streetNumber" => "123",
                        "cityName" => "CABA",
                        "state" => "C",
                        "zipCode" => "1407"
                    ]
                ],
                "shippingData" => [
                    "name" => "Juan Pérez",
                    "email" => "juan@example.com",
                    "address" => [
                        "streetName" => "Av. Siempre Viva",
                        "streetNumber" => "742",
                        "cityName" => "Rosario",
                        "state" => "S",
                        "zipCode" => "2000"
                    ]
                ],
                "parcels" => [
                    [
                        "dimensions" => ["height" => "10", "width" => "20", "depth" => "30"],
                        "productWeight" => "1000",
                        "declaredValue" => "1500"
                    ]
                ],
                "deliveryType" => "homeDelivery",
                "serviceType" => "CP"
            ];
            
            $result = call_paqar_api('orders', 'POST', $test_data, $config);
            
            echo "<p>URL: <code>{$result['url']}</code></p>";
            echo "<p>Código HTTP: <strong>{$result['http_code']}</strong></p>";
            
            echo "<h3>Respuesta:</h3>";
            echo "<pre>" . print_r($result['body'], true) . "</pre>";
            
            if (!empty($result['error'])) {
                echo "<h3>Error de CURL:</h3>";
                echo "<pre>{$result['error']}</pre>";
            }
        }
        ?>
    </div>

    <div class="card">
        <h3>Instrucciones</h3>
        <ol>
            <li>Edita este archivo (<code>/stadium/test_correo_argentino.php</code>) y coloca tu <strong>API Key</strong> y <strong>Agreement ID</strong>.</li>
            <li>Asegúrate de que el servidor tenga salida a internet para conectar con <code>correoargentino.com.ar</code>.</li>
            <li>Usa el botón "Validar Credenciales" para confirmar que el enlace funciona.</li>
        </ol>
    </div>

</body>
</html>
