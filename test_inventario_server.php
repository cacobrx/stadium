<?php
/**
 * Script de Prueba e Integridad para Inventario
 * Diagnostica problemas en el servidor remoto
 * 
 * Uso: Acceder desde el navegador a /stadium/test_inventario_server.php
 */

// Configurar para mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Inventario - Stadium</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #007bff; margin-top: 30px; }
        .test { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; background: #f9f9f9; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-success { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .badge-info { background: #17a2b8; color: white; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .code { font-family: 'Courier New', monospace; background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 Test de Integridad - Sistema de Inventario</h1>
<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
<p><strong>Servidor:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
<p><strong>PHP Version:</strong> " . phpversion() . "</p>
";

$errores = [];
$advertencias = [];
$exitos = [];

// ============================================
// TEST 1: Verificar Archivos Necesarios
// ============================================
echo "<h2>📁 Test 1: Archivos del Sistema</h2>";

$archivos_requeridos = [
    'config/database.php' => 'Configuración de base de datos',
    'includes/auth.php' => 'Sistema de autenticación',
    'api/inventario.php' => 'API de inventario',
    'pages/inventario.php' => 'Página de inventario',
    'includes/sweetalert-helpers.js' => 'Helpers de SweetAlert',
    'includes/dashboard-functions.js' => 'Funciones del dashboard',
    'includes/dashboard-styles.css' => 'Estilos del dashboard'
];

foreach ($archivos_requeridos as $archivo => $descripcion) {
    $ruta = __DIR__ . '/' . $archivo;
    if (file_exists($ruta)) {
        echo "<div class='test success'>✅ <strong>$descripcion</strong><br>Archivo: <span class='code'>$archivo</span> <span class='badge badge-success'>EXISTE</span></div>";
        $exitos[] = "Archivo $archivo existe";
    } else {
        echo "<div class='test error'>❌ <strong>$descripcion</strong><br>Archivo: <span class='code'>$archivo</span> <span class='badge badge-error'>NO ENCONTRADO</span></div>";
        $errores[] = "Archivo $archivo no encontrado en $ruta";
    }
}

// ============================================
// TEST 2: Conexión a Base de Datos
// ============================================
echo "<h2>🔌 Test 2: Conexión a Base de Datos</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    echo "<div class='test success'>✅ Archivo database.php cargado correctamente <span class='badge badge-success'>OK</span></div>";
    $exitos[] = "Archivo database.php cargado";
    
    $mysqli = get_database_connection();
    
    if ($mysqli && !$mysqli->connect_error) {
        echo "<div class='test success'>✅ Conexión a base de datos establecida <span class='badge badge-success'>CONECTADO</span></div>";
        echo "<div class='test info'>
            <strong>Información de conexión:</strong><br>
            • Host: " . $mysqli->host_info . "<br>
            • Base de datos: " . ($mysqli->query("SELECT DATABASE()")->fetch_row()[0] ?? 'N/A') . "<br>
            • Usuario: (oculto por seguridad)<br>
            • Charset: " . $mysqli->character_set_name() . "
        </div>";
        $exitos[] = "Conexión a BD exitosa";
    } else {
        throw new Exception("Error de conexión: " . ($mysqli->connect_error ?? 'Desconocido'));
    }
} catch (Exception $e) {
    echo "<div class='test error'>❌ Error en base de datos: " . htmlspecialchars($e->getMessage()) . " <span class='badge badge-error'>ERROR</span></div>";
    $errores[] = "Error BD: " . $e->getMessage();
    $mysqli = null;
}

// ============================================
// TEST 3: Verificar Tablas Necesarias
// ============================================
echo "<h2>🗄️ Test 3: Estructura de Base de Datos</h2>";

if ($mysqli) {
    $tablas_requeridas = [
        'productos' => ['id', 'nombre', 'sku', 'descripcion', 'marca_id'],
        'producto_variantes' => ['id', 'producto_id', 'sku_variante', 'stock', 'precio', 'activa'],
        'variante_atributos' => ['id', 'variante_id', 'nombre', 'valor'],
        'marcas' => ['id', 'nombre'],
        'producto_imagenes' => ['id', 'producto_id', 'url', 'es_principal']
    ];
    
    foreach ($tablas_requeridas as $tabla => $columnas_esperadas) {
        $resultado = $mysqli->query("SHOW TABLES LIKE '$tabla'");
        if ($resultado && $resultado->num_rows > 0) {
            echo "<div class='test success'>✅ Tabla <strong>$tabla</strong> existe <span class='badge badge-success'>OK</span></div>";
            
            // Verificar columnas
            $columnas_result = $mysqli->query("DESCRIBE $tabla");
            $columnas_existentes = [];
            while ($col = $columnas_result->fetch_assoc()) {
                $columnas_existentes[] = $col['Field'];
            }
            
            $columnas_faltantes = array_diff($columnas_esperadas, $columnas_existentes);
            if (empty($columnas_faltantes)) {
                echo "<div class='test info'>→ Todas las columnas necesarias existen</div>";
                $exitos[] = "Tabla $tabla completa";
            } else {
                echo "<div class='test warning'>⚠️ Columnas faltantes en <strong>$tabla</strong>: " . implode(', ', $columnas_faltantes) . " <span class='badge badge-warning'>ADVERTENCIA</span></div>";
                $advertencias[] = "Tabla $tabla: columnas faltantes " . implode(', ', $columnas_faltantes);
            }
            
            // Contar registros
            $count_result = $mysqli->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $count_result->fetch_assoc()['total'];
            echo "<div class='test info'>→ Registros en <strong>$tabla</strong>: <strong>$count</strong></div>";
            
        } else {
            echo "<div class='test error'>❌ Tabla <strong>$tabla</strong> NO existe <span class='badge badge-error'>ERROR</span></div>";
            $errores[] = "Tabla $tabla no existe";
        }
    }
} else {
    echo "<div class='test error'>❌ No se puede verificar tablas (sin conexión a BD) <span class='badge badge-error'>ERROR</span></div>";
    $errores[] = "No se puede verificar tablas sin conexión";
}

// ============================================
// TEST 4: Datos de Prueba en Inventario
// ============================================
echo "<h2>📦 Test 4: Datos en Inventario</h2>";

if ($mysqli) {
    // Productos con variantes
    $query = "
        SELECT 
            p.id as producto_id,
            p.nombre as producto_nombre,
            p.sku as producto_sku,
            COUNT(pv.id) as num_variantes,
            SUM(pv.stock) as stock_total
        FROM productos p
        LEFT JOIN producto_variantes pv ON p.id = pv.producto_id
        GROUP BY p.id
        ORDER BY p.nombre
        LIMIT 10
    ";
    
    $result = $mysqli->query($query);
    if ($result && $result->num_rows > 0) {
        echo "<div class='test success'>✅ Se encontraron productos con variantes <span class='badge badge-success'>OK</span></div>";
        echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th>Variantes</th>
                    <th>Stock Total</th>
                </tr>
            </thead>
            <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            $badge_class = $row['num_variantes'] > 0 ? 'success' : 'warning';
            echo "<tr>
                <td>{$row['producto_id']}</td>
                <td>{$row['producto_nombre']}</td>
                <td>{$row['producto_sku']}</td>
                <td><span class='badge badge-$badge_class'>{$row['num_variantes']}</span></td>
                <td>{$row['stock_total']}</td>
            </tr>";
        }
        echo "</tbody></table>";
        $exitos[] = "Datos de productos encontrados";
    } else {
        echo "<div class='test warning'>⚠️ No se encontraron productos en la base de datos <span class='badge badge-warning'>VACÍO</span></div>";
        $advertencias[] = "No hay productos en la BD";
    }
    
    // Variantes con atributos
    $query = "
        SELECT 
            pv.id,
            p.nombre as producto,
            pv.sku_variante,
            pv.precio,
            pv.stock,
            pv.activa,
            GROUP_CONCAT(CONCAT(va.nombre, ': ', va.valor) SEPARATOR ', ') as atributos
        FROM producto_variantes pv
        INNER JOIN productos p ON pv.producto_id = p.id
        LEFT JOIN variante_atributos va ON pv.id = va.variante_id
        WHERE pv.activa = 1
        GROUP BY pv.id
        ORDER BY p.nombre, pv.sku_variante
        LIMIT 10
    ";
    
    $result = $mysqli->query($query);
    if ($result && $result->num_rows > 0) {
        echo "<div class='test success'>✅ Se encontraron variantes activas <span class='badge badge-success'>OK</span></div>";
        echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>SKU Variante</th>
                    <th>Atributos</th>
                    <th>Precio</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            $precio = $row['precio'] > 0 ? '$' . number_format($row['precio'], 2) : '<span style="color:red;">Sin precio</span>';
            $stock_class = $row['stock'] > 0 ? '' : 'style="color:red;"';
            $atributos = $row['atributos'] ?: '<em>Sin atributos</em>';
            
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['producto']}</td>
                <td>{$row['sku_variante']}</td>
                <td>{$atributos}</td>
                <td>{$precio}</td>
                <td $stock_class>{$row['stock']}</td>
            </tr>";
        }
        echo "</tbody></table>";
        $exitos[] = "Variantes activas encontradas";
    } else {
        echo "<div class='test warning'>⚠️ No se encontraron variantes activas <span class='badge badge-warning'>VACÍO</span></div>";
        $advertencias[] = "No hay variantes activas";
    }
}

// ============================================
// TEST 5: Permisos de Archivos
// ============================================
echo "<h2>🔒 Test 5: Permisos de Archivos</h2>";

$archivos_permisos = [
    'api/inventario.php',
    'pages/inventario.php',
    'config/database.php'
];

foreach ($archivos_permisos as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    if (file_exists($ruta)) {
        $perms = fileperms($ruta);
        $perms_oct = substr(sprintf('%o', $perms), -4);
        $readable = is_readable($ruta);
        
        if ($readable) {
            echo "<div class='test success'>✅ <strong>$archivo</strong> - Permisos: <span class='code'>$perms_oct</span> <span class='badge badge-success'>LEGIBLE</span></div>";
            $exitos[] = "Permisos OK en $archivo";
        } else {
            echo "<div class='test error'>❌ <strong>$archivo</strong> - Permisos: <span class='code'>$perms_oct</span> <span class='badge badge-error'>NO LEGIBLE</span></div>";
            $errores[] = "Archivo $archivo no legible";
        }
    }
}

// ============================================
// TEST 6: Test de API de Inventario
// ============================================
echo "<h2>🔗 Test 6: API de Inventario</h2>";

try {
    // Simular llamada a la API
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    
    // Intentar incluir la API
    if (file_exists(__DIR__ . '/api/inventario.php')) {
        // No podemos ejecutar directamente por la autenticación
        echo "<div class='test info'>ℹ️ API existe, pero requiere autenticación para prueba completa <span class='badge badge-info'>INFO</span></div>";
        echo "<div class='test warning'>⚠️ Para probar la API completamente, acceder a: <span class='code'>/stadium/api/inventario.php</span> después de hacer login <span class='badge badge-warning'>REQUIERE AUTH</span></div>";
        $advertencias[] = "API requiere autenticación para prueba completa";
    } else {
        echo "<div class='test error'>❌ API de inventario no encontrada <span class='badge badge-error'>ERROR</span></div>";
        $errores[] = "API de inventario no encontrada";
    }
    
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
    echo "<div class='test error'>❌ Error al probar API: " . htmlspecialchars($e->getMessage()) . " <span class='badge badge-error'>ERROR</span></div>";
    $errores[] = "Error en API: " . $e->getMessage();
}

// ============================================
// TEST 7: Verificar Extensiones PHP
// ============================================
echo "<h2>🔧 Test 7: Extensiones PHP Requeridas</h2>";

$extensiones_requeridas = ['mysqli', 'json', 'session'];

foreach ($extensiones_requeridas as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='test success'>✅ Extensión <strong>$ext</strong> cargada <span class='badge badge-success'>OK</span></div>";
        $exitos[] = "Extensión $ext cargada";
    } else {
        echo "<div class='test error'>❌ Extensión <strong>$ext</strong> NO cargada <span class='badge badge-error'>ERROR</span></div>";
        $errores[] = "Extensión $ext no cargada";
    }
}

// ============================================
// TEST 8: Variables de Sesión
// ============================================
echo "<h2>👤 Test 8: Sistema de Sesión</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='test success'>✅ Sesión PHP activa <span class='badge badge-success'>ACTIVA</span></div>";
    $exitos[] = "Sesión PHP activa";
    
    if (isset($_SESSION['user_id'])) {
        echo "<div class='test success'>✅ Usuario autenticado - ID: {$_SESSION['user_id']} <span class='badge badge-success'>AUTH</span></div>";
        $exitos[] = "Usuario autenticado";
    } else {
        echo "<div class='test warning'>⚠️ Usuario NO autenticado (necesario para acceder a inventario) <span class='badge badge-warning'>NO AUTH</span></div>";
        $advertencias[] = "Usuario no autenticado";
    }
} else {
    echo "<div class='test warning'>⚠️ Sesión PHP no iniciada <span class='badge badge-warning'>INACTIVA</span></div>";
    $advertencias[] = "Sesión PHP no iniciada";
}

// ============================================
// RESUMEN FINAL
// ============================================
echo "<h2>📊 Resumen de Pruebas</h2>";

$total_tests = count($exitos) + count($errores) + count($advertencias);

echo "<div class='test " . (empty($errores) ? 'success' : 'error') . "'>
    <strong>Total de pruebas:</strong> $total_tests<br>
    <strong style='color: #28a745;'>✅ Exitosas:</strong> " . count($exitos) . "<br>
    <strong style='color: #ffc107;'>⚠️ Advertencias:</strong> " . count($advertencias) . "<br>
    <strong style='color: #dc3545;'>❌ Errores:</strong> " . count($errores) . "
</div>";

if (!empty($errores)) {
    echo "<div class='test error'><h3>❌ Errores Críticos:</h3><ul>";
    foreach ($errores as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}

if (!empty($advertencias)) {
    echo "<div class='test warning'><h3>⚠️ Advertencias:</h3><ul>";
    foreach ($advertencias as $adv) {
        echo "<li>" . htmlspecialchars($adv) . "</li>";
    }
    echo "</ul></div>";
}

// ============================================
// RECOMENDACIONES
// ============================================
echo "<h2>💡 Recomendaciones</h2>";

if (!empty($errores)) {
    echo "<div class='test error'>
        <h3>🔴 Acción Inmediata Requerida:</h3>
        <ol>
            <li>Corregir los errores críticos listados arriba</li>
            <li>Verificar que todos los archivos estén subidos al servidor</li>
            <li>Verificar permisos de archivos (deben ser legibles por el servidor web)</li>
            <li>Verificar configuración de base de datos en <span class='code'>config/database.php</span></li>
        </ol>
    </div>";
}

if (empty($errores) && !empty($advertencias)) {
    echo "<div class='test warning'>
        <h3>🟡 Mejoras Sugeridas:</h3>
        <ol>
            <li>Agregar datos de prueba si las tablas están vacías</li>
            <li>Hacer login antes de acceder a inventario</li>
            <li>Verificar que las variantes tengan precio y stock definidos</li>
        </ol>
    </div>";
}

if (empty($errores) && empty($advertencias)) {
    echo "<div class='test success'>
        <h3>🟢 Sistema Listo:</h3>
        <p>✅ Todos los tests pasaron correctamente. El sistema de inventario debería funcionar sin problemas.</p>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Hacer login en el sistema</li>
            <li>Acceder a <a href='/stadium/pages/inventario'>/stadium/pages/inventario</a></li>
            <li>Verificar que se muestren las variantes correctamente</li>
        </ol>
    </div>";
}

// ============================================
// INFORMACIÓN DE DEBUGGING
// ============================================
echo "<h2>🐛 Información de Debugging</h2>";

echo "<div class='test info'>";
echo "<strong>Variables de entorno:</strong><br>";
echo "• Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "• Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "• Current Dir: " . __DIR__ . "<br>";
echo "• PHP Self: " . $_SERVER['PHP_SELF'] . "<br>";
echo "• Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "</div>";

// Log de errores PHP
$error_log = ini_get('error_log');
echo "<div class='test info'>";
echo "<strong>Log de errores PHP:</strong> " . ($error_log ?: 'No configurado') . "<br>";
echo "<strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "<strong>Error Reporting:</strong> " . error_reporting() . "<br>";
echo "</div>";

// ============================================
// FOOTER
// ============================================
echo "<div class='footer'>
    <p><strong>Script de prueba ejecutado correctamente</strong></p>
    <p>Fecha: " . date('Y-m-d H:i:s') . " | Servidor: " . $_SERVER['SERVER_NAME'] . "</p>
    <p>Para soporte adicional, revisar los archivos de log del servidor</p>
</div>";

echo "</div>
</body>
</html>";

// Cerrar conexión si existe
if ($mysqli) {
    $mysqli->close();
}
?>
