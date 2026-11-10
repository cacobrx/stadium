<?php
// diagnostico-remoto.php - Script para diagnosticar problemas en el sitio remoto
session_start();

// Simular sesión para las pruebas
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_nombre'] = 'Usuario de Prueba';
$_SESSION['login_time'] = time();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Stadium - Sitio Remoto</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: bold;
        }
        
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #007bff;
            color: white;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico Stadium - Sitio Remoto</h1>
            <p>Verificación completa del sistema de productos, categorías y colecciones</p>
            <p><strong>Fecha:</strong> <?= date('Y-m-d H:i:s') ?> | <strong>Servidor:</strong> <?= $_SERVER['SERVER_NAME'] ?></p>
        </div>

        <?php
        $diagnosticos = [];
        $errores = [];
        
        // 1. VERIFICAR ARCHIVOS CRÍTICOS
        echo '<div class="test-section">';
        echo '<h2>📁 1. Verificación de Archivos Críticos</h2>';
        
        $archivos_criticos = [
            'api/productos.php' => 'API principal de productos',
            'api/categorias.php' => 'API principal de categorías',
            'api/colecciones.php' => 'API principal de colecciones',
            'api-productos-simple.php' => 'API simple de productos',
            'api-categorias-simple.php' => 'API simple de categorías',
            'api-colecciones-simple.php' => 'API simple de colecciones',
            'frontend/ProductosForm.jsx' => 'Componente React del formulario',
            'frontend/ProductosForm.css' => 'Estilos del formulario',
            'includes/auth.php' => 'Sistema de autenticación',
            'config/database.php' => 'Configuración de base de datos'
        ];
        
        echo '<table>';
        echo '<tr><th>Archivo</th><th>Estado</th><th>Descripción</th><th>Tamaño</th><th>Modificado</th></tr>';
        
        foreach ($archivos_criticos as $archivo => $descripcion) {
            $ruta = __DIR__ . '/' . $archivo;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($archivo) . '</td>';
            
            if (file_exists($ruta)) {
                $size = filesize($ruta);
                $modified = date('Y-m-d H:i:s', filemtime($ruta));
                echo '<td class="success">✅ Existe</td>';
                echo '<td>' . htmlspecialchars($descripcion) . '</td>';
                echo '<td>' . number_format($size) . ' bytes</td>';
                echo '<td>' . $modified . '</td>';
                $diagnosticos[] = "✅ $archivo existe ($size bytes)";
            } else {
                echo '<td class="error">❌ No existe</td>';
                echo '<td>' . htmlspecialchars($descripcion) . '</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                $errores[] = "❌ Archivo faltante: $archivo";
            }
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // 2. VERIFICAR CONEXIÓN A BASE DE DATOS
        echo '<div class="test-section">';
        echo '<h2>🗄️ 2. Verificación de Base de Datos</h2>';
        
        try {
            if (file_exists(__DIR__ . '/config/database.php')) {
                require_once __DIR__ . '/config/database.php';
                $mysqli = get_database_connection();
                
                if ($mysqli->connect_errno) {
                    throw new Exception('Error de conexión: ' . $mysqli->connect_error);
                }
                
                echo '<div class="status success">✅ Conexión a base de datos exitosa</div>';
                echo '<div class="code-block">Servidor: ' . $mysqli->host_info . '</div>';
                
                // Verificar tablas
                $tablas_necesarias = ['productos', 'categorias', 'colecciones', 'producto_categorias', 'producto_colecciones'];
                
                echo '<h3>Verificación de Tablas:</h3>';
                echo '<table>';
                echo '<tr><th>Tabla</th><th>Estado</th><th>Registros</th><th>Estructura</th></tr>';
                
                foreach ($tablas_necesarias as $tabla) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($tabla) . '</td>';
                    
                    $result = $mysqli->query("SHOW TABLES LIKE '$tabla'");
                    if ($result && $result->num_rows > 0) {
                        // Contar registros
                        $count_result = $mysqli->query("SELECT COUNT(*) as count FROM `$tabla`");
                        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
                        
                        // Obtener estructura
                        $desc_result = $mysqli->query("DESCRIBE `$tabla`");
                        $columns = [];
                        while ($desc_result && $row = $desc_result->fetch_assoc()) {
                            $columns[] = $row['Field'];
                        }
                        
                        echo '<td class="success">✅ Existe</td>';
                        echo '<td>' . number_format($count) . ' registros</td>';
                        echo '<td>' . implode(', ', array_slice($columns, 0, 5)) . 
                             (count($columns) > 5 ? '...' : '') . '</td>';
                        
                        $diagnosticos[] = "✅ Tabla $tabla: $count registros";
                    } else {
                        echo '<td class="error">❌ No existe</td>';
                        echo '<td>-</td>';
                        echo '<td>-</td>';
                        $errores[] = "❌ Tabla faltante: $tabla";
                    }
                    echo '</tr>';
                }
                echo '</table>';
                
                $mysqli->close();
            } else {
                throw new Exception('Archivo de configuración database.php no encontrado');
            }
        } catch (Exception $e) {
            echo '<div class="status error">❌ Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $errores[] = "❌ BD: " . $e->getMessage();
        }
        echo '</div>';
        
        // 3. VERIFICAR APIs
        echo '<div class="test-section">';
        echo '<h2>🔌 3. Verificación de APIs</h2>';
        
        $apis_a_probar = [
            'api/categorias.php' => 'API Categorías (autenticada)',
            'api/colecciones.php' => 'API Colecciones (autenticada)',
            'api-categorias-simple.php' => 'API Categorías (simple)',
            'api-colecciones-simple.php' => 'API Colecciones (simple)'
        ];
        
        echo '<table>';
        echo '<tr><th>API</th><th>Estado</th><th>Respuesta</th><th>Datos</th></tr>';
        
        foreach ($apis_a_probar as $api => $descripcion) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($descripcion) . '</td>';
            
            // Simular llamada a la API
            $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $api;
            
            // Usar cURL para hacer la petición
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo '<td class="error">❌ Error cURL</td>';
                echo '<td>' . htmlspecialchars($error) . '</td>';
                echo '<td>-</td>';
                $errores[] = "❌ API $api: $error";
            } elseif ($http_code === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['success']) && $data['success']) {
                    $count = 0;
                    if (isset($data['categorias'])) $count = count($data['categorias']);
                    if (isset($data['colecciones'])) $count = count($data['colecciones']);
                    
                    echo '<td class="success">✅ Funciona</td>';
                    echo '<td>HTTP ' . $http_code . ' - JSON válido</td>';
                    echo '<td>' . $count . ' elementos</td>';
                    $diagnosticos[] = "✅ API $api: $count elementos";
                } else {
                    echo '<td class="warning">⚠️ Respuesta inválida</td>';
                    echo '<td>' . htmlspecialchars(substr($response, 0, 100)) . '...</td>';
                    echo '<td>-</td>';
                    $errores[] = "⚠️ API $api: respuesta inválida";
                }
            } else {
                echo '<td class="error">❌ HTTP ' . $http_code . '</td>';
                echo '<td>' . htmlspecialchars(substr($response, 0, 100)) . '...</td>';
                echo '<td>-</td>';
                $errores[] = "❌ API $api: HTTP $http_code";
            }
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // 4. VERIFICAR PERMISOS Y CONFIGURACIÓN
        echo '<div class="test-section">';
        echo '<h2>⚙️ 4. Configuración del Servidor</h2>';
        
        echo '<h3>Información PHP:</h3>';
        echo '<div class="code-block">';
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "\n";
        echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        echo "Current Directory: " . __DIR__ . "\n";
        echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Activa' : 'Inactiva') . "\n";
        echo "MySQL Extension: " . (extension_loaded('mysqli') ? 'Disponible' : 'NO disponible') . "\n";
        echo "cURL Extension: " . (extension_loaded('curl') ? 'Disponible' : 'NO disponible') . "\n";
        echo "</div>";
        
        echo '<h3>Variables de Sesión:</h3>';
        echo '<div class="code-block">';
        echo "Session ID: " . session_id() . "\n";
        echo "Logged In: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'Sí' : 'No') : 'No definido') . "\n";
        echo "User ID: " . ($_SESSION['user_id'] ?? 'No definido') . "\n";
        echo "Login Time: " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'No definido') . "\n";
        echo "</div>";
        
        echo '</div>';
        
        // 5. RESUMEN FINAL
        echo '<div class="test-section">';
        echo '<h2>📊 5. Resumen del Diagnóstico</h2>';
        
        echo '<h3>✅ Elementos Funcionando (' . count($diagnosticos) . '):</h3>';
        if (!empty($diagnosticos)) {
            echo '<div class="code-block">';
            foreach ($diagnosticos as $diag) {
                echo $diag . "\n";
            }
            echo '</div>';
        }
        
        echo '<h3>❌ Problemas Encontrados (' . count($errores) . '):</h3>';
        if (!empty($errores)) {
            echo '<div class="code-block">';
            foreach ($errores as $error) {
                echo $error . "\n";
            }
            echo '</div>';
        } else {
            echo '<div class="status success">¡No se encontraron problemas!</div>';
        }
        
        // Recomendaciones
        echo '<h3>💡 Recomendaciones:</h3>';
        echo '<div class="info status">';
        if (count($errores) > 0) {
            echo '<strong>Acción requerida:</strong><br>';
            echo '1. Verifica que todos los archivos se hayan subido correctamente<br>';
            echo '2. Revisa los permisos de archivos y directorios<br>';
            echo '3. Verifica la configuración de la base de datos<br>';
            echo '4. Comprueba que las extensiones PHP necesarias estén instaladas<br>';
        } else {
            echo '<strong>Sistema funcionando correctamente</strong><br>';
            echo 'Todos los componentes están operativos. Si aún tienes problemas:<br>';
            echo '1. Limpia la caché del navegador<br>';
            echo '2. Verifica la consola del navegador por errores JavaScript<br>';
            echo '3. Comprueba la configuración de CORS si usas subdominios<br>';
        }
        echo '</div>';
        
        echo '</div>';
        ?>
        
        <!-- Botones de acción -->
        <div class="test-section">
            <h2>🔧 Herramientas de Diagnóstico</h2>
            <a href="?test_apis=1" class="btn">🔄 Probar APIs</a>
            <a href="test-sistema-productos.php" class="btn">📝 Probar Formulario</a>
            <a href="?phpinfo=1" class="btn">ℹ️ PHP Info</a>
            <a href="?" class="btn">🔄 Recargar Diagnóstico</a>
        </div>
        
        <?php
        // Prueba específica de APIs si se solicita
        if (isset($_GET['test_apis'])) {
            echo '<div class="test-section">';
            echo '<h2>🧪 Prueba Detallada de APIs</h2>';
            
            $test_urls = [
                'Categorías Simple' => '/stadium/api-categorias-simple.php',
                'Colecciones Simple' => '/stadium/api-colecciones-simple.php',
                'Categorías Auth' => '/stadium/api/categorias.php',
                'Colecciones Auth' => '/stadium/api/colecciones.php'
            ];
            
            foreach ($test_urls as $nombre => $path) {
                echo '<h3>' . $nombre . '</h3>';
                $full_url = 'http://' . $_SERVER['HTTP_HOST'] . $path;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $full_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo '<div class="code-block">';
                echo "URL: $full_url\n";
                echo "HTTP Code: $http_code\n";
                echo "Response: " . ($response ? substr($response, 0, 500) : 'Sin respuesta') . "\n";
                echo '</div>';
            }
            echo '</div>';
        }
        
        // Mostrar PHP Info si se solicita
        if (isset($_GET['phpinfo'])) {
            echo '<div class="test-section">';
            echo '<h2>ℹ️ Información PHP</h2>';
            echo '<iframe src="data:text/html;base64,' . base64_encode('<?php phpinfo(); ?>') . '" width="100%" height="600"></iframe>';
            echo '</div>';
        }
        ?>
    </div>

    <script>
        // Auto-recargar cada 30 segundos si hay errores
        <?php if (count($errores) > 0): ?>
        console.log('Diagnóstico encontró <?= count($errores) ?> errores');
        setTimeout(function() {
            if (confirm('¿Recargar diagnóstico para verificar correcciones?')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
        
        // Log del estado
        console.log('Diagnóstico Stadium:', {
            archivos_ok: <?= count($diagnosticos) ?>,
            errores: <?= count($errores) ?>,
            servidor: '<?= $_SERVER['SERVER_NAME'] ?>',
            timestamp: '<?= date('c') ?>'
        });
    </script>
</body>
</html>