<?php
// api/categorias.php
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

// GET - Obtener categorías
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita una categoría específica
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM categorias WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($categoria = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'categoria' => $categoria]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Categoría no encontrada']);
            }
            $stmt->close();
        } else {
            // Obtener todas las categorías
            $result = $mysqli->query('SELECT * FROM categorias ORDER BY orden, nombre');
            $categorias = [];
            
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
            
            echo json_encode(['success' => true, 'categorias' => $categorias]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear categoría
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
        $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE nombre = ?');
        $stmt->bind_param('s', $data['nombre']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('Ya existe una categoría con ese nombre');
        }
        $stmt->close();
        
        // Generar slug si no se proporciona
        $slug = !empty($data['slug']) ? $data['slug'] : strtolower(str_replace(' ', '-', $data['nombre']));
        
        // Verificar si el slug ya existe
        $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE slug = ?');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $slug = $slug . '-' . time();
        }
        $stmt->close();
        
        // Preparar datos con valores por defecto
        $nombre = $data['nombre'];
        $descripcion = $data['descripcion'] ?? '';
        $categoria_padre_id = !empty($data['categoria_padre_id']) ? intval($data['categoria_padre_id']) : null;
        $orden = intval($data['orden'] ?? 0);
        $activa = intval($data['activa'] ?? 1);
        $principal = isset($data['principal']) ? intval($data['principal']) : 0;
        $imagen = $data['imagen'] ?? '';
        
        $stmt = $mysqli->prepare('INSERT INTO categorias (nombre, descripcion, categoria_padre_id, slug, orden, activa, principal, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssisiiss', $nombre, $descripcion, $categoria_padre_id, $slug, $orden, $activa, $principal, $imagen);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Categoría creada correctamente']);
        } else {
            throw new Exception('Error al crear categoría: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar categoría
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de categoría requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la categoría existe
        $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Categoría no encontrada');
        }
        $stmt->close();
        
        // Verificar si el nombre ya existe en otra categoría
        if (!empty($data['nombre'])) {
            $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE nombre = ? AND id != ?');
            $stmt->bind_param('si', $data['nombre'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception('Ya existe otra categoría con ese nombre');
            }
            $stmt->close();
        }
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['nombre', 'descripcion', 'categoria_padre_id', 'slug', 'orden', 'activa', 'principal', 'imagen'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'categoria_padre_id' || $field === 'orden' || $field === 'activa' || $field === 'principal') {
                    $types .= 'i';
                    $values[] = ($field === 'categoria_padre_id' && empty($data[$field])) ? null : intval($data[$field]);
                } else {
                    $types .= 's';
                    $values[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $types .= 'i';
        $values[] = $id;
        
        $sql = 'UPDATE categorias SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada correctamente']);
        } else {
            throw new Exception('Error al actualizar categoría: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar categoría
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de categoría requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la categoría existe
        $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Categoría no encontrada');
        }
        $stmt->close();
        
        // Verificar si hay categorías hijas
        $stmt = $mysqli->prepare('SELECT id FROM categorias WHERE categoria_padre_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('No se puede eliminar la categoría porque tiene subcategorías');
        }
        $stmt->close();
        
        // Eliminar categoría
        $stmt = $mysqli->prepare('DELETE FROM categorias WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada correctamente']);
        } else {
            throw new Exception('Error al eliminar categoría: ' . $stmt->error);
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