<?php
// config/database.example.php
// Archivo de ejemplo para configuración de base de datos
// Copiar como database.php y ajustar los valores

// ===== CONFIGURACIÓN DE BASE DE DATOS =====
// Ajustar estos valores según tu entorno

define('DB_HOST', 'localhost');        // Servidor de base de datos
define('DB_USER', 'tu_usuario');       // Usuario de MySQL
define('DB_PASSWORD', 'tu_contraseña'); // Contraseña de MySQL
define('DB_NAME', 'nombre_bd');        // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');       // Charset por defecto

// ===== EJEMPLOS DE USO =====

/*
// Ejemplo 1: Consulta simple
$productos = execute_query(
    "SELECT * FROM productos WHERE esta_activo = ?", 
    "i", 
    [1]
);

// Ejemplo 2: Insertar datos
$nuevo_id = execute_update(
    "INSERT INTO clientes (nombre, apellido, email) VALUES (?, ?, ?)",
    "sss",
    ["Juan", "Pérez", "juan@email.com"]
);

// Ejemplo 3: Actualizar datos
$filas_afectadas = execute_update(
    "UPDATE productos SET precio = ? WHERE id = ?",
    "di",
    [99.99, 1]
);

// Ejemplo 4: Conexión directa para transacciones
$conn = get_database_connection();
$conn->begin_transaction();
try {
    // Múltiples operaciones...
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}
*/

// ===== CONFIGURACIÓN DE ENTORNO =====
// Uncomment para producción
// ini_set('display_errors', 0);
// error_reporting(0);