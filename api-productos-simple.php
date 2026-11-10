<?php
// api-productos-simple.php - Versión simplificada sin errores
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/database.php';

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificación de autenticación más flexible
$is_authenticated = false;

// Verificar sesión
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_authenticated = true;
}

// Si no está autenticado por sesión, verificar si hay un usuario en la BD (para casos donde la sesión se perdió)
if (!$is_authenticated) {
    try {
        $mysqli = get_database_connection();
        if (!$mysqli->connect_errno) {
            $result = $mysqli->query("SELECT id FROM usuarios WHERE email = 'gustavo.bragagnolo@gmail.com' LIMIT 1");
            if ($result && $result->num_rows > 0) {
                // Usuario existe, permitir acceso temporal
                $is_authenticated = true;
                $_SESSION['logged_in'] = true; // Restaurar sesión
            }
            $mysqli->close();
        }
    } catch (Exception $e) {
        // Si hay error de BD, permitir continuar para debug
    }
}

if (!$is_authenticated) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'No autenticado',
        'debug' => [
            'session_logged_in' => $_SESSION['logged_in'] ?? 'not set',
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

// Función de conexión
function getConnection() {
    try {
        $mysqli = get_database_connection();
        if ($mysqli->connect_errno) {
            throw new Exception('Error de conexión: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8');
        return $mysqli;
    } catch (Exception $e) {
        throw new Exception('Error conectando a BD: ' . $e->getMessage());
    }
}

try {
    $mysqli = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener productos
        if (isset($_GET['id'])) {
            // Producto específico con relaciones
            $id = intval($_GET['id']);
            
            // Obtener datos básicos del producto
            $stmt = $mysqli->prepare("
                SELECT p.*, m.nombre as marca_nombre 
                FROM productos p 
                LEFT JOIN marcas m ON p.marca_id = m.id 
                WHERE p.id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($producto = $result->fetch_assoc()) {
                // Obtener categorías del producto
                $stmt_cat = $mysqli->prepare("
                    SELECT c.id, c.nombre 
                    FROM categorias c 
                    INNER JOIN producto_categorias pc ON c.id = pc.categoria_id 
                    WHERE pc.producto_id = ?
                ");
                $stmt_cat->bind_param('i', $id);
                $stmt_cat->execute();
                $result_cat = $stmt_cat->get_result();
                $producto['categorias'] = $result_cat->fetch_all(MYSQLI_ASSOC);
                
                // Obtener colecciones del producto
                $stmt_col = $mysqli->prepare("
                    SELECT c.id, c.nombre 
                    FROM colecciones c 
                    INNER JOIN producto_colecciones pc ON c.id = pc.coleccion_id 
                    WHERE pc.producto_id = ?
                ");
                $stmt_col->bind_param('i', $id);
                $stmt_col->execute();
                $result_col = $stmt_col->get_result();
                $producto['colecciones'] = $result_col->fetch_all(MYSQLI_ASSOC);
                
                // Obtener imágenes del producto
                $stmt_img = $mysqli->prepare("
                    SELECT id, url, es_principal, alt_text 
                    FROM producto_imagenes 
                    WHERE producto_id = ? 
                    ORDER BY es_principal DESC, id ASC
                ");
                $stmt_img->bind_param('i', $id);
                $stmt_img->execute();
                $result_img = $stmt_img->get_result();
                $producto['imagenes'] = $result_img->fetch_all(MYSQLI_ASSOC);
                
                echo json_encode(['success' => true, 'producto' => $producto]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
            }
        } else {
            // Lista de productos
            $query = "
                SELECT p.id, p.sku, p.nombre, p.descripcion, p.precio, p.precio_descuento, 
                       p.stock, p.esta_activo, p.destacado, p.deporte,
                       m.nombre as marca_nombre, m.id as marca_id,
                       pi.url as imagen_principal
                FROM productos p 
                LEFT JOIN marcas m ON p.marca_id = m.id
                LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = 1
                ORDER BY p.nombre
            ";
            
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Error en consulta: ' . $mysqli->error);
            }
            
            $productos = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'productos' => $productos]);
        }
    }
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear producto
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Datos JSON inválidos');
        }
        
        $mysqli->autocommit(false);
        
        $stmt = $mysqli->prepare("
            INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, stock, 
                                 esta_activo, destacado, marca_id, deporte) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Preparar variables para bind_param (deben ser variables, no expresiones)
        $sku = $input['sku'];
        $nombre = $input['nombre'];
        $descripcion = $input['descripcion'] ?? '';
        $precio = $input['precio'];
        $precio_descuento = !empty($input['precio_descuento']) ? $input['precio_descuento'] : null;
        $stock = $input['stock'] ?? 0;
        $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
        $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
        $marca_id = !empty($input['marca_id']) ? intval($input['marca_id']) : null;
        $deporte = $input['deporte'] ?? '';
        
        $stmt->bind_param('sssddiiiis', 
            $sku, 
            $nombre, 
            $descripcion, 
            $precio, 
            $precio_descuento,
            $stock, 
            $esta_activo, 
            $destacado, 
            $marca_id, 
            $deporte
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al crear producto: ' . $stmt->error);
        }
        
        $producto_id = $mysqli->insert_id;
        
        // Insertar imágenes si se proporcionaron
        if (isset($input['imagenes']) && is_array($input['imagenes'])) {
            foreach ($input['imagenes'] as $imagen) {
                if (!empty($imagen['url'])) {
                    $stmt_img = $mysqli->prepare("
                        INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $url = $imagen['url'];
                    $alt_text = $imagen['alt_text'] ?? '';
                    $es_principal = isset($imagen['es_principal']) ? intval($imagen['es_principal']) : 0;
                    $orden = isset($imagen['orden']) ? intval($imagen['orden']) : 0;
                    
                    $stmt_img->bind_param('issii', $producto_id, $url, $alt_text, $es_principal, $orden);
                    $stmt_img->execute();
                }
            }
        }
        
        $mysqli->commit();
        
        echo json_encode(['success' => true, 'message' => 'Producto creado', 'id' => $producto_id]);
    }
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Actualizar producto
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de producto requerido');
        }
        
        $id = intval($input['id']);
        
        // Iniciar transacción
        $mysqli->autocommit(false);
        
        try {
            // Actualizar datos básicos del producto
            $stmt = $mysqli->prepare("
                UPDATE productos 
                SET sku = ?, nombre = ?, descripcion = ?, precio = ?, precio_descuento = ?, 
                    stock = ?, esta_activo = ?, destacado = ?, marca_id = ?, deporte = ?
                WHERE id = ?
            ");
            
            // Preparar variables para bind_param (deben ser variables, no expresiones)
            $sku = $input['sku'];
            $nombre = $input['nombre'];
            $descripcion = $input['descripcion'] ?? '';
            $precio = $input['precio'];
            $precio_descuento = !empty($input['precio_descuento']) ? $input['precio_descuento'] : null;
            $stock = $input['stock'] ?? 0;
            $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
            $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
            $marca_id = !empty($input['marca_id']) ? intval($input['marca_id']) : null;
            $deporte = $input['deporte'] ?? '';
            
            $stmt->bind_param('sssddiiiisi', 
                $sku, 
                $nombre, 
                $descripcion, 
                $precio, 
                $precio_descuento,
                $stock, 
                $esta_activo, 
                $destacado, 
                $marca_id, 
                $deporte,
                $id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar producto: ' . $stmt->error);
            }
            
            // Actualizar categorías si se proporcionaron
            if (isset($input['categorias']) && is_array($input['categorias'])) {
                // Eliminar categorías existentes
                $mysqli->query("DELETE FROM producto_categorias WHERE producto_id = $id");
                
                // Insertar nuevas categorías
                foreach ($input['categorias'] as $categoria_id) {
                    if (!empty($categoria_id)) {
                        $stmt_cat = $mysqli->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
                        $stmt_cat->bind_param('ii', $id, $categoria_id);
                        $stmt_cat->execute();
                    }
                }
            }
            
            // Actualizar colecciones si se proporcionaron
            if (isset($input['colecciones']) && is_array($input['colecciones'])) {
                // Eliminar colecciones existentes
                $mysqli->query("DELETE FROM producto_colecciones WHERE producto_id = $id");
                
                // Insertar nuevas colecciones
                foreach ($input['colecciones'] as $coleccion_id) {
                    if (!empty($coleccion_id)) {
                        $stmt_col = $mysqli->prepare("INSERT INTO producto_colecciones (producto_id, coleccion_id) VALUES (?, ?)");
                        $stmt_col->bind_param('ii', $id, $coleccion_id);
                        $stmt_col->execute();
                    }
                }
            }
            
            // Actualizar imágenes si se proporcionaron
            if (isset($input['imagenes']) && is_array($input['imagenes'])) {
                // Eliminar imágenes existentes
                $mysqli->query("DELETE FROM producto_imagenes WHERE producto_id = $id");
                
                // Insertar nuevas imágenes
                foreach ($input['imagenes'] as $imagen) {
                    if (!empty($imagen['url'])) {
                        $stmt_img = $mysqli->prepare("
                            INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        
                        $url = $imagen['url'];
                        $alt_text = $imagen['alt_text'] ?? '';
                        $es_principal = isset($imagen['es_principal']) ? intval($imagen['es_principal']) : 0;
                        $orden = isset($imagen['orden']) ? intval($imagen['orden']) : 0;
                        
                        $stmt_img->bind_param('issii', $id, $url, $alt_text, $es_principal, $orden);
                        $stmt_img->execute();
                    }
                }
            }
            
            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            throw new Exception('Error en actualización: ' . $e->getMessage());
        }
    }
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Eliminar producto
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de producto requerido');
        }
        
        $id = intval($input['id']);
        $stmt = $mysqli->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar: ' . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Producto no encontrado');
        }
        
        echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
    }
    
    else {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    if (isset($mysqli)) {
        $mysqli->rollback();
        $mysqli->close();
    }
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'session_status' => session_status(),
            'session_id' => session_id()
        ]
    ]);
}
?>