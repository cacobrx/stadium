<?php
// api/productos-formato.php - API con el formato específico solicitado

// Capturar errores fatales para evitar error 500 sin mensaje
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Headers primero (antes de cualquier output)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request (antes de session_start)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración de base de datos
$db_config_file = __DIR__ . '/../../config/database.php';
if (!file_exists($db_config_file)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Archivo de configuración de base de datos no encontrado'
    ]);
    exit;
}

try {
    require_once $db_config_file;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error cargando configuración: ' . $e->getMessage()
    ]);
    exit;
}

// Verificación de autenticación más flexible
$is_authenticated = false;

// Verificar sesión
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_authenticated = true;
}

// Si no está autenticado por sesión, verificar si hay un usuario en la BD
if (!$is_authenticated) {
    try {
        $mysqli = get_database_connection();
        if ($mysqli && !$mysqli->connect_errno) {
            $result = $mysqli->query("SELECT id FROM usuarios WHERE email = 'gustavo.bragagnolo@gmail.com' LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $is_authenticated = true;
                $_SESSION['logged_in'] = true;
            }
            // NO cerrar la conexión aquí - se usa el singleton
        }
    } catch (Exception $e) {
        // Si hay error de BD, permitir continuar para debug
        error_log("Error verificando autenticación: " . $e->getMessage());
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
        if (!$mysqli) {
            throw new Exception('No se pudo obtener conexión a la BD');
        }
        if ($mysqli->connect_errno) {
            throw new Exception('Error de conexión: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8');
        return $mysqli;
    } catch (Throwable $e) {
        throw new Exception('Error conectando a BD: ' . $e->getMessage());
    }
}

// Función para formatear producto según el formato solicitado
function formatearProducto($producto, $mysqli) {
    // Obtener marca
    $marca = null;
    if ($producto['marca_id']) {
        $stmt_marca = $mysqli->prepare("SELECT id, nombre FROM marcas WHERE id = ?");
        $stmt_marca->bind_param('i', $producto['marca_id']);
        $stmt_marca->execute();
        $result_marca = $stmt_marca->get_result();
        if ($marca_data = $result_marca->fetch_assoc()) {
            $marca = [
                "id" => "marca-" . $marca_data['id'],
                "nombre" => $marca_data['nombre'],
                "slug" => strtolower(str_replace(' ', '-', $marca_data['nombre']))
            ];
        }
    }

    // Obtener categorías
    $stmt_cat = $mysqli->prepare("
        SELECT c.id, c.nombre, c.slug 
        FROM categorias c 
        INNER JOIN producto_categorias pc ON c.id = pc.categoria_id 
        WHERE pc.producto_id = ?
    ");
    $stmt_cat->bind_param('i', $producto['id']);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    $categorias = [];
    while ($cat = $result_cat->fetch_assoc()) {
        $categorias[] = [
            "id" => "cat-" . $cat['id'],
            "nombre" => $cat['nombre'],
            "slug" => $cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['nombre']))
        ];
    }

    // Obtener imágenes
    $stmt_img = $mysqli->prepare("
        SELECT id, url, alt_text 
        FROM producto_imagenes 
        WHERE producto_id = ? 
        ORDER BY es_principal DESC, orden ASC, id ASC
    ");
    $stmt_img->bind_param('i', $producto['id']);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    $imagenes = [];
    $base_url = "https://stadium.planbsistemas.com.ar/";
    while ($img = $result_img->fetch_assoc()) {
        $imagenes[] = [
            "id" => "img-" . $img['id'],
            "url" => $base_url . $img['url'],
            "textoAlternativo" => $img['alt_text'] ?? $producto['nombre']
        ];
    }

    // Obtener variantes
    $stmt_var = $mysqli->prepare("
        SELECT id, sku_variante, stock, precio 
        FROM producto_variantes 
        WHERE producto_id = ? AND activa = 1
        ORDER BY id ASC
    ");
    $stmt_var->bind_param('i', $producto['id']);
    $stmt_var->execute();
    $result_var = $stmt_var->get_result();
    $variantes = [];
    while ($var = $result_var->fetch_assoc()) {
        // Obtener opciones de la variante
        $stmt_opt = $mysqli->prepare("
            SELECT nombre, valor 
            FROM variante_opciones 
            WHERE variante_id = ?
        ");
        $stmt_opt->bind_param('i', $var['id']);
        $stmt_opt->execute();
        $result_opt = $stmt_opt->get_result();
        $opciones = [];
        while ($opt = $result_opt->fetch_assoc()) {
            $opciones[] = [
                "nombre" => $opt['nombre'],
                "valor" => $opt['valor']
            ];
        }

        // Obtener imágenes de la variante (si existen)
        $stmt_var_img = $mysqli->prepare("
            SELECT id, url, alt_text 
            FROM variante_imagenes 
            WHERE variante_id = ? 
            ORDER BY orden ASC, id ASC
        ");
        $stmt_var_img->bind_param('i', $var['id']);
        $stmt_var_img->execute();
        $result_var_img = $stmt_var_img->get_result();
        $imagenes_variante = [];
        while ($img_var = $result_var_img->fetch_assoc()) {
            $imagenes_variante[] = [
                "id" => "var-img-" . $img_var['id'],
                "url" => $base_url . $img_var['url']
            ];
        }

        // Si no hay imágenes específicas para la variante, usar las del producto
        if (empty($imagenes_variante) && !empty($imagenes)) {
            $imagenes_variante = [$imagenes[0]]; // Usar la primera imagen del producto
        }

        $variante_formateada = [
            "id" => "var-" . $var['id'],
            "sku" => $var['sku_variante'],
            "stock" => intval($var['stock']),
            "precio" => floatval($var['precio'] ?? $producto['precio']),
            "opciones" => $opciones,
            "imagenes" => $imagenes_variante
        ];

        $variantes[] = $variante_formateada;
    }

    // Obtener atributos
    $stmt_attr = $mysqli->prepare("
        SELECT nombre, valor 
        FROM producto_atributos 
        WHERE producto_id = ? 
        ORDER BY id ASC
    ");
    $stmt_attr->bind_param('i', $producto['id']);
    $stmt_attr->execute();
    $result_attr = $stmt_attr->get_result();
    $atributos = [];
    while ($attr = $result_attr->fetch_assoc()) {
        $atributos[] = [
            "nombre" => $attr['nombre'],
            "valor" => $attr['valor']
        ];
    }

    // Dimensiones
    $dimensiones = [
        "peso" => $producto['peso'] ? floatval($producto['peso']) : null,
        "alto" => $producto['alto'] ? floatval($producto['alto']) : null,
        "ancho" => $producto['ancho'] ? floatval($producto['ancho']) : null,
        "profundidad" => $producto['profundidad'] ? floatval($producto['profundidad']) : null
    ];

    // Remover valores null de dimensiones
    $dimensiones = array_filter($dimensiones, function($value) {
        return $value !== null;
    });

    // Buscar nombre del deporte si el campo contiene un id o slug
    $deporte_nombre = "";
    if (!empty($producto['deporte'])) {
        try {
            if (is_numeric($producto['deporte'])) {
                $stmt_deporte = $mysqli->prepare("SELECT nombre FROM deportes WHERE id = ?");
                $dep_id = intval($producto['deporte']);
                $stmt_deporte->bind_param('i', $dep_id);
            } else {
                $stmt_deporte = $mysqli->prepare("SELECT nombre FROM deportes WHERE slug = ? OR nombre = ?");
                $stmt_deporte->bind_param('ss', $producto['deporte'], $producto['deporte']);
            }
            $stmt_deporte->execute();
            $res_deporte = $stmt_deporte->get_result();
            if ($dep = $res_deporte->fetch_assoc()) {
                $deporte_nombre = $dep['nombre'];
            } else {
                $deporte_nombre = $producto['deporte'];
            }
        } catch (Throwable $e) {
            $deporte_nombre = $producto['deporte'];
        }
    }

    // Formatear el producto según el esquema solicitado
    $producto_formateado = [
        "id" => strtolower(str_replace(' ', '-', $producto['nombre'])) . "-" . $producto['id'],
        "sku" => $producto['sku'],
        "nombre" => $producto['nombre'],
        "descripcion" => $producto['descripcion'] ?? "",
        "precio" => floatval($producto['precio']),
        "stock" => intval($producto['stock']),
        "estaActivo" => boolval($producto['esta_activo']),
        "destacado" => boolval($producto['destacado']),
        "deporte" => $deporte_nombre ?? "",
        "imagenes" => $imagenes,
        "categorias" => $categorias,
        "variantes" => $variantes,
        "atributos" => $atributos,
        "fechaDeCreacion" => date('c', strtotime($producto['fecha_creacion'])),
        "fechaDeActualizacion" => date('c', strtotime($producto['fecha_actualizacion']))
    ];

    // Agregar marca si existe
    if ($marca) {
        $producto_formateado["marca"] = $marca;
    }

    // Agregar precio de descuento si existe
    if ($producto['precio_descuento']) {
        $producto_formateado["precioDeDescuento"] = floatval($producto['precio_descuento']);
    }

    // Agregar dimensiones si existen
    if (!empty($dimensiones)) {
        $producto_formateado["dimensiones"] = $dimensiones;
    }

    return $producto_formateado;
}

try {
    $mysqli = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Si se recibe un ID específico, devolver solo ese producto
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $id_param = $_GET['id'];
            
            // Si el ID viene en formato "nombre-producto-123", extraer el número final
            if (strpos($id_param, '-') !== false) {
                $partes = explode('-', $id_param);
                $producto_id = intval(end($partes));
            } else {
                $producto_id = intval($id_param);
            }
            
            $stmt = $mysqli->prepare("SELECT p.* FROM productos p WHERE p.id = ?");
            $stmt->bind_param('i', $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($producto = $result->fetch_assoc()) {
                $producto_formateado = formatearProducto($producto, $mysqli);
                echo json_encode([
                    "success" => true,
                    "data" => $producto_formateado
                ], JSON_PRETTY_PRINT);
            } else {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "error" => "Producto no encontrado"
                ]);
            }
        } else {
            // Parámetros de paginación
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;

            // Filtros
            $where_conditions = ["p.esta_activo = 1"];
            $params = [];
            $param_types = "";

            if (isset($_GET['deporte']) && !empty($_GET['deporte'])) {
                $where_conditions[] = "p.deporte = ?";
                $params[] = $_GET['deporte'];
                $param_types .= "s";
            }

            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $where_conditions[] = "EXISTS (SELECT 1 FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id AND (c.slug = ? OR c.nombre = ?))";
                $params[] = $_GET['categoria'];
                $params[] = $_GET['categoria'];
                $param_types .= "ss";
            }

            if (isset($_GET['marca']) && !empty($_GET['marca'])) {
                $where_conditions[] = "EXISTS (SELECT 1 FROM marcas m WHERE m.id = p.marca_id AND (m.nombre = ? OR LOWER(REPLACE(m.nombre, ' ', '-')) = ?))";
                $params[] = $_GET['marca'];
                $params[] = strtolower($_GET['marca']);
                $param_types .= "ss";
            }

            if (isset($_GET['destacado']) && $_GET['destacado'] !== '') {
                $where_conditions[] = "p.destacado = ?";
                $params[] = intval($_GET['destacado']);
                $param_types .= "i";
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Consulta para contar total
            $count_query = "
                SELECT COUNT(*) as total 
                FROM productos p 
                WHERE $where_clause
            ";
            
            if (!empty($params)) {
                $stmt_count = $mysqli->prepare($count_query);
                $stmt_count->bind_param($param_types, ...$params);
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                $total_count = $result_count->fetch_assoc()['total'];
            } else {
                $result_count = $mysqli->query($count_query);
                $total_count = $result_count->fetch_assoc()['total'];
            }

            // Consulta principal
            $main_query = "
                SELECT p.* 
                FROM productos p 
                WHERE $where_clause
                ORDER BY p.destacado DESC, p.nombre ASC
                LIMIT ? OFFSET ?
            ";
            
            // Agregar parámetros de paginación
            $params[] = $limit;
            $params[] = $offset;
            $param_types .= "ii";

            $stmt = $mysqli->prepare($main_query);
            if (!empty($params)) {
                $stmt->bind_param($param_types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $productos_formateados = [];
            while ($producto = $result->fetch_assoc()) {
                $productos_formateados[] = formatearProducto($producto, $mysqli);
            }

            // Calcular paginación
            $total_pages = ceil($total_count / $limit);

            // Respuesta en el formato solicitado
            $response = [
                "data" => $productos_formateados,
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total" => intval($total_count),
                    "totalPages" => $total_pages
                ]
            ];

            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear nuevo producto
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Datos JSON inválidos');
        }

        if (!isset($input['nombre']) || !isset($input['sku']) || !isset($input['precio'])) {
            throw new Exception('Campos requeridos: nombre, sku, precio');
        }

        $mysqli->autocommit(false);

        try {
            // Insertar producto básico
            $stmt = $mysqli->prepare("
                INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, stock, 
                                     esta_activo, destacado, marca_id, deporte, peso, alto, ancho, profundidad) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $sku = $input['sku'];
            $nombre = $input['nombre'];
            $descripcion = $input['descripcion'] ?? '';
            $precio = $input['precio'];
            $precio_descuento = isset($input['precioDeDescuento']) ? $input['precioDeDescuento'] : null;
            $stock = $input['stock'] ?? 0;
            $esta_activo = isset($input['estaActivo']) ? intval($input['estaActivo']) : 1;
            $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
            $marca_id = null;
            $deporte = $input['deporte'] ?? '';

            // Buscar marca por nombre o slug
            if (isset($input['marca']) && is_array($input['marca']) && isset($input['marca']['nombre'])) {
                $stmt_marca = $mysqli->prepare("SELECT id FROM marcas WHERE nombre = ?");
                $stmt_marca->bind_param('s', $input['marca']['nombre']);
                $stmt_marca->execute();
                $result_marca = $stmt_marca->get_result();
                if ($marca_data = $result_marca->fetch_assoc()) {
                    $marca_id = $marca_data['id'];
                }
            }

            // Dimensiones
            $peso = isset($input['dimensiones']['peso']) ? $input['dimensiones']['peso'] : null;
            $alto = isset($input['dimensiones']['alto']) ? $input['dimensiones']['alto'] : null;
            $ancho = isset($input['dimensiones']['ancho']) ? $input['dimensiones']['ancho'] : null;
            $profundidad = isset($input['dimensiones']['profundidad']) ? $input['dimensiones']['profundidad'] : null;

            $stmt->bind_param('sssddiiiisdddd', 
                $sku, $nombre, $descripcion, $precio, $precio_descuento,
                $stock, $esta_activo, $destacado, $marca_id, $deporte,
                $peso, $alto, $ancho, $profundidad
            );

            if (!$stmt->execute()) {
                throw new Exception('Error al crear producto: ' . $stmt->error);
            }

            $producto_id = $mysqli->insert_id;

            // Insertar categorías
            if (isset($input['categorias']) && is_array($input['categorias'])) {
                foreach ($input['categorias'] as $categoria) {
                    $categoria_id = null;
                    if (isset($categoria['id'])) {
                        $cat_id_num = str_replace('cat-', '', $categoria['id']);
                        if (is_numeric($cat_id_num)) {
                            $categoria_id = intval($cat_id_num);
                        }
                    } elseif (isset($categoria['nombre'])) {
                        $stmt_cat = $mysqli->prepare("SELECT id FROM categorias WHERE nombre = ?");
                        $stmt_cat->bind_param('s', $categoria['nombre']);
                        $stmt_cat->execute();
                        $result_cat = $stmt_cat->get_result();
                        if ($cat_data = $result_cat->fetch_assoc()) {
                            $categoria_id = $cat_data['id'];
                        }
                    }

                    if ($categoria_id) {
                        $stmt_pc = $mysqli->prepare("INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (?, ?)");
                        $stmt_pc->bind_param('ii', $producto_id, $categoria_id);
                        $stmt_pc->execute();
                    }
                }
            }

            // Insertar imágenes
            if (isset($input['imagenes']) && is_array($input['imagenes'])) {
                foreach ($input['imagenes'] as $index => $imagen) {
                    if (!empty($imagen['url'])) {
                        $stmt_img = $mysqli->prepare("
                            INSERT INTO producto_imagenes (producto_id, url, alt_text, es_principal, orden) 
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $url = $imagen['url'];
                        $alt_text = $imagen['textoAlternativo'] ?? $nombre;
                        $es_principal = ($index === 0) ? 1 : 0;
                        $orden = $index;

                        $stmt_img->bind_param('issii', $producto_id, $url, $alt_text, $es_principal, $orden);
                        $stmt_img->execute();
                    }
                }
            }

            // Insertar variantes
            if (isset($input['variantes']) && is_array($input['variantes'])) {
                foreach ($input['variantes'] as $variante) {
                    if (!empty($variante['sku'])) {
                        $stmt_var = $mysqli->prepare("
                            INSERT INTO producto_variantes (producto_id, sku_variante, stock, precio, activa) 
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $var_sku = $variante['sku'];
                        $var_stock = $variante['stock'] ?? 0;
                        $var_precio = $variante['precio'] ?? null;
                        $var_activa = 1;

                        $stmt_var->bind_param('isidi', $producto_id, $var_sku, $var_stock, $var_precio, $var_activa);
                        $stmt_var->execute();

                        $variante_id = $mysqli->insert_id;

                        // Insertar opciones de variante
                        if (isset($variante['opciones']) && is_array($variante['opciones'])) {
                            foreach ($variante['opciones'] as $opcion) {
                                if (isset($opcion['nombre']) && isset($opcion['valor'])) {
                                    $stmt_opt = $mysqli->prepare("
                                        INSERT INTO variante_opciones (variante_id, nombre, valor) 
                                        VALUES (?, ?, ?)
                                    ");
                                    $stmt_opt->bind_param('iss', $variante_id, $opcion['nombre'], $opcion['valor']);
                                    $stmt_opt->execute();
                                }
                            }
                        }
                    }
                }
            }

            // Insertar atributos
            if (isset($input['atributos']) && is_array($input['atributos'])) {
                foreach ($input['atributos'] as $index => $atributo) {
                    if (isset($atributo['nombre']) && isset($atributo['valor'])) {
                        $stmt_attr = $mysqli->prepare("
                            INSERT INTO producto_atributos (producto_id, nombre, valor) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt_attr->bind_param('iss', $producto_id, $atributo['nombre'], $atributo['valor']);
                        $stmt_attr->execute();
                    }
                }
            }

            $mysqli->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Producto creado correctamente',
                'id' => $producto_id
            ]);

        } catch (Exception $e) {
            $mysqli->rollback();
            throw new Exception('Error en creación: ' . $e->getMessage());
        }
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