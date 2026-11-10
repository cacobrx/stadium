<?php
// verificar-configuracion.php - Verifica que todas las APIs usen la configuración centralizada
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Configuración de Base de Datos - Stadium</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 20px; }
        .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        pre { background: white; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f8f9fa; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔧 Verificar Configuración de Base de Datos</h1>
    
    <?php
    // 1. Verificar archivo de configuración
    echo '<div class="section">';
    echo '<h2>📁 1. Verificación de Archivo de Configuración</h2>';
    
    $config_file = __DIR__ . '/config/database.php';
    
    if (file_exists($config_file)) {
        echo '<div class="success">✅ Archivo config/database.php existe</div>';
        
        // Verificar contenido
        $config_content = file_get_contents($config_file);
        
        $checks = [
            'DB_HOST definido' => strpos($config_content, 'define(\'DB_HOST\'') !== false,
            'DB_USER definido' => strpos($config_content, 'define(\'DB_USER\'') !== false,
            'DB_PASSWORD definido' => strpos($config_content, 'define(\'DB_PASSWORD\'') !== false,
            'DB_NAME definido' => strpos($config_content, 'define(\'DB_NAME\'') !== false,
            'Función get_database_connection' => strpos($config_content, 'function get_database_connection') !== false,
            'Sin credenciales hardcodeadas' => strpos($config_content, 'new mysqli(\'localhost\', \'gus\'') === false
        ];
        
        echo '<table>';
        echo '<tr><th>Verificación</th><th>Estado</th></tr>';
        foreach ($checks as $check => $result) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($check) . '</td>';
            echo '<td class="' . ($result ? 'status-ok">✅ OK' : 'status-error">❌ Falta') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
    } else {
        echo '<div class="error">❌ Archivo config/database.php NO existe</div>';
        echo '<p>Necesitas crear el archivo de configuración. Puedes usar database-template.php como base.</p>';
    }
    echo '</div>';
    
    // 2. Probar conexión con configuración
    echo '<div class="section">';
    echo '<h2>🗄️ 2. Test de Conexión</h2>';
    
    try {
        if (file_exists($config_file)) {
            require_once $config_file;
            
            if (function_exists('get_database_connection')) {
                $mysqli = get_database_connection();
                
                echo '<div class="success">✅ Conexión exitosa con configuración centralizada</div>';
                
                // Mostrar información de la conexión
                if (function_exists('get_database_info')) {
                    $db_info = get_database_info();
                    echo '<table>';
                    echo '<tr><th>Parámetro</th><th>Valor</th></tr>';
                    foreach ($db_info as $key => $value) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($key) . '</td>';
                        echo '<td>' . htmlspecialchars($value) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<pre>';
                    echo "Host: " . (defined('DB_HOST') ? DB_HOST : 'No definido') . "\n";
                    echo "Usuario: " . (defined('DB_USER') ? DB_USER : 'No definido') . "\n";
                    echo "Base de datos: " . (defined('DB_NAME') ? DB_NAME : 'No definido') . "\n";
                    echo "Charset: " . (defined('DB_CHARSET') ? DB_CHARSET : 'No definido') . "\n";
                    echo "Host info: " . $mysqli->host_info . "\n";
                    echo "Server info: " . $mysqli->server_info . "\n";
                    echo '</pre>';
                }
                
                $mysqli->close();
            } else {
                echo '<div class="error">❌ Función get_database_connection no está definida</div>';
            }
        } else {
            echo '<div class="error">❌ No se puede probar: archivo de configuración no existe</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    echo '</div>';
    
    // 3. Verificar APIs
    echo '<div class="section">';
    echo '<h2>🔌 3. Verificación de APIs</h2>';
    
    $apis_to_check = [
        'api-categorias-simple.php' => 'API Categorías Simple',
        'api-colecciones-simple.php' => 'API Colecciones Simple',
        'api-categorias-fixed.php' => 'API Categorías Corregida',
        'api-colecciones-fixed.php' => 'API Colecciones Corregida',
        'api-test-minimo.php' => 'API Test Mínimo'
    ];
    
    echo '<table>';
    echo '<tr><th>Archivo</th><th>Existe</th><th>Usa Config</th><th>Sin Hardcode</th><th>Estado</th></tr>';
    
    foreach ($apis_to_check as $file => $name) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($name) . '</td>';
        
        $file_path = __DIR__ . '/' . $file;
        
        if (file_exists($file_path)) {
            echo '<td class="status-ok">✅</td>';
            
            $content = file_get_contents($file_path);
            
            // Verificar si usa configuración centralizada
            $uses_config = strpos($content, 'require_once __DIR__ . \'/config/database.php\'') !== false ||
                          strpos($content, 'get_database_connection()') !== false;
            
            // Verificar si NO tiene credenciales hardcodeadas
            $no_hardcode = strpos($content, 'new mysqli(\'localhost\', \'gus\'') === false;
            
            echo '<td class="' . ($uses_config ? 'status-ok">✅' : 'status-error">❌') . '</td>';
            echo '<td class="' . ($no_hardcode ? 'status-ok">✅' : 'status-error">❌') . '</td>';
            
            if ($uses_config && $no_hardcode) {
                echo '<td class="status-ok">✅ OK</td>';
            } else {
                echo '<td class="status-error">❌ Necesita corrección</td>';
            }
            
        } else {
            echo '<td class="status-warning">⚠️</td>';
            echo '<td>-</td>';
            echo '<td>-</td>';
            echo '<td class="status-warning">No existe</td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // 4. Test rápido de APIs
    echo '<div class="section">';
    echo '<h2>🧪 4. Test Rápido de APIs</h2>';
    
    $test_urls = [
        'api-categorias-simple.php',
        'api-colecciones-simple.php'
    ];
    
    echo '<table>';
    echo '<tr><th>API</th><th>Respuesta</th><th>Tiempo</th><th>Datos</th></tr>';
    
    foreach ($test_urls as $url) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($url) . '</td>';
        
        $start_time = microtime(true);
        
        try {
            // Hacer petición HTTP local
            $full_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $url;
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $response = file_get_contents($full_url, false, $context);
            $time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['success']) && $data['success']) {
                    $count = count($data['categorias'] ?? $data['colecciones'] ?? []);
                    echo '<td class="status-ok">✅ OK</td>';
                    echo '<td>' . $time . 'ms</td>';
                    echo '<td>' . $count . ' elementos</td>';
                } else {
                    echo '<td class="status-error">❌ Error JSON</td>';
                    echo '<td>' . $time . 'ms</td>';
                    echo '<td>' . htmlspecialchars(substr($response, 0, 50)) . '...</td>';
                }
            } else {
                echo '<td class="status-error">❌ Sin respuesta</td>';
                echo '<td>' . $time . 'ms</td>';
                echo '<td>-</td>';
            }
            
        } catch (Exception $e) {
            $time = round((microtime(true) - $start_time) * 1000, 2);
            echo '<td class="status-error">❌ Error</td>';
            echo '<td>' . $time . 'ms</td>';
            echo '<td>' . htmlspecialchars($e->getMessage()) . '</td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // 5. Recomendaciones
    echo '<div class="section">';
    echo '<h2>💡 5. Recomendaciones</h2>';
    
    $issues = [];
    
    if (!file_exists($config_file)) {
        $issues[] = "Crear archivo config/database.php usando database-template.php como base";
    }
    
    // Verificar cada API
    foreach ($apis_to_check as $file => $name) {
        $file_path = __DIR__ . '/' . $file;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $uses_config = strpos($content, 'get_database_connection()') !== false;
            $no_hardcode = strpos($content, 'new mysqli(\'localhost\', \'gus\'') === false;
            
            if (!$uses_config || !$no_hardcode) {
                $issues[] = "Corregir $file para usar configuración centralizada";
            }
        }
    }
    
    if (empty($issues)) {
        echo '<div class="success">✅ Todo está configurado correctamente</div>';
        echo '<p>Todas las APIs están usando la configuración centralizada de base de datos.</p>';
    } else {
        echo '<div class="warning">⚠️ Se encontraron ' . count($issues) . ' problemas:</div>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . htmlspecialchars($issue) . '</li>';
        }
        echo '</ul>';
    }
    
    echo '<div class="section">';
    echo '<h3>📋 Próximos pasos:</h3>';
    echo '<ol>';
    echo '<li>Subir todos los archivos corregidos al servidor remoto</li>';
    echo '<li>Verificar que config/database.php tenga las credenciales correctas del servidor remoto</li>';
    echo '<li>Ejecutar este script en el servidor remoto para verificar la configuración</li>';
    echo '<li>Probar las APIs individualmente</li>';
    echo '<li>Probar el formulario de productos completo</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>🔄 Acciones Rápidas</h2>
        <p>
            <a href="api-categorias-simple.php" target="_blank" style="margin-right: 10px;">🔗 Test API Categorías</a>
            <a href="api-colecciones-simple.php" target="_blank" style="margin-right: 10px;">🔗 Test API Colecciones</a>
            <a href="debug-timeout.php" target="_blank" style="margin-right: 10px;">🔍 Debug Timeout</a>
            <a href="test-sistema-productos.php" target="_blank">📝 Test Formulario</a>
        </p>
    </div>
</body>
</html>