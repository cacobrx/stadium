<?php
// api/equipamiento_profesional_hero.php
// Endpoint para obtener/actualizar la sección Hero de Equipamiento Profesional

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

requireAuthAPI();

function getConnection() {
    return get_database_connection();
}

try {
    $mysqli = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $check = $mysqli->query("SHOW TABLES LIKE 'equipamiento_profesional_hero'");
        if ($check->num_rows == 0) {
            // Crear tabla si no existe
            $sql = "CREATE TABLE IF NOT EXISTS equipamiento_profesional_hero (
                id INT PRIMARY KEY DEFAULT 1,
                etiqueta VARCHAR(255),
                titulo VARCHAR(255),
                titulo_destacado VARCHAR(255),
                descripcion TEXT,
                imagen_fondo VARCHAR(255),
                boton_principal VARCHAR(255),
                boton_principal_link VARCHAR(255),
                boton_secundario VARCHAR(255),
                boton_secundario_link VARCHAR(255),
                badges TEXT,
                esta_activo TINYINT(1) DEFAULT 1
            )";
            $mysqli->query($sql);
        } else {
             // Verificar columnas nuevas y agregarlas si faltan
             $cols = $mysqli->query("SHOW COLUMNS FROM equipamiento_profesional_hero LIKE 'boton_principal_link'");
             if($cols->num_rows == 0) {
                 $mysqli->query("ALTER TABLE equipamiento_profesional_hero ADD COLUMN boton_principal_link VARCHAR(255) AFTER boton_principal");
             }
             $cols = $mysqli->query("SHOW COLUMNS FROM equipamiento_profesional_hero LIKE 'boton_secundario_link'");
             if($cols->num_rows == 0) {
                 $mysqli->query("ALTER TABLE equipamiento_profesional_hero ADD COLUMN boton_secundario_link VARCHAR(255) AFTER boton_secundario");
             }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Intentar obtener la configuración
        $res = $mysqli->query("SELECT * FROM equipamiento_profesional_hero WHERE id = 1");
        $hero = $res->fetch_assoc();
        
        if (!$hero) {
            // valores por defecto solicitados
            $def_etiqueta = '🏟️ Equipamiento Profesional';
            $def_titulo = 'Equipamiento';
            $def_tit_dest = 'Profesional';
            $def_desc = 'Soluciones integrales para canchas, clubes y complejos deportivos. Equipamiento de primera calidad con instalación profesional.';
            $def_badges = 'Certificación FIBA,Instalación profesional,Financiación disponible';
            $def_btn1 = 'Ver Catálogo';
            $def_btn2 = 'Solicitar Cotización';
            
            // Insertar
            $stmt = $mysqli->prepare("INSERT INTO equipamiento_profesional_hero 
                (id, etiqueta, titulo, titulo_destacado, descripcion, imagen_fondo, boton_principal, boton_principal_link, boton_secundario, boton_secundario_link, badges, esta_activo) 
                VALUES (1, ?, ?, ?, ?, '', ?, '', ?, '', ?, 1)");
            
            $stmt->bind_param("ssssssss", $def_etiqueta, $def_titulo, $def_tit_dest, $def_desc, $def_btn1, $def_btn2, $def_badges);
            $stmt->execute();
            
            $res = $mysqli->query("SELECT * FROM equipamiento_profesional_hero WHERE id = 1");
            $hero = $res->fetch_assoc();
        }
        
        echo json_encode(['success' => true, 'data' => $hero]);
        $mysqli->close();
        exit(0);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('JSON inválido: ' . json_last_error_msg());

        $h = $input['hero'] ?? $input;
        $etiqueta = $h['etiqueta'] ?? '';
        $titulo = $h['titulo'] ?? '';
        $titulo_destacado = $h['titulo_destacado'] ?? '';
        $descripcion = $h['descripcion'] ?? '';
        $imagen_fondo = $h['imagen_fondo'] ?? '';
        $boton_principal = $h['boton_principal'] ?? '';
        $boton_principal_link = $h['boton_principal_link'] ?? '';
        $boton_secundario = $h['boton_secundario'] ?? '';
        $boton_secundario_link = $h['boton_secundario_link'] ?? '';
        $badges = is_array($h['badges']) ? implode(',', $h['badges']) : ($h['badges'] ?? '');
        $esta_activo = isset($h['esta_activo']) ? intval($h['esta_activo']) : 1;

        $stmt = $mysqli->prepare("
            INSERT INTO equipamiento_profesional_hero 
            (id, etiqueta, titulo, titulo_destacado, descripcion, imagen_fondo, boton_principal, boton_principal_link, boton_secundario, boton_secundario_link, badges, esta_activo)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                etiqueta = VALUES(etiqueta),
                titulo = VALUES(titulo),
                titulo_destacado = VALUES(titulo_destacado),
                descripcion = VALUES(descripcion),
                imagen_fondo = VALUES(imagen_fondo),
                boton_principal = VALUES(boton_principal),
                boton_principal_link = VALUES(boton_principal_link),
                boton_secundario = VALUES(boton_secundario),
                boton_secundario_link = VALUES(boton_secundario_link),
                badges = VALUES(badges),
                esta_activo = VALUES(esta_activo)
        ");
        
        $stmt->bind_param("ssssssssssi", 
            $etiqueta, $titulo, $titulo_destacado, $descripcion, $imagen_fondo, 
            $boton_principal, $boton_principal_link, $boton_secundario, $boton_secundario_link, 
            $badges, $esta_activo
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }

        echo json_encode(['success' => true]);
        $mysqli->close();
        exit(0);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
