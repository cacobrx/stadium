<?php
/**
 * Script Simple de Debug - Inventario
 * Muestra errores PHP de forma clara
 */

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Configurar sesión antes de iniciarla
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

echo "<pre style='background: #f4f4f4; padding: 20px; border: 2px solid #333;'>";
echo "===========================================\n";
echo "   DEBUG INVENTARIO - STADIUM\n";
echo "===========================================\n\n";

echo "📅 Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "🌐 Servidor: " . $_SERVER['SERVER_NAME'] . "\n";
echo "🐘 PHP: " . phpversion() . "\n\n";

// 1. Test básico de archivos
echo "--- TEST 1: ARCHIVOS ---\n";
$files = [
    'config/database.php',
    'includes/auth.php', 
    'api/inventario.php',
    'pages/inventario.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? '✅' : '❌';
    $readable = is_readable($path) ? 'R' : '-';
    echo "$exists $readable  $file\n";
}

// 2. Test de BD
echo "\n--- TEST 2: BASE DE DATOS ---\n";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✅ database.php cargado\n";
    
    $mysqli = get_database_connection();
    
    if ($mysqli && !$mysqli->connect_error) {
        echo "✅ Conexión exitosa\n";
        echo "   Host: " . $mysqli->host_info . "\n";
        
        $db = $mysqli->query("SELECT DATABASE()")->fetch_row()[0];
        echo "   DB: $db\n";
        
        // Verificar tablas
        echo "\n--- TEST 3: TABLAS ---\n";
        $tables = ['productos', 'producto_variantes', 'variante_atributos'];
        foreach ($tables as $table) {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $count = $mysqli->query("SELECT COUNT(*) as c FROM $table")->fetch_row()[0];
                echo "✅ $table ($count registros)\n";
            } else {
                echo "❌ $table NO EXISTE\n";
            }
        }
        
        // Query de prueba
        echo "\n--- TEST 4: QUERY INVENTARIO ---\n";
        $sql = "SELECT pv.id, p.nombre, pv.sku_variante, pv.stock, pv.precio 
                FROM producto_variantes pv 
                INNER JOIN productos p ON pv.producto_id = p.id 
                LIMIT 3";
        
        $result = $mysqli->query($sql);
        if ($result) {
            echo "✅ Query ejecutada\n";
            echo "   Resultados: " . $result->num_rows . "\n";
            if ($result->num_rows > 0) {
                echo "\n   Muestra:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "   - ID:{$row['id']} | {$row['nombre']} | SKU:{$row['sku_variante']} | Stock:{$row['stock']} | \${$row['precio']}\n";
                }
            } else {
                echo "   ⚠️ No hay variantes en la BD\n";
            }
        } else {
            echo "❌ Error en query: " . $mysqli->error . "\n";
        }
        
        $mysqli->close();
    } else {
        echo "❌ Error conexión: " . ($mysqli->connect_error ?? 'Desconocido') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
}

// 3. Test de sesión
echo "\n--- TEST 5: SESIÓN ---\n";

// Intentar iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    try {
        session_start();
        echo "→ Sesión iniciada por el script\n";
    } catch (Exception $e) {
        echo "❌ Error al iniciar sesión: " . $e->getMessage() . "\n";
    }
}

$session_status = session_status();
$status_text = '';
switch ($session_status) {
    case PHP_SESSION_DISABLED:
        $status_text = '❌ DESHABILITADA';
        break;
    case PHP_SESSION_NONE:
        $status_text = '⚠️ NO INICIADA';
        break;
    case PHP_SESSION_ACTIVE:
        $status_text = '✅ ACTIVA';
        break;
}

echo "Estado: $status_text\n";
echo "Session ID: " . (session_id() ?: '(vacío)') . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '❌ NO AUTENTICADO') . "\n";

// Mostrar todas las variables de sesión si existen
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION)) {
    echo "Variables de sesión:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_scalar($value)) {
            echo "   • $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
        } else {
            echo "   • $key: [" . gettype($value) . "]\n";
        }
    }
} else {
    echo "→ No hay variables de sesión\n";
}

// Información adicional de sesión
echo "Session save path: " . session_save_path() . "\n";
echo "Session cookie params: \n";
$params = session_get_cookie_params();
echo "   • Lifetime: " . $params['lifetime'] . "s\n";
echo "   • Path: " . $params['path'] . "\n";
echo "   • Domain: " . ($params['domain'] ?: '(default)') . "\n";
echo "   • Secure: " . ($params['secure'] ? 'Yes' : 'No') . "\n";
echo "   • HttpOnly: " . ($params['httponly'] ? 'Yes' : 'No') . "\n";

// 4. Test de extensiones
echo "\n--- TEST 6: EXTENSIONES PHP ---\n";
$exts = ['mysqli', 'json', 'session'];
foreach ($exts as $ext) {
    echo (extension_loaded($ext) ? '✅' : '❌') . " $ext\n";
}

// 5. Info del sistema
echo "\n--- INFO ADICIONAL ---\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Dir: " . __DIR__ . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";

echo "\n===========================================\n";
echo "   FIN DEL TEST\n";
echo "===========================================\n";
echo "</pre>";

// Si hay errores en el log, intentar mostrarlos
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log) && is_readable($error_log)) {
    echo "<h3>📋 Últimas 10 líneas del log de errores:</h3>";
    echo "<pre style='background: #fff3cd; padding: 15px; border: 2px solid #ffc107;'>";
    $lines = file($error_log);
    $last_lines = array_slice($lines, -10);
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";
}
?>
