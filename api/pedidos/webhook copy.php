<?php
// api/pedidos/webhook.php
// Webhook de MercadoPago: registra el pago en la tabla `pagos` y actualiza el pedido.
// Optimizado según skill mercadopago-integration v2
//
// Seguridad implementada:
//   - Verificación de firma HMAC-SHA256 (si MP_WEBHOOK_SECRET está configurado)
//   - Protección anti-replay (timestamp máximo de 5 minutos)
//   - Verificación de monto (cross-check transaction_amount vs total en BD)
//   - Actualización atómica del estado (previene race conditions)
//   - Validación de formato de payment_id (solo numérico, 1-20 dígitos)
//
// MP envía un POST con:
//   { "type": "payment", "data": { "id": "12345678" } }

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// GET endpoint para verificación de disponibilidad (MP envía pings)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mercadopago.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../../includes/phpmailer/Exception.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ── Logger ─────────────────────────────────────────────────────────────────
function wlog(string $msg): void {
    $logDir  = __DIR__ . '/../../log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    $archivo = $logDir . '/wh_' . date('ymd') . '.log';
    $linea   = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
    error_log($msg);
}

// ── Verificación de firma HMAC-SHA256 ──────────────────────────────────────
// Basado en la documentación oficial de MercadoPago y la skill mercadopago-integration.
// Si MP_WEBHOOK_SECRET está configurado, verifica la firma. Si no, logea advertencia.
function verificarFirmaWebhook(string $body): bool {
    $secret = MP_WEBHOOK_SECRET;
    
    // Si no hay secret configurado, advertir pero permitir (para migración gradual)
    if (empty($secret)) {
        wlog("[WEBHOOK] ⚠ ADVERTENCIA: MP_WEBHOOK_SECRET no configurado. Verificación de firma DESACTIVADA.");
        wlog("[WEBHOOK] ⚠ Configurar en config/mercadopago.php para activar protección HMAC.");
        return true; // Permitir temporalmente — cambiar a false cuando el secret esté configurado
    }
    
    $xSignature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $xRequestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    
    if (empty($xSignature) || empty($xRequestId)) {
        wlog("[WEBHOOK] FIRMA: Headers x-signature o x-request-id ausentes");
        return false;
    }
    
    // Parsear ts y v1 del header x-signature
    $parts = [];
    foreach (explode(',', $xSignature) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) === 2) {
            $parts[$kv[0]] = $kv[1];
        }
    }
    
    $ts   = $parts['ts'] ?? '';
    $hash = $parts['v1'] ?? '';
    
    if (empty($ts) || empty($hash)) {
        wlog("[WEBHOOK] FIRMA: Faltan campos ts o v1 en x-signature");
        return false;
    }
    
    // Protección anti-replay: rechazar webhooks con timestamp > 5 minutos
    $webhookAge = time() - (int)$ts;
    if ($webhookAge < 0 || $webhookAge > MP_MAX_WEBHOOK_AGE_SECONDS) {
        wlog("[WEBHOOK] FIRMA: Webhook rechazado por antigüedad ({$webhookAge}s > " . MP_MAX_WEBHOOK_AGE_SECONDS . "s)");
        return false;
    }
    
    // Obtener data.id del body
    $parsed = json_decode($body, true);
    $dataId = $parsed['data']['id'] ?? '';
    
    // Construir template según docs de MercadoPago
    $template = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
    $expected = hash_hmac('sha256', $template, $secret);
    
    // Comparación timing-safe para prevenir timing attacks
    if (!hash_equals($expected, $hash)) {
        wlog("[WEBHOOK] FIRMA: Hash no coincide. Posible falsificación.");
        return false;
    }
    
    wlog("[WEBHOOK] FIRMA: ✓ Verificación HMAC exitosa (edad: {$webhookAge}s)");
    return true;
}

// ── Envío de email: pago acreditado ───────────────────────────────────────
function enviarCorreoPagoAcreditado(
    string $emailDestino,
    string $nombre,
    string $apellido,
    int    $idped,
    array  $items,
    float  $subtotal,
    float  $costoEnvio,
    float  $total
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
        $mail->Subject = "¡Tu pago fue acreditado! Pedido #{$idped} - Stadium";
        $mail->Body    = htmlPagoAcreditado($nombre, $idped, $items, $subtotal, $costoEnvio, $total);
        $mail->AltBody = "Hola {$nombre}, tu pago del pedido #{$idped} fue acreditado correctamente.\n"
                       . "Total abonado: $" . number_format($total, 2, ',', '.') . "\n"
                       . "Estamos preparando tu pedido. ¡Gracias por tu compra!";

        $mail->send();
        wlog("[WEBHOOK] ✓ Email de pago acreditado enviado a {$emailDestino} para pedido #{$idped}");
    } catch (PHPMailerException $e) {
        wlog("[WEBHOOK] ✗ Error enviando email de pago acreditado para pedido #{$idped}: " . $e->getMessage());
    }
}

// ── Template HTML: pago acreditado ────────────────────────────────────────
function htmlPagoAcreditado(
    string $nombre,
    int    $idped,
    array  $items,
    float  $subtotal,
    float  $costoEnvio,
    float  $total
): string {
    $nombreHtml = htmlspecialchars($nombre ?: 'Cliente');
    $fecha      = date('d/m/Y H:i');

    // Filas de productos
    $filasHtml = '';
    foreach ($items as $item) {
        $tituloEsc = htmlspecialchars($item['titulo'] ?? '');
        $cantidad  = (int)($item['cantidad'] ?? 1);
        $precio    = number_format((float)($item['precio'] ?? 0), 2, ',', '.');
        $itemTotal = number_format((float)($item['total']  ?? 0), 2, ',', '.');
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
        $costoFmt  = number_format($costoEnvio, 2, ',', '.');
        $envioFila = "
            <tr>
                <td colspan='3' style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>Envío:</td>
                <td style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>\${$costoFmt}</td>
            </tr>";
    }

    $subtotalFmt = number_format($subtotal, 2, ',', '.');
    $totalFmt    = number_format($total,    2, ',', '.');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pago acreditado - Pedido #{$idped}</title>
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
                  <td><span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:2px;">STADIUM</span></td>
                  <td style="text-align:right;"><span style="color:#aab4cc;font-size:13px;">Pedido #{$idped}</span></td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Banner pago acreditado -->
          <tr>
            <td style="background:#00c853;padding:18px 32px;text-align:center;">
              <span style="color:#ffffff;font-size:20px;font-weight:bold;">✓ ¡Pago acreditado exitosamente!</span>
            </td>
          </tr>

          <!-- Saludo -->
          <tr>
            <td style="padding:28px 32px 8px;">
              <p style="margin:0 0 6px;font-size:18px;font-weight:bold;color:#1a1a2e;">¡Hola, {$nombreHtml}!</p>
              <p style="margin:0;font-size:14px;color:#666;">
                Confirmamos que recibimos tu pago el <strong>{$fecha}</strong>.<br>
                Estamos preparando tu pedido y te avisaremos cuando sea despachado.
              </p>
            </td>
          </tr>

          <!-- Tabla de productos -->
          <tr>
            <td style="padding:16px 32px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;overflow:hidden;">
                <thead>
                  <tr style="background:#f8f9fa;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;">Producto</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;">Cant.</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;">Precio</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {$filasHtml}
                  {$envioFila}
                  <tr style="background:#f8f9fa;">
                    <td colspan="3" style="padding:12px;text-align:right;font-size:15px;font-weight:bold;color:#1a1a2e;">Total abonado:</td>
                    <td style="padding:12px;text-align:right;font-size:16px;font-weight:bold;color:#00c853;">\${$totalFmt}</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Mensaje final -->
          <tr>
            <td style="padding:20px 32px 32px;">
              <p style="margin:0;font-size:14px;color:#555;line-height:1.6;">
                Si tenés alguna consulta sobre tu pedido, respondé este email y te ayudamos.<br>
                <strong>¡Gracias por comprar en Stadium!</strong>
              </p>
            </td>
          </tr>

          <!-- Pie -->
          <tr>
            <td style="background:#f8f9fa;padding:16px 32px;border-top:1px solid #eee;text-align:center;">
              <span style="font-size:12px;color:#aaa;">© Stadium — Este email fue enviado automáticamente, no respondas directamente.</span>
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

// ── Leer cuerpo de la notificación ─────────────────────────────────────────
$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

// ── Verificar firma HMAC ───────────────────────────────────────────────────
if (!verificarFirmaWebhook($body)) {
    wlog("[WEBHOOK] ✗ Notificación rechazada: firma inválida");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Firma inválida']);
    exit;
}

// MP también puede enviar parámetros GET en la URL (para simulaciones o retornos)
$type      = $payload['type']      ?? ($_GET['type']      ?? null);
$paymentId = $payload['data']['id'] ?? ($_GET['data_id']  ?? ($_GET['id'] ?? null));

// ── Log de la notificación recibida ────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
wlog("[WEBHOOK] ── Nueva notificación ─────────────────────────────");
wlog("[WEBHOOK] IP: $ip  type=$type  payment_id=$paymentId");
wlog("[WEBHOOK] body=" . mb_substr($body, 0, 2000)); // Limitar log del body

// Solo procesamos notificaciones de pago
if ($type !== 'payment' || !$paymentId) {
    wlog("[WEBHOOK] Ignorada: type='$type' no es 'payment' o falta payment_id");
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Notificación ignorada (no es pago)']);
    exit;
}

// ── Validar formato de payment_id (solo numérico, 1-20 dígitos) ────────────
$paymentIdStr = (string)$paymentId;
if (!preg_match('/^\d{1,20}$/', $paymentIdStr)) {
    wlog("[WEBHOOK] ERROR: payment_id con formato inválido: '{$paymentIdStr}'");
    http_response_code(200); // No reintentar
    echo json_encode(['success' => true, 'message' => 'payment_id inválido']);
    exit;
}

// ── Consultar MP para obtener detalles del pago ────────────────────────────
function obtenerPagoMP(string $paymentId): ?array
{
    // Determinar token según el modo del pedido (se intenta con ambos como fallback)
    $tokens = [MP_TOKEN_SANDBOX, MP_TOKEN_PRODUCTION];

    foreach ($tokens as $tkn) {
        $ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$tkn}"],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($resp, true);
            $data['_mp_token_used'] = ($tkn === MP_TOKEN_PRODUCTION) ? 'production' : 'sandbox';
            return $data;
        }
    }
    return null;
}

$mpPago = obtenerPagoMP($paymentIdStr);

if (!$mpPago) {
    wlog("[WEBHOOK] ERROR: No se pudo obtener el pago $paymentIdStr de MP (ambos tokens fallaron)");
    // Retornar 200 para no reintentar indefinidamente (según recomendación de la skill)
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar el pago en MercadoPago']);
    exit;
}

// Campos del pago MP
$mpStatus       = $mpPago['status']        ?? 'unknown';
$mpStatusDetail = $mpPago['status_detail'] ?? null;
$mpPaymentType  = $mpPago['payment_type_id'] ?? null;
$mpExtRef       = $mpPago['external_reference'] ?? null;  // = idped guardado en crear.php
$mpMonto        = isset($mpPago['transaction_amount']) ? (float)$mpPago['transaction_amount'] : 0.0;
$datosJson      = json_encode($mpPago, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

wlog("[WEBHOOK] Pago obtenido de MP: status=$mpStatus  status_detail=$mpStatusDetail  ext_ref=$mpExtRef  monto=$mpMonto  tipo=$mpPaymentType");

// ── Buscar pedido por external_reference ──────────────────────────────────
if (!$mpExtRef) {
    wlog("[WEBHOOK] El pago de MP no tiene external_reference");
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Sin external_reference']);
    exit;
}

try {
    $conn = get_database_connection();

    // Obtener pedido + token + total + datos del cliente
    $stmtPed = $conn->prepare("
        SELECT p.id, p.token, p.estado, p.total, p.subtotal, p.envio,
               c.email  AS cli_email,
               c.nombre AS cli_nombre,
               c.apellido AS cli_apellido
        FROM pedidos p
        JOIN clientes c ON c.id = p.idcli
        WHERE p.id = ? LIMIT 1
    ");
    $stmtPed->bind_param("i", $mpExtRef);
    $stmtPed->execute();
    $pedido = $stmtPed->get_result()->fetch_assoc();
    $stmtPed->close();

    if (!$pedido) {
        wlog("[WEBHOOK] ERROR: Pedido {$mpExtRef} no encontrado en BD");
        http_response_code(200); // No reintentar
        echo json_encode(['success' => false, 'message' => "Pedido {$mpExtRef} no encontrado"]);
        exit;
    }

    $idped        = (int)$pedido['id'];
    $tokenPedido  = $pedido['token'];
    $totalPedido  = (float)$pedido['total'];
    $estadoActual = (int)$pedido['estado'];
    $cliEmail     = $pedido['cli_email']    ?? '';
    $cliNombre    = $pedido['cli_nombre']   ?? '';
    $cliApellido  = $pedido['cli_apellido'] ?? '';
    $subtotalPed  = (float)($pedido['subtotal'] ?? 0);
    $envioPed     = (float)($pedido['envio']    ?? 0);
    $estadoNombres = [0=>'pendiente',1=>'confirmado',2=>'preparando',3=>'enviado',4=>'entregado',5=>'cancelado'];
    $estadoNombreActual = $estadoNombres[$estadoActual] ?? 'desconocido';
    wlog("[WEBHOOK] Pedido encontrado: id=$idped  estado=$estadoActual ($estadoNombreActual)  total=$totalPedido");

    // ── Verificar que el token coincide si viene por GET/querystring ───────
    if (!empty($_GET['token']) && $_GET['token'] !== $tokenPedido) {
        wlog("[WEBHOOK] ERROR 403: Token GET inválido para pedido $idped");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }

    // ── Verificación de monto (cross-check) ───────────────────────────────
    // Rechazar si el monto pagado no coincide con el total del pedido
    if ($totalPedido > 0 && abs($mpMonto - $totalPedido) > 0.01) {
        wlog("[WEBHOOK] ✗ ALERTA MONTO: Pedido $idped esperaba \$$totalPedido pero MP reporta \$$mpMonto");
        wlog("[WEBHOOK] Payment ID: $paymentIdStr — Posible manipulación de monto");
        // Guardar igualmente en pagos para auditoría, pero NO confirmar el pedido
        $stmtAlert = $conn->prepare("
            INSERT INTO pagos
                (idped, token, monto, estado, mp_payment_id, mp_status,
                 mp_status_detail, mp_payment_type, mp_external_ref, datos_json)
            VALUES (?, ?, ?, 'amount_mismatch', ?, 'amount_mismatch', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                estado           = 'amount_mismatch',
                mp_status        = 'amount_mismatch',
                datos_json       = VALUES(datos_json)
        ");
        $stmtAlert->bind_param(
            "isdsssss",
            $idped, $tokenPedido, $mpMonto,
            $paymentIdStr, $mpStatusDetail,
            $mpPaymentType, $mpExtRef, $datosJson
        );
        $stmtAlert->execute();
        $stmtAlert->close();
        
        $conn->close();
        http_response_code(200); // Responder 200 para que MP no reintente
        echo json_encode(['success' => false, 'message' => 'Error de verificación de monto']);
        exit;
    }

    // ── Insertar o actualizar en tabla pagos ───────────────────────────────
    // UPSERT: si ya existe el mp_payment_id, actualizamos estado y datos (idempotente)
    $stmtIns = $conn->prepare("
        INSERT INTO pagos
            (idped, token, monto, estado, mp_payment_id, mp_status,
             mp_status_detail, mp_payment_type, mp_external_ref, datos_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            estado           = VALUES(estado),
            mp_status        = VALUES(mp_status),
            mp_status_detail = VALUES(mp_status_detail),
            datos_json       = VALUES(datos_json)
    ");
    $stmtIns->bind_param(
        "isdsssssss",
        $idped, $tokenPedido, $mpMonto, $mpStatus,
        $paymentIdStr, $mpStatus, $mpStatusDetail,
        $mpPaymentType, $mpExtRef, $datosJson
    );
    $stmtIns->execute();
    $pagoInsertId = $conn->insert_id;
    $stmtIns->close();
    wlog("[WEBHOOK] Pago guardado en tabla `pagos` (id=$pagoInsertId  mp_payment_id=$paymentIdStr)");

    // ── Actualizar estado del pedido (atómico: solo si está en estado pendiente) ──
    if ($mpStatus === 'approved') {
        $nuevoEstado = 1; // 1: confirmado
        wlog("[WEBHOOK] UPDATE pedido: intentando cambiar estado=$estadoActual ($estadoNombreActual) → 1 (confirmado)  id=$idped  mp_payment_id=$paymentIdStr");

        // Actualización atómica: previene race conditions con webhooks duplicados
        $sqlUpd = "UPDATE pedidos SET estado = ?, fechapago = NOW(), mercadopago_payment_id = ?, mercadopago_status = ? WHERE id = ? AND estado < 1";
        $stmtUpd = $conn->prepare($sqlUpd);
        if (!$stmtUpd) {
            wlog("[WEBHOOK] ✗ ERROR prepare UPDATE: " . $conn->error . " (errno=" . $conn->errno . ")");
        } else {
            $mpStatusForDb = 'approved';
            $bindOk = $stmtUpd->bind_param("issi", $nuevoEstado, $paymentIdStr, $mpStatusForDb, $idped);
            wlog("[WEBHOOK] UPDATE bind_param: " . ($bindOk ? 'OK' : 'FALLÓ — ' . $stmtUpd->error));
            wlog("[WEBHOOK] UPDATE params: nuevoEstado=$nuevoEstado  mp_payment_id=$paymentIdStr  mp_status=$mpStatusForDb  idped=$idped");

            $execOk = $stmtUpd->execute();
            if (!$execOk) {
                wlog("[WEBHOOK] ✗ ERROR execute UPDATE: " . $stmtUpd->error . " (errno=" . $stmtUpd->errno . ")");
            } else {
                $filasMod = $stmtUpd->affected_rows;
                wlog("[WEBHOOK] UPDATE execute OK — affected_rows=$filasMod");
            }
            $stmtUpd->close();

            // Verificar estado real en BD después del UPDATE
            $stmtCheck = $conn->prepare("SELECT estado, fechapago, mercadopago_payment_id FROM pedidos WHERE id = ? LIMIT 1");
            $stmtCheck->bind_param("i", $idped);
            $stmtCheck->execute();
            $pedidoPost = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();
            wlog("[WEBHOOK] BD post-UPDATE: estado={$pedidoPost['estado']}  fechapago={$pedidoPost['fechapago']}  mp_payment_id={$pedidoPost['mercadopago_payment_id']}");

            if (isset($filasMod) && $filasMod > 0) {
                wlog("[WEBHOOK] ✓ ESTADO CAMBIADO: Pedido $idped  $estadoActual ($estadoNombreActual) → 1 (confirmado)  fechapago=NOW()");

                // Obtener items del pedido para incluir en el email
                $itemsMail = [];
                $stmtItems = $conn->prepare("
                    SELECT detalle AS titulo, cantidad, precio, total
                    FROM pedidos_det
                    WHERE idped = ?
                    ORDER BY id
                ");
                $stmtItems->bind_param("i", $idped);
                $stmtItems->execute();
                $resItems = $stmtItems->get_result();
                while ($row = $resItems->fetch_assoc()) {
                    $itemsMail[] = $row;
                }
                $stmtItems->close();

                // Enviar email de pago acreditado al cliente
                if (!empty($cliEmail)) {
                    enviarCorreoPagoAcreditado(
                        $cliEmail, $cliNombre, $cliApellido,
                        $idped, $itemsMail, $subtotalPed, $envioPed, $totalPedido
                    );
                } else {
                    wlog("[WEBHOOK] ⚠ Email de cliente vacío, no se envía notificación para pedido $idped");
                }
            } else {
                wlog("[WEBHOOK] ✗ SIN CAMBIO: Pedido $idped estado en BD={$pedidoPost['estado']} — condición 'estado < 1' no se cumplió o fallo en execute");
            }
        }
    } elseif (in_array($mpStatus, ['rejected', 'cancelled', 'refunded'], true)) {
        // Registrar estados de rechazo/cancelación en la tabla pedidos
        $stmtUpd = $conn->prepare("
            UPDATE pedidos
            SET mercadopago_payment_id = ?, mercadopago_status = ?
            WHERE id = ?
        ");
        $stmtUpd->bind_param("ssi", $paymentIdStr, $mpStatus, $idped);
        $stmtUpd->execute();
        $stmtUpd->close();
        wlog("[WEBHOOK] Estado MP '$mpStatus': pedido $idped — registrado en BD sin confirmar");
    } else {
        wlog("[WEBHOOK] Estado MP '$mpStatus': pedido $idped NO se confirma aún (in_process/pending)");
    }

    $conn->close();

    wlog("[WEBHOOK] ✓ Finalizado OK — pedido=$idped  mp_status=$mpStatus  monto=$mpMonto  cli_email=$cliEmail");
    wlog("[WEBHOOK] ────────────────────────────────────────────────");

    // Siempre retornar 200 para evitar reintentos infinitos de MP
    http_response_code(200);
    echo json_encode([
        'success'    => true,
        'message'    => 'Pago registrado correctamente',
        'idped'      => $idped,
        'mp_status'  => $mpStatus
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    wlog("[WEBHOOK] EXCEPCIÓN: " . $e->getMessage());
    wlog("[WEBHOOK] Traza: " . $e->getTraceAsString());
    wlog("[WEBHOOK] ────────────────────────────────────────────────");
    if (isset($conn)) $conn->close();
    // Retornar 200 incluso en error para evitar reintentos infinitos de MP
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al registrar el pago'
    ], JSON_UNESCAPED_UNICODE);
}
