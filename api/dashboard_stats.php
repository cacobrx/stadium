<?php
// api/dashboard_stats.php
// API para obtener estadísticas del dashboard

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración y autenticación
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticación
requireAuthAPI();

/**
 * Obtener estadísticas generales del sistema
 */
function obtener_estadisticas_generales() {
    try {
        $conn = get_database_connection();
        $stats = [];
        
        // Total de productos activos
        $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE esta_activo = 1");
        $stats['total_productos'] = $result->fetch_assoc()['total'];
        
        // Total de clientes
        $result = $conn->query("SELECT COUNT(*) as total FROM clientes");
        $stats['total_clientes'] = $result->fetch_assoc()['total'];
        
        // Total de pedidos
        $result = $conn->query("SELECT COUNT(*) as total FROM pedidos");
        $stats['total_pedidos'] = $result->fetch_assoc()['total'];
        
        // Total de ventas (suma de totales de pedidos)
        $result = $conn->query("SELECT SUM(total) as total_ventas FROM pedidos WHERE estado != 4"); // Excluir cancelados
        $stats['ventas_totales'] = number_format((float)($result->fetch_assoc()['total_ventas'] ?: 0), 2, '.', '');
        
        // Pedidos del mes actual
        $result = $conn->query("
            SELECT COUNT(*) as pedidos_mes 
            FROM pedidos 
            WHERE MONTH(fechacreate) = MONTH(CURDATE()) 
            AND YEAR(fechacreate) = YEAR(CURDATE())
        ");
        $stats['pedidos_mes'] = $result->fetch_assoc()['pedidos_mes'];
        
        // Ventas del mes actual
        $result = $conn->query("
            SELECT SUM(total) as ventas_mes 
            FROM pedidos 
            WHERE MONTH(fechacreate) = MONTH(CURDATE()) 
            AND YEAR(fechacreate) = YEAR(CURDATE())
            AND estado != 4
        ");
        $stats['ventas_mes'] = $result->fetch_assoc()['ventas_mes'] ?: 0;
        
        // Productos con stock bajo (menos de 10)
        $result = $conn->query("SELECT COUNT(*) as stock_bajo FROM productos WHERE stock < 10 AND esta_activo = 1");
        $stats['stock_bajo'] = $result->fetch_assoc()['stock_bajo'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error en obtener_estadisticas_generales: " . $e->getMessage());
        throw new Exception("Error obteniendo estadísticas generales");
    }
}

/**
 * Obtener ventas por día de los últimos 30 días
 */
function obtener_ventas_diarias() {
    try {
        $conn = get_database_connection();
        
        $sql = "
            SELECT 
                DATE(fechacreate) as fecha,
                COUNT(*) as cantidad_pedidos,
                SUM(total) as total_ventas
            FROM pedidos 
            WHERE fechacreate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND estado != 4
            GROUP BY DATE(fechacreate)
            ORDER BY fecha ASC
        ";
        
        $result = $conn->query($sql);
        $ventas = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ventas[] = [
                    'fecha' => $row['fecha'],
                    'cantidad_pedidos' => (int)$row['cantidad_pedidos'],
                    'total' => (float)$row['total_ventas']
                ];
            }
        }
        
        return $ventas;
    } catch (Exception $e) {
        error_log("Error en obtener_ventas_diarias: " . $e->getMessage());
        throw new Exception("Error obteniendo ventas diarias");
    }
}

/**
 * Obtener actividad reciente
 */
function obtener_actividad_reciente() {
    try {
        $conn = get_database_connection();
        $actividades = [];
        
        // Pedidos recientes (últimos 10)
        $sql = "
            SELECT 
                p.id,
                p.fechacreate,
                p.total,
                p.estado,
                c.nombre,
                c.apellido
            FROM pedidos p
            LEFT JOIN clientes c ON p.idcli = c.id
            ORDER BY p.fechacreate DESC
            LIMIT 10
        ";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cliente_nombre = trim($row['nombre'] . ' ' . $row['apellido']) ?: 'Cliente ID: ' . $row['id'];
                $estados = ['Pendiente', 'En Proceso', 'Listo', 'Entregado', 'Cancelado'];
                $estado_texto = $estados[$row['estado']] ?? 'Desconocido';
                
                $actividades[] = [
                    'tipo' => 'pedido',
                    'icono' => 'bi-cart',
                    'color' => getColorByEstado($row['estado']),
                    'descripcion' => "Pedido #{$row['id']} - {$cliente_nombre}",
                    'detalle' => "Estado: {$estado_texto} - \${$row['total']}",
                    'fecha' => $row['fechacreate']
                ];
            }
        }
        
        // Productos agregados recientemente (últimos 5)
        $sql = "
            SELECT nombre, precio, fecha_creacion
            FROM productos
            WHERE esta_activo = 1
            ORDER BY fecha_creacion DESC
            LIMIT 5
        ";
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $actividades[] = [
                    'tipo' => 'producto',
                    'icono' => 'bi-box',
                    'color' => 'text-success',
                    'descripcion' => "Nuevo producto: {$row['nombre']}",
                    'detalle' => "Precio: \${$row['precio']}",
                    'fecha' => $row['fecha_creacion']
                ];
            }
        }
        
        // Ordenar por fecha descendente
        usort($actividades, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        // Retornar solo los 15 más recientes
        return array_slice($actividades, 0, 15);
        
    } catch (Exception $e) {
        error_log("Error en obtener_actividad_reciente: " . $e->getMessage());
        throw new Exception("Error obteniendo actividad reciente");
    }
}

/**
 * Obtener color por estado del pedido
 */
function getColorByEstado($estado) {
    switch ($estado) {
        case 0: return 'text-warning';  // Pendiente
        case 1: return 'text-info';     // En Proceso
        case 2: return 'text-primary';  // Listo
        case 3: return 'text-success';  // Entregado
        case 4: return 'text-danger';   // Cancelado
        default: return 'text-muted';
    }
}

// Manejar solicitudes
try {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $tipo = $_GET['tipo'] ?? 'completo';
        
        switch ($tipo) {
            case 'general':
                echo json_encode([
                    'success' => true,
                    'data' => obtener_estadisticas_generales()
                ]);
                break;
                
            case 'ventas_diarias':
                echo json_encode([
                    'success' => true,
                    'data' => obtener_ventas_diarias()
                ]);
                break;
                
            case 'actividad':
                echo json_encode([
                    'success' => true,
                    'data' => obtener_actividad_reciente()
                ]);
                break;
                
            case 'completo':
                echo json_encode([
                    'success' => true,
                    'estadisticas' => obtener_estadisticas_generales(),
                    'ventas_diarias' => obtener_ventas_diarias(),
                    'actividad_reciente' => obtener_actividad_reciente()
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Tipo de consulta no válido'
                ]);
                break;
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>