<?php
// verificar-datos-remoto.php - Verificación específica de datos en APIs
session_start();

// Simular sesión para las pruebas
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $resultado = [
        'timestamp' => date('Y-m-d H:i:s'),
        'servidor' => $_SERVER['SERVER_NAME'] ?? 'localhost',
        'tests' => [],
        'summary' => [
            'total' => 0,
            'passed' => 0,
            'failed' => 0
        ]
    ];

    // Función para ejecutar un test
    function ejecutarTest($nombre, $funcion) {
        global $resultado;
        
        $resultado['summary']['total']++;
        
        try {
            $start_time = microtime(true);
            $result = $funcion();
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($result['success']) {
                $resultado['summary']['passed']++;
                $status = 'PASS';
            } else {
                $resultado['summary']['failed']++;
                $status = 'FAIL';
            }
            
            $resultado['tests'][] = [
                'name' => $nombre,
                'status' => $status,
                'execution_time_ms' => $execution_time,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            $resultado['summary']['failed']++;
            $resultado['tests'][] = [
                'name' => $nombre,
                'status' => 'ERROR',
                'execution_time_ms' => 0,
                'data' => [
                    'success' => false,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    // Test 1: Verificar conexión a base de datos
    ejecutarTest('Conexión Base de Datos', function() {
        if (!file_exists(__DIR__ . '/config/database.php')) {
            return ['success' => false, 'error' => 'Archivo database.php no encontrado'];
        }
        
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        if ($mysqli->connect_errno) {
            return ['success' => false, 'error' => 'Error de conexión: ' . $mysqli->connect_error];
        }
        
        $version_result = $mysqli->query("SELECT VERSION() as version");
        $version = $version_result ? $version_result->fetch_assoc()['version'] : 'Desconocida';
        
        $mysqli->close();
        
        return [
            'success' => true,
            'mysql_version' => $version,
            'host_info' => $mysqli->host_info ?? 'N/A'
        ];
    });

    // Test 2: Verificar tabla categorías
    ejecutarTest('Datos Categorías', function() {
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        if ($mysqli->connect_errno) {
            return ['success' => false, 'error' => 'Error de conexión BD'];
        }
        
        // Verificar estructura de tabla
        $desc_result = $mysqli->query("DESCRIBE categorias");
        if (!$desc_result) {
            return ['success' => false, 'error' => 'Tabla categorias no existe'];
        }
        
        $columns = [];
        while ($row = $desc_result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Contar registros
        $count_result = $mysqli->query("SELECT COUNT(*) as count FROM categorias");
        $total = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        
        // Obtener algunas muestras
        $sample_result = $mysqli->query("SELECT id, nombre FROM categorias LIMIT 5");
        $samples = [];
        while ($sample_result && $row = $sample_result->fetch_assoc()) {
            $samples[] = $row;
        }
        
        $mysqli->close();
        
        return [
            'success' => true,
            'total_records' => (int)$total,
            'columns' => $columns,
            'samples' => $samples
        ];
    });

    // Test 3: Verificar tabla colecciones
    ejecutarTest('Datos Colecciones', function() {
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        if ($mysqli->connect_errno) {
            return ['success' => false, 'error' => 'Error de conexión BD'];
        }
        
        // Verificar estructura de tabla
        $desc_result = $mysqli->query("DESCRIBE colecciones");
        if (!$desc_result) {
            return ['success' => false, 'error' => 'Tabla colecciones no existe'];
        }
        
        $columns = [];
        while ($row = $desc_result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Contar registros
        $count_result = $mysqli->query("SELECT COUNT(*) as count FROM colecciones");
        $total = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        
        // Obtener algunas muestras
        $sample_result = $mysqli->query("SELECT id, nombre FROM colecciones LIMIT 5");
        $samples = [];
        while ($sample_result && $row = $sample_result->fetch_assoc()) {
            $samples[] = $row;
        }
        
        $mysqli->close();
        
        return [
            'success' => true,
            'total_records' => (int)$total,
            'columns' => $columns,
            'samples' => $samples
        ];
    });

    // Test 4: Verificar API simple categorías
    ejecutarTest('API Categorías Simple', function() {
        // Simular llamada interna
        ob_start();
        $_GET = []; // Limpiar GET params
        
        try {
            include __DIR__ . '/api-categorias-simple.php';
            $output = ob_get_clean();
            
            $data = json_decode($output, true);
            if (!$data) {
                return ['success' => false, 'error' => 'Respuesta no es JSON válido', 'raw_output' => substr($output, 0, 200)];
            }
            
            if (!$data['success']) {
                return ['success' => false, 'error' => $data['error'] ?? 'Error desconocido en API'];
            }
            
            return [
                'success' => true,
                'categories_count' => count($data['categorias'] ?? []),
                'sample_data' => array_slice($data['categorias'] ?? [], 0, 3)
            ];
            
        } catch (Exception $e) {
            ob_end_clean();
            return ['success' => false, 'error' => 'Excepción: ' . $e->getMessage()];
        }
    });

    // Test 5: Verificar API simple colecciones
    ejecutarTest('API Colecciones Simple', function() {
        // Simular llamada interna
        ob_start();
        $_GET = []; // Limpiar GET params
        
        try {
            include __DIR__ . '/api-colecciones-simple.php';
            $output = ob_get_clean();
            
            $data = json_decode($output, true);
            if (!$data) {
                return ['success' => false, 'error' => 'Respuesta no es JSON válido', 'raw_output' => substr($output, 0, 200)];
            }
            
            if (!$data['success']) {
                return ['success' => false, 'error' => $data['error'] ?? 'Error desconocido en API'];
            }
            
            return [
                'success' => true,
                'collections_count' => count($data['colecciones'] ?? []),
                'sample_data' => array_slice($data['colecciones'] ?? [], 0, 3)
            ];
            
        } catch (Exception $e) {
            ob_end_clean();
            return ['success' => false, 'error' => 'Excepción: ' . $e->getMessage()];
        }
    });

    // Test 6: Verificar archivos críticos
    ejecutarTest('Archivos Críticos', function() {
        $archivos = [
            'frontend/ProductosForm.jsx',
            'frontend/ProductosForm.css',
            'api-categorias-simple.php',
            'api-colecciones-simple.php'
        ];
        
        $existentes = [];
        $faltantes = [];
        $info = [];
        
        foreach ($archivos as $archivo) {
            $ruta = __DIR__ . '/' . $archivo;
            if (file_exists($ruta)) {
                $existentes[] = $archivo;
                $info[$archivo] = [
                    'size' => filesize($ruta),
                    'modified' => date('Y-m-d H:i:s', filemtime($ruta))
                ];
            } else {
                $faltantes[] = $archivo;
            }
        }
        
        return [
            'success' => count($faltantes) === 0,
            'files_found' => count($existentes),
            'files_missing' => count($faltantes),
            'existing_files' => $existentes,
            'missing_files' => $faltantes,
            'files_info' => $info
        ];
    });

    // Calcular porcentaje de éxito
    $resultado['summary']['success_rate'] = $resultado['summary']['total'] > 0 
        ? round(($resultado['summary']['passed'] / $resultado['summary']['total']) * 100, 2)
        : 0;

    // Generar recomendaciones
    $recomendaciones = [];
    
    if ($resultado['summary']['success_rate'] < 50) {
        $recomendaciones[] = 'Problemas críticos detectados - revisar configuración del servidor';
    }
    
    if ($resultado['summary']['success_rate'] < 80) {
        $recomendaciones[] = 'Verificar que todos los archivos se subieron correctamente';
        $recomendaciones[] = 'Revisar permisos de archivos y directorios';
    }
    
    if ($resultado['summary']['success_rate'] >= 80) {
        $recomendaciones[] = 'Sistema funcionando correctamente';
        if ($resultado['summary']['success_rate'] < 100) {
            $recomendaciones[] = 'Algunos tests fallaron - revisar detalles específicos';
        }
    }
    
    $resultado['recommendations'] = $recomendaciones;
    $resultado['status'] = $resultado['summary']['success_rate'] >= 80 ? 'healthy' : 
                          ($resultado['summary']['success_rate'] >= 50 ? 'warning' : 'critical');

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fatal: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>