<?php
// api/login.php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_log("Login API called - Method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
error_log("Raw input: " . $input);

$data = json_decode($input, true);
error_log("Decoded data: " . print_r($data, true));

$email = $data['email'] ?? '';
$passwordMd5 = $data['passwordMd5'] ?? '';

error_log("Email: " . $email . ", PasswordMd5: " . $passwordMd5);

if (!$email || !$passwordMd5) {
    error_log("Datos incompletos detectados");
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Conexión a la base de datos usando configuración centralizada
try {
    $mysqli = get_database_connection();
    error_log("Conexión BD exitosa");
} catch (Exception $e) {
    error_log("Error de conexión BD: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión DB']);
    exit;
}

$stmt = $mysqli->prepare('SELECT id, email, nombre FROM usuarios WHERE email = ? AND clave = ? LIMIT 1');
if (!$stmt) {
    error_log("Error en prepared statement: " . $mysqli->error);
    echo json_encode(['success' => false, 'error' => 'Error en consulta']);
    exit;
}

$stmt->bind_param('ss', $email, $passwordMd5);
$stmt->execute();
$result = $stmt->get_result();

error_log("Consulta ejecutada. Filas encontradas: " . $result->num_rows);

if ($user = $result->fetch_assoc()) {
    error_log("Usuario encontrado: " . print_r($user, true));
    
    // Iniciar sesión y guardar datos del usuario
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'user' => $user,
        'message' => 'Login exitoso'
    ]);
} else {
    error_log("Usuario no encontrado para email: " . $email . " y clave: " . $passwordMd5);
    echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
}

$stmt->close();
$mysqli->close();
?>
