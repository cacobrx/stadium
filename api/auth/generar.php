<?php
// api/auth/generar.php
// Genera un código de verificación numérico para un cliente existente
// y lo envía por correo si corresponde (si pasó más de 1 hora desde el último código)

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

// Capturar errores fatales que PHP no puede atrapar con try/catch
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $resp = ['success' => false, 'message' => 'Error interno del servidor'];
        if (($_GET['_dbg'] ?? '') === 'stadium') {
            $resp['debug'] = $err['message'] . ' en ' . $err['file'] . ':' . $err['line'];
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        error_log('[auth/generar] Fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']);
    }
    ob_end_flush();
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../../includes/phpmailer/Exception.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ─── Solo POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Leer JSON de entrada ───────────────────────────────────────────────────────
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // También intentar desde form-data / x-www-form-urlencoded
    $data = $_POST;
}

$email = trim($data['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email inválido o no proporcionado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Buscar cliente por email ───────────────────────────────────────────────────
try {
    $conn = get_database_connection();

    $stmt = $conn->prepare("
        SELECT id, nombre, apellido, email, codigo, fechavalidar
        FROM clientes
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();

    if (!$cliente) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No existe ningún cliente con ese email'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Generar siempre un código nuevo ────────────────────────────────────────
    $ahora       = new DateTime();
    $nuevoCodigo = random_int(100000, 999999);
    $fechaHoy    = $ahora->format('Y-m-d H:i:s');

    $stmtUp = $conn->prepare("
        UPDATE clientes SET codigo = ?, fechavalidar = ? WHERE id = ?
    ");
    $stmtUp->bind_param('isi', $nuevoCodigo, $fechaHoy, $cliente['id']);
    $stmtUp->execute();
    $stmtUp->close();

    $cliente['codigo'] = $nuevoCodigo;

    // ─── Enviar email con el código ──────────────────────────────────────────────
    $mailError     = null;
    $smtpLog       = null;
    $codigoEnviado = enviarCodigoPorMail(
        $email,
        $cliente['nombre'] ?? '',
        $cliente['apellido'] ?? '',
        $nuevoCodigo,
        $mailError,
        $smtpLog
    );

    // ─── Respuesta ──────────────────────────────────────────────────────────────
    $resp = [
        'success'      => true,
        'emailEnviado' => $codigoEnviado,
        'message'      => $codigoEnviado
            ? 'Código generado y enviado por email'
            : 'Código generado pero hubo un problema al enviar el email',
        'data' => [
            'idcli'  => (int) $cliente['id'],
            'email'  => $email,
            'nombre' => trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? '')),
        ]
    ];
    if ($mailError !== null) {
        $resp['mailError'] = $mailError;
    }
    if ($smtpLog !== null) {
        $resp['smtpDebug'] = $smtpLog;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[auth/generar] Error: ' . $e->getMessage());
    http_response_code(500);
    $resp = ['success' => false, 'message' => 'Error interno del servidor'];
    if (($_GET['_dbg'] ?? '') === 'stadium') {
        $resp['debug'] = $e->getMessage();
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}

// ─── Función de envío de mail ───────────────────────────────────────────────────
function enviarCodigoPorMail(string $emailDestino, string $nombre, string $apellido, int $codigo, ?string &$errorMsg = null, ?string &$smtpLog = null): bool
{
    $debugActivo = isset($_GET['debug']) && $_GET['debug'] === '1';
    $logBuffer   = '';

    try {
        $mail = new PHPMailer(true);

        if ($debugActivo) {
            $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function (string $str, int $level) use (&$logBuffer) {
                $logBuffer .= "[{$level}] " . trim($str) . "\n";
            };
        }

        // Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remitente: debe coincidir con el usuario autenticado
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($emailDestino, trim("$nombre $apellido"));

        // Reply-To con la dirección de marca
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Tu código de verificación - Stadium';
        $mail->Body    = generarHtmlCodigo($nombre, $codigo);
        $mail->AltBody = "Hola $nombre, tu código de verificación es: $codigo\n"
                       . "Este código es válido por 1 hora.";

        $mail->send();
        if ($debugActivo) $smtpLog = $logBuffer;
        return true;

    } catch (PHPMailerException $e) {
        $errorMsg = $e->getMessage();
        if ($debugActivo) $smtpLog = $logBuffer;
        error_log('[auth/generar] PHPMailer error: ' . $errorMsg);
        return false;
    }
}

// ─── Template HTML del mail ─────────────────────────────────────────────────────
function generarHtmlCodigo(string $nombre, int $codigo): string
{
    $nombreHtml = htmlspecialchars($nombre ?: 'Cliente');
    $codigoStr  = str_pad((string)$codigo, 6, '0', STR_PAD_LEFT);

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Código de verificación</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">

          <!-- Encabezado -->
          <tr>
            <td style="background:#1a1a2e;padding:28px 32px;text-align:center;">
              <img src="https://stadium.planbsistemas.com.ar/api/logostadium_small.jpg" width="58" height="40" alt="Stadium Deportes" style="vertical-align:middle; margin-right:12px; border-radius:4px;">
              <span style="color:#ffffff;font-size:24px;font-weight:bold;letter-spacing:2px;vertical-align:middle;">STADIUM DEPORTES</span>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 32px;text-align:center;">
              <p style="margin:0 0 8px;color:#333;font-size:16px;">Hola, <strong>{$nombreHtml}</strong></p>
              <p style="margin:0 0 28px;color:#666;font-size:14px;">
                Usá el siguiente código para verificar tu cuenta. Es válido por <strong>1 hora</strong>.
              </p>

              <!-- Código destacado -->
              <div style="display:inline-block;background:#f0f4ff;border:2px dashed #4361ee;border-radius:10px;padding:18px 40px;margin-bottom:28px;">
                <span style="font-size:40px;font-weight:bold;letter-spacing:10px;color:#1a1a2e;">{$codigoStr}</span>
              </div>

              <p style="margin:0;color:#999;font-size:12px;">
                Si no solicitaste este código, ignorá este mensaje.
              </p>
            </td>
          </tr>

          <!-- Pie -->
          <tr>
            <td style="background:#f8f8f8;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
              <p style="margin:0;color:#aaa;font-size:11px;">
                © 2026 Stadium &bull; Este es un correo automático, no respondas a este mensaje.
              </p>
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
