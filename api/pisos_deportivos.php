<?php
// api/pisos_deportivos.php
// Wrapper sencillo que delega a la implementación existente en
// api/pisosdeportivos/datos.php para mantener compatibilidad con
// el frontend y exponer un endpoint tipo "single-file" similar a
// otros como api/equipamiento_profesional.php

// Si por algún motivo se quiere una implementación independiente
// se puede reemplazar este archivo por la lógica completa.

$target = __DIR__ . '/pisosdeportivos/datos.php';

if (file_exists($target)) {
    // Delegar la ejecución al archivo existente
    require $target;
    exit(0);
}

// Si no existe la implementación devolvemos 404 JSON
header('Content-Type: application/json');
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado: pisosdeportivos/datos.php']);
