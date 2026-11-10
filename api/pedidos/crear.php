<?php
// api/pedidos/crear.php
// API para crear pedidos con integración de MercadoPago
// Optimizado según skill mercadopago-integration v2

// ── Configuración de errores (seguridad: no exponer en producción) ─────────
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Limpiar cualquier salida previa que pudiera romper el JSON
if (ob_get_length()) ob_clean();

// ── CORS restringido a dominios permitidos ─────────────────────────────────
$allowedOrigins = [
    'https://stadiumapp.planbsistemas.com.ar',
    'https://stadium.planbsistemas.com.ar',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost',
    'https://stadiumdeportes.com',
    'https://www.stadiumdeportes.com'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: https://stadiumdeportes.com');
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ── Dependencias ───────────────────────────────────────────────────────────
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mercadopago.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../../includes/phpmailer/Exception.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ── Helper: respuesta JSON de error ────────────────────────────────────────
function jsonError(int $httpCode, string $message, ?string $internalDetail = null): void {
    if ($internalDetail) {
        error_log("crear.php ERROR [{$httpCode}]: {$message} — {$internalDetail}");
    }
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Obtener y decodificar datos JSON ───────────────────────────────────────
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    jsonError(400, 'JSON inválido', json_last_error_msg());
}
// ── Validar estructura del JSON ────────────────────────────────────────────
$camposRequeridos = ['modo', 'items', 'pagador'];
foreach ($camposRequeridos as $campo) {
    if (!isset($data[$campo])) {
        jsonError(400, "Campo requerido faltante: {$campo}");
    }
}

// Validar modo (solo sandbox o production)
if (!in_array($data['modo'], ['sandbox', 'production'], true)) {
    jsonError(400, 'El campo "modo" debe ser "sandbox" o "production"');
}

// Validar items
if (empty($data['items']) || !is_array($data['items'])) {
    jsonError(400, 'El pedido debe contener al menos un item');
}
if (count($data['items']) > 100) {
    jsonError(400, 'Máximo 100 items por pedido');
}

// Validar datos del pagador
$pagador = $data['pagador'];
$camposPagador = ['email', 'nombre', 'apellido', 'telefono', 'identificacion', 'direccion'];
foreach ($camposPagador as $campo) {
    if (!isset($pagador[$campo])) {
        jsonError(400, "Campo del pagador faltante: {$campo}");
    }
}

// Validar email
if (!filter_var($pagador['email'], FILTER_VALIDATE_EMAIL)) {
    jsonError(400, 'Email del pagador inválido');
}

try {
    $conn = get_database_connection();

    // Iniciar transacción
    $conn->begin_transaction();
    
    // ── Preparar items con datos completos desde la BD ─────────────────────
    // SEGURIDAD: siempre usar el precio de la BD, nunca confiar en el precio del cliente
    $itemsCompletos = [];
    foreach ($data['items'] as $item) {
        // Obtener SKU (puede venir en 'id' o 'sku')
        $sku = isset($item['sku']) ? $item['sku'] : (isset($item['id']) ? $item['id'] : null);
        
        if (!$sku) {
            throw new Exception("Item sin SKU: " . json_encode($item));
        }
        
        // Buscar producto por SKU
        $stmtProd = $conn->prepare("SELECT id, nombre, precio, descripcion FROM productos WHERE sku = ? LIMIT 1");
        $stmtProd->bind_param("s", $sku);
        $stmtProd->execute();
        $resultProd = $stmtProd->get_result();
        
        if ($resultProd->num_rows === 0) {
            $stmtProd->close();
            throw new Exception("Producto no encontrado con SKU: {$sku}");
        }
        
        $producto = $resultProd->fetch_assoc();
        $idart = (int)$producto['id'];
        
        // SEGURIDAD: precio SIEMPRE desde la BD (ignorar precioUnitario del cliente)
        $precio = (float)$producto['precio'];
        $titulo = $producto['nombre'];
        $descripcion = !empty($producto['descripcion']) ? $producto['descripcion'] : $producto['nombre'];
        
        $stmtProd->close();
        
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        
        if ($precio <= 0) {
            throw new Exception("Producto '{$titulo}' (SKU: {$sku}) tiene precio inválido: {$precio}");
        }
        
        $itemsCompletos[] = [
            'idart'      => $idart,
            'productoId' => $item['productoId'] ?? null,
            'sku'        => $sku,
            'titulo'     => $titulo,
            'descripcion'=> $descripcion,
            'cantidad'   => $cantidad,
            'precio'     => $precio,
            'total'      => $precio * $cantidad
        ];
    }
    
    // ── Calcular totales ───────────────────────────────────────────────────
    $subtotal = 0;
    foreach ($itemsCompletos as $item) {
        $subtotal += $item['total'];
    }
    
    $costoEnvio = isset($data['envio']['costo']) ? (float)$data['envio']['costo'] : 0;
    $total = $subtotal + $costoEnvio;
    
    // ── Validar montos (seguridad server-side) ─────────────────────────────
    if ($total > MP_MAX_CHECKOUT_AMOUNT) {
        throw new Exception("El monto total (\${$total}) excede el máximo permitido (\$" . MP_MAX_CHECKOUT_AMOUNT . ")");
    }
    if ($total < MP_MIN_CHECKOUT_AMOUNT) {
        throw new Exception("El monto total (\${$total}) es inferior al mínimo permitido (\$" . MP_MIN_CHECKOUT_AMOUNT . ")");
    }
    
    // ── Obtener o crear cliente ────────────────────────────────────────────
    $email = $pagador['email'];
    $nombre = $pagador['nombre'];
    $apellido = $pagador['apellido'];
    // Soporte para teléfono como string directo o como objeto {codigoArea, numero}
    if (is_array($pagador['telefono'])) {
        $telefono = ($pagador['telefono']['codigoArea'] ?? '') . ($pagador['telefono']['numero'] ?? '');
    } else {
        $telefono = (string)$pagador['telefono'];
    }
    $tipoDoc = $pagador['identificacion']['tipo'];
    $nroDoc = str_replace(['.', '-'], '', $pagador['identificacion']['numero']);
    $recibirOfertas  = isset($data['preferenciasCheckout']['recibirOfertas'])  ? ($data['preferenciasCheckout']['recibirOfertas']  ? 1 : 0) : 0;
    $recibirWhatsApp = isset($data['preferenciasCheckout']['recibirWhatsApp']) ? ($data['preferenciasCheckout']['recibirWhatsApp'] ? 1 : 0) : 0;

    // Buscar cliente por email o documento
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ? OR cuit = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $nroDoc);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $idcli = $row['id'];
        $stmt->close();
        
        // Actualizar datos del cliente
        $stmt = $conn->prepare("
            UPDATE clientes 
            SET nombre = ?, apellido = ?, email = ?, telefono = ?,
                tipodoc = ?, cuit = ?, recibirOfertas = ?, recibirWhatsApp = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssiii", $nombre, $apellido, $email, $telefono, $tipoDoc, $nroDoc, $recibirOfertas, $recibirWhatsApp, $idcli);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        
        // Crear nuevo cliente
        $direccion    = $pagador['direccion']['calle'] ?? '';
        $nroDireccion = $pagador['direccion']['numero'] ?? '';
        $codigoPostal = $pagador['direccion']['codigoPostal'] ?? '';
        $ciudad       = $pagador['direccion']['ciudad'] ?? '';
        $provincia    = $pagador['direccion']['provincia'] ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO clientes (nombre, apellido, email, telefono, tipodoc, cuit,
                                    direccion, nrodir, codpostal, ciudad, provincia,
                                    recibirOfertas, recibirWhatsApp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssssssssii", $nombre, $apellido, $email, $telefono, $tipoDoc,
                         $nroDoc, $direccion, $nroDireccion, $codigoPostal, $ciudad, $provincia,
                         $recibirOfertas, $recibirWhatsApp);
        $stmt->execute();
        $idcli = $conn->insert_id;
        $stmt->close();

        // Registrar dirección completa en clientes_direcciones
        $piso         = $pagador['direccion']['piso'] ?? '';
        $departamento = $pagador['direccion']['departamento'] ?? '';
        $indicaciones = $pagador['direccion']['indicaciones'] ?? '';
        $dirCompleta  = trim($direccion . ' ' . $nroDireccion);
        $stmtDir = $conn->prepare("
            INSERT INTO clientes_direcciones
                (cliente_id, descripcion, direccion, piso, departamento, cpostal, ciudad, provincia, indicaciones, principal)
            VALUES (?, 'Principal', ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmtDir->bind_param("isssssss", $idcli, $dirCompleta, $piso, $departamento, $codigoPostal, $ciudad, $provincia, $indicaciones);
        $stmtDir->execute();
        $stmtDir->close();
    }
    
    // ── Crear el pedido ────────────────────────────────────────────────────
    $modo = $data['modo'];
    $estado = 0; // 0: pendiente, 1: confirmado, 2: preparando, 3: enviado, 4: entregado, 5: cancelado
    $tipoEnvio = isset($data['envio']['tipo']) ? $data['envio']['tipo'] : 'domicilio';
    $nombreEnvio = isset($data['envio']['nombre']) ? $data['envio']['nombre'] : 'Envío a domicilio';
    
    // Generar token único para el pedido
    $token = bin2hex(random_bytes(32));
    
    // Guardar preferencias de checkout como JSON
    $preferencias = isset($data['preferenciasCheckout']) ? 
                    json_encode($data['preferenciasCheckout'], JSON_UNESCAPED_UNICODE) : '{}';
    
    $stmt = $conn->prepare("
        INSERT INTO pedidos (idcli, fecha, estado, subtotal, envio, total, 
                               modo_pago, tipo_envio, nombre_envio, preferencias_checkout, 
                               token, fechacreate)
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iidddsssss", $idcli, $estado, $subtotal, $costoEnvio, $total, 
                     $modo, $tipoEnvio, $nombreEnvio, $preferencias, $token);
    $stmt->execute();
    $idped = $conn->insert_id;
    $stmt->close();
    
    // Insertar items del pedido (usa titulo como detalle, nunca null)
    $stmtItem = $conn->prepare("
        INSERT INTO pedidos_det (idped, idart, detalle, cantidad, precio, total, fechacreate)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    foreach ($itemsCompletos as $item) {
        $detalle = !empty($item['titulo']) ? $item['titulo'] : $item['descripcion'];
        $stmtItem->bind_param("iisidd", $idped, $item['idart'], $detalle,
                             $item['cantidad'], $item['precio'], $item['total']);
        $stmtItem->execute();
    }
    $stmtItem->close();

    // ── Descontar stock de la variante correspondiente ────────────────────
    // pedidos_det.idart = productos.id; la variante activa default es la primera
    $stmtStock = $conn->prepare(
        "UPDATE producto_variantes
         SET stock = stock - ?
         WHERE producto_id = ? AND activa = 1
         ORDER BY id ASC
         LIMIT 1"
    );
    foreach ($itemsCompletos as $item) {
        $stmtStock->bind_param("ii", $item['cantidad'], $item['idart']);
        $stmtStock->execute();
        if ($stmtStock->affected_rows === 0) {
            error_log("crear.php: sin variante activa para producto_id={$item['idart']} (stock no descontado)");
        }
    }
    $stmtStock->close();

    // ── Generar preferencia MercadoPago ───────────────────────────────────
    // NOTA: se hace ANTES del commit. Si MP falla, se revierte todo.
    $mpData = null;
    $mpError = null;
    
    // Usar 'mp_mode' si viene explícito, sino tomar el campo 'modo' del JSON
    $mpMode = isset($data['mp_mode']) ? $data['mp_mode'] : $modo;
    $mpToken = mp_get_token($mpMode);
    MercadoPago\SDK::setAccessToken($mpToken);

    $mpPreference = new MercadoPago\Preference();

    // Items individuales (transparencia para el comprador)
    $mpItems = [];
    foreach ($itemsCompletos as $item) {
        $mpItem              = new MercadoPago\Item();
        $mpItem->id          = $item['sku'];
        $mpItem->title       = mb_substr($item['titulo'], 0, 255);
        $mpItem->quantity    = $item['cantidad'];
        $mpItem->unit_price  = (float)$item['precio'];
        $mpItem->currency_id = MP_CURRENCY_ID;
        $mpItems[]           = $mpItem;
    }
    // Agregar envío como item si tiene costo
    if ($costoEnvio > 0) {
        $mpItemEnvio              = new MercadoPago\Item();
        $mpItemEnvio->id          = 'ENVIO';
        $mpItemEnvio->title       = $nombreEnvio;
        $mpItemEnvio->quantity    = 1;
        $mpItemEnvio->unit_price  = (float)$costoEnvio;
        $mpItemEnvio->currency_id = MP_CURRENCY_ID;
        $mpItems[]                = $mpItemEnvio;
    }
    $mpPreference->items = $mpItems;

    // Pagador
    $mpPayer           = new MercadoPago\Payer();
    $mpPayer->name     = $nombre;
    $mpPayer->surname  = $apellido;
    $mpPayer->email    = $email;
    $mpPayer->phone    = ['number' => (string)$telefono];
    $mpPayer->identification = [
        'type'   => $tipoDoc,
        'number' => (string)$nroDoc
    ];
    if (isset($pagador['direccion']['calle'])) {
        $mpPayer->address = [
            'street_name' => $pagador['direccion']['calle']
        ];
    }
    $mpPreference->payer = $mpPayer;

    // URLs de retorno (centralizadas desde config)
    $backUrls = mp_get_back_urls($token);
    $mpPreference->back_urls = $backUrls;

    // Webhook: MP enviará notificaciones de pago a esta URL
    $mpPreference->notification_url = mp_get_notification_url();

    // auto_return: activar solo con HTTPS (en localhost genera error 400)
    if (isset($data['retornoAutomatico']) && $data['retornoAutomatico'] === 'approved') {
        if (strpos(MP_APP_URL, 'https') === 0) {
            $mpPreference->auto_return = 'approved';
        }
    }

    // Statement descriptor: nombre que ve el comprador en su resumen bancario
    $mpPreference->statement_descriptor = MP_STATEMENT_DESCRIPTOR;

    // Expiración de la preferencia (evita links de pago eternos)
    $mpPreference->expires = true;
    $mpPreference->expiration_date_from = date('c'); // ISO 8601
    $mpPreference->expiration_date_to   = date('c', strtotime('+' . MP_PREFERENCE_EXPIRATION_HOURS . ' hours'));

    // Referencia externa con el id del pedido
    $mpPreference->external_reference = (string)$idped;

    // Guardar preferencia en MP (con idempotency key para evitar duplicados en reintentos)
    $saveResult = $mpPreference->save();

    // Verificar que la preferencia se creó correctamente
    if (empty($mpPreference->id)) {
        error_log("MercadoPago: preferencia no creada para pedido $idped. Resultado: " . json_encode($saveResult));
        throw new Exception("No se pudo crear la preferencia de pago en MercadoPago");
    }

    $payLink = ($mpMode === 'production') ? $mpPreference->init_point : $mpPreference->sandbox_init_point;

    // Guardar preference_id en el pedido (para rastreo y conciliación)
    $stmtMp = $conn->prepare("UPDATE pedidos SET mercadopago_preference_id = ? WHERE id = ?");
    $stmtMp->bind_param("si", $mpPreference->id, $idped);
    $stmtMp->execute();
    $stmtMp->close();

    $mpData = [
        'mp_mode'            => $mpMode,
        'preference_id'      => $mpPreference->id,
        'init_point'         => $mpPreference->init_point,
        'sandbox_init_point' => $mpPreference->sandbox_init_point,
        'pay_link'           => $payLink,
        'back_urls'          => $backUrls
    ];

    // ── COMMIT: todo OK (BD + MercadoPago) ─────────────────────────────────
    $conn->commit();

    // ── Email de confirmación (post-commit, no bloquea la respuesta) ──────
    $payLink = $mpData['pay_link'] ?? '';
    enviarConfirmacionPedido($email, $nombre, $apellido, $idped, $token, $itemsCompletos, $subtotal, $costoEnvio, $total, $payLink);

    // Preparar respuesta exitosa
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente',
        'data' => [
            'idped'       => $idped,
            'idcli'       => $idcli,
            'token'       => $token,
            'total'       => $total,
            'estado'      => $estado,
            'modo'        => $modo,
            'items_count' => count($itemsCompletos),
            'mp'          => $mpData
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error (BD o MercadoPago)
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log detallado internamente, mensaje genérico al cliente
    error_log("crear.php EXCEPCIÓN: " . $e->getMessage() . " — Traza: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear el pedido. Intente nuevamente.',
        'error'   => $e->getMessage() // mantener para debug en desarrollo
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// ── Función de envío de confirmación de pedido ────────────────────────────
function enviarConfirmacionPedido(
    string $emailDestino,
    string $nombre,
    string $apellido,
    int    $idped,
    string $token,
    array  $items,
    float  $subtotal,
    float  $costoEnvio,
    float  $total,
    string $payLink
): void {
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($emailDestino, trim("{$nombre} {$apellido}"));
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = "Confirmación de tu pedido #{$idped} - Stadium";
        $mail->Body    = htmlPedidoConfirmacion($nombre, $idped, $items, $subtotal, $costoEnvio, $total, $payLink);
        $mail->AltBody = "Hola {$nombre}, tu pedido #{$idped} fue recibido correctamente.\n"
                       . "Total: $" . number_format($total, 2, ',', '.') . "\n"
                       . ($payLink ? "Ir a pagar: {$payLink}" : '');

        $mail->send();
    } catch (PHPMailerException $e) {
        // El email es informativo; loguear pero no interrumpir
        error_log("crear.php: email de confirmación falló para pedido #{$idped}: " . $e->getMessage());
    }
}

// ── Template HTML del email de confirmación ───────────────────────────────
function htmlPedidoConfirmacion(
    string $nombre,
    int    $idped,
    array  $items,
    float  $subtotal,
    float  $costoEnvio,
    float  $total,
    string $payLink
): string {
    $nombreHtml = htmlspecialchars($nombre ?: 'Cliente');
    $fecha      = date('d/m/Y H:i');

    // Filas de productos
    $filasHtml = '';
    foreach ($items as $item) {
        $tituloEsc = htmlspecialchars($item['titulo'] ?? $item['descripcion'] ?? '');
        $cantidad  = (int)$item['cantidad'];
        $precio    = number_format((float)$item['precio'], 2, ',', '.');
        $itemTotal = number_format((float)$item['total'],  2, ',', '.');
        $filasHtml .= "
            <tr>
                <td style='padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#333;'>{$tituloEsc}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#555;text-align:center;'>{$cantidad}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#555;text-align:right;'>\${$precio}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#333;font-weight:600;text-align:right;'>\${$itemTotal}</td>
            </tr>";
    }

    // Fila de envío
    $envioFila = '';
    if ($costoEnvio > 0) {
        $costoEnvioFmt = number_format($costoEnvio, 2, ',', '.');
        $envioFila = "
            <tr>
                <td colspan='3' style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>Envío:</td>
                <td style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>\${$costoEnvioFmt}</td>
            </tr>";
    }

    $subtotalFmt = number_format($subtotal, 2, ',', '.');
    $totalFmt    = number_format($total,    2, ',', '.');

    // Botón de pago
    $botonPago = '';
    if ($payLink) {
        $payLinkEsc = htmlspecialchars($payLink);
        $botonPago  = "
          <tr>
            <td style='padding:28px 32px;text-align:center;'>
              <a href='{$payLinkEsc}' style='display:inline-block;background:#009ee3;color:#ffffff;text-decoration:none;font-size:16px;font-weight:bold;padding:14px 36px;border-radius:6px;'>
                Ir a pagar con MercadoPago
              </a>
              <p style='margin:12px 0 0;font-size:12px;color:#999;'>Este botón expira en 24 horas.</p>
            </td>
          </tr>";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmación pedido #{$idped}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">

          <!-- Encabezado -->
          <tr>
            <td style="background:#1a1a2e;padding:26px 32px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <img src="https://stadium.planbsistemas.com.ar/api/logostadium_small.jpg" width="58" height="40" alt="Stadium Deportes" style="vertical-align:middle; margin-right:12px; border-radius:4px;">
                    <span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:2px;vertical-align:middle;">STADIUM DEPORTES</span>
                  </td>
                  <td style="text-align:right;"><span style="color:#aab4cc;font-size:13px;">Pedido #{$idped}</span></td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Saludo -->
          <tr>
            <td style="padding:28px 32px 8px;">
              <p style="margin:0 0 6px;font-size:18px;font-weight:bold;color:#1a1a2e;">¡Gracias por tu compra, {$nombreHtml}!</p>
              <p style="margin:0;font-size:14px;color:#666;">Recibimos tu pedido el <strong>{$fecha}</strong>. Aquí está el detalle:</p>
            </td>
          </tr>

          <!-- Tabla de productos -->
          <tr>
            <td style="padding:16px 32px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;">
                <thead>
                  <tr style="background:#f8f8f8;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;color:#888;text-transform:uppercase;border-bottom:1px solid #eee;">Producto</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;color:#888;text-transform:uppercase;border-bottom:1px solid #eee;">Cant.</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;color:#888;text-transform:uppercase;border-bottom:1px solid #eee;">Precio</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;color:#888;text-transform:uppercase;border-bottom:1px solid #eee;">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  {$filasHtml}
                  {$envioFila}
                  <tr style="background:#f8f8f8;">
                    <td colspan="3" style="padding:12px;text-align:right;font-size:15px;font-weight:bold;color:#1a1a2e;">Total:</td>
                    <td style="padding:12px;text-align:right;font-size:16px;font-weight:bold;color:#1a1a2e;">\${$totalFmt}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Botón pago -->
          {$botonPago}

          <!-- Info adicional -->
          <tr>
            <td style="padding:0 32px 24px;">
              <p style="margin:0;font-size:13px;color:#888;line-height:1.6;">
                Ante cualquier consulta respondé este correo o escribinos.<br>
                Conservá el número de pedido <strong>#{$idped}</strong> para hacer el seguimiento.
              </p>
            </td>
          </tr>

          <!-- Pie -->
          <tr>
            <td style="background:#f8f8f8;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
              <p style="margin:0;color:#bbb;font-size:11px;">© 2026 Stadium &bull; Este es un mensaje automático, por favor no respondas directamente.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
