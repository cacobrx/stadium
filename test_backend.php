<?php
$url = 'http://localhost/stadium/api/pisos_deportivos_tipos.php';
$data = array(
    'tiposPisos' => array(
        array(
            'nombre' => 'Test Auto ' . rand(100,999),
            'slug' => 'test-auto',
            'etiqueta' => 'New',
            'descripcion' => 'Descr',
            'imagen' => '',
            'caracteristicas' => ['A','B'],
            'aplicaciones' => ['C'],
            'certificaciones' => [],
            'especificaciones' => [],
            'orden' => 1,
            'esta_activo' => 1
        )
    )
);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);
$context  = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) { 
    echo "Error al conectar con $url\n";
    print_r(error_get_last());
} else {
    echo "Respuesta: " . $result . "\n";
}
?>
