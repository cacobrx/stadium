<?php
// api/proyectos/lista.php - API para proyectos de Stadium
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// URL base para las imágenes
$base_url = "https://stadium.planbsistemas.com.ar/";

try {
    // Datos del proyecto
    $proyectos = [
        [
            'id' => '1',
            'slug' => 'polideportivo-islas-malvinas',
            'title' => 'Polideportivo Islas Malvinas',
            'subtitle' => 'Instalación completa de piso deportivo profesional',
            'badge' => 'Proyecto Destacado',
            'heroImage' => $base_url . 'stadium-deportes/assets/estadios/polideportivo-islas-malvinas.jpg',
            'description' => 'Proyecto integral de instalación de piso deportivo de alto rendimiento en el Polideportivo Islas Malvinas. Este complejo deportivo de primer nivel ahora cuenta con una superficie certificada FIBA Level 1, diseñada específicamente para competencias profesionales de baloncesto y otros deportes de interior. La instalación incluyó la preparación del sustrato, nivelación avanzada, y la aplicación de un sistema multicapa que garantiza óptima absorción de impactos y tracción.',
            'location' => 'Buenos Aires, Argentina',
            'client' => 'Municipalidad de Buenos Aires',
            'completionDate' => 'Marzo 2024',
            'area' => '1,200 m²',
            'duration' => '3 meses',
            'categories' => ['Baloncesto', 'Voleibol', 'Multipropósito'],
            'specifications' => [
                ['label' => 'Material Principal', 'value' => 'Piso Sintético Poliuretano'],
                ['label' => 'Certificación', 'value' => 'FIBA Level 1'],
                ['label' => 'Espesor Total', 'value' => '6.5 mm'],
                ['label' => 'Absorción de Impactos', 'value' => '45% - EN 14808'],
                ['label' => 'Coeficiente de Fricción', 'value' => '0.6 - EN 13036-4'],
                ['label' => 'Resistencia al Deslizamiento', 'value' => 'Clase 2 (Óptima)'],
                ['label' => 'Deformación Vertical', 'value' => '2.3 mm'],
                ['label' => 'Rebote de Balón', 'value' => '95%'],
                ['label' => 'Color', 'value' => 'Azul/Rojo con marcaciones blancas'],
                ['label' => 'Garantía', 'value' => '10 años'],
                ['label' => 'Mantenimiento', 'value' => 'Bajo - Limpieza regular'],
                ['label' => 'Vida Útil Estimada', 'value' => '15-20 años']
            ],
            'features' => [
                [
                    'icon' => 'shield-check',
                    'title' => 'Certificación Internacional',
                    'description' => 'Cumple con todos los estándares FIBA para competencias profesionales de nivel 1.'
                ],
                [
                    'icon' => 'activity',
                    'title' => 'Alta Absorción de Impactos',
                    'description' => 'Sistema de amortiguación que reduce el estrés en articulaciones y previene lesiones.'
                ],
                [
                    'icon' => 'droplet',
                    'title' => 'Superficie Antideslizante',
                    'description' => 'Tracción óptima en todas las condiciones para máxima seguridad y rendimiento.'
                ],
                [
                    'icon' => 'zap',
                    'title' => 'Respuesta Elástica',
                    'description' => 'Retorno de energía del 95% para un mejor rendimiento deportivo.'
                ],
                [
                    'icon' => 'sun',
                    'title' => 'Resistencia UV',
                    'description' => 'Materiales estabilizados contra radiación ultravioleta para durabilidad prolongada.'
                ],
                [
                    'icon' => 'tool',
                    'title' => 'Fácil Mantenimiento',
                    'description' => 'Superficie de fácil limpieza que mantiene sus propiedades con mínimo mantenimiento.'
                ]
            ],
            'gallery' => [
                [
                    'id' => '1',
                    'url' => $base_url . 'stadium-deportes/assets/estadios/polideportivo-islas-malvinas.jpg',
                    'alt' => 'Vista general del polideportivo',
                    'caption' => 'Vista panorámica de la cancha principal'
                ],
                [
                    'id' => '2',
                    'url' => $base_url . 'stadium-deportes/assets/estadios/club-gei.jpg',
                    'alt' => 'Detalle de marcaciones',
                    'caption' => 'Marcaciones precisas para baloncesto y voleibol'
                ],
                [
                    'id' => '3',
                    'url' => $base_url . 'stadium-deportes/assets/estadios/estadio-gimnasia-ituzaingo.jpg',
                    'alt' => 'Sistema de absorción',
                    'caption' => 'Detalle del sistema multicapa de absorción de impactos'
                ],
                [
                    'id' => '4',
                    'url' => $base_url . 'stadium-deportes/assets/estadios/polideportivo-islas-malvinas.jpg',
                    'alt' => 'Instalación en proceso',
                    'caption' => 'Proceso de instalación del piso deportivo'
                ]
            ]
        ]
    ];

    // Manejar diferentes tipos de consultas
    $action = $_GET['action'] ?? 'all';
    $id = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;

    switch($action) {
        case 'single':
            if ($id) {
                $proyecto = array_filter($proyectos, function($p) use ($id) {
                    return $p['id'] == $id;
                });
                $proyecto = array_values($proyecto);
                echo json_encode([
                    'success' => true,
                    'data' => $proyecto[0] ?? null,
                    'message' => $proyecto ? 'Proyecto encontrado' : 'Proyecto no encontrado'
                ]);
            } elseif ($slug) {
                $proyecto = array_filter($proyectos, function($p) use ($slug) {
                    return $p['slug'] == $slug;
                });
                $proyecto = array_values($proyecto);
                echo json_encode([
                    'success' => true,
                    'data' => $proyecto[0] ?? null,
                    'message' => $proyecto ? 'Proyecto encontrado' : 'Proyecto no encontrado'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Se requiere ID o slug para obtener un proyecto específico'
                ]);
            }
            break;

        case 'all':
        default:
            echo json_encode([
                'success' => true,
                'data' => $proyectos,
                'count' => count($proyectos),
                'message' => 'Lista de proyectos obtenida exitosamente'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>
