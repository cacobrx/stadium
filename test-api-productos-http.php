<?php
// test-api-productos-http.php
// Verifica la API de productos realizando una petición HTTP real con sesión compartida.

session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'tester@example.com';
$_SESSION['user_nombre'] = 'Tester API';
$_SESSION['login_time'] = time();
$sessionId = session_id();
session_write_close();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host;
$apiPath = '/stadium/api/productos.php';
$apiUrl = $baseUrl . $apiPath;

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-Test-Request: productos-api-check'
    ],
    CURLOPT_COOKIE => 'PHPSESSID=' . $sessionId,
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$errorNo = curl_errno($ch);
$errorMsg = curl_error($ch);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$decoded = json_decode($body, true);

// Reabrir la sesión para mostrar datos en la vista	session_start();

$headers = array_filter(array_map('trim', explode("\n", $rawHeaders)));

function renderStatusBadge(int $statusCode): string {
    $type = 'secondary';
    if ($statusCode >= 200 && $statusCode < 300) {
        $type = 'success';
    } elseif ($statusCode >= 400 && $statusCode < 500) {
        $type = 'warning';
    } elseif ($statusCode >= 500) {
        $type = 'danger';
    }
    return "<span class='badge bg-$type'>$statusCode</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test API Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        pre { background: #1e1e1e; color: #f1f1f1; padding: 1rem; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-4">
            <h1 class="h3">Prueba API Productos</h1>
            <p class="text-muted">Verifica la respuesta real de <code><?= htmlspecialchars($apiPath) ?></code> con sesión simulada.</p>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5">Resumen de la petición</h2>
                <ul class="mb-0">
                    <li><strong>URL:</strong> <code><?= htmlspecialchars($apiUrl) ?></code></li>
                    <li><strong>Estado:</strong> <?= renderStatusBadge($statusCode) ?></li>
                    <li><strong>cURL errno:</strong> <?= $errorNo ?></li>
                    <?php if ($errorNo !== 0): ?>
                        <li class="text-danger"><strong>Error:</strong> <?= htmlspecialchars($errorMsg) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5">Cabeceras de respuesta</h2>
                <pre><?= htmlspecialchars($rawHeaders) ?></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5">Contenido (JSON)</h2>
                <?php if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)): ?>
                    <pre><?= htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    <p class="mt-3 mb-0"><strong>Total artículos:</strong> <?= isset($decoded['productos']) && is_array($decoded['productos']) ? count($decoded['productos']) : 0 ?></p>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <p class="mb-1">La respuesta no es un JSON válido.</p>
                        <p class="small mb-0"><strong>Error JSON:</strong> <?= json_last_error_msg() ?></p>
                    </div>
                    <pre><?= htmlspecialchars($body) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h5">Datos de sesión enviados</h2>
                <pre><?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
