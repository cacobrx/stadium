<?php
// api-categorias-fixed.php - Versión corregida para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 10);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Debug info
$debug = [];
$debug[] = "Inicio: " . date('H:i:s.u');

try {
    // Autenticación simple
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['logged_in'] = true;
        $debug[] = "Sesión creada";
    } else {
        $debug[] = "Sesión existente";
    }
    
    $debug[] = "Intentando conexión BD";
    
    // Usar configuración centralizada
    require_once __DIR__ . '/config/database.php';
    $mysqli = get_database_connection();
    
    $debug[] = "Conexión establecida: " . $mysqli->host_info;
    $debug[] = "Charset configurado";
    
    // Primero verificar si la tabla existe y su estructura
    $debug[] = "Verificando tabla categorias";
    
    $table_check = $mysqli->query("SHOW TABLES LIKE 'categorias'");
    if (!$table_check || $table_check->num_rows === 0) {
        throw new Exception('Tabla categorias no existe');
    }
    
    $debug[] = "Tabla categorias existe";
    
    // Verificar estructura de la tabla
    $structure = $mysqli->query("DESCRIBE categorias");
    $columns = [];
    while ($row = $structure->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $debug[] = "Columnas: " . implode(', ', $columns);
    
    // Construir query según las columnas disponibles
    $query = "SELECT id, nombre";
    
    // Solo agregar WHERE si existe la columna activa
    if (in_array('activa', $columns)) {
        $query .= " FROM categorias WHERE activa = 1 ORDER BY nombre LIMIT 20";
        $debug[] = "Usando filtro activa = 1";
    } else {
        $query .= " FROM categorias ORDER BY nombre LIMIT 20";
        $debug[] = "Sin filtro activa (columna no existe)";
    }
    
    $debug[] = "Query: " . $query;
    
    // Ejecutar query con timeout
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception('Error en consulta: ' . $mysqli->error);
    }
    
    $debug[] = "Query ejecutada exitosamente";
    
    // Obtener resultados
    $categorias = [];
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
        $count++;
        
        // Limitar para evitar timeouts
        if ($count >= 50) {
            break;
        }
    }
    
    $debug[] = "Resultados obtenidos: " . count($categorias);
    
    $mysqli->close();
    $debug[] = "Conexión cerrada";
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'categorias' => $categorias,
        'debug' => $debug,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $debug[] = "ERROR: " . $e->getMessage();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => $debug,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Error $e) {
    $debug[] = "FATAL: " . $e->getMessage();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => $debug,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>