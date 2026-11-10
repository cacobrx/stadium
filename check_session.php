<?php
// Página de diagnóstico de sesión
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

echo json_encode([
    'session_active' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'logged_in' => isLoggedIn(),
    'session_expired' => isSessionExpired(),
    'session_data' => $_SESSION ?? [],
    'current_user' => getCurrentUser()
], JSON_PRETTY_PRINT);
