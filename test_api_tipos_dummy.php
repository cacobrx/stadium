<?php
// test_api_tipos.php
require_once 'includes/bootstrap.php';

$url = 'http://localhost/stadium/api/pisos_deportivos_tipos.php'; // Ajustar según entorno, pero usaré ruta relativa en fetch si pudiera, aquí emularé post.

// Simular payload
$payload = [
    'tiposPisos' => [
        [
            'nombre' => 'Test Piso ' . time(),
            'slug' => 'test-piso',
            'etiqueta' => 'Nuevo',
            'descripcion' => 'Desc de prueba',
            'imagen' => '',
            'caracteristicas' => ['Car1', 'Car2'],
            'aplicaciones' => ['App1'],
            'certificaciones' => [],
            'especificaciones' => [['etiqueta' => 'Grosor', 'valor' => '5mm']],
            'orden' => 1,
            'esta_activo' => 1
        ]
    ]
];

$ch = curl_init('http://localhost/stadium/api/pisos_deportivos_tipos.php'); // Intentaré localhost, puede fallar si el puerto/host es distinto
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
// Necesitamos cookies de sesión si hay auth
// Pero como soy una IA y no tengo sesión activa fácil, voy a revisar el archivo auth.php.
// El archivo api requiereAuthAPI().
?>
