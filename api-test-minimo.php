<?php
// api-test-minimo.php - API mínima para test de timeout
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 10);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Log de debug
$debug_log = [];
$debug_log[] = "Inicio: " . date('Y-m-d H:i:s');

try {
    // Test 1: Respuesta básica
    if (isset($_GET['test']) && $_GET['test'] === 'basic') {
        $debug_log[] = "Test básico solicitado";
        echo json_encode([
            'success' => true,
            'test' => 'basic',
            'timestamp' => date('Y-m-d H:i:s'),
            'debug' => $debug_log
        ]);
        exit;
    }
    
    // Test 2: Con sesión
    if (isset($_GET['test']) && $_GET['test'] === 'session') {
        session_start();
        $_SESSION['logged_in'] = true;
        
        $debug_log[] = "Sesión iniciada";
        echo json_encode([
            'success' => true,
            'test' => 'session',
            'session_id' => session_id(),
            'debug' => $debug_log
        ]);
        exit;
    }
    
    // Test 3: Con base de datos
    if (isset($_GET['test']) && $_GET['test'] === 'database') {
        $debug_log[] = "Probando conexión BD";
        
        // Usar configuración centralizada
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        if ($mysqli->connect_errno) {
            throw new Exception('Error MySQL: ' . $mysqli->connect_error);
        }
        
        $debug_log[] = "Conexión BD exitosa";
        
        $result = $mysqli->query("SELECT COUNT(*) as count FROM categorias LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $debug_log[] = "Query exitosa: " . $row['count'] . " categorías";
        }
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'test' => 'database',
            'debug' => $debug_log
        ]);
        exit;
    }
    
    // Test 4: Categorías real pero simplificado
    if (isset($_GET['test']) && $_GET['test'] === 'categorias') {
        session_start();
        $_SESSION['logged_in'] = true;
        
        $debug_log[] = "Iniciando test categorías";
        
        // Usar configuración centralizada
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        $debug_log[] = "Conexión establecida";
        $debug_log[] = "Charset configurado";
        
        $result = $mysqli->query("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre LIMIT 10");
        
        if (!$result) {
            throw new Exception('Error en query: ' . $mysqli->error);
        }
        
        $debug_log[] = "Query ejecutada";
        
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
        
        $debug_log[] = "Datos procesados: " . count($categorias) . " categorías";
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'categorias' => $categorias,
            'debug' => $debug_log
        ]);
        exit;
    }
    
    // Test 5: Colecciones real pero simplificado
    if (isset($_GET['test']) && $_GET['test'] === 'colecciones') {
        session_start();
        $_SESSION['logged_in'] = true;
        
        $debug_log[] = "Iniciando test colecciones";
        
        // Usar configuración centralizada
        require_once __DIR__ . '/config/database.php';
        $mysqli = get_database_connection();
        
        $debug_log[] = "Conexión establecida";
        $debug_log[] = "Charset configurado";
        
        $result = $mysqli->query("SELECT id, nombre FROM colecciones WHERE activa = 1 ORDER BY nombre LIMIT 10");
        
        if (!$result) {
            throw new Exception('Error en query: ' . $mysqli->error);
        }
        
        $debug_log[] = "Query ejecutada";
        
        $colecciones = [];
        while ($row = $result->fetch_assoc()) {
            $colecciones[] = $row;
        }
        
        $debug_log[] = "Datos procesados: " . count($colecciones) . " colecciones";
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'colecciones' => $colecciones,
            'debug' => $debug_log
        ]);
        exit;
    }
    
    // Respuesta por defecto con instrucciones
    echo json_encode([
        'success' => true,
        'message' => 'API Test disponible',
        'tests_available' => [
            '?test=basic' => 'Test básico (sin BD)',
            '?test=session' => 'Test con sesión',
            '?test=database' => 'Test conexión BD',
            '?test=categorias' => 'Test categorías reales',
            '?test=colecciones' => 'Test colecciones reales'
        ],
        'debug' => $debug_log
    ]);
    
} catch (Exception $e) {
    $debug_log[] = "ERROR: " . $e->getMessage();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_log
    ]);
} catch (Error $e) {
    $debug_log[] = "FATAL ERROR: " . $e->getMessage();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => $debug_log
    ]);
}
?>