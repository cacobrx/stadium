<?php
// api/presupuestos.php
// API REST para gestión de presupuestos con transacciones

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Función de compatibilidad (mantener nombre original)
function get_db_connection() {
    return get_database_connection();
}

// Obtener todos los presupuestos
function obtener_presupuestos() {
    try {
        $conn = get_db_connection();
        $sql = "SELECT p.id, p.fecha, p.idcli, p.cli_nombre, p.cli_empresa, p.cli_telefono, p.cli_email, p.tipoProyecto, p.origen, p.observaciones, p.ubicacion, p.nro_presupuesto, 
                p.fechacreate, p.fechamod, p.estado, p.usuario, p.validez, 
                p.fecha_vencimiento, p.notas_internas,
                ori.descripcion as origen_desc,
                CONCAT(c.apellido, ' ', c.nombre) as cliente_nombre,
                COALESCE((SELECT SUM(pd.cantidad * pd.precio * (1 + pd.iva/100))
                          FROM presupuestos_det pd WHERE pd.idpres = p.id), 0) AS total
                FROM presupuestos p
                LEFT JOIN clientes c ON p.idcli = c.id
                LEFT JOIN tablas ori ON ori.codtab = 'ORI' AND ori.valor = p.origen
                ORDER BY p.fecha DESC, p.id DESC";
        $result = $conn->query($sql);
        $presupuestos = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $presupuestos[] = $row;
            }
        }
        $conn->close();
        return $presupuestos;
    } catch (Exception $e) {
        error_log("Error en obtener_presupuestos: " . $e->getMessage());
        return [];
    }
}

// Obtener presupuesto por ID
function obtener_presupuesto($id) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT p.id, p.fecha, p.idcli, p.cli_nombre, p.cli_empresa, p.cli_telefono, p.cli_email, p.tipoProyecto, p.origen, p.observaciones, p.ubicacion, p.nro_presupuesto, 
                p.fechacreate, p.fechamod, p.estado, p.total, p.usuario, p.validez, 
                p.fecha_vencimiento, p.notas_internas,
                ori.descripcion as origen_desc,
                CONCAT(c.apellido, ' ', c.nombre) as cliente_nombre
                FROM presupuestos p
                LEFT JOIN clientes c ON p.idcli = c.id
                LEFT JOIN tablas ori ON ori.codtab = 'ORI' AND ori.valor = p.origen
                WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $presupuesto = $result->fetch_assoc();
        $conn->close();
        return $presupuesto;
    } catch (Exception $e) {
        error_log("Error en obtener_presupuesto: " . $e->getMessage());
        return null;
    }
}

// Obtener detalles de un presupuesto
function obtener_presupuesto_detalles($idpres) {
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT pd.id, pd.idpres, pd.cantidad, pd.idart, pd.precio,
                (pd.cantidad * pd.precio * (1 + pd.iva/100)) AS total,
                pd.detalle, pd.iva, pd.tipo_producto,
                CASE 
                    WHEN pd.tipo_producto = 'equipamiento' THEN ep.nombre
                    ELSE p.nombre
                END as producto_nombre,
                CASE 
                    WHEN pd.tipo_producto = 'equipamiento' THEN CONCAT('EQ-', LPAD(ep.id, 5, '0'))
                    ELSE p.sku
                END as producto_codigo
                FROM presupuestos_det pd
                LEFT JOIN productos p ON pd.idart = p.id AND pd.tipo_producto = 'producto'
                LEFT JOIN equipamiento_profesional ep ON pd.idart = ep.id AND pd.tipo_producto = 'equipamiento'
                WHERE pd.idpres = ?
                ORDER BY pd.id");
        $stmt->bind_param("i", $idpres);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $detalles[] = $row;
            }
        }
        $conn->close();
        return $detalles;
    } catch (Exception $e) {
        error_log("Error en obtener_presupuesto_detalles: " . $e->getMessage());
        return [];
    }
}

// Crear nuevo presupuesto
function crear_presupuesto($datos) {
    try {
        $conn = get_db_connection();
        // Compatibilidad: si la instalación de mysqli no expone begin_transaction, usar autocommit(false)
        if (method_exists($conn, 'begin_transaction')) {
            $conn->begin_transaction();
        } else {
            $conn->autocommit(false);
        }
        
        // Calcular fecha de vencimiento
        $validez = isset($datos['validez']) ? intval($datos['validez']) : 30;
        $fecha_vencimiento = date('Y-m-d', strtotime($datos['fecha'] . " +{$validez} days"));
        
        // Crear el presupuesto
        $stmt = $conn->prepare("INSERT INTO presupuestos (fecha, idcli, cli_nombre, cli_empresa, cli_telefono, cli_email, tipoProyecto, origen, observaciones, ubicacion, nro_presupuesto, 
                estado, total, usuario, validez, fecha_vencimiento, notas_internas) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Obtener usuario actual de sesión
        session_start();
        $usuario_actual = $_SESSION['usuario_id'] ?? 1;
        
        $nro_presupuesto = $datos['nro_presupuesto'] ?? null;
        $notas_internas = $datos['notas_internas'] ?? null;
        $cli_nombre = $datos['cli_nombre'] ?? null;
        $cli_empresa = $datos['cli_empresa'] ?? null;
        $cli_telefono = $datos['cli_telefono'] ?? null;
        $cli_email = $datos['cli_email'] ?? null;
        $tipoProyecto = $datos['tipoProyecto'] ?? null;
        $origen = (isset($datos['origen']) && $datos['origen'] !== '') ? intval($datos['origen']) : null;
        
        $stmt->bind_param("sisssssisssidiiss", 
            $datos['fecha'], 
            $datos['idcli'], 
            $cli_nombre,
            $cli_empresa,
            $cli_telefono,
            $cli_email,
            $tipoProyecto,
            $origen,
            $datos['observaciones'], 
            $datos['ubicacion'], 
            $nro_presupuesto, 
            $datos['estado'], 
            $datos['total'], 
            $usuario_actual,
            $validez,
            $fecha_vencimiento,
            $notas_internas
        );
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error;
            error_log("Error MySQL en crear_presupuesto: " . $error);
            throw new Exception("Error al crear presupuesto: " . $error);
        }
        
        $presupuesto_id = $conn->insert_id;
        
        // Crear los detalles del presupuesto si hay productos
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            $stmt_det = $conn->prepare("INSERT INTO presupuestos_det (idpres, cantidad, idart, precio, total, detalle, iva, tipo_producto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($datos['productos'] as $producto) {
                $iva = $producto['iva'] ?? 21.00;
                $tipo_producto = $producto['tipo_producto'] ?? 'producto';
                $detalle = $producto['detalle'] ?? null;
                
                $stmt_det->bind_param("iiiddsds",
                    $presupuesto_id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total'],
                    $detalle,
                    $iva,
                    $tipo_producto
                );
                
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al crear detalle del presupuesto");
                }
            }
        }
        
        // Commit
        if (method_exists($conn, 'commit')) {
            $conn->commit();
        } else {
            $conn->autocommit(true);
        }
        $conn->close();
        return $presupuesto_id;
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            if (method_exists($conn, 'rollback')) {
                $conn->rollback();
            } else {
                try { $conn->rollback(); } catch (Exception $ex) { /* ignore */ }
                $conn->autocommit(true);
            }
            $conn->close();
        }
        error_log("Error en crear_presupuesto: " . $e->getMessage());
        return false;
    }
}

// Actualizar presupuesto
function actualizar_presupuesto($id, $datos) {
    try {
        $conn = get_db_connection();
        if (method_exists($conn, 'begin_transaction')) {
            $conn->begin_transaction();
        } else {
            $conn->autocommit(false);
        }
        
        // Actualizar presupuesto
        $stmt = $conn->prepare("UPDATE presupuestos SET fecha=?, idcli=?, cli_nombre=?, cli_empresa=?, cli_telefono=?, cli_email=?, tipoProyecto=?, origen=?, observaciones=?, ubicacion=?, 
                nro_presupuesto=?, estado=?, total=?, validez=?, fecha_vencimiento=?, notas_internas=? 
                WHERE id=?");
        
        $validez = isset($datos['validez']) ? intval($datos['validez']) : 30;
        $fecha_vencimiento = date('Y-m-d', strtotime($datos['fecha'] . " +{$validez} days"));
        $nro_presupuesto = $datos['nro_presupuesto'] ?? null;
        $notas_internas = $datos['notas_internas'] ?? null;
        $cli_nombre = $datos['cli_nombre'] ?? null;
        $cli_empresa = $datos['cli_empresa'] ?? null;
        $cli_telefono = $datos['cli_telefono'] ?? null;
        $cli_email = $datos['cli_email'] ?? null;
        $tipoProyecto = $datos['tipoProyecto'] ?? null;
        $origen = (isset($datos['origen']) && $datos['origen'] !== '') ? intval($datos['origen']) : null;
        
        $stmt->bind_param("sisssssisssidissi", 
            $datos['fecha'], 
            $datos['idcli'], 
            $cli_nombre,
            $cli_empresa,
            $cli_telefono,
            $cli_email,
            $tipoProyecto,
            $origen,
            $datos['observaciones'], 
            $datos['ubicacion'], 
            $nro_presupuesto,
            $datos['estado'], 
            $datos['total'],
            $validez,
            $fecha_vencimiento,
            $notas_internas,
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar presupuesto");
        }
        
        // Eliminar detalles existentes
        $stmt_del = $conn->prepare("DELETE FROM presupuestos_det WHERE idpres = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        
        // Insertar nuevos detalles
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            $stmt_det = $conn->prepare("INSERT INTO presupuestos_det (idpres, cantidad, idart, precio, total, detalle, iva, tipo_producto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($datos['productos'] as $producto) {
                $iva = $producto['iva'] ?? 21.00;
                $tipo_producto = $producto['tipo_producto'] ?? 'producto';
                $detalle = $producto['detalle'] ?? null;
                
                $stmt_det->bind_param("iiiddsds",
                    $id,
                    $producto['cantidad'],
                    $producto['idart'],
                    $producto['precio'],
                    $producto['total'],
                    $detalle,
                    $iva,
                    $tipo_producto
                );
                
                if (!$stmt_det->execute()) {
                    throw new Exception("Error al crear detalle del presupuesto");
                }
            }
        }
        
        // Commit
        if (method_exists($conn, 'commit')) {
            $conn->commit();
        } else {
            $conn->autocommit(true);
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            if (method_exists($conn, 'rollback')) {
                $conn->rollback();
            } else {
                try { $conn->rollback(); } catch (Exception $ex) { /* ignore */ }
                $conn->autocommit(true);
            }
            $conn->close();
        }
        error_log("Error en actualizar_presupuesto: " . $e->getMessage());
        return false;
    }
}

// Eliminar presupuesto
function eliminar_presupuesto($id) {
    try {
        $conn = get_db_connection();
        if (method_exists($conn, 'begin_transaction')) {
            $conn->begin_transaction();
        } else {
            $conn->autocommit(false);
        }
        
        // Eliminar detalles
        $stmt = $conn->prepare("DELETE FROM presupuestos_det WHERE idpres = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Eliminar presupuesto
        $stmt = $conn->prepare("DELETE FROM presupuestos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        if (!$success) {
            throw new Exception("Error al eliminar presupuesto");
        }
        
        // Commit
        if (method_exists($conn, 'commit')) {
            $conn->commit();
        } else {
            $conn->autocommit(true);
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            if (method_exists($conn, 'rollback')) {
                $conn->rollback();
            } else {
                try { $conn->rollback(); } catch (Exception $ex) { /* ignore */ }
                $conn->autocommit(true);
            }
            $conn->close();
        }
        error_log("Error en eliminar_presupuesto: " . $e->getMessage());
        return false;
    }
}

// Manejo de peticiones HTTP
try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['ultimo_nro']) && $_GET['ultimo_nro'] == 1 && isset($_GET['anio'])) {
                // Obtener el último número correlativo de presupuesto para el año dado
                $anio = intval($_GET['anio']);
                $conn = get_db_connection();
                $stmt = $conn->prepare("SELECT nro_presupuesto FROM presupuestos WHERE nro_presupuesto LIKE CONCAT('PRES-', ?, '-%') ORDER BY nro_presupuesto DESC LIMIT 1");
                $stmt->bind_param("i", $anio);
                $stmt->execute();
                $stmt->bind_result($nro);
                $ultimo = null;
                if ($stmt->fetch() && preg_match('/PRES-\\d{4}-(\\d{3})/', $nro, $m)) {
                    $ultimo = intval($m[1]);
                }
                $stmt->close();
                $conn->close();
                echo json_encode(['ultimo_nro' => $ultimo]);
            } elseif (isset($_GET['id'])) {
                // Obtener presupuesto específico
                $presupuesto = obtener_presupuesto($_GET['id']);
                if ($presupuesto) {
                    echo json_encode($presupuesto);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Presupuesto no encontrado']);
                }
            } elseif (isset($_GET['detalles']) && isset($_GET['idpres'])) {
                // Obtener detalles de un presupuesto
                $detalles = obtener_presupuesto_detalles($_GET['idpres']);
                echo json_encode($detalles);
            } else {
                // Obtener todos los presupuestos
                echo json_encode(obtener_presupuestos());
            }
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!$datos) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                break;
            }
            
            $resultado = crear_presupuesto($datos);
            if ($resultado) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $resultado]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear presupuesto']);
            }
            break;

        case 'PUT':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (!$datos || !isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                break;
            }
            
            $resultado = actualizar_presupuesto($_GET['id'], $datos);
            if ($resultado) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar presupuesto']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                break;
            }
            
            $resultado = eliminar_presupuesto($_GET['id']);
            if ($resultado) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar presupuesto']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    error_log("Error general en API presupuestos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
