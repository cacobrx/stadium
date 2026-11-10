<?php
// includes/auth.php - Sistema de autenticación

// Iniciar sesión si no está iniciada y si es posible (evitar warnings si ya se enviaron headers)
if (session_status() == PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    } else {
        // No podemos iniciar sesión porque ya se enviaron headers. Registramos para debugging.
        error_log("includes/auth.php: headers already sent, session not started for " . ($_SERVER['REQUEST_URI'] ?? 'cli'));
    }
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    // Asegurarnos de que la sesión esté activa antes de leer variables
    if (session_status() !== PHP_SESSION_ACTIVE) return false;
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'nombre' => $_SESSION['user_nombre'],
        'login_time' => $_SESSION['login_time']
    ];
}

/**
 * Verificar si la sesión ha expirado (opcional - 2 horas)
 */
function isSessionExpired($timeout = 7200) { // 2 horas por defecto
    if (!isLoggedIn()) {
        return true;
    }
    
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        return true;
    }
    
    return false;
}

/**
 * Requerir autenticación - redirige al login si no está logueado
 */
function requireAuth($redirectTo = 'inicio') {
    if (!isLoggedIn() || isSessionExpired()) {
        // Limpiar sesión si está expirada
        if (isSessionExpired()) {
            session_destroy();
        }
        
        // Redirigir al login
        header("Location: $redirectTo");
        exit;
    }
    
    // Actualizar tiempo de actividad
    $_SESSION['last_activity'] = time();
}

/**
 * Logout - destruir sesión
 */
function logout($redirectTo = 'inicio') {
    session_start();
    session_destroy();
    header("Location: $redirectTo");
    exit;
}

/**
 * Verificar autenticación para APIs (JSON response)
 */
function requireAuthAPI() {
    header('Content-Type: application/json');
    
    if (!isLoggedIn() || isSessionExpired()) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'No autenticado',
            'code' => 'NOT_AUTHENTICATED'
        ]);
        exit;
    }
    
    // Actualizar tiempo de actividad
    $_SESSION['last_activity'] = time();
}

/**
 * Obtener información de la sesión para debugging
 */
function getSessionInfo() {
    return [
        'logged_in' => isLoggedIn(),
        'session_expired' => isSessionExpired(),
        'current_user' => getCurrentUser(),
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? []
    ];
}
?>