<?php
// includes/bootstrap.php
// Arranca la sesión lo antes posible y configura controles básicos
if (session_status() === PHP_SESSION_NONE) {
    // Solo iniciar si headers no fueron enviados
    if (!headers_sent()) {
        session_start();
    } else {
        // Registrar evento para debugging
        error_log("includes/bootstrap.php: headers already sent, session not started for " . ($_SERVER['REQUEST_URI'] ?? 'cli'));
    }
}

// Establecer zona horaria por defecto (opcional)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

?>