<?php
// api/equipamiento_profesional.php
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

// GET - Obtener equipamiento
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mysqli = getConnection();
        
        // Si se solicita un equipamiento específico
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Obtener equipamiento principal con deporte
            $stmt = $mysqli->prepare("
                SELECT e.*, d.nombre as deporte_nombre
                FROM equipamiento_profesional e
                LEFT JOIN deportes d ON e.deporte_id = d.id
                WHERE e.id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($equipamiento = $result->fetch_assoc()) {
                // Obtener especificaciones
                $stmt = $mysqli->prepare("
                    SELECT etiqueta, valor, orden 
                    FROM equipamiento_especificaciones 
                    WHERE equipamiento_id = ? 
                    ORDER BY orden ASC, id ASC
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $especificaciones_result = $stmt->get_result();
                $equipamiento['especificaciones'] = $especificaciones_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener características
                $stmt = $mysqli->prepare("
                    SELECT caracteristica, orden 
                    FROM equipamiento_caracteristicas 
                    WHERE equipamiento_id = ? 
                    ORDER BY orden ASC, id ASC
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $caracteristicas_result = $stmt->get_result();
                $equipamiento['caracteristicas'] = $caracteristicas_result->fetch_all(MYSQLI_ASSOC);
                
                // Obtener imágenes
                $stmt = $mysqli->prepare("
                    SELECT url, alt_text, es_principal, orden 
                    FROM equipamiento_imagenes 
                    WHERE equipamiento_id = ? 
                    ORDER BY es_principal DESC, orden ASC
                ");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $imagenes_result = $stmt->get_result();
                $equipamiento['imagenes'] = $imagenes_result->fetch_all(MYSQLI_ASSOC);
                
                echo json_encode(['success' => true, 'equipamiento' => $equipamiento]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Equipamiento no encontrado']);
            }
        } elseif (isset($_GET['max_orden'])) {
            // Devolver el máximo orden actual
            $row = $mysqli->query("SELECT COALESCE(MAX(orden), 0) AS max_orden FROM equipamiento_profesional")->fetch_assoc();
            echo json_encode(['success' => true, 'max_orden' => (int)$row['max_orden']]);
            $mysqli->close();
            exit;
        } else {
            // Obtener lista de equipamiento con filtros opcionales
            $where_conditions = [];
            $params = [];
            $types = '';
            
            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $where_conditions[] = "e.categoria = ?";
                $params[] = $_GET['categoria'];
                $types .= 's';
            }
            
            if (isset($_GET['deporte']) && !empty($_GET['deporte'])) {
                $where_conditions[] = "e.deporte_id = ?";
                $params[] = intval($_GET['deporte']);
                $types .= 'i';
            }
            
            if (isset($_GET['disponible']) && $_GET['disponible'] !== '') {
                $where_conditions[] = "e.disponible = ?";
                $params[] = intval($_GET['disponible']);
                $types .= 'i';
            }
            
            if (isset($_GET['uso']) && !empty($_GET['uso'])) {
                $where_conditions[] = "e.uso = ?";
                $params[] = $_GET['uso'];
                $types .= 's';
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $where_conditions[] = "(e.nombre LIKE ? OR e.descripcion LIKE ?)";
                $search_term = '%' . $_GET['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $types .= 'ss';
            }
            
            $where_clause = '';
            if (!empty($where_conditions)) {
                $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            }
            
            $query = "
                SELECT e.id, e.nombre, e.categoria, e.deporte_id, e.descripcion, e.disponible, 
                       e.tiempo_entrega, e.garantia, e.instalacion, e.financiacion, 
                       e.esta_activo, e.destacado, e.fecha_creacion, e.orden, e.uso,
                       c.nombre as categoria_nombre,
                       d.nombre as deporte_nombre,
                       ei.url as imagen_principal
                FROM equipamiento_profesional e 
                LEFT JOIN categorias c ON e.categoria = c.id
                LEFT JOIN deportes d ON e.deporte_id = d.id
                LEFT JOIN equipamiento_imagenes ei ON e.id = ei.equipamiento_id AND ei.es_principal = 1
                $where_clause
                ORDER BY e.orden ASC
            ";
            
            if (!empty($params)) {
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $mysqli->query($query);
            }
            
            $equipamiento = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'equipamiento' => $equipamiento]);
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// POST - Crear equipamiento
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            throw new Exception('No se recibieron datos');
        }
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error de JSON: ' . json_last_error_msg());
        }
        
        if (!$input) {
            throw new Exception('Datos inválidos');
        }
        
        // Validar campos requeridos - solo nombre es obligatorio
        if (empty($input['nombre'])) {
            throw new Exception('Nombre es requerido');
        }
        
        $mysqli = getConnection();
        
        // Validar categoría contra la tabla categorias (si se proporciona)
        $categoria = !empty($input['categoria']) ? intval($input['categoria']) : null;
        if ($categoria !== null) {
            $stmt_cat = $mysqli->prepare("SELECT id FROM categorias WHERE id = ? AND activa = 1");
            $stmt_cat->bind_param('i', $categoria);
            $stmt_cat->execute();
            if ($stmt_cat->get_result()->num_rows === 0) {
                $categoria = null;
            }
        }
        
        $mysqli->autocommit(false); // Iniciar transacción

        // Determinar orden de inserción
        $maxOrdenRow = $mysqli->query("SELECT COALESCE(MAX(orden), 0) AS max_orden FROM equipamiento_profesional")->fetch_assoc();
        $maxOrden = (int)$maxOrdenRow['max_orden'];

        if (!empty($input['orden']) && intval($input['orden']) >= 1) {
            $nuevoOrden = intval($input['orden']);
            $nuevoOrden = min($nuevoOrden, $maxOrden + 1); // No permite saltar más allá del último
            // Desplazar hacia abajo todos los registros con orden >= nuevoOrden
            $mysqli->query("UPDATE equipamiento_profesional SET orden = orden + 1 WHERE orden >= $nuevoOrden");
        } else {
            $nuevoOrden = $maxOrden + 1;
        }

        // Insertar equipamiento principal
        $stmt = $mysqli->prepare("
            INSERT INTO equipamiento_profesional (nombre, categoria, deporte_id, descripcion, disponible, 
                                                  tiempo_entrega, garantia, instalacion, 
                                                  financiacion, esta_activo, destacado, orden, uso) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $disponible = isset($input['disponible']) ? intval($input['disponible']) : 1;
        $instalacion = isset($input['instalacion']) ? intval($input['instalacion']) : 0;
        $financiacion = isset($input['financiacion']) ? intval($input['financiacion']) : 0;
        $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
        $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
        $tiempo_entrega = !empty($input['tiempo_entrega']) ? $input['tiempo_entrega'] : null;
        $garantia = !empty($input['garantia']) ? $input['garantia'] : null;
        $deporte_id = !empty($input['deporte_id']) ? intval($input['deporte_id']) : null;
        $uso = isset($input['uso']) && in_array($input['uso'], ['Profesional', 'Domestico']) ? $input['uso'] : null;
        
        $stmt->bind_param('siisissiiiiis', 
            $input['nombre'], 
            $categoria,
            $deporte_id,
            $input['descripcion'], 
            $disponible,
            $tiempo_entrega, 
            $garantia, 
            $instalacion, 
            $financiacion, 
            $esta_activo, 
            $destacado,
            $maxOrden,
            $uso
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al crear equipamiento: ' . $stmt->error);
        }
        
        $equipamiento_id = $mysqli->insert_id;
        
        // Insertar especificaciones si se proporcionaron
        if (!empty($input['especificaciones']) && is_array($input['especificaciones'])) {
            $stmt_esp = $mysqli->prepare("INSERT INTO equipamiento_especificaciones (equipamiento_id, etiqueta, valor, orden) VALUES (?, ?, ?, ?)");
            foreach ($input['especificaciones'] as $index => $especificacion) {
                $stmt_esp->bind_param('issi', $equipamiento_id, $especificacion['etiqueta'], $especificacion['valor'], $index);
                $stmt_esp->execute();
            }
        }
        
        // Insertar características si se proporcionaron
        if (!empty($input['caracteristicas']) && is_array($input['caracteristicas'])) {
            $stmt_car = $mysqli->prepare("INSERT INTO equipamiento_caracteristicas (equipamiento_id, caracteristica, orden) VALUES (?, ?, ?)");
            foreach ($input['caracteristicas'] as $index => $caracteristica) {
                // Si caracteristica es string directamente
                $car_texto = is_array($caracteristica) ? $caracteristica['caracteristica'] : $caracteristica;
                $stmt_car->bind_param('isi', $equipamiento_id, $car_texto, $index);
                $stmt_car->execute();
            }
        }
        
        // Insertar imágenes si se proporcionaron
        if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
            $stmt_img = $mysqli->prepare("INSERT INTO equipamiento_imagenes (equipamiento_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($input['imagenes'] as $index => $imagen) {
                $es_principal = isset($imagen['es_principal']) ? intval($imagen['es_principal']) : (($index === 0) ? 1 : 0);
                $alt_text = $imagen['alt_text'] ?? $input['nombre'];
                $stmt_img->bind_param('issii', $equipamiento_id, $imagen['url'], $alt_text, $es_principal, $index);
                $stmt_img->execute();
            }
        }
        
        $mysqli->commit();
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Equipamiento creado exitosamente', 'id' => $equipamiento_id]);
        
    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->rollback();
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PUT - Actualizar equipamiento
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de equipamiento requerido');
        }
        
        // Validar campos requeridos - solo nombre es obligatorio
        if (empty($input['nombre'])) {
            throw new Exception('Nombre es requerido');
        }
        
        $mysqli = getConnection();
        
        // Validar categoría contra la tabla categorias (si se proporciona)
        $categoria = !empty($input['categoria']) ? intval($input['categoria']) : null;
        if ($categoria !== null) {
            $stmt_cat = $mysqli->prepare("SELECT id FROM categorias WHERE id = ? AND activa = 1");
            $stmt_cat->bind_param('i', $categoria);
            $stmt_cat->execute();
            if ($stmt_cat->get_result()->num_rows === 0) {
                $categoria = null;
            }
        }
        
        $mysqli->autocommit(false);
        
        $id = intval($input['id']);

        // Manejar reordenamiento si se cambió el orden
        $rowActual = $mysqli->query("SELECT orden FROM equipamiento_profesional WHERE id = $id LIMIT 1")->fetch_assoc();
        $ordenActual = (int)$rowActual['orden'];

        if (!empty($input['orden'])) {
            $nuevoOrden = intval($input['orden']);
            $maxOrdenRow = $mysqli->query("SELECT COALESCE(MAX(orden), 0) AS max_orden FROM equipamiento_profesional")->fetch_assoc();
            $maxOrden = (int)$maxOrdenRow['max_orden'];
            $nuevoOrden = max(1, min($nuevoOrden, $maxOrden));

            if ($nuevoOrden !== $ordenActual) {
                // Mover temporalmente fuera del rango para no interferir
                $mysqli->query("UPDATE equipamiento_profesional SET orden = 99999 WHERE id = $id");
                if ($nuevoOrden < $ordenActual) {
                    // Sube: desplazar intermedios hacia abajo (+1)
                    $mysqli->query("UPDATE equipamiento_profesional SET orden = orden + 1 WHERE orden >= $nuevoOrden AND orden < $ordenActual");
                } else {
                    // Baja: desplazar intermedios hacia arriba (-1)
                    $mysqli->query("UPDATE equipamiento_profesional SET orden = orden - 1 WHERE orden > $ordenActual AND orden <= $nuevoOrden");
                }
                $mysqli->query("UPDATE equipamiento_profesional SET orden = $nuevoOrden WHERE id = $id");
                $ordenActual = $nuevoOrden;
            }
        }

        // Actualizar equipamiento principal
        $stmt = $mysqli->prepare("
            UPDATE equipamiento_profesional 
            SET nombre = ?, categoria = ?, deporte_id = ?, descripcion = ?, disponible = ?, 
                tiempo_entrega = ?, garantia = ?, instalacion = ?, financiacion = ?, 
                esta_activo = ?, destacado = ?, orden = ?, uso = ?, fecha_modificacion = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $disponible = isset($input['disponible']) ? intval($input['disponible']) : 1;
        $instalacion = isset($input['instalacion']) ? intval($input['instalacion']) : 0;
        $financiacion = isset($input['financiacion']) ? intval($input['financiacion']) : 0;
        $esta_activo = isset($input['esta_activo']) ? intval($input['esta_activo']) : 1;
        $destacado = isset($input['destacado']) ? intval($input['destacado']) : 0;
        $tiempo_entrega = !empty($input['tiempo_entrega']) ? $input['tiempo_entrega'] : null;
        $garantia = !empty($input['garantia']) ? $input['garantia'] : null;
        $deporte_id = !empty($input['deporte_id']) ? intval($input['deporte_id']) : null;
        $uso = isset($input['uso']) && in_array($input['uso'], ['Profesional', 'Domestico']) ? $input['uso'] : null;
        
        $stmt->bind_param('siisissiiiiisi', 
            $input['nombre'], 
            $categoria,
            $deporte_id,
            $input['descripcion'], 
            $disponible,
            $tiempo_entrega, 
            $garantia, 
            $instalacion, 
            $financiacion, 
            $esta_activo, 
            $destacado,
            $ordenActual,
            $uso,
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar equipamiento: ' . $stmt->error);
        }
        
        // Eliminar especificaciones anteriores
        $mysqli->query("DELETE FROM equipamiento_especificaciones WHERE equipamiento_id = $id");
        
        // Insertar nuevas especificaciones
        if (!empty($input['especificaciones']) && is_array($input['especificaciones'])) {
            $stmt_esp = $mysqli->prepare("INSERT INTO equipamiento_especificaciones (equipamiento_id, etiqueta, valor, orden) VALUES (?, ?, ?, ?)");
            foreach ($input['especificaciones'] as $index => $especificacion) {
                $stmt_esp->bind_param('issi', $id, $especificacion['etiqueta'], $especificacion['valor'], $index);
                $stmt_esp->execute();
            }
        }
        
        // Eliminar características anteriores
        $mysqli->query("DELETE FROM equipamiento_caracteristicas WHERE equipamiento_id = $id");
        
        // Insertar nuevas características
        if (!empty($input['caracteristicas']) && is_array($input['caracteristicas'])) {
            $stmt_car = $mysqli->prepare("INSERT INTO equipamiento_caracteristicas (equipamiento_id, caracteristica, orden) VALUES (?, ?, ?)");
            foreach ($input['caracteristicas'] as $index => $caracteristica) {
                // Si caracteristica es string directamente
                $car_texto = is_array($caracteristica) ? $caracteristica['caracteristica'] : $caracteristica;
                $stmt_car->bind_param('isi', $id, $car_texto, $index);
                $stmt_car->execute();
            }
        }
        
        // Eliminar imágenes anteriores
        $mysqli->query("DELETE FROM equipamiento_imagenes WHERE equipamiento_id = $id");
        
        // Insertar nuevas imágenes
        if (!empty($input['imagenes']) && is_array($input['imagenes'])) {
            $stmt_img = $mysqli->prepare("INSERT INTO equipamiento_imagenes (equipamiento_id, url, alt_text, es_principal, orden) VALUES (?, ?, ?, ?, ?)");
            foreach ($input['imagenes'] as $index => $imagen) {
                $es_principal = isset($imagen['es_principal']) ? intval($imagen['es_principal']) : (($index === 0) ? 1 : 0);
                $alt_text = $imagen['alt_text'] ?? $input['nombre'];
                $stmt_img->bind_param('issii', $id, $imagen['url'], $alt_text, $es_principal, $index);
                $stmt_img->execute();
            }
        }
        
        $mysqli->commit();
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Equipamiento actualizado exitosamente']);
        
    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->rollback();
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// DELETE - Eliminar equipamiento
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de equipamiento requerido');
        }
        
        $mysqli = getConnection();
        $id = intval($input['id']);
        
        // Verificar si el equipamiento existe
        $stmt = $mysqli->prepare("SELECT id FROM equipamiento_profesional WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Equipamiento no encontrado');
        }
        
        // Eliminar equipamiento (las tablas relacionadas se eliminarán por CASCADE)
        $stmt = $mysqli->prepare("DELETE FROM equipamiento_profesional WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar equipamiento: ' . $stmt->error);
        }
        
        $mysqli->close();
        
        echo json_encode(['success' => true, 'message' => 'Equipamiento eliminado exitosamente']);
        
    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// PATCH - Reordenar equipamiento o togglear destacado
elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['id'])) {
            throw new Exception('ID de equipamiento requerido');
        }

        $id = intval($input['id']);

        // Toggle destacado
        if (!empty($input['toggle_destacado'])) {
            $mysqli = getConnection();
            $stmt = $mysqli->prepare("SELECT destacado FROM equipamiento_profesional WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) {
                throw new Exception('Equipamiento no encontrado');
            }
            $nuevo = $row['destacado'] ? 0 : 1;
            $stmtU = $mysqli->prepare("UPDATE equipamiento_profesional SET destacado = ? WHERE id = ?");
            $stmtU->bind_param('ii', $nuevo, $id);
            if (!$stmtU->execute()) {
                throw new Exception('Error al actualizar destacado: ' . $stmtU->error);
            }
            $mysqli->close();
            echo json_encode(['success' => true, 'destacado' => $nuevo]);
            exit;
        }

        if (!isset($input['direction'])) {
            throw new Exception('Parámetros requeridos: id, direction (up|down)');
        }

        $direction = $input['direction'] === 'up' ? 'up' : 'down';

        $mysqli = getConnection();

        // Obtener el orden actual del elemento
        $stmt = $mysqli->prepare("SELECT orden FROM equipamiento_profesional WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            throw new Exception('Equipamiento no encontrado');
        }

        $orden_actual = intval($row['orden']);

        // Buscar el elemento adyacente
        if ($direction === 'up') {
            $stmt2 = $mysqli->prepare("SELECT id, orden FROM equipamiento_profesional WHERE orden < ? ORDER BY orden DESC LIMIT 1");
        } else {
            $stmt2 = $mysqli->prepare("SELECT id, orden FROM equipamiento_profesional WHERE orden > ? ORDER BY orden ASC LIMIT 1");
        }
        $stmt2->bind_param('i', $orden_actual);
        $stmt2->execute();
        $vecino = $stmt2->get_result()->fetch_assoc();

        if (!$vecino) {
            echo json_encode(['success' => false, 'error' => 'No hay elemento para intercambiar']);
            $mysqli->close();
            exit;
        }

        $id_vecino    = intval($vecino['id']);
        $orden_vecino = intval($vecino['orden']);

        // Intercambiar órdenes
        $mysqli->autocommit(false);
        $stmtA = $mysqli->prepare("UPDATE equipamiento_profesional SET orden = ? WHERE id = ?");
        $stmtA->bind_param('ii', $orden_vecino, $id);
        $stmtA->execute();
        $stmtA->bind_param('ii', $orden_actual, $id_vecino);
        $stmtA->execute();
        $mysqli->commit();
        $mysqli->close();

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        if (isset($mysqli)) {
            $mysqli->rollback();
            $mysqli->close();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
