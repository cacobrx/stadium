<?php
// api/productos.php
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

// GET - Obtener productos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita un producto específico
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Obtener producto principal
            $stmt = $mysqli->prepare("
                SELECT p.*, m.nombre as marca_nombre, m.id as marca_id
                FROM productos p 
                LEFT JOIN marcas m ON p.marca_id = m.id 
                WHERE p.id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($producto = $result->fetch_assoc()) {
                // Obtener imágenes
                $stmt = $mysqli->prepare("
                    SELECT * FROM producto_imagenes 
                    WHERE producto_id = ? 
                    ORDER BY es_principal DESC, orden ASC
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $imagenes_result = $stmt->get_result();
                $producto['imagenes'] = $imagenes_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener categorías
                $stmt = $mysqli->prepare("
                    SELECT c.id, c.nombre, c.slug 
                    FROM categorias c 
                    JOIN producto_categorias pc ON c.id = pc.categoria_id 
                    WHERE pc.producto_id = ?
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $categorias_result = $stmt->get_result();
                $producto['categorias'] = $categorias_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener colecciones
                $stmt = $mysqli->prepare("
                    SELECT col.id, col.nombre, col.descripcion 
                    FROM colecciones col 
                    JOIN producto_colecciones pc ON col.id = pc.coleccion_id 
                    WHERE pc.producto_id = ?
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $colecciones_result = $stmt->get_result();
                $producto['colecciones'] = $colecciones_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener atributos
                $stmt = $mysqli->prepare("
                    SELECT nombre, valor 
                    FROM producto_atributos 
                    WHERE producto_id = ?
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $atributos_result = $stmt->get_result();
                $producto['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener variantes
                $stmt = $mysqli->prepare("
                    SELECT * FROM producto_variantes 
                    WHERE producto_id = ? 
                    ORDER BY nombre
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $variantes_result = $stmt->get_result();
                $variantes = $variantes_result->fetch_all(MYSQLI_ASSOC);
                
                // Para cada variante, obtener sus atributos e imágenes
                foreach ($variantes as &$variante) {
                    // Obtener atributos de la variante
                    $stmt = $mysqli->prepare("
                        SELECT nombre, valor 
                        FROM variante_atributos 
                        WHERE variante_id = ?
                    ");
                    $stmt->bind_param('i', $variante['id']);
                    $stmt->execute();
                    $var_attr_result = $stmt->get_result();
                    $variante['atributos'] = $var_attr_result->fetch_all(MYSQLI_ASSOC);
                    
                    // Obtener imágenes de la variante
                    $stmt = $mysqli->prepare("
                        SELECT * FROM variante_imagenes 
                        WHERE variante_id = ? 
                        ORDER BY es_principal DESC, orden ASC
                    ");
                    $stmt->bind_param('i', $variante['id']);
                    $stmt->execute();
                    $var_img_result = $stmt->get_result();
                    $variante['imagenes'] = $var_img_result->fetch_all(MYSQLI_ASSOC);
                }
                $producto['variantes'] = $variantes;
                
                echo json_encode(['success' => true, 'producto' => $producto]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
            }
        } else {
            // Obtener lista de productos
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
            $productos = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'productos' => $productos]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear producto
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            throw new Exception('No se recibieron datos');
        }
        
        // Validar tamaño del payload
        $payload_size = strlen($rawInput);
        $max_size = 10 * 1024 * 1024; // 10 MB
        if ($payload_size > $max_size) {
            throw new Exception('El tamaño del payload excede el límite permitido (' . round($payload_size / 1024 / 1024, 2) . ' MB)');
        }
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error de JSON: ' . json_last_error_msg());
        }
        
        if (!$input) {
            throw new Exception('Datos inválidos');
        }
        
        // Validar campos requeridos
        if (empty($input['sku']) || empty($input['nombre'])) {
            throw new Exception('SKU y Nombre son requeridos');
        }
        
        $mysqli = getConnection();
        $mysqli->autocommit(false); // Iniciar transacción
        
        // Insertar producto principal
        $stmt = $mysqli->prepare("
            INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, stock, 
                                 esta_activo, destacado, marca_id, deporte, peso, alto, ancho, profundidad) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $precio_descuento = !empty($input['precio_descuento']) ? floatval($input['precio_descuento']) : null;
        $marca_id = !empty($input['marca_id']) ? intval($input['marca_id']) : null;
        $peso = !empty($input['peso']) ? floatval($input['peso']) : null;
        $alto = !empty($input['alto']) ? floatval($input['alto']) : null;
        $ancho = !empty($input['ancho']) ? floatval($input['ancho']) : null;
        $profundidad = !empty($input['profundidad']) ? floatval($input['profundidad']) : null;
        $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
        $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
        
        // Validar tipos de datos
        if (!is_numeric($input['precio']) || floatval($input['precio']) < 0) {
            throw new Exception('El precio debe ser un número válido mayor o igual a 0');
        }
        
        if (!is_numeric($input['stock']) || intval($input['stock']) < 0) {
            throw new Exception('El stock debe ser un número entero mayor o igual a 0');
        }
        
        $stmt->bind_param('sssddiiiisdddd', 
            $input['sku'], 
            $input['nombre'], 
            $input['descripcion'], 
            $input['precio'], 
            $precio_descuento,
            $input['stock'], 
            $esta_activo, 
            $destacado, 
            $marca_id, 
            $input['deporte'],
            $peso, $alto, $ancho, $profundidad
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al crear producto: ' . $stmt->error);
        }
        
        $producto_id = $mysqli->insert_id;
        
        // Insertar categorías si se proporcionaron
        if (!empty($input['categorias']) && is_array($input['categorias'])) {
            $stmt_cat = $mysqli->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
            foreach ($input['categorias'] as $categoria_id) {
                $stmt_cat->bind_param('ii', $producto_id, $categoria_id);
                $stmt_cat->execute();
            }
        }
        
        // Insertar colecciones si se proporcionaron
        if (!empty($input['colecciones']) && is_array($input['colecciones'])) {
            $stmt_col = $mysqli->prepare("INSERT INTO producto_colecciones (producto_id, coleccion_id) VALUES (?, ?)");
            foreach ($input['colecciones'] as $coleccion_id) {
                $stmt_col->bind_param('ii', $producto_id, $coleccion_id);
                $stmt_col->execute();
            }
        }
        
        // Insertar imágenes si se proporcionaron
        if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
            $stmt_img = $mysqli->prepare("INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($input['imagenes'] as $index => $imagen) {
                $es_principal = ($index === 0) ? 1 : 0; // Primera imagen es principal por defecto
                $alt_text = $imagen['alt_text'] ?? $input['nombre'];
                $img_url = $imagen['url'] ?? '';
                $img_alt = $alt_text;
                $img_es_principal = (int)$es_principal;
                $img_order = (int)$index;
                $stmt_img->bind_param('issii', $producto_id, $img_url, $img_alt, $img_es_principal, $img_order);
                $stmt_img->execute();
            }
        }
        
        // Insertar atributos si se proporcionaron
        if (!empty($input['atributos']) && is_array($input['atributos'])) {
            $stmt_attr = $mysqli->prepare("INSERT INTO producto_atributos (producto_id, nombre, valor) VALUES (?, ?, ?)");
            foreach ($input['atributos'] as $atributo) {
                $attr_nombre = $atributo['nombre'] ?? '';
                $attr_valor = $atributo['valor'] ?? '';
                $stmt_attr->bind_param('iss', $producto_id, $attr_nombre, $attr_valor);
                $stmt_attr->execute();
            }
        }
        
        // Insertar variantes
        $stmt_var = $mysqli->prepare("INSERT INTO producto_variantes (producto_id, sku_variante, nombre, stock, precio, activa) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_var_attr = $mysqli->prepare("INSERT INTO variante_atributos (variante_id, nombre, valor) VALUES (?, ?, ?)");
        $stmt_var_img = $mysqli->prepare("INSERT INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");

        if (!empty($input['variantes']) && is_array($input['variantes'])) {
            // Limitar número de variantes para evitar timeouts
            $max_variantes = 50;
            if (count($input['variantes']) > $max_variantes) {
                throw new Exception("Se permiten máximo {$max_variantes} variantes por producto");
            }
            
            // Insertar las variantes definidas por el usuario
            foreach ($input['variantes'] as $variante) {
                $precio_variante = !empty($variante['precio']) ? $variante['precio'] : null;
                $activa = isset($variante['activa']) ? intval($variante['activa']) : 1;
                
                // Insertar variante
                $sku_var = $variante['sku'] ?? '';
                $nombre_var = $variante['nombre'] ?? '';
                $stock_var = isset($variante['stock']) ? intval($variante['stock']) : 0;
                $precio_var = $precio_variante;
                $activa_var = $activa;

                $stmt_var->bind_param('issidi', $producto_id, $sku_var, $nombre_var, $stock_var, $precio_var, $activa_var);
                $stmt_var->execute();
                $variante_id = $mysqli->insert_id;

                // Insertar atributos de la variante
                if (!empty($variante['atributos']) && is_array($variante['atributos'])) {
                    foreach ($variante['atributos'] as $atributo) {
                        $attr_nombre = $atributo['nombre'] ?? '';
                        $attr_valor = $atributo['valor'] ?? '';
                        $stmt_var_attr->bind_param('iss', $variante_id, $attr_nombre, $attr_valor);
                        $stmt_var_attr->execute();
                    }
                }

                // Insertar imágenes de la variante
                if (!empty($variante['imagenes']) && is_array($variante['imagenes'])) {
                    foreach ($variante['imagenes'] as $index => $imagen) {
                        if (empty($imagen['url'])) {
                            continue;
                        }
                        $es_principal = isset($imagen['es_principal']) ? (int)$imagen['es_principal'] : ($index === 0 ? 1 : 0);
                        $alt_text = $imagen['alt_text'] ?? ($input['nombre'] . ' - ' . $sku_var);
                        $img_url = $imagen['url'];
                        $img_alt = $alt_text;
                        $img_es_principal = (int)$es_principal;
                        $img_order = (int)$index;
                        $stmt_var_img->bind_param('issii', $variante_id, $img_url, $img_alt, $img_es_principal, $img_order);
                        $stmt_var_img->execute();
                    }
                }
            }
        } else {
            // Sin variantes definidas → crear una variante por defecto igual al artículo
            $sku_def   = $input['sku'];
            $nombre_def = $input['nombre'];
            $stock_def  = intval($input['stock']);
            $precio_def = floatval($input['precio']);
            $activa_def = 1;
            $stmt_var->bind_param('issidi', $producto_id, $sku_def, $nombre_def, $stock_def, $precio_def, $activa_def);
            $stmt_var->execute();
            $variante_def_id = $mysqli->insert_id;

            // Copiar imágenes del producto a la variante por defecto
            if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
                foreach ($input['imagenes'] as $index => $imagen) {
                    if (empty($imagen['url'])) continue;
                    $img_url          = $imagen['url'];
                    $img_alt          = $imagen['alt_text'] ?? $input['nombre'];
                    $img_es_principal = ($index === 0) ? 1 : 0;
                    $img_order        = (int)$index;
                    $stmt_var_img->bind_param('issii', $variante_def_id, $img_url, $img_alt, $img_es_principal, $img_order);
                    $stmt_var_img->execute();
                }
            }
        }
        
        $mysqli->commit();
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente', 'id' => $producto_id]);
        
    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->rollback();
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar producto
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            throw new Exception('No se recibieron datos');
        }
        
        // Validar tamaño del payload
        $payload_size = strlen($rawInput);
        $max_size = 10 * 1024 * 1024; // 10 MB
        if ($payload_size > $max_size) {
            throw new Exception('El tamaño del payload excede el límite permitido (' . round($payload_size / 1024 / 1024, 2) . ' MB)');
        }
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error de JSON: ' . json_last_error_msg());
        }
        
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de producto requerido');
        }
        
        $mysqli = getConnection();
        $mysqli->autocommit(false);
        
        $id = intval($input['id']);
        
        // Actualizar producto principal
        $stmt = $mysqli->prepare("
            UPDATE productos 
            SET sku = ?, nombre = ?, descripcion = ?, precio = ?, precio_descuento = ?, 
                stock = ?, esta_activo = ?, destacado = ?, marca_id = ?, deporte = ?,
                peso = ?, alto = ?, ancho = ?, profundidad = ?, fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $precio_descuento = !empty($input['precio_descuento']) ? $input['precio_descuento'] : null;
        $marca_id = !empty($input['marca_id']) ? intval($input['marca_id']) : null;
        $peso = !empty($input['peso']) ? floatval($input['peso']) : null;
        $alto = !empty($input['alto']) ? floatval($input['alto']) : null;
        $ancho = !empty($input['ancho']) ? floatval($input['ancho']) : null;
        $profundidad = !empty($input['profundidad']) ? floatval($input['profundidad']) : null;
        $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
        $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
        
        $stmt->bind_param('sssddiiiiisdddi', 
            $input['sku'], 
            $input['nombre'], 
            $input['descripcion'], 
            $input['precio'], 
            $precio_descuento,
            $input['stock'], 
            $esta_activo, 
            $destacado, 
            $marca_id, 
            $input['deporte'],
            $peso, $alto, $ancho, $profundidad,
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar producto: ' . $stmt->error);
        }
        
        // Actualizar categorías (eliminar existentes y recrear)
        $mysqli->query("DELETE FROM producto_categorias WHERE producto_id = $id");
        if (!empty($input['categorias']) && is_array($input['categorias'])) {
            $stmt_cat = $mysqli->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
            foreach ($input['categorias'] as $categoria_id) {
                $stmt_cat->bind_param('ii', $id, $categoria_id);
                $stmt_cat->execute();
            }
        }
        
        // Actualizar colecciones
        $mysqli->query("DELETE FROM producto_colecciones WHERE producto_id = $id");
        if (!empty($input['colecciones']) && is_array($input['colecciones'])) {
            $stmt_col = $mysqli->prepare("INSERT INTO producto_colecciones (producto_id, coleccion_id) VALUES (?, ?)");
            foreach ($input['colecciones'] as $coleccion_id) {
                $stmt_col->bind_param('ii', $id, $coleccion_id);
                $stmt_col->execute();
            }
        }
        
        // Actualizar imágenes del producto
        if (isset($input['imagenes'])) {
            $mysqli->query("DELETE FROM producto_imagenes WHERE producto_id = $id");

            if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
                $stmt_img = $mysqli->prepare("INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
                $hasPrincipal = false;
                foreach ($input['imagenes'] as $index => $imagen) {
                    if (empty($imagen['url'])) {
                        continue;
                    }
                    $alt_text = !empty($imagen['alt_text']) ? $imagen['alt_text'] : $input['nombre'];
                    $es_principal = isset($imagen['es_principal']) ? (int)$imagen['es_principal'] : ($index === 0 ? 1 : 0);
                    if ($es_principal === 1) {
                        $hasPrincipal = true;
                    }
                    $orden = isset($imagen['orden']) ? (int)$imagen['orden'] : $index;
                    $img_url = $imagen['url'] ?? '';
                    $img_alt = $alt_text;
                    $img_es_principal = (int)$es_principal;
                    $img_order = (int)$orden;
                    $stmt_img->bind_param('issii', $id, $img_url, $img_alt, $img_es_principal, $img_order);
                    $stmt_img->execute();
                }

                if (!$hasPrincipal) {
                    $stmt_principal = $mysqli->prepare("UPDATE producto_imagenes SET es_principal = 1 WHERE producto_id = ? ORDER BY orden ASC, id ASC LIMIT 1");
                    $stmt_principal->bind_param('i', $id);
                    $stmt_principal->execute();
                }
            }
        }

        // Actualizar variantes si se proporcionaron
        if (isset($input['variantes'])) {
            // Eliminar variantes existentes y sus dependencias (CASCADE se encarga de las imágenes y atributos)
            $mysqli->query("DELETE FROM producto_variantes WHERE producto_id = $id");
            
            // Liberar memoria después del DELETE
            $mysqli->query("COMMIT");
            $mysqli->autocommit(false);
            
            $stmt_var     = $mysqli->prepare("INSERT INTO producto_variantes (producto_id, sku_variante, nombre, stock, precio, activa) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_var_attr = $mysqli->prepare("INSERT INTO variante_atributos (variante_id, nombre, valor) VALUES (?, ?, ?)");
            $stmt_var_img  = $mysqli->prepare("INSERT INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");

            if (!empty($input['variantes']) && is_array($input['variantes'])) {
                // Limitar número de variantes para evitar timeouts
                $max_variantes = 50;
                if (count($input['variantes']) > $max_variantes) {
                    throw new Exception("Se permiten máximo {$max_variantes} variantes por producto");
                }
                
                // Insertar las variantes proporcionadas por el usuario
                foreach ($input['variantes'] as $variante) {
                    $precio_variante = !empty($variante['precio']) ? $variante['precio'] : null;
                    $activa = isset($variante['activa']) ? intval($variante['activa']) : 1;
                    
                    // Insertar variante
                    $sku_var = $variante['sku'] ?? '';
                    $nombre_var = $variante['nombre'] ?? '';
                    $stock_var = isset($variante['stock']) ? intval($variante['stock']) : 0;
                    $precio_var = $precio_variante;
                    $activa_var = $activa;

                    $stmt_var->bind_param('issidi', $id, $sku_var, $nombre_var, $stock_var, $precio_var, $activa_var);
                    $stmt_var->execute();
                    $variante_id = $mysqli->insert_id;

                    // Insertar atributos de la variante
                    if (!empty($variante['atributos']) && is_array($variante['atributos'])) {
                        foreach ($variante['atributos'] as $atributo) {
                            $attr_nombre = $atributo['nombre'] ?? '';
                            $attr_valor = $atributo['valor'] ?? '';
                            $stmt_var_attr->bind_param('iss', $variante_id, $attr_nombre, $attr_valor);
                            $stmt_var_attr->execute();
                        }
                    }

                    // Insertar imágenes de la variante
                    if (!empty($variante['imagenes']) && is_array($variante['imagenes'])) {
                        $hasPrincipalVariante = false;
                        foreach ($variante['imagenes'] as $index => $imagen) {
                            if (empty($imagen['url'])) {
                                continue;
                            }
                            $es_principal = isset($imagen['es_principal']) ? (int)$imagen['es_principal'] : ($index === 0 ? 1 : 0);
                            if ($es_principal === 1) {
                                $hasPrincipalVariante = true;
                            }
                            $alt_text = $imagen['alt_text'] ?? ($input['nombre'] . ' - ' . $sku_var);
                            $img_url = $imagen['url'];
                            $img_alt = $alt_text;
                            $img_es_principal = (int)$es_principal;
                            $img_order = (int)$index;
                            $stmt_var_img->bind_param('issii', $variante_id, $img_url, $img_alt, $img_es_principal, $img_order);
                            $stmt_var_img->execute();
                        }

                        if (!$hasPrincipalVariante) {
                            $stmt_var_set = $mysqli->prepare("UPDATE variante_imagenes SET es_principal = 1 WHERE variante_id = ? ORDER BY orden ASC, id ASC LIMIT 1");
                            $stmt_var_set->bind_param('i', $variante_id);
                            $stmt_var_set->execute();
                        }
                    }
                }
            } else {
                // Sin variantes definidas → crear una variante por defecto igual al artículo
                $sku_def    = $input['sku'];
                $nombre_def = $input['nombre'];
                $stock_def  = intval($input['stock']);
                $precio_def = floatval($input['precio']);
                $activa_def = 1;
                $stmt_var->bind_param('issidi', $id, $sku_def, $nombre_def, $stock_def, $precio_def, $activa_def);
                $stmt_var->execute();
                $variante_def_id = $mysqli->insert_id;

                // Copiar imágenes del producto a la variante por defecto
                if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
                    foreach ($input['imagenes'] as $index => $imagen) {
                        if (empty($imagen['url'])) continue;
                        $img_url          = $imagen['url'];
                        $img_alt          = $imagen['alt_text'] ?? $input['nombre'];
                        $img_es_principal = ($index === 0) ? 1 : 0;
                        $img_order        = (int)$index;
                        $stmt_var_img->bind_param('issii', $variante_def_id, $img_url, $img_alt, $img_es_principal, $img_order);
                        $stmt_var_img->execute();
                    }
                }
            }
        }
        
        $mysqli->commit();
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
        
    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->rollback();
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar producto
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de producto requerido');
        }
        
        $mysqli = getConnection();
        $id = intval($input['id']);
        
        // Las relaciones se eliminan automáticamente por CASCADE
        $stmt = $mysqli->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar producto: ' . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Producto no encontrado');
        }
        
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>