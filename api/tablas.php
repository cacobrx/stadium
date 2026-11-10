<?php
// api/tablas.php
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

// GET - Obtener tablas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita una tabla específica
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM tablas WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($tabla = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'tabla' => $tabla]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Tabla no encontrada']);
            }
            $stmt->close();
        } 
        // Si se solicitan las definiciones (codtab='DEF')
        elseif (isset($_GET['definiciones'])) {
            $result = $mysqli->query("SELECT * FROM tablas WHERE codtab='DEF' ORDER BY descripcion");
            $definiciones = [];
            
            while ($row = $result->fetch_assoc()) {
                $definiciones[] = $row;
            }
            
            echo json_encode(['success' => true, 'definiciones' => $definiciones]);
        }
        // Si se solicitan registros por tipo (valorc)
        elseif (isset($_GET['tipo'])) {
            $tipo = $mysqli->real_escape_string($_GET['tipo']);
            $stmt = $mysqli->prepare('SELECT * FROM tablas WHERE codtab = ? ORDER BY orden, descripcion');
            $stmt->bind_param('s', $tipo);
            $stmt->execute();
            $result = $stmt->get_result();
            $registros = [];
            
            while ($row = $result->fetch_assoc()) {
                $registros[] = $row;
            }
            
            echo json_encode(['success' => true, 'registros' => $registros]);
            $stmt->close();
        } else {
            // Obtener todas las tablas
            $result = $mysqli->query('SELECT * FROM tablas ORDER BY codtab, orden, descripcion');
            $tablas = [];
            
            while ($row = $result->fetch_assoc()) {
                $tablas[] = $row;
            }
            
            echo json_encode(['success' => true, 'tablas' => $tablas]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear tabla
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }
        
        // Validar campos requeridos
        if (empty($data['codtab'])) {
            throw new Exception('El código de tabla es requerido');
        }
        
        if (empty($data['descripcion'])) {
            throw new Exception('La descripción es requerida');
        }
        
        $mysqli = getConnection();
        
        // Preparar datos con valores por defecto
        $codtab = $data['codtab'];
        $descripcion = $data['descripcion'];
        $valor = isset($data['valor']) ? intval($data['valor']) : null;
        $orden = isset($data['orden']) ? intval($data['orden']) : null;
        $valorc = $data['valorc'] ?? null;
        
        // Preparar la consulta con manejo de NULL
        $stmt = $mysqli->prepare('INSERT INTO tablas (codtab, descripcion, valor, orden, valorc) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssis', $codtab, $descripcion, $valor, $orden, $valorc);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Tabla creada correctamente']);
        } else {
            throw new Exception('Error al crear tabla: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar tabla
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de tabla requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la tabla existe
        $stmt = $mysqli->prepare('SELECT id FROM tablas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Tabla no encontrada');
        }
        $stmt->close();
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['codtab', 'descripcion', 'valor', 'orden', 'valorc'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                if ($field === 'valor' || $field === 'orden') {
                    $types .= 'i';
                    $values[] = $data[$field] === null ? null : intval($data[$field]);
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
        
        $sql = 'UPDATE tablas SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tabla actualizada correctamente']);
        } else {
            throw new Exception('Error al actualizar tabla: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar tabla
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de tabla requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si la tabla existe
        $stmt = $mysqli->prepare('SELECT id, codtab, valorc FROM tablas WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$tabla = $result->fetch_assoc()) {
            throw new Exception('Tabla no encontrada');
        }
        $stmt->close();
        
        // Si es una definición (codtab='DEF'), verificar si tiene registros asociados
        if ($tabla['codtab'] === 'DEF' && $tabla['valorc']) {
            $stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM tablas WHERE codtab = ?');
            $stmt->bind_param('s', $tabla['valorc']);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();
            
            if ($count > 0) {
                throw new Exception('No se puede eliminar esta definición porque tiene registros asociados');
            }
        }
        
        // Eliminar tabla
        $stmt = $mysqli->prepare('DELETE FROM tablas WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tabla eliminada correctamente']);
        } else {
            throw new Exception('Error al eliminar tabla: ' . $stmt->error);
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