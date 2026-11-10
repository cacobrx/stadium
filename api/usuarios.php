<?php
// api/usuarios.php
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

// Conexión a la base de datos usando configuración centralizada
function getConnection() {
    return get_database_connection();
}

// GET - Obtener usuarios
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita un usuario específico
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $mysqli->prepare('SELECT * FROM usuarios WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // No devolver la clave por seguridad
                unset($user['clave']);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            }
            $stmt->close();
        } else {
            // Obtener todos los usuarios
            $result = $mysqli->query('SELECT * FROM usuarios ORDER BY apellido, nombre');
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                // No devolver la clave por seguridad
                unset($row['clave']);
                $users[] = $row;
            }
            
            echo json_encode(['success' => true, 'users' => $users]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear usuario
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }
        
        // Validar campos requeridos
        if (empty($data['email'])) {
            throw new Exception('El email es requerido');
        }
        
        $mysqli = getConnection();
        
        // Verificar si el email ya existe
        $stmt = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('El email ya está registrado');
        }
        $stmt->close();
        
        // Preparar datos con valores por defecto
        $apellido = $data['apellido'] ?? '';
        $nombre = $data['nombre'] ?? '';
        $email = $data['email'];
        $clave = $data['clave'] ?? md5('123456'); // Contraseña por defecto
        $acceso = $data['acceso'] ?? '';
        $nivel = intval($data['nivel'] ?? 0);
        $idcuit = intval($data['idcuit'] ?? 0);
        $dash = intval($data['dash'] ?? 0);
        $avisopago = intval($data['avisopago'] ?? 0);
        
        $stmt = $mysqli->prepare('INSERT INTO usuarios (apellido, nombre, email, clave, acceso, nivel, idcuit, dash, avisopago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssiiii', $apellido, $nombre, $email, $clave, $acceso, $nivel, $idcuit, $dash, $avisopago);
        
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Usuario creado correctamente']);
        } else {
            throw new Exception('Error al crear usuario: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar usuario
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de usuario requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si el usuario existe
        $stmt = $mysqli->prepare('SELECT id FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Usuario no encontrado');
        }
        $stmt->close();
        
        // Verificar si el email ya existe en otro usuario
        if (!empty($data['email'])) {
            $stmt = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
            $stmt->bind_param('si', $data['email'], $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception('El email ya está registrado por otro usuario');
            }
            $stmt->close();
        }
        
        // Construir query dinámicamente
        $updates = [];
        $types = '';
        $values = [];
        
        $allowedFields = ['apellido', 'nombre', 'email', 'clave', 'acceso', 'nivel', 'idcuit', 'dash', 'avisopago'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'nivel' || $field === 'idcuit' || $field === 'dash' || $field === 'avisopago') {
                    $types .= 'i';
                    $values[] = intval($data[$field]);
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
        
        $sql = 'UPDATE usuarios SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } else {
            throw new Exception('Error al actualizar usuario: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar usuario
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception('ID de usuario requerido');
        }
        
        $id = intval($data['id']);
        $mysqli = getConnection();
        
        // Verificar si el usuario existe
        $stmt = $mysqli->prepare('SELECT id FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('Usuario no encontrado');
        }
        $stmt->close();
        
        // Eliminar usuario
        $stmt = $mysqli->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } else {
            throw new Exception('Error al eliminar usuario: ' . $stmt->error);
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