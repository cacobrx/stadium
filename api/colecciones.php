<?php
// api/colecciones.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar autenticación para todas las operaciones
requireAuthAPI();

// Conexión a la base de datos
function getConnection() {
    $mysqli = get_database_connection();
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión a la base de datos: ' . $mysqli->connect_error);
    }
    return $mysqli;
}

// GET - Obtener colecciones
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita una colección específica
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM colecciones WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($coleccion = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'coleccion' => $coleccion]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Colección no encontrada']);
            }
            $stmt->close();
        } else {
            // Obtener todas las colecciones
            $result = $mysqli->query('SELECT * FROM colecciones ORDER BY nombre');
            $colecciones = [];
            
            while ($row = $result->fetch_assoc()) {
                $colecciones[] = $row;
            }
            
            echo json_encode(['success' => true, 'colecciones' => $colecciones]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear colección
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }
        
        // Validar campos requeridos
        if (empty($data['nombre'])) {
            throw new Exception('El nombre es requerido');
        }
        
        $mysqli = getConnection();
        
        // Verificar si el nombre ya existe
        $stmt = $mysqli->prepare('SELECT id FROM colecciones WHERE nombre = ?');
        $stmt->bind_param('s', $data['nombre']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('Ya existe una colección con ese nombre');
        }
        $stmt->close();
        
        // Generar slug si no se proporciona
        $slug = !empty($data['slug']) ? $data['slug'] : strtolower(str_replace(' ', '-', $data['nombre']));
        
        // Verificar si el slug ya existe
        $stmt = $mysqli->prepare('SELECT id FROM colecciones WHERE slug = ?');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $slug = $slug . '-' . time();
        }
        $stmt->close();
        
        // Preparar datos con valores por defecto
        $nombre = $data['nombre'];
        $descripcion = $data['descripcion'] ?? '';
        $imagen = $data['imagen'] ?? '';
        $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        $fecha_fin = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
        $activa = intval($data['activa'] ?? 1);
        
        $stmt = $mysqli->prepare('INSERT INTO colecciones (nombre, slug, descripcion, imagen, fecha_inicio, fecha_fin, activa) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssi', $nombre, $slug, $descripcion, $imagen, $fecha_inicio, $fecha_fin, $activa);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Colección creada correctamente']);
        } else {
            throw new Exception('Error al crear colección: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar colección
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de colección requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la colección existe
        $stmt = $mysqli->prepare('SELECT id FROM colecciones WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Colección no encontrada');
        }
        $stmt->close();
        
        // Verificar si el nombre ya existe en otra colección
        if (!empty($data['nombre'])) {
            $stmt = $mysqli->prepare('SELECT id FROM colecciones WHERE nombre = ? AND id != ?');
            $stmt->bind_param('si', $data['nombre'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception('Ya existe otra colección con ese nombre');
            }
            $stmt->close();
        }
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['nombre', 'slug', 'descripcion', 'imagen', 'fecha_inicio', 'fecha_fin', 'activa'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'activa') {
                    $types .= 'i';
                    $values[] = intval($data[$field]);
                } else {
                    $types .= 's';
                    $values[] = ($data[$field] === '') ? null : $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $types .= 'i';
        $values[] = $id;
        
        $sql = 'UPDATE colecciones SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Colección actualizada correctamente']);
        } else {
            throw new Exception('Error al actualizar colección: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar colección
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de colección requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la colección existe
        $stmt = $mysqli->prepare('SELECT id FROM colecciones WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Colección no encontrada');
        }
        $stmt->close();
        
        // Eliminar colección
        $stmt = $mysqli->prepare('DELETE FROM colecciones WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Colección eliminada correctamente']);
        } else {
            throw new Exception('Error al eliminar colección: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>