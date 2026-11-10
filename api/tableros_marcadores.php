<?php
// api/tableros_marcadores.php
// API para gestionar la configuración de la landing de Tableros y Marcadores
// Configuración ÚNICA (ID = 1) - No hay múltiples registros

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar autenticación para todas las operaciones
requireAuthAPI();

// Conexión a la base de datos
function getConnection() {
    return get_database_connection();
}

// URL base para las imágenes en producción
$base_url = "https://stadium.planbsistemas.com.ar/";

// Función para construir URL completa de imagen
function buildImageUrl($relativeUrl, $baseUrl) {
    if (empty($relativeUrl)) return '';
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
    $relativeUrl = ltrim($relativeUrl, '/');
    return $baseUrl . $relativeUrl;
}

// GET - Obtener toda la configuración
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Obtener configuración principal
        $result = $mysqli->query("SELECT * FROM tableros_marcadores_config WHERE id = 1");
        $config = $result->fetch_assoc();
        
        if (!$config) {
            // Si no existe, crear registro inicial
            $mysqli->query("INSERT INTO tableros_marcadores_config (id, titulo_pagina, esta_activo) VALUES (1, 'Tableros y Marcadores Profesionales', 1) ON DUPLICATE KEY UPDATE id=1");
            $result = $mysqli->query("SELECT * FROM tableros_marcadores_config WHERE id = 1");
            $config = $result->fetch_assoc();
        }
        
        // Obtener slides del hero
        $result = $mysqli->query("
            SELECT id, etiqueta, titulo, titulo_destacado, descripcion, imagen, estadisticas, orden, esta_activo
            FROM tableros_marcadores_slides 
            WHERE config_id = 1 
            ORDER BY orden ASC, id ASC
        ");
        $slides = [];
        global $base_url;
        while ($row = $result->fetch_assoc()) {
            $row['estadisticas'] = json_decode($row['estadisticas'], true) ?: [];
            // Construir URL completa para la imagen
            if (!empty($row['imagen'])) {
                $row['imagen'] = buildImageUrl($row['imagen'], $base_url);
            }
            $slides[] = $row;
        }
        
        // Obtener beneficios
        $result = $mysqli->query("
            SELECT id, titulo, descripcion, icono, orden, esta_activo
            FROM tableros_marcadores_beneficios 
            WHERE config_id = 1 
            ORDER BY orden ASC, id ASC
        ");
        $beneficios = $result->fetch_all(MYSQLI_ASSOC);
        
        // Obtener proceso de instalación
        $result = $mysqli->query("
            SELECT id, numero, titulo, descripcion, duracion, orden, esta_activo
            FROM tableros_marcadores_proceso 
            WHERE config_id = 1 
            ORDER BY orden ASC, numero ASC
        ");
        $proceso = $result->fetch_all(MYSQLI_ASSOC);
        
        // Obtener galería
        $result = $mysqli->query("
            SELECT id, titulo, categoria, url, alt, orden, esta_activo
            FROM tableros_marcadores_galeria 
            WHERE config_id = 1 
            ORDER BY orden ASC, id ASC
        ");
        $galeria = [];
        while ($row = $result->fetch_assoc()) {
            // Construir URL completa para la imagen
            if (!empty($row['url'])) {
                $row['url'] = buildImageUrl($row['url'], $base_url);
            }
            $galeria[] = $row;
        }
        
        // Obtener categorías únicas de la galería (dinámico)
        $result = $mysqli->query("
            SELECT DISTINCT categoria 
            FROM tableros_marcadores_galeria 
            WHERE config_id = 1 AND esta_activo = 1
            ORDER BY categoria ASC
        ");
        $categoriasGaleria = ['Todos'];
        while ($row = $result->fetch_assoc()) {
            $categoriasGaleria[] = $row['categoria'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'config' => $config,
                'slidesHero' => $slides,
                'beneficios' => $beneficios,
                'procesoInstalacion' => $proceso,
                'galeria' => $galeria,
                'categoriasGaleria' => $categoriasGaleria
            ]
        ]);
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar toda la configuración
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            throw new Exception('No se recibieron datos');
        }
        
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido: ' . json_last_error_msg());
        }
        
        $mysqli = getConnection();
        $mysqli->begin_transaction();
        
        try {
            // Actualizar configuración principal
            if (isset($data['config'])) {
                $config = $data['config'];
                $stmt = $mysqli->prepare("
                    UPDATE tableros_marcadores_config 
                    SET titulo_pagina = ?, descripcion_pagina = ?, esta_activo = ?
                    WHERE id = 1
                ");
                $titulo = $config['titulo_pagina'] ?? 'Tableros y Marcadores';
                $descripcion = $config['descripcion_pagina'] ?? '';
                $activo = isset($config['esta_activo']) ? intval($config['esta_activo']) : 1;
                $stmt->bind_param('ssi', $titulo, $descripcion, $activo);
                $stmt->execute();
            }
            
            // Actualizar slides del hero
            if (isset($data['slidesHero'])) {
                // Eliminar slides existentes
                $mysqli->query("DELETE FROM tableros_marcadores_slides WHERE config_id = 1");
                
                // Insertar nuevos slides
                $stmt = $mysqli->prepare("
                    INSERT INTO tableros_marcadores_slides 
                    (config_id, etiqueta, titulo, titulo_destacado, descripcion, imagen, estadisticas, orden, esta_activo)
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['slidesHero'] as $index => $slide) {
                    $etiqueta = $slide['etiqueta'] ?? '';
                    $titulo = $slide['titulo'] ?? '';
                    $tituloDestacado = $slide['titulo_destacado'] ?? $slide['tituloDestacado'] ?? '';
                    $descripcion = $slide['descripcion'] ?? '';
                    $imagen = $slide['imagen'] ?? '';
                    $estadisticas = json_encode($slide['estadisticas'] ?? []);
                    $orden = $slide['orden'] ?? $index + 1;
                    $activo = isset($slide['esta_activo']) ? intval($slide['esta_activo']) : 1;
                    
                    $stmt->bind_param('ssssssii', $etiqueta, $titulo, $tituloDestacado, $descripcion, $imagen, $estadisticas, $orden, $activo);
                    $stmt->execute();
                }
            }
            
            // Actualizar beneficios
            if (isset($data['beneficios'])) {
                // Eliminar beneficios existentes
                $mysqli->query("DELETE FROM tableros_marcadores_beneficios WHERE config_id = 1");
                
                // Insertar nuevos beneficios
                $stmt = $mysqli->prepare("
                    INSERT INTO tableros_marcadores_beneficios 
                    (config_id, titulo, descripcion, icono, orden, esta_activo)
                    VALUES (1, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['beneficios'] as $index => $beneficio) {
                    $titulo = $beneficio['titulo'] ?? '';
                    $descripcion = $beneficio['descripcion'] ?? '';
                    $icono = $beneficio['icono'] ?? null;
                    $orden = $beneficio['orden'] ?? $index + 1;
                    $activo = isset($beneficio['esta_activo']) ? intval($beneficio['esta_activo']) : 1;
                    
                    $stmt->bind_param('sssii', $titulo, $descripcion, $icono, $orden, $activo);
                    $stmt->execute();
                }
            }
            
            // Actualizar proceso de instalación
            if (isset($data['procesoInstalacion'])) {
                // Eliminar proceso existente
                $mysqli->query("DELETE FROM tableros_marcadores_proceso WHERE config_id = 1");
                
                // Insertar nuevo proceso
                $stmt = $mysqli->prepare("
                    INSERT INTO tableros_marcadores_proceso 
                    (config_id, numero, titulo, descripcion, duracion, orden, esta_activo)
                    VALUES (1, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['procesoInstalacion'] as $index => $paso) {
                    $numero = $paso['numero'] ?? $index + 1;
                    $titulo = $paso['titulo'] ?? '';
                    $descripcion = $paso['descripcion'] ?? '';
                    $duracion = $paso['duracion'] ?? '';
                    $orden = $paso['orden'] ?? $index + 1;
                    $activo = isset($paso['esta_activo']) ? intval($paso['esta_activo']) : 1;
                    
                    $stmt->bind_param('isssii', $numero, $titulo, $descripcion, $duracion, $orden, $activo);
                    $stmt->execute();
                }
            }
            
            // Actualizar galería
            if (isset($data['galeria'])) {
                // Eliminar galería existente
                $mysqli->query("DELETE FROM tableros_marcadores_galeria WHERE config_id = 1");
                
                // Insertar nueva galería
                $stmt = $mysqli->prepare("
                    INSERT INTO tableros_marcadores_galeria 
                    (config_id, titulo, categoria, url, alt, orden, esta_activo)
                    VALUES (1, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['galeria'] as $index => $imagen) {
                    $titulo = $imagen['titulo'] ?? '';
                    $categoria = $imagen['categoria'] ?? 'Sin categoría';
                    $url = $imagen['url'] ?? '';
                    $alt = $imagen['alt'] ?? '';
                    $orden = $imagen['orden'] ?? $index + 1;
                    $activo = isset($imagen['esta_activo']) ? intval($imagen['esta_activo']) : 1;
                    
                    $stmt->bind_param('ssssii', $titulo, $categoria, $url, $alt, $orden, $activo);
                    $stmt->execute();
                }
            }
            
            $mysqli->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            throw $e;
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Método no permitido
else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
