<?php
/**
 * Diagnóstico de Sesión - Stadium
 * Verifica y diagnostica problemas relacionados con sesiones PHP
 */

// Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Arrancar buffer de salida para evitar "headers already sent" y permitir session_start()
ob_start();

// Intentar iniciar sesión lo antes posible
if (session_status() === PHP_SESSION_NONE) {
    // Intentamos iniciar, pero capturamos warnings configurando un handler simple
    try {
        session_start();
    } catch (Throwable $e) {
        // No hacemos nada aquí: el script de diagnóstico comprobará el estado luego
    }
}

// Ahora podemos imprimir sin problemas
echo "<pre style='background: #1e1e1e; color: #00ff00; padding: 20px; font-family: monospace;'>";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         DIAGNÓSTICO DE SESIÓN - STADIUM                 ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📅 Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "🌐 Servidor: " . $_SERVER['SERVER_NAME'] . "\n";
echo "🐘 PHP: " . phpversion() . "\n\n";

// ========================================
// TEST 1: Estado Inicial de la Sesión
// ========================================
echo "═══════════════════════════════════════════════════════════\n";
echo "TEST 1: ESTADO INICIAL DE LA SESIÓN\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$status_before = session_status();
$status_text = match($status_before) {
    PHP_SESSION_DISABLED => '❌ DESHABILITADA (sessions desactivadas en php.ini)',
    PHP_SESSION_NONE => '⚠️  NO INICIADA (esto es normal antes de session_start)',
    PHP_SESSION_ACTIVE => '✅ YA ACTIVA (session_start ya fue llamado)',
    default => '❓ DESCONOCIDO'
};

echo "Estado antes de session_start(): $status_text\n";
echo "Session ID antes: " . (session_id() ?: '(vacío)') . "\n\n";

// ========================================
// TEST 2: Configuración de Sesión PHP
// ========================================
echo "═══════════════════════════════════════════════════════════\n";
echo "TEST 2: CONFIGURACIÓN DE SESIÓN PHP\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$session_configs = [
    'session.save_handler' => ini_get('session.save_handler'),
    'session.save_path' => ini_get('session.save_path'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.cookie_path' => ini_get('session.cookie_path'),
    'session.cookie_domain' => ini_get('session.cookie_domain'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
    'session.name' => ini_get('session.name'),
    'session.auto_start' => ini_get('session.auto_start'),
    'session.gc_probability' => ini_get('session.gc_probability'),
    'session.gc_divisor' => ini_get('session.gc_divisor'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime')
];

foreach ($session_configs as $key => $value) {
    $display_value = $value === '' ? '(vacío)' : $value;
    $display_value = $value === false ? 'Off' : $display_value;
    $display_value = $value === true || $value === '1' ? 'On' : $display_value;
    echo sprintf("%-30s : %s\n", $key, $display_value);
}

// ========================================
// TEST 3: Permisos del Directorio de Sesiones
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 3: PERMISOS DEL DIRECTORIO DE SESIONES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$save_path = session_save_path() ?: sys_get_temp_dir();
echo "Directorio de sesiones: $save_path\n";

if (file_exists($save_path)) {
    echo "✅ Directorio existe\n";
    echo "Permisos: " . substr(sprintf('%o', fileperms($save_path)), -4) . "\n";
    echo "Es escribible: " . (is_writable($save_path) ? '✅ Sí' : '❌ No') . "\n";
    echo "Es legible: " . (is_readable($save_path) ? '✅ Sí' : '❌ No') . "\n";
    echo "Propietario: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($save_path))['name'] : 'N/A') . "\n";
} else {
    echo "❌ Directorio NO existe\n";
}

// ========================================
// TEST 4: Intentar Iniciar Sesión
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 4: INTENTAR INICIAR SESIÓN\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    if ($status_before !== PHP_SESSION_ACTIVE) {
        session_start();
        echo "✅ session_start() ejecutado exitosamente\n";
    } else {
        echo "ℹ️  Sesión ya estaba activa\n";
    }
    
    $status_after = session_status();
    echo "Estado después: " . match($status_after) {
        PHP_SESSION_ACTIVE => '✅ ACTIVA',
        PHP_SESSION_NONE => '❌ NO INICIADA',
        PHP_SESSION_DISABLED => '❌ DESHABILITADA',
        default => '❓ DESCONOCIDO'
    } . "\n";
    
    echo "Session ID: " . session_id() . "\n";
    echo "Session Name: " . session_name() . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR al iniciar sesión:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
}

// ========================================
// TEST 5: Probar Escritura en Sesión
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 5: PROBAR ESCRITURA EN SESIÓN\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (session_status() === PHP_SESSION_ACTIVE) {
    try {
        $_SESSION['test_debug'] = 'Prueba ' . date('Y-m-d H:i:s');
        $_SESSION['test_number'] = rand(1000, 9999);
        echo "✅ Escritura en sesión exitosa\n";
        echo "   test_debug: {$_SESSION['test_debug']}\n";
        echo "   test_number: {$_SESSION['test_number']}\n";
    } catch (Exception $e) {
        echo "❌ Error al escribir en sesión: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No se puede probar (sesión no activa)\n";
}

// ========================================
// TEST 6: Variables de Sesión Actuales
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 6: VARIABLES DE SESIÓN ACTUALES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (session_status() === PHP_SESSION_ACTIVE) {
    if (!empty($_SESSION)) {
        echo "Variables encontradas: " . count($_SESSION) . "\n\n";
        foreach ($_SESSION as $key => $value) {
            $type = gettype($value);
            if (is_scalar($value)) {
                $display = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                echo "• $key ($type): $display\n";
            } else {
                echo "• $key ($type): " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    } else {
        echo "⚠️  No hay variables en la sesión\n";
        echo "   (Esto es normal si no has hecho login)\n";
    }
} else {
    echo "❌ No se pueden leer (sesión no activa)\n";
}

// ========================================
// TEST 7: Cookies de Sesión
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 7: COOKIES DE SESIÓN\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$session_name = session_name();
echo "Nombre de cookie de sesión: $session_name\n";

if (isset($_COOKIE[$session_name])) {
    echo "✅ Cookie de sesión presente en el navegador\n";
    echo "   Valor: " . substr($_COOKIE[$session_name], 0, 20) . "...\n";
} else {
    echo "❌ Cookie de sesión NO presente\n";
    echo "   Posibles causas:\n";
    echo "   • Primera vez que accedes (cookie no creada aún)\n";
    echo "   • Cookie bloqueada por navegador\n";
    echo "   • Configuración cookie_secure incompatible\n";
}

if (!empty($_COOKIE)) {
    echo "\nOtras cookies presentes:\n";
    foreach ($_COOKIE as $name => $value) {
        if ($name !== $session_name) {
            echo "   • $name\n";
        }
    }
}

// ========================================
// TEST 8: Headers Enviados
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 8: HEADERS ENVIADOS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (headers_sent($file, $line)) {
    echo "⚠️  Headers ya fueron enviados\n";
    echo "   Archivo: $file\n";
    echo "   Línea: $line\n";
    echo "   Esto puede impedir que session_start() funcione\n";
} else {
    echo "✅ Headers no enviados aún (correcto)\n";
}

// ========================================
// TEST 9: Verificar Auth de Stadium
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 9: VERIFICAR SISTEMA AUTH DE STADIUM\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (file_exists(__DIR__ . '/includes/auth.php')) {
    echo "✅ Archivo includes/auth.php existe\n";
    try {
        require_once __DIR__ . '/includes/auth.php';
        echo "✅ Archivo auth.php cargado\n";
        
        // Verificar si hay usuario autenticado según el sistema
        if (isset($_SESSION['user_id'])) {
            echo "✅ Usuario autenticado en Stadium\n";
            echo "   User ID: {$_SESSION['user_id']}\n";
            if (isset($_SESSION['user_nombre'])) {
                echo "   Nombre: {$_SESSION['user_nombre']}\n";
            }
            if (isset($_SESSION['user_email'])) {
                echo "   Email: {$_SESSION['user_email']}\n";
            }
        } else {
            echo "❌ Usuario NO autenticado\n";
            echo "   → Debes hacer LOGIN primero\n";
            echo "   → Accede a: /stadium/pages/login\n";
        }
    } catch (Exception $e) {
        echo "❌ Error al cargar auth.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Archivo includes/auth.php NO existe\n";
}

// ========================================
// TEST 10: Probar Archivo de Sesión
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "TEST 10: ARCHIVO DE SESIÓN EN DISCO\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (session_status() === PHP_SESSION_ACTIVE) {
    $session_file = $save_path . '/sess_' . session_id();
    echo "Ruta esperada: $session_file\n";
    
    if (file_exists($session_file)) {
        echo "✅ Archivo de sesión existe\n";
        echo "Tamaño: " . filesize($session_file) . " bytes\n";
        echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($session_file)) . "\n";
        
        if (is_readable($session_file)) {
            $content = file_get_contents($session_file);
            echo "Contenido (primeros 200 chars):\n";
            echo substr($content, 0, 200) . "\n";
        }
    } else {
        echo "⚠️  Archivo de sesión NO existe aún\n";
        echo "   (Puede crearse después del primer write)\n";
    }
}

// ========================================
// RESUMEN FINAL
// ========================================
echo "\n═══════════════════════════════════════════════════════════\n";
echo "RESUMEN Y RECOMENDACIONES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$problemas = [];
$recomendaciones = [];

if (session_status() !== PHP_SESSION_ACTIVE) {
    $problemas[] = "❌ La sesión NO está activa";
    $recomendaciones[] = "Verificar configuración de PHP y permisos";
}

if (!is_writable($save_path)) {
    $problemas[] = "❌ Directorio de sesiones no es escribible";
    $recomendaciones[] = "Ejecutar: chmod 777 $save_path (temporalmente para test)";
}

if (!isset($_SESSION['user_id'])) {
    $problemas[] = "⚠️  Usuario NO autenticado";
    $recomendaciones[] = "Hacer LOGIN en /stadium/pages/login primero";
}

if (empty($problemas)) {
    echo "✅✅✅ TODO CORRECTO ✅✅✅\n\n";
    echo "La sesión funciona perfectamente.\n";
    echo "Si el inventario sigue sin funcionar, el problema está en otro lado.\n";
} else {
    echo "PROBLEMAS ENCONTRADOS:\n";
    foreach ($problemas as $p) {
        echo "$p\n";
    }
    echo "\nRECOMENDACIONES:\n";
    foreach ($recomendaciones as $r) {
        echo "→ $r\n";
    }
}

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║                    FIN DEL DIAGNÓSTICO                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "</pre>";
?>
