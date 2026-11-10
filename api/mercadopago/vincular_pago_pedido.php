<?php
/*
 * Archivo: api/mercadopago/vincular_pago_pedido.php
 * Vincula / registra un pago de Mercado Pago a un pedido determinado que está pendiente.
 * Emite la factura automática AFIP y envía el correo electrónico de pago acreditado al cliente.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mercadopago.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../../includes/phpmailer/Exception.php';
require_once __DIR__ . '/../../includes/afip.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Verificar autenticación de usuario admin
requireAuth();

$conn = get_database_connection();

// ── Logger de la vinculación ─────────────────────────────────────────────
function vlog(string $msg): void {
    $logDir = __DIR__ . '/../../log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    $archivo = $logDir . '/vincular_pago_' . date('ymd') . '.log';
    $linea   = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
    error_log($msg);
}

// ── Envío de email: pago acreditado ───────────────────────────────────────
function enviarCorreoPagoAcreditadoVincular(
    string  $emailDestino,
    string  $nombre,
    string  $apellido,
    int     $idped,
    array   $items,
    float   $subtotal,
    float   $costoEnvio,
    float   $total,
    ?string $pdfFactura = null,
    int     $idFactura  = 0
): bool {
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
        $mail->Body    = htmlPagoAcreditadoVincular($nombre, $idped, $items, $subtotal, $costoEnvio, $total);
        $mail->AltBody = "Hola {$nombre}, tu pago del pedido #{$idped} fue acreditado correctamente.\n"
                       . "Total abonado: $" . number_format($total, 2, ',', '.') . "\n"
                       . "Estamos preparando tu pedido. ¡Gracias por tu compra!";

        if ($pdfFactura !== null && $pdfFactura !== '') {
            $nombrePdf = $idFactura > 0 ? "factura_{$idFactura}.pdf" : "factura.pdf";
            $mail->addStringAttachment($pdfFactura, $nombrePdf, 'base64', 'application/pdf');
        }

        $mail->send();
        vlog("✓ Email de pago acreditado enviado a {$emailDestino} para pedido #{$idped}" . ($pdfFactura ? ' (con PDF adjunto)' : ''));
        return true;
    } catch (PHPMailerException $e) {
        vlog("✗ Error enviando email de pago acreditado para pedido #{$idped}: " . $e->getMessage());
        return false;
    }
}

// ── Template HTML: pago acreditado ────────────────────────────────────────
function htmlPagoAcreditadoVincular(
    string $nombre,
    int    $idped,
    array  $items,
    float  $subtotal,
    float  $costoEnvio,
    float  $total
): string {
    $nombreHtml = htmlspecialchars($nombre ?: 'Cliente');
    $fecha      = date('d/m/Y H:i');

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

    $envioFila = '';
    if ($costoEnvio > 0) {
        $costoFmt  = number_format($costoEnvio, 2, ',', '.');
        $envioFila = "
            <tr>
                <td colspan='3' style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>Envío:</td>
                <td style='padding:8px 12px;text-align:right;font-size:13px;color:#666;'>\${$costoFmt}</td>
            </tr>";
    }

    $totalFmt = number_format($total, 2, ',', '.');

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
          <tr>
            <td style="background:#00c853;padding:18px 32px;text-align:center;">
              <span style="color:#ffffff;font-size:20px;font-weight:bold;">✓ ¡Pago acreditado exitosamente!</span>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px 8px;">
              <p style="margin:0 0 6px;font-size:18px;font-weight:bold;color:#1a1a2e;">¡Hola, {$nombreHtml}!</p>
              <p style="margin:0;font-size:14px;color:#666;">
                Confirmamos que recibimos tu pago el <strong>{$fecha}</strong>.<br>
                Estamos preparando tu pedido y te avisaremos cuando sea despachado.
              </p>
            </td>
          </tr>
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
          <tr>
            <td style="padding:20px 32px 32px;">
              <p style="margin:0;font-size:14px;color:#555;line-height:1.6;">
                Si tenés alguna consulta sobre tu pedido, respondé este email y te ayudamos.<br>
                <strong>¡Gracias por comprar en Stadium!</strong>
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#f8f9fa;padding:16px 32px;border-top:1px solid #eee;text-align:center;">
              <span style="font-size:12px;color:#aaa;">© Stadium — Este email fue enviado automáticamente.</span>
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

// ── GET: Obtener lista de pedidos pendientes ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "
            SELECT p.id, p.fecha, p.total, p.subtotal, p.envio, p.estado, p.token, p.fechacreate,
                   c.nombre AS cli_nombre, c.apellido AS cli_apellido, c.email AS cli_email
            FROM pedidos p
            LEFT JOIN clientes c ON c.id = p.idcli
            WHERE p.estado = 0
            ORDER BY p.id DESC
            LIMIT 100
        ";
        $result = $conn->query($sql);
        $pedidos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pedidos[] = [
                    'id' => (int)$row['id'],
                    'fecha' => $row['fecha'],
                    'fechacreate' => $row['fechacreate'],
                    'total' => (float)$row['total'],
                    'token' => $row['token'],
                    'cliente' => trim(($row['cli_nombre'] ?? '') . ' ' . ($row['cli_apellido'] ?? '')),
                    'email' => $row['cli_email'] ?? ''
                ];
            }
        }
        echo json_encode([
            'success' => true,
            'pedidos_pendientes' => $pedidos
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// ── POST: Vincular un pago MP a un pedido y enviar email/factura ────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (!$inputData) {
        $inputData = $_POST;
    }

    $idped     = isset($inputData['idped']) ? (int)$inputData['idped'] : 0;
    $paymentId = isset($inputData['payment_id']) ? trim($inputData['payment_id']) : '';
    $paymentData = isset($inputData['payment_data']) ? $inputData['payment_data'] : null;

    if ($idped <= 0 || empty($paymentId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requieren el ID del pedido (idped) y el ID de pago de Mercado Pago (payment_id).'
        ]);
        exit();
    }

    try {
        // 1. Obtener pedido y datos completos del cliente
        $stmt = $conn->prepare("
            SELECT p.id, p.idcli, p.estado, p.total, p.subtotal, p.envio, p.token,
                   c.nombre AS cli_nombre, c.apellido AS cli_apellido, c.email AS cli_email
            FROM pedidos p
            LEFT JOIN clientes c ON c.id = p.idcli
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $idped);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "El pedido #{$idped} no existe."]);
            exit();
        }

        $idcli       = (int)($pedido['idcli'] ?? 0);
        $cliEmail    = $pedido['cli_email'] ?? '';
        $cliNombre   = $pedido['cli_nombre'] ?? '';
        $cliApellido = $pedido['cli_apellido'] ?? '';
        $totalPedido = (float)($pedido['total'] ?? 0);
        $subtotalPed = (float)($pedido['subtotal'] ?? 0);
        $envioPed    = (float)($pedido['envio'] ?? 0);

        // 2. Si no viene el payment_data completo, consultarlo desde MP API
        if (!$paymentData || !is_array($paymentData)) {
            $tokenMP = defined('MP_TOKEN_SANDBOX') ? MP_TOKEN_SANDBOX : MP_TOKEN_SANDBOX_VINCULAR;
            $ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer {$tokenMP}"],
                CURLOPT_TIMEOUT => 20
            ]);
            $respMP = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $paymentData = json_decode($respMP, true);
            }
        }

        $mpStatus       = isset($paymentData['status']) ? $paymentData['status'] : 'approved';
        $mpStatusDetail = isset($paymentData['status_detail']) ? $paymentData['status_detail'] : 'manual_link';
        $mpPaymentType  = isset($paymentData['payment_type_id']) ? $paymentData['payment_type_id'] : 'credit_card';
        $mpExtRef       = isset($paymentData['external_reference']) ? $paymentData['external_reference'] : (string)$idped;
        $monto          = isset($paymentData['transaction_amount']) ? (float)$paymentData['transaction_amount'] : $totalPedido;
        $jsonStr        = is_array($paymentData) ? json_encode($paymentData, JSON_UNESCAPED_UNICODE) : '{}';

        $tokenPedido    = !empty($pedido['token']) ? $pedido['token'] : md5(uniqid('ped_', true));

        // 3. Registrar o actualizar en la tabla `pagos`
        $stmtPago = $conn->prepare("
            INSERT INTO pagos
                (idped, token, monto, estado, mp_payment_id, mp_status, mp_status_detail, mp_payment_type, mp_external_ref, datos_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                idped = VALUES(idped),
                monto = VALUES(monto),
                estado = VALUES(estado),
                mp_status = VALUES(mp_status),
                mp_status_detail = VALUES(mp_status_detail),
                mp_payment_type = VALUES(mp_payment_type),
                datos_json = VALUES(datos_json)
        ");
        $stmtPago->bind_param(
            "isdsssssss",
            $idped,
            $tokenPedido,
            $monto,
            $mpStatus,
            $paymentId,
            $mpStatus,
            $mpStatusDetail,
            $mpPaymentType,
            $mpExtRef,
            $jsonStr
        );
        $stmtPago->execute();
        $stmtPago->close();

        // 4. Actualizar el pedido a estado = 1 (Confirmado / Pagado)
        $estadoAnterior = (int)$pedido['estado'];
        $nuevoEstado    = 1;

        $stmtUpd = $conn->prepare("
            UPDATE pedidos
            SET estado = ?,
                fechapago = CURRENT_TIMESTAMP(),
                mercadopago_payment_id = ?,
                mercadopago_status = ?
            WHERE id = ?
        ");
        $stmtUpd->bind_param("issi", $nuevoEstado, $paymentId, $mpStatus, $idped);
        $stmtUpd->execute();
        $stmtUpd->close();

        // 5. Registrar log de cambio de estado si existe la tabla
        $checkTable = $conn->query("SHOW TABLES LIKE 'pedidos_estados_log'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $userObj = getCurrentUser();
            $userId  = isset($userObj['id']) ? (int)$userObj['id'] : 0;
            $obs     = "Pago de Mercado Pago #{$paymentId} vinculado manualmente desde Actividad MP.";

            $stmtLog = $conn->prepare("
                INSERT INTO pedidos_estados_log (idped, estado_anterior, estado_nuevo, usuario, observaciones)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtLog->bind_param("iiiis", $idped, $estadoAnterior, $nuevoEstado, $userId, $obs);
            $stmtLog->execute();
            $stmtLog->close();
        }

        // ── 6. Crear Factura Automática AFIP y enviar Email (Idéntico a webhook.php) ──
        $pdfFactAdj = null;
        $idFactAdj  = 0;
        $emailEnviado = false;

        try {
            // CUIT por defecto
            $resCuit = $conn->query("SELECT id, razonsocial, cuit, puntoventa, test FROM datoscuit ORDER BY pordefecto DESC, id ASC LIMIT 1");
            $cuitRow = $resCuit ? $resCuit->fetch_assoc() : null;
            $idcuit  = $cuitRow ? (int)$cuitRow['id'] : null;
            $ptovta  = $cuitRow ? (int)$cuitRow['puntoventa'] : 1;

            $fechaHoy  = date('Y-m-d');
            $docRef    = "Pedido #{$idped} - MP {$paymentId}";
            $envioFact = round($envioPed, 2);

            $stmtFac = $conn->prepare("
                INSERT INTO facturas (fecha, idcli, tipocom, letra, ptovta, docreferencia, envio, idped, idcuit)
                VALUES (?, ?, 'FACTURA', 'B', ?, ?, ?, ?, ?)
            ");
            $stmtFac->bind_param('siisdii', $fechaHoy, $idcli, $ptovta, $docRef, $envioFact, $idped, $idcuit);
            $stmtFac->execute();
            $idFactura = $conn->insert_id;
            $stmtFac->close();

            // Renglones desde pedidos_det
            $stmtDet = $conn->prepare("SELECT cantidad, idart, precio, detalle FROM pedidos_det WHERE idped = ? ORDER BY id");
            $stmtDet->bind_param('i', $idped);
            $stmtDet->execute();
            $resDet = $stmtDet->get_result();

            $stmtIFD = $conn->prepare("
                INSERT INTO facturas_det (idfis, cantidad, descripcion, idart, precio, alicuota)
                VALUES (?, ?, ?, ?, ?, 21.00)
            ");
            while ($det = $resDet->fetch_assoc()) {
                $cant       = (float)$det['cantidad'];
                $idart      = $det['idart'] ?: null;
                $precoBruto = (float)$det['precio'];
                $precNeto   = round($precoBruto / 1.21, 2);
                $desc       = $det['detalle'] ?? '';
                $stmtIFD->bind_param('idsid', $idFactura, $cant, $desc, $idart, $precNeto);
                $stmtIFD->execute();
            }
            $stmtIFD->close();
            $stmtDet->close();

            if ($envioPed > 0) {
                $envioNeto = round($envioPed / 1.21, 2);
                $stmtEnv   = $conn->prepare("
                    INSERT INTO facturas_det (idfis, cantidad, descripcion, idart, precio, alicuota)
                    VALUES (?, 1, 'Costo de envío', NULL, ?, 21.00)
                ");
                $stmtEnv->bind_param('id', $idFactura, $envioNeto);
                $stmtEnv->execute();
                $stmtEnv->close();
            }

            vlog("✓ Factura #{$idFactura} creada para pedido #{$idped}. Autorizando con AFIP...");

            if ($cuitRow) {
                try {
                    $caeResult = afip_autorizar_factura($conn, $idFactura, $cuitRow);
                    vlog("✓ Factura #{$idFactura} autorizada — N°={$caeResult['numero']} CAE={$caeResult['cae']}");
                } catch (Exception $eAfip) {
                    vlog("✗ Error AFIP para factura #{$idFactura}: " . $eAfip->getMessage());
                }
            }

            $idFactAdj = $idFactura;
            try {
                require_once __DIR__ . '/../../impresion/pdf_factura.php';
                $pdfFactAdj = generar_pdf_factura($conn, $idFactura);
                vlog("✓ PDF de factura #{$idFactura} generado directamente mediante generar_pdf_factura()");
            } catch (Exception $ePdf) {
                vlog("⚠ Error generando PDF directamente para factura #{$idFactura}: " . $ePdf->getMessage() . " — intentando URL fallback");
                $url_pdf = "https://stadium.planbsistemas.com.ar/comprobante_fis?id=" . $idFactura . "&cliente_id=" . $idcli;
                $pdfFactAdj = @file_get_contents($url_pdf);
                if ($pdfFactAdj === false) {
                    $pdfFactAdj = null;
                }
            }
        } catch (Exception $eFac) {
            vlog("✗ Error al crear factura automática para pedido #{$idped}: " . $eFac->getMessage());
        }

        // Obtener ítems para correo
        $itemsMail = [];
        if (isset($idFactura) && $idFactura > 0) {
            $stmtItems = $conn->prepare("
                SELECT descripcion AS titulo, cantidad, 
                       precio * (1 + (alicuota/100)) AS precio, 
                       (cantidad * precio * (1 + (alicuota/100))) AS total
                FROM facturas_det
                WHERE idfis = ?
                ORDER BY id
            ");
            $stmtItems->bind_param("i", $idFactura);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();
            while ($row = $resItems->fetch_assoc()) {
                if ($row['titulo'] === 'Costo de envío') continue;
                $itemsMail[] = $row;
            }
            $stmtItems->close();
        }

        if (empty($itemsMail)) {
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
        }

        // Enviar correo electrónico
        if (!empty($cliEmail)) {
            vlog("Enviando email de confirmación a {$cliEmail} para pedido #{$idped}...");
            $emailEnviado = enviarCorreoPagoAcreditadoVincular(
                $cliEmail, $cliNombre, $cliApellido,
                $idped, $itemsMail, $subtotalPed, $envioPed, $totalPedido,
                $pdfFactAdj, $idFactAdj
            );
        } else {
            vlog("⚠ Email de cliente vacío para pedido #{$idped}");
        }

        $conn->close();

        $msgFin = "El pago #{$paymentId} se registró exitosamente en el Pedido #{$idped}.";
        if ($emailEnviado) {
            $msgFin .= " Se envió el correo de confirmación a {$cliEmail}.";
        }

        echo json_encode([
            'success' => true,
            'message' => $msgFin,
            'idped' => $idped,
            'payment_id' => $paymentId,
            'email_enviado' => $emailEnviado,
            'id_factura' => $idFactAdj
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al vincular pago: ' . $e->getMessage()
        ]);
        exit();
    }
}
