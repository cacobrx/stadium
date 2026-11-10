<?php
// api/presupuestos/crear.php
// Crea un presupuesto a partir de un JSON de contacto + proyecto + productos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/mailer.php';

function get_conn() {
    return get_database_connection();
}

try {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!$body) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido o vacío']);
        exit;
    }

    // --- Extraer secciones del JSON ---
    $contacto  = $body['contacto']  ?? [];
    $proyecto  = $body['proyecto']  ?? [];
    $productos = $body['productos'] ?? [];

    // --- Campos del cliente (idcli=0, datos personalizados) ---
    $cli_nombre   = trim($contacto['nombre']   ?? '');
    $cli_empresa  = trim($contacto['empresa']  ?? '');
    $cli_telefono = trim($contacto['telefono'] ?? '');
    $cli_email    = trim($contacto['email']    ?? '');

    // --- Campos del proyecto ---
    $tipoProyecto = trim($proyecto['tipoProyecto'] ?? '');
    $ubicacion    = trim($proyecto['ubicacion']    ?? '');
    $requerimientos = trim($proyecto['requerimientos'] ?? '');
    $plazo          = trim($proyecto['plazo']          ?? '');

    // --- Origen (tablas codtab='ORI'); por defecto 1 = Web ---
    $origen = intval($body['origen'] ?? $proyecto['origen'] ?? 1) ?: 1;

    // observaciones = requerimientos + plazo
    $observaciones = '';
    if ($requerimientos !== '') {
        $observaciones .= $requerimientos;
    }
    if ($plazo !== '') {
        $observaciones .= ($observaciones !== '' ? "\n" : '') . 'Plazo: ' . $plazo;
    }

    // --- Validaciones mínimas ---
    if (empty($cli_nombre)) {
        http_response_code(422);
        echo json_encode(['error' => 'El campo contacto.nombre es obligatorio']);
        exit;
    }
    if (empty($productos)) {
        http_response_code(422);
        echo json_encode(['error' => 'Se requiere al menos un producto']);
        exit;
    }

    // --- Calcular total (precio=0 porque viene del formulario web sin precios) ---
    $total = 0.00;

    // --- Generar número de presupuesto automático ---
    $conn = get_conn();
    $anio = date('Y');
    $stmt_nro = $conn->prepare(
        "SELECT nro_presupuesto FROM presupuestos 
         WHERE nro_presupuesto LIKE CONCAT('PRES-', ?, '-%') 
         ORDER BY nro_presupuesto DESC LIMIT 1"
    );
    $stmt_nro->bind_param("s", $anio);
    $stmt_nro->execute();
    $stmt_nro->bind_result($ultimo_nro);
    $ultimo = 0;
    if ($stmt_nro->fetch() && preg_match('/PRES-\d{4}-(\d{3})/', $ultimo_nro, $m)) {
        $ultimo = intval($m[1]);
    }
    $stmt_nro->close();
    $nro_presupuesto = 'PRES-' . $anio . '-' . str_pad($ultimo + 1, 3, '0', STR_PAD_LEFT);

    // --- Insertar presupuesto ---
    $fecha            = date('Y-m-d');
    $validez          = 30;
    $fecha_vencimiento = date('Y-m-d', strtotime("+{$validez} days"));
    $estado           = 0; // borrador
    $idcli            = 0;

    if (method_exists($conn, 'begin_transaction')) {
        $conn->begin_transaction();
    } else {
        $conn->autocommit(false);
    }

    $stmt = $conn->prepare(
        "INSERT INTO presupuestos 
            (fecha, idcli, cli_nombre, cli_empresa, cli_telefono, cli_email, tipoProyecto, origen,
             observaciones, ubicacion, nro_presupuesto, estado, total, validez, fecha_vencimiento)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sisssssisssidis",
        $fecha,
        $idcli,
        $cli_nombre,
        $cli_empresa,
        $cli_telefono,
        $cli_email,
        $tipoProyecto,
        $origen,
        $observaciones,
        $ubicacion,
        $nro_presupuesto,
        $estado,
        $total,
        $validez,
        $fecha_vencimiento
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar presupuesto: " . $stmt->error);
    }

    $presupuesto_id = $conn->insert_id;
    $stmt->close();

    // --- Insertar detalles (equipamiento) ---
    $stmt_det = $conn->prepare(
        "INSERT INTO presupuestos_det (idpres, cantidad, idart, precio, total, detalle, iva, tipo_producto)
         VALUES (?, ?, ?, 0, 0, ?, 21, 'equipamiento')"
    );

    foreach ($productos as $prod) {
        $cantidad = intval($prod['cantidad'] ?? 1);
        $idart    = intval($prod['id']       ?? 0);
        $detalle  = trim($prod['nombre']     ?? '');

        if ($idart <= 0) {
            continue;
        }

        $stmt_det->bind_param("iiis", $presupuesto_id, $cantidad, $idart, $detalle);
        if (!$stmt_det->execute()) {
            throw new Exception("Error al insertar detalle: " . $stmt_det->error);
        }
    }
    $stmt_det->close();

    // --- Commit ---
    if (method_exists($conn, 'commit')) {
        $conn->commit();
    } else {
        $conn->autocommit(true);
    }

    // --- Aviso por correo (no afecta la creación si falla) ---
    $mailEnviado = false;
    try {
        $origen_desc = '';
        $stmt_ori = $conn->prepare("SELECT descripcion FROM tablas WHERE codtab = 'ORI' AND valor = ? LIMIT 1");
        $stmt_ori->bind_param("i", $origen);
        $stmt_ori->execute();
        $stmt_ori->bind_result($origen_desc);
        $stmt_ori->fetch();
        $stmt_ori->close();

        $cfgMail = obtenerConfigMail($conn);
        $destino = $cfgMail['presupuestos_destino'] ?: 'info@stadiumdeportes.com';
        $html = htmlPresupuestoWeb([
            'nro_presupuesto' => $nro_presupuesto,
            'fecha'           => $fecha,
            'origen'          => $origen_desc ?: (string) $origen,
            'nombre'          => $cli_nombre,
            'empresa'         => $cli_empresa,
            'telefono'        => $cli_telefono,
            'email'           => $cli_email,
            'tipoProyecto'    => $tipoProyecto,
            'ubicacion'       => $ubicacion,
            'requerimientos'  => $requerimientos,
            'plazo'           => $plazo,
            'productos'       => $productos,
        ]);
        $asunto = "Nuevo presupuesto {$nro_presupuesto} - {$cli_nombre}";
        $r = enviarCorreo($destino, $asunto, $html, $cli_email ?: null, $cli_nombre, $cfgMail);
        $mailEnviado = $r['ok'];
    } catch (Throwable $em) {
        error_log("api/presupuestos/crear.php - error enviando correo: " . $em->getMessage());
    }

    $conn->close();

    http_response_code(201);
    echo json_encode([
        'success'         => true,
        'id'              => $presupuesto_id,
        'nro_presupuesto' => $nro_presupuesto,
        'mail_enviado'    => $mailEnviado
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn) {
        if (method_exists($conn, 'rollback')) {
            $conn->rollback();
        } else {
            try { $conn->rollback(); } catch (Exception $ex) {}
            $conn->autocommit(true);
        }
        $conn->close();
    }
    error_log("Error en api/presupuestos/crear.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno al crear el presupuesto']);
}

// ── HTML del aviso de nuevo presupuesto ─────────────────────────────────────
function htmlPresupuestoWeb(array $d): string {
    $e = fn($v) => nl2br(htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
    $fila = function (string $label, $valor) use ($e) {
        if ($valor === null || $valor === '') {
            return '';
        }
        return '<tr><td style="padding:6px 10px;background:#f5f5f5;font-weight:bold;width:170px;vertical-align:top;">'
             . $e($label) . '</td><td style="padding:6px 10px;">' . $e($valor) . '</td></tr>';
    };

    $filasProductos = '';
    foreach ($d['productos'] as $p) {
        $filasProductos .= '<tr>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;">' . $e($p['nombre'] ?? ('Artículo #' . ($p['id'] ?? ''))) . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:center;">' . $e(intval($p['cantidad'] ?? 1)) . '</td>'
            . '</tr>';
    }

    $fecha = $d['fecha'] ? date('d/m/Y', strtotime($d['fecha'])) : '';

    return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;max-width:650px;">'
        . '<h2 style="margin:0 0 12px;">Nuevo pedido de presupuesto</h2>'
        . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;margin-bottom:18px;border:1px solid #ddd;">'
        . $fila('Nro. presupuesto', $d['nro_presupuesto'])
        . $fila('Fecha', $fecha)
        . $fila('Origen', $d['origen'])
        . '</table>'
        . '<h3 style="margin:0 0 8px;">Contacto</h3>'
        . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;margin-bottom:18px;border:1px solid #ddd;">'
        . $fila('Nombre', $d['nombre'])
        . $fila('Empresa', $d['empresa'])
        . $fila('Teléfono', $d['telefono'])
        . $fila('Email', $d['email'])
        . '</table>'
        . '<h3 style="margin:0 0 8px;">Proyecto</h3>'
        . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;margin-bottom:18px;border:1px solid #ddd;">'
        . $fila('Tipo de proyecto', $d['tipoProyecto'])
        . $fila('Ubicación', $d['ubicacion'])
        . $fila('Plazo', $d['plazo'])
        . $fila('Requerimientos', $d['requerimientos'])
        . '</table>'
        . '<h3 style="margin:0 0 8px;">Productos</h3>'
        . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;border:1px solid #ddd;">'
        . '<tr><th style="padding:6px 10px;background:#222;color:#fff;text-align:left;">Producto</th>'
        . '<th style="padding:6px 10px;background:#222;color:#fff;width:90px;">Cantidad</th></tr>'
        . $filasProductos
        . '</table>'
        . '</div>';
}
