<?php
// includes/mailer.php
// Envío de correo usando la configuración guardada en la tabla `configuracion`
// (Utilidades > Configuración). Si un valor no está cargado, usa config/mail.php.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Lee todas las claves de la tabla configuracion.
 */
function obtenerConfiguracion(?mysqli $conn = null): array {
    $cerrar = false;
    if ($conn === null) {
        $conn = get_database_connection();
        $cerrar = true;
    }
    $config = [];
    $res = @$conn->query("SELECT clave, valor FROM configuracion");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    if ($cerrar) {
        $conn->close();
    }
    return $config;
}

/**
 * Configuración SMTP efectiva (tabla configuracion con respaldo en config/mail.php).
 */
function obtenerConfigMail(?mysqli $conn = null): array {
    if (file_exists(__DIR__ . '/../config/mail.php')) {
        require_once __DIR__ . '/../config/mail.php';
    }
    $c = obtenerConfiguracion($conn);
    $v = function (string $clave, string $const, $default = '') use ($c) {
        if (isset($c[$clave]) && $c[$clave] !== '') {
            return $c[$clave];
        }
        return defined($const) ? constant($const) : $default;
    };
    return [
        'host'       => $v('mail_host', 'MAIL_HOST'),
        'port'       => (int) $v('mail_port', 'MAIL_PORT', 465),
        'username'   => $v('mail_username', 'MAIL_USERNAME'),
        'password'   => $v('mail_password', 'MAIL_PASSWORD'),
        'encryption' => strtolower($v('mail_encryption', 'MAIL_ENCRYPTION', 'ssl')),
        'from_email' => $v('mail_from_email', 'MAIL_FROM_EMAIL'),
        'from_name'  => $v('mail_from_name', 'MAIL_FROM_NAME', 'Stadium'),
        'presupuestos_destino' => $c['mail_presupuestos_destino'] ?? 'info@stadiumdeportes.com',
    ];
}

/**
 * Envía un correo HTML. Devuelve ['ok' => bool, 'error' => string|null].
 * $cfg permite pasar una configuración puntual (por ejemplo, para probarla antes de guardar).
 */
function enviarCorreo(string $para, string $asunto, string $html, ?string $replyTo = null, ?string $replyToNombre = null, ?array $cfg = null): array {
    $cfg = $cfg ?? obtenerConfigMail();
    try {
        if (empty($cfg['host'])) {
            throw new Exception('No hay servidor SMTP configurado');
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host     = $cfg['host'];
        $mail->Port     = (int) $cfg['port'];
        $mail->CharSet  = 'UTF-8';
        $mail->Timeout  = 15;
        $mail->SMTPAuth = $cfg['username'] !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = $cfg['username'];
            $mail->Password = $cfg['password'];
        }
        if ($cfg['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($cfg['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $from = $cfg['from_email'] ?: $cfg['username'];
        $mail->setFrom($from, $cfg['from_name']);
        $mail->addAddress($para);
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, $replyToNombre ?? '');
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $html;
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</tr>', '</p>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (\Throwable $e) {
        $err = isset($mail) && $mail->ErrorInfo ? $mail->ErrorInfo : $e->getMessage();
        error_log('enviarCorreo: ' . $err);
        return ['ok' => false, 'error' => $err];
    }
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
