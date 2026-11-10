<?php
// debug-timeout.php - Diagnóstico específico para timeouts en APIs
session_start();

// Configurar timeout más corto para debug
ini_set('max_execution_time', 30);

// Simular sesión
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Timeout - Stadium APIs</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e8; color: #2e7d32; }
        .warning { background: #fff3e0; color: #ef6c00; }
        .info { background: #e3f2fd; color: #1565c0; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .test-btn { padding: 8px 16px; margin: 5px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔍 Debug Timeout - Stadium APIs</h1>
    
    <?php
    echo "<div class='section info'>";
    echo "<h2>📊 Información del Servidor</h2>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "\n";
    echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
    echo "Memory Limit: " . ini_get('memory_limit') . "\n";
    echo "Error Reporting: " . error_reporting() . "\n";
    echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
    echo "MySQL Extension: " . (extension_loaded('mysqli') ? 'Disponible' : 'NO disponible') . "\n";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "Current Dir: " . __DIR__ . "\n";
    echo "</pre>";
    echo "</div>";
    
    // Test 1: Verificar archivos uno por uno
    echo "<div class='section'>";
    echo "<h2>📁 Test 1: Verificación Individual de Archivos</h2>";
    
    $archivos = [
        'config/database.php',
        'includes/auth.php', 
        'api/categorias.php',
        'api/colecciones.php',
        'api-categorias-simple.php',
        'api-colecciones-simple.php'
    ];
    
    foreach ($archivos as $archivo) {
        $ruta = __DIR__ . '/' . $archivo;
        echo "<strong>$archivo:</strong> ";
        
        if (file_exists($ruta)) {
            $size = filesize($ruta);
            echo "<span class='success'>✅ Existe ($size bytes)</span><br>";
            
            // Verificar sintaxis PHP
            if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php') {
                $output = [];
                $return_var = 0;
                exec("php -l " . escapeshellarg($ruta) . " 2>&1", $output, $return_var);
                
                if ($return_var === 0) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;Sintaxis PHP: <span class='success'>✅ OK</span><br>";
                } else {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;Sintaxis PHP: <span class='error'>❌ " . implode(' ', $output) . "</span><br>";
                }
            }
        } else {
            echo "<span class='error'>❌ No existe</span><br>";
        }
    }
    echo "</div>";
    
    // Test 2: Probar conexión a BD paso a paso
    echo "<div class='section'>";
    echo "<h2>🗄️ Test 2: Conexión a Base de Datos Paso a Paso</h2>";
    
    try {
        echo "<strong>Paso 1:</strong> Cargando config/database.php... ";
        if (file_exists(__DIR__ . '/config/database.php')) {
            require_once __DIR__ . '/config/database.php';
            echo "<span class='success'>✅ Cargado</span><br>";
            
            echo "<strong>Paso 2:</strong> Verificando función get_database_connection... ";
            if (function_exists('get_database_connection')) {
                echo "<span class='success'>✅ Función existe</span><br>";
                
                echo "<strong>Paso 3:</strong> Intentando conexión... ";
                $start_time = microtime(true);
                
                try {
                    $mysqli = get_database_connection();
                    $connection_time = round((microtime(true) - $start_time) * 1000, 2);
                    
                    if ($mysqli->connect_errno) {
                        throw new Exception('Error MySQL: ' . $mysqli->connect_error);
                    }
                    
                    echo "<span class='success'>✅ Conectado en {$connection_time}ms</span><br>";
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;Host: " . $mysqli->host_info . "<br>";
                    
                    echo "<strong>Paso 4:</strong> Probando query simple... ";
                    $start_time = microtime(true);
                    $result = $mysqli->query("SELECT 1 as test");
                    $query_time = round((microtime(true) - $start_time) * 1000, 2);
                    
                    if ($result) {
                        echo "<span class='success'>✅ Query OK en {$query_time}ms</span><br>";
                        
                        echo "<strong>Paso 5:</strong> Verificando tablas... ";
                        $tables_result = $mysqli->query("SHOW TABLES LIKE 'categorias'");
                        if ($tables_result && $tables_result->num_rows > 0) {
                            echo "<span class='success'>✅ Tabla categorias existe</span><br>";
                            
                            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM categorias");
                            if ($count_result) {
                                $count = $count_result->fetch_assoc()['count'];
                                echo "&nbsp;&nbsp;&nbsp;&nbsp;Registros en categorias: $count<br>";
                            }
                        } else {
                            echo "<span class='error'>❌ Tabla categorias no existe</span><br>";
                        }
                        
                        $tables_result = $mysqli->query("SHOW TABLES LIKE 'colecciones'");
                        if ($tables_result && $tables_result->num_rows > 0) {
                            echo "&nbsp;&nbsp;&nbsp;&nbsp;<span class='success'>✅ Tabla colecciones existe</span><br>";
                            
                            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM colecciones");
                            if ($count_result) {
                                $count = $count_result->fetch_assoc()['count'];
                                echo "&nbsp;&nbsp;&nbsp;&nbsp;Registros en colecciones: $count<br>";
                            }
                        } else {
                            echo "&nbsp;&nbsp;&nbsp;&nbsp;<span class='error'>❌ Tabla colecciones no existe</span><br>";
                        }
                    } else {
                        echo "<span class='error'>❌ Error en query: " . $mysqli->error . "</span><br>";
                    }
                    
                    $mysqli->close();
                    
                } catch (Exception $e) {
                    $connection_time = round((microtime(true) - $start_time) * 1000, 2);
                    echo "<span class='error'>❌ Error después de {$connection_time}ms: " . $e->getMessage() . "</span><br>";
                }
                
            } else {
                echo "<span class='error'>❌ Función no existe</span><br>";
            }
        } else {
            echo "<span class='error'>❌ Archivo no encontrado</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
    }
    echo "</div>";
    
    // Test 3: Ejecutar APIs directamente (include)
    echo "<div class='section'>";
    echo "<h2>🔌 Test 3: Ejecutar APIs Directamente</h2>";
    
    $apis = [
        'api-categorias-simple.php' => 'API Categorías Simple',
        'api-colecciones-simple.php' => 'API Colecciones Simple'
    ];
    
    foreach ($apis as $archivo => $nombre) {
        echo "<h3>$nombre</h3>";
        
        if (!file_exists(__DIR__ . '/' . $archivo)) {
            echo "<span class='error'>❌ Archivo no existe</span><br>";
            continue;
        }
        
        echo "<strong>Ejecutando directamente...</strong><br>";
        
        // Capturar output
        ob_start();
        $start_time = microtime(true);
        
        try {
            // Limpiar variables globales que puedan interferir
            unset($_GET['test']);
            
            // Incluir el archivo
            include __DIR__ . '/' . $archivo;
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            $output = ob_get_clean();
            
            echo "<span class='success'>✅ Ejecutado en {$execution_time}ms</span><br>";
            
            // Analizar output
            echo "<strong>Output:</strong><br>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . (strlen($output) > 500 ? '...' : '') . "</pre>";
            
            // Verificar si es JSON válido
            $json_data = json_decode($output, true);
            if ($json_data) {
                echo "<strong>JSON válido:</strong> <span class='success'>✅</span><br>";
                if (isset($json_data['success'])) {
                    if ($json_data['success']) {
                        $count = count($json_data['categorias'] ?? $json_data['colecciones'] ?? []);
                        echo "<strong>Éxito:</strong> <span class='success'>✅ $count elementos</span><br>";
                    } else {
                        echo "<strong>Error en API:</strong> <span class='error'>" . ($json_data['error'] ?? 'Error desconocido') . "</span><br>";
                    }
                }
            } else {
                echo "<strong>JSON válido:</strong> <span class='error'>❌ No es JSON válido</span><br>";
            }
            
        } catch (Exception $e) {
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            ob_end_clean();
            echo "<span class='error'>❌ Excepción después de {$execution_time}ms: " . $e->getMessage() . "</span><br>";
        } catch (Error $e) {
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            ob_end_clean();
            echo "<span class='error'>❌ Error fatal después de {$execution_time}ms: " . $e->getMessage() . "</span><br>";
        }
        
        echo "<hr>";
    }
    echo "</div>";
    
    // Test 4: Verificar logs de error
    echo "<div class='section'>";
    echo "<h2>📋 Test 4: Logs de Error PHP</h2>";
    
    $error_log = ini_get('error_log');
    echo "<strong>Error log configurado:</strong> " . ($error_log ?: 'Default') . "<br>";
    
    if ($error_log && file_exists($error_log)) {
        echo "<strong>Últimas líneas del error log:</strong><br>";
        $lines = file($error_log);
        $recent_lines = array_slice($lines, -10);
        echo "<pre>" . htmlspecialchars(implode('', $recent_lines)) . "</pre>";
    } else {
        echo "<span class='warning'>⚠️ No se puede acceder al log de errores</span><br>";
    }
    echo "</div>";
    
    // Test 5: Información de red
    echo "<div class='section'>";
    echo "<h2>🌐 Test 5: Información de Red y Headers</h2>";
    echo "<pre>";
    echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "\n";
    echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "\n";
    echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'No definido') . "\n";
    echo "Remote Addr: " . ($_SERVER['REMOTE_ADDR'] ?? 'No definido') . "\n";
    echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'No definido') . "\n";
    echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'Sí' : 'No') . "\n";
    echo "</pre>";
    echo "</div>";
    ?>
    
    <!-- Botones de prueba JavaScript -->
    <div class="section">
        <h2>🧪 Test 6: Pruebas JavaScript con Diferentes Timeouts</h2>
        <button class="test-btn" onclick="testAPI('api-categorias-simple.php', 5000)">Test Categorías (5s)</button>
        <button class="test-btn" onclick="testAPI('api-colecciones-simple.php', 5000)">Test Colecciones (5s)</button>
        <button class="test-btn" onclick="testAPI('api-categorias-simple.php', 2000)">Test Categorías (2s)</button>
        <button class="test-btn" onclick="testAPI('api-colecciones-simple.php', 2000)">Test Colecciones (2s)</button>
        
        <div id="js-results" style="margin-top: 15px;"></div>
    </div>

    <script>
        function testAPI(api, timeout) {
            const resultsDiv = document.getElementById('js-results');
            const startTime = Date.now();
            
            resultsDiv.innerHTML += `<div>🔄 Probando ${api} con timeout ${timeout}ms...</div>`;
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);
            
            fetch(api, {
                signal: controller.signal,
                method: 'GET'
            })
            .then(response => {
                clearTimeout(timeoutId);
                const duration = Date.now() - startTime;
                
                if (response.ok) {
                    return response.json().then(data => {
                        resultsDiv.innerHTML += `<div style="color: green;">✅ ${api}: ${duration}ms - ${data.success ? 'Éxito' : 'Error en datos'}</div>`;
                    });
                } else {
                    resultsDiv.innerHTML += `<div style="color: orange;">⚠️ ${api}: ${duration}ms - HTTP ${response.status}</div>`;
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                const duration = Date.now() - startTime;
                
                if (error.name === 'AbortError') {
                    resultsDiv.innerHTML += `<div style="color: red;">❌ ${api}: TIMEOUT después de ${duration}ms</div>`;
                } else {
                    resultsDiv.innerHTML += `<div style="color: red;">❌ ${api}: ERROR después de ${duration}ms - ${error.message}</div>`;
                }
            });
        }
        
        // Auto-ejecutar tests después de cargar
        setTimeout(() => {
            testAPI('api-categorias-simple.php', 3000);
        }, 2000);
    </script>
</body>
</html>