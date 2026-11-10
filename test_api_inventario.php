<?php
/**
 * Test Rápido de API de Inventario
 * Prueba la API sin necesidad de autenticación para debugging
 * 
 * USO: /stadium/test_api_inventario.php
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [
    'test_date' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'php_version' => phpversion(),
    'tests' => [],
    'errors' => [],
    'warnings' => [],
    'success' => true
];

// Test 1: Verificar archivo database.php
$response['tests']['database_file'] = false;
if (file_exists(__DIR__ . '/config/database.php')) {
    $response['tests']['database_file'] = true;
    try {
        require_once __DIR__ . '/config/database.php';
        $response['tests']['database_loaded'] = true;
    } catch (Exception $e) {
        $response['tests']['database_loaded'] = false;
        $response['errors'][] = "Error cargando database.php: " . $e->getMessage();
        $response['success'] = false;
    }
} else {
    $response['errors'][] = "Archivo config/database.php no encontrado";
    $response['success'] = false;
}

// Test 2: Conexión a BD
$response['tests']['database_connection'] = false;
if ($response['tests']['database_file']) {
    try {
        $mysqli = get_database_connection();
        if ($mysqli && !$mysqli->connect_error) {
            $response['tests']['database_connection'] = true;
            $response['database_info'] = [
                'host' => $mysqli->host_info,
                'charset' => $mysqli->character_set_name()
            ];
            
            // Obtener nombre de la base de datos
            $db_result = $mysqli->query("SELECT DATABASE()");
            if ($db_result) {
                $db_name = $db_result->fetch_row()[0];
                $response['database_info']['database'] = $db_name;
            }
        } else {
            $response['errors'][] = "Error de conexión MySQL: " . ($mysqli->connect_error ?? 'Desconocido');
            $response['success'] = false;
        }
    } catch (Exception $e) {
        $response['errors'][] = "Excepción en conexión BD: " . $e->getMessage();
        $response['success'] = false;
    }
}

// Test 3: Verificar tablas
$response['tests']['tables'] = [];
if ($response['tests']['database_connection'] && isset($mysqli)) {
    $required_tables = ['productos', 'producto_variantes', 'variante_atributos', 'marcas'];
    
    foreach ($required_tables as $table) {
        $result = $mysqli->query("SHOW TABLES LIKE '$table'");
        $exists = $result && $result->num_rows > 0;
        $response['tests']['tables'][$table] = $exists;
        
        if (!$exists) {
            $response['errors'][] = "Tabla '$table' no existe";
            $response['success'] = false;
        } else {
            // Contar registros
            $count_result = $mysqli->query("SELECT COUNT(*) as total FROM $table");
            if ($count_result) {
                $count = $count_result->fetch_assoc()['total'];
                $response['tests']['tables'][$table . '_count'] = $count;
                
                if ($count == 0 && $table != 'marcas') {
                    $response['warnings'][] = "Tabla '$table' está vacía";
                }
            }
        }
    }
}

// Test 4: Consulta de inventario (sin autenticación)
$response['tests']['inventory_query'] = false;
$response['inventory_sample'] = [];

if ($response['tests']['database_connection'] && isset($mysqli)) {
    try {
        $query = "
            SELECT 
                pv.id,
                pv.producto_id,
                pv.sku_variante,
                pv.stock,
                pv.precio,
                pv.activa,
                p.nombre as producto_nombre,
                p.sku as producto_sku,
                m.nombre as marca_nombre
            FROM producto_variantes pv
            INNER JOIN productos p ON pv.producto_id = p.id
            LEFT JOIN marcas m ON p.marca_id = m.id
            WHERE pv.activa = 1
            ORDER BY p.nombre, pv.sku_variante
            LIMIT 5
        ";
        
        $result = $mysqli->query($query);
        
        if ($result) {
            $response['tests']['inventory_query'] = true;
            $inventario = [];
            
            while ($row = $result->fetch_assoc()) {
                // Obtener atributos
                $stmt = $mysqli->prepare("
                    SELECT nombre, valor 
                    FROM variante_atributos 
                    WHERE variante_id = ?
                    ORDER BY nombre
                ");
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
                $atributos_result = $stmt->get_result();
                $row['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
                
                $inventario[] = $row;
            }
            
            $response['inventory_sample'] = $inventario;
            $response['inventory_count'] = count($inventario);
            
            if (count($inventario) == 0) {
                $response['warnings'][] = "No hay variantes activas en el inventario";
            }
        } else {
            $response['errors'][] = "Error en consulta de inventario: " . $mysqli->error;
            $response['success'] = false;
        }
    } catch (Exception $e) {
        $response['errors'][] = "Excepción en consulta de inventario: " . $e->getMessage();
        $response['success'] = false;
    }
}

// Test 5: Verificar API inventario.php
$response['tests']['api_file_exists'] = file_exists(__DIR__ . '/api/inventario.php');
if (!$response['tests']['api_file_exists']) {
    $response['errors'][] = "Archivo api/inventario.php no encontrado";
    $response['success'] = false;
} else {
    $response['tests']['api_file_readable'] = is_readable(__DIR__ . '/api/inventario.php');
    if (!$response['tests']['api_file_readable']) {
        $response['errors'][] = "Archivo api/inventario.php no es legible (permisos)";
        $response['success'] = false;
    }
}

// Test 6: Verificar página inventario.php
$response['tests']['page_file_exists'] = file_exists(__DIR__ . '/pages/inventario.php');
if (!$response['tests']['page_file_exists']) {
    $response['errors'][] = "Archivo pages/inventario.php no encontrado";
    $response['success'] = false;
} else {
    $response['tests']['page_file_readable'] = is_readable(__DIR__ . '/pages/inventario.php');
    if (!$response['tests']['page_file_readable']) {
        $response['errors'][] = "Archivo pages/inventario.php no es legible (permisos)";
        $response['success'] = false;
    }
}

// Test 7: Verificar includes
$includes = [
    'includes/auth.php',
    'includes/sweetalert-helpers.js',
    'includes/dashboard-functions.js',
    'includes/dashboard-styles.css'
];

$response['tests']['includes'] = [];
foreach ($includes as $include) {
    $exists = file_exists(__DIR__ . '/' . $include);
    $response['tests']['includes'][$include] = $exists;
    if (!$exists) {
        $response['warnings'][] = "Archivo '$include' no encontrado";
    }
}

// Test 8: Extensiones PHP
$response['tests']['php_extensions'] = [];
$required_extensions = ['mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $response['tests']['php_extensions'][$ext] = $loaded;
    if (!$loaded) {
        $response['errors'][] = "Extensión PHP '$ext' no está cargada";
        $response['success'] = false;
    }
}

// Test 9: Sesión
$response['tests']['session_active'] = session_status() === PHP_SESSION_ACTIVE;
if (!$response['tests']['session_active']) {
    session_start();
    $response['tests']['session_started'] = true;
}
$response['tests']['user_authenticated'] = isset($_SESSION['user_id']);

// Resumen
$response['summary'] = [
    'total_errors' => count($response['errors']),
    'total_warnings' => count($response['warnings']),
    'can_access_inventory' => $response['success'] && count($response['errors']) == 0
];

if (count($response['errors']) == 0 && count($response['warnings']) == 0) {
    $response['message'] = "✅ Todos los tests pasaron correctamente. El sistema de inventario debería funcionar.";
} elseif (count($response['errors']) > 0) {
    $response['message'] = "❌ Se encontraron errores críticos. Revisar la sección 'errors'.";
} else {
    $response['message'] = "⚠️ Sistema funcional pero con advertencias. Revisar 'warnings'.";
}

// Recomendaciones
$response['recommendations'] = [];
if (!$response['tests']['user_authenticated']) {
    $response['recommendations'][] = "Hacer login antes de acceder a inventario";
}
if (isset($response['inventory_count']) && $response['inventory_count'] == 0) {
    $response['recommendations'][] = "Agregar variantes de productos al inventario";
}
if (count($response['errors']) > 0) {
    $response['recommendations'][] = "Corregir los errores listados antes de continuar";
}

// Cerrar conexión
if (isset($mysqli)) {
    $mysqli->close();
}

// Salida JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
