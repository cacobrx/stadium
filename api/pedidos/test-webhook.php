<?php
// api/pedidos/test-webhook.php
// Herramienta de diagnostico y prueba para webhook.php
//
// SECCIONES (parametro GET "seccion"):
//   (sin parametros)  -> Todo: diagnostico + estado + tests A, B, C
//   ?seccion=diagnostico -> Chequeos de infraestructura (BD, config, ext. PHP)
//   ?seccion=estado      -> Estado actual: pedidos pendientes, pagos, facturas
//   ?seccion=ping        -> TEST A: webhook responde al ping GET de MercadoPago
//   ?seccion=ignorar     -> TEST B: webhook ignora tipos de notificacion incorrectos
//   ?seccion=formato     -> TEST C: webhook maneja gracefully un payment_id invalido
//   ?seccion=completo&mp_payment_id=XXX
//                        -> TEST D: flujo completo con payment_id real de sandbox
//
// IMPORTANTE: El flujo completo (pago -> pedido -> factura -> AFIP) SOLO puede
// probarse con un payment_id real de sandbox, porque webhook.php llama a la API
// de MercadoPago internamente para obtener los datos del pago.
// Como obtener el ID: panel sandbox de MP -> Actividad -> copiar el ID numerico.

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

// ── Helpers de salida ─────────────────────────────────────────────────────────
function isCli(): bool { return PHP_SAPI === 'cli'; }

function printHtml(bool $ok, string $titulo, string $detalle = ''): void {
    $icon  = $ok ? 'OK' : 'ERROR';
    $color = $ok ? '#155724' : '#721c24';
    $bg    = $ok ? '#d4edda' : '#f8d7da';
    if (isCli()) {
        echo ($ok ? '[OK]  ' : '[ERR] ') . "$titulo\n";
        if ($detalle) echo "      $detalle\n";
        return;
    }
    echo "<div style='background:$bg;color:$color;padding:10px;margin:6px 0;border-radius:4px;'>";
    echo "<strong>" . ($ok ? "&#x2705;" : "&#x274C;") . " $titulo</strong>";
    if ($detalle) echo "<pre style='margin:6px 0 0;font-size:12px;white-space:pre-wrap;overflow:auto;'>$detalle</pre>";
    echo "</div>";
}

function warn(string $msg, string $detalle = ''): void {
    if (isCli()) { echo "[WARN] $msg\n"; if ($detalle) echo "       $detalle\n"; return; }
    echo "<div style='background:#fff3cd;color:#856404;padding:10px;margin:6px 0;border-radius:4px;'>";
    echo "<strong>&#x26A0;&#xFE0F; $msg</strong>";
    if ($detalle) echo "<pre style='margin:6px 0 0;font-size:12px;white-space:pre-wrap;'>$detalle</pre>";
    echo "</div>";
}

function info(string $msg): void {
    if (isCli()) { echo "[INF] $msg\n"; return; }
    echo "<div style='background:#cce5ff;color:#004085;padding:10px;margin:6px 0;border-radius:4px;'>&#x2139;&#xFE0F; $msg</div>";
}

function seccion(string $titulo): void {
    if (isCli()) { echo "\n--- $titulo ---\n"; return; }
    echo "<h3 style='margin-top:20px;margin-bottom:8px;color:#343a40;"
       . "border-left:4px solid #007bff;padding-left:10px;'>$titulo</h3>";
}

function jsonBonito(mixed $v): string {
    if (is_string($v)) $v = json_decode($v, true) ?? $v;
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ── URL del webhook ───────────────────────────────────────────────────────────
function webhookUrl(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (str_contains($host, 'stadium.planbsistemas.com.ar') || str_contains($host, 'stadiumapp')) {
        return 'https://stadium.planbsistemas.com.ar/api/pedidos/webhook.php';
    }
    return 'http://' . $host . '/stadium/api/pedidos/webhook.php';
}

// ── Llamada HTTP al webhook ───────────────────────────────────────────────────
function httpWebhook(array $payload, string $method = 'POST'): array {
    $url = webhookUrl();
    $ch  = curl_init($url);
    if ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string)$body, 'error' => $err];
}

// ── Conexion BD ───────────────────────────────────────────────────────────────
function db(): ?mysqli {
    require_once __DIR__ . '/../../config/database.php';
    try { return get_database_connection(); } catch (Throwable) { return null; }
}

// ── Enmascarar token ──────────────────────────────────────────────────────────
function maskToken(string $t): string {
    $len = strlen($t);
    if ($len <= 14) return str_repeat('*', $len);
    return substr($t, 0, 8) . str_repeat('*', $len - 14) . substr($t, -6);
}

// ═════════════════════════════════════════════════════════════════════════════
// HTML wrapper
// ═════════════════════════════════════════════════════════════════════════════
$seccion = $_GET['seccion'] ?? 'todo';

if (!isCli()) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
       . '<title>Test Webhook MP</title><style>'
       . 'body{font-family:Arial,sans-serif;max-width:960px;margin:30px auto;padding:0 20px;background:#f9f9f9;}'
       . 'h1{color:#212529;}h2{color:#343a40;border-bottom:2px solid #007bff;padding-bottom:6px;margin-top:36px;}'
       . 'pre{background:#f4f4f4;padding:10px;border-radius:4px;overflow:auto;font-size:12px;}'
       . 'nav{margin-bottom:24px;}'
       . 'nav a{display:inline-block;margin:4px 6px 4px 0;padding:7px 14px;background:#007bff;'
       .       'color:#fff;text-decoration:none;border-radius:4px;font-size:13px;}'
       . 'nav a:hover{background:#0056b3;}'
       . 'table{border-collapse:collapse;width:100%;font-size:13px;margin:8px 0;}'
       . 'th,td{border:1px solid #dee2e6;padding:6px 10px;text-align:left;}'
       . 'th{background:#e9ecef;}'
       . '.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;}'
       . '.ok{background:#c3e6cb;color:#155724;}.warn{background:#fff3cd;color:#856404;}'
       . '.err{background:#f5c6cb;color:#721c24;}'
       . '.form-box{background:#fff;border:1px solid #dee2e6;padding:16px;border-radius:6px;margin:12px 0;}'
       . '.form-box input{padding:7px 10px;border:1px solid #ccc;border-radius:4px;width:340px;font-size:14px;}'
       . '.form-box button{padding:8px 18px;background:#007bff;color:#fff;border:none;'
       .                  'border-radius:4px;cursor:pointer;font-size:14px;}'
       . '.form-box button:hover{background:#0056b3;}'
       . '</style></head><body>'
       . '<h1>&#x1F9EA; Test Webhook &mdash; MercadoPago</h1>'
       . '<nav>'
       . '<a href="?seccion=todo">Todo</a>'
       . '<a href="?seccion=diagnostico">Diagn&oacute;stico</a>'
       . '<a href="?seccion=estado">Estado BD</a>'
       . '<a href="?seccion=ping">TEST A &middot; Ping</a>'
       . '<a href="?seccion=ignorar">TEST B &middot; Tipo ignorado</a>'
       . '<a href="?seccion=formato">TEST C &middot; ID inv&aacute;lido</a>'
       . '<a href="?seccion=completo">TEST D &middot; Flujo completo</a>'
       . '</nav>';
}

$conn = null; // se inicializa al primer uso

// ═════════════════════════════════════════════════════════════════════════════
// DIAGNOSTICO — infraestructura
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'diagnostico'])) {
    if (!isCli()) echo "<h2>&#x1F4CB; Diagn&oacute;stico del sistema</h2>";

    // BD
    seccion('Base de datos');
    $conn = db();
    if ($conn) {
        printHtml(true, 'Conexion a BD establecida');
        foreach (['pedidos', 'pagos', 'facturas', 'facturas_det', 'datoscuit'] as $t) {
            $r = $conn->query("SHOW TABLES LIKE '$t'");
            printHtml($r && $r->num_rows > 0, "Tabla `$t`");
        }
    } else {
        printHtml(false, 'No se pudo conectar a la BD');
    }

    // Extensiones PHP
    seccion('Extensiones PHP');
    foreach (['soap', 'curl', 'openssl'] as $ext) {
        printHtml(extension_loaded($ext), "Extension $ext: " . (extension_loaded($ext) ? 'OK' : 'FALTA'));
    }

    // Config MercadoPago
    seccion('Configuracion MercadoPago');
    require_once __DIR__ . '/../../config/mercadopago.php';
    printHtml(!empty(MP_TOKEN_SANDBOX),
        'MP_TOKEN_SANDBOX    &rarr; ' . (empty(MP_TOKEN_SANDBOX) ? 'NO configurado' : maskToken(MP_TOKEN_SANDBOX)));
    printHtml(!empty(MP_TOKEN_PRODUCTION),
        'MP_TOKEN_PRODUCTION &rarr; ' . (empty(MP_TOKEN_PRODUCTION) ? 'NO configurado' : maskToken(MP_TOKEN_PRODUCTION)));
    if (!empty(MP_WEBHOOK_SECRET)) {
        printHtml(true, 'MP_WEBHOOK_SECRET &rarr; ' . maskToken(MP_WEBHOOK_SECRET));
    } else {
        warn('MP_WEBHOOK_SECRET no configurado &mdash; verificacion HMAC desactivada',
             'Para activar la proteccion, configurar el secret en config/mercadopago.php');
    }
    info('APP_URL: ' . MP_APP_URL . ' &nbsp;|&nbsp; BACKEND: ' . MP_BACKEND_URL);

    // CUITs / AFIP
    seccion('CUITs / AFIP configurados (tabla datoscuit)');
    if ($conn) {
        $r = $conn->query("SELECT id, razonsocial, cuit, puntoventa, test FROM datoscuit ORDER BY id");
        if ($r && $r->num_rows > 0) {
            if (!isCli()) {
                echo "<table><tr><th>ID</th><th>Razon Social</th><th>CUIT</th>"
                   . "<th>Pto.Vta</th><th>Modo</th><th>Certificado</th></tr>";
                while ($f = $r->fetch_assoc()) {
                    $modo  = $f['test']
                        ? "<span class='badge warn'>SANDBOX</span>"
                        : "<span class='badge ok'>PRODUCCION</span>";
                    $cert  = file_exists(__DIR__ . "/../../config/certs/{$f['cuit']}.pem")
                        ? "&#x2705;" : "&#x274C; falta";
                    echo "<tr><td>{$f['id']}</td><td>" . htmlspecialchars($f['razonsocial'])
                       . "</td><td>{$f['cuit']}</td><td>{$f['puntoventa']}</td>"
                       . "<td>$modo</td><td>$cert</td></tr>";
                }
                echo "</table>";
            } else {
                while ($f = $r->fetch_assoc()) {
                    $modo = (int)$f['test'] ? '[SANDBOX]' : '[PRODUCCION]';
                    $cert = file_exists(__DIR__ . "/../../config/certs/{$f['cuit']}.pem") ? 'cert OK' : 'SIN cert';
                    echo "  #{$f['id']} {$f['razonsocial']} — {$f['cuit']} | PV {$f['puntoventa']} | $modo | $cert\n";
                }
            }
        } else {
            warn('No hay CUITs en datoscuit. La autorizacion AFIP no funcionara.');
        }
    }

    // Logs
    seccion('Directorio de logs');
    $logDir  = __DIR__ . '/../../log';
    $logOk   = is_dir($logDir) && is_writable($logDir);
    printHtml($logOk, 'log/: ' . ($logOk ? 'existe y escribible' : (is_dir($logDir) ? 'existe pero NO escribible' : 'NO existe')));
    if ($logOk) {
        $logs = glob($logDir . '/wh_*.log');
        if ($logs) {
            $ultimo = end($logs);
            info('Ultimo log: ' . basename($ultimo) . ' (' . round(filesize($ultimo) / 1024, 1) . ' KB)');
        }
    }

    // URL del webhook
    seccion('URL del webhook (registrar en el panel de MercadoPago)');
    info(htmlspecialchars(webhookUrl()));
}

// ═════════════════════════════════════════════════════════════════════════════
// ESTADO BD
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'estado'])) {
    if (!isCli()) echo "<h2>&#x1F4CA; Estado de la base de datos</h2>";

    if (!$conn) $conn = db();
    if (!$conn) { warn('Sin conexion a BD'); goto fin_estado; }

    // Pedidos pendientes
    seccion('Pedidos pendientes de pago (estado=0) &mdash; candidatos para TEST D');
    $r = $conn->query("
        SELECT p.id, p.total, p.estado, p.token,
               CONCAT(COALESCE(c.nombre,'?'),' ',COALESCE(c.apellido,'')) AS cliente
        FROM pedidos p
        LEFT JOIN clientes c ON c.id = p.idcli
        WHERE p.estado = 0
        ORDER BY p.id DESC LIMIT 10
    ");
    if ($r && $r->num_rows > 0) {
        if (!isCli()) {
            echo "<table><tr><th>ID</th><th>Cliente</th><th>Total</th><th>Token</th></tr>";
            while ($f = $r->fetch_assoc()) {
                echo "<tr><td>{$f['id']}</td><td>" . htmlspecialchars(trim($f['cliente'])) . "</td>"
                   . "<td>$" . number_format((float)$f['total'], 2, ',', '.') . "</td>"
                   . "<td><code style='font-size:11px'>{$f['token']}</code></td></tr>";
            }
            echo "</table>";
        } else {
            while ($f = $r->fetch_assoc())
                echo "  #{$f['id']} " . trim($f['cliente']) . " \${$f['total']} | {$f['token']}\n";
        }
    } else {
        warn('No hay pedidos pendientes (estado=0). Crea un pedido sin pagar para usar en el TEST D.');
    }

    // Ultimos pagos
    seccion('Ultimos 5 pagos registrados');
    $r2 = $conn->query("
        SELECT id, idped, monto, estado, mp_payment_id
        FROM pagos ORDER BY id DESC LIMIT 5
    ");
    if ($r2 && $r2->num_rows > 0) {
        if (!isCli()) {
            echo "<table><tr><th>ID</th><th>Pedido</th><th>Monto</th><th>Estado</th><th>MP Payment ID</th><th>Fecha</th></tr>";
            while ($f = $r2->fetch_assoc()) {
                $cls = $f['estado'] === 'approved' ? 'ok' : 'warn';
                echo "<tr><td>{$f['id']}</td><td>#{$f['idped']}</td>"
                   . "<td>$" . number_format((float)$f['monto'], 2, ',', '.') . "</td>"
                   . "<td><span class='badge $cls'>{$f['estado']}</span></td>"
                   . "<td><code>{$f['mp_payment_id']}</code></td>"
                   . "<td>{$f['created_at']}</td></tr>";
            }
            echo "</table>";
        } else {
            while ($f = $r2->fetch_assoc())
                echo "  #{$f['id']} pedido#{$f['idped']} \${$f['monto']} {$f['estado']} mp={$f['mp_payment_id']}\n";
        }
    } else {
        info('No hay pagos registrados aun.');
    }

    // Ultimas facturas
    seccion('Ultimas 5 facturas (con estado AFIP)');
    $r3 = $conn->query("
        SELECT f.id, f.idped, f.tipocom, f.letra, f.ptovta, f.numero,
               f.numerocae, f.fechacae, f.fecha, d.razonsocial
        FROM facturas f
        LEFT JOIN datoscuit d ON d.id = f.idcuit
        ORDER BY f.id DESC LIMIT 5
    ");
    if ($r3 && $r3->num_rows > 0) {
        if (!isCli()) {
            echo "<table><tr><th>ID</th><th>Pedido</th><th>Comprobante</th><th>Numero</th><th>CAE</th><th>Venc.CAE</th><th>Emisor</th></tr>";
            while ($f = $r3->fetch_assoc()) {
                $caeOk = !empty($f['numerocae']);
                $nro   = $f['ptovta']
                    ? str_pad($f['ptovta'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($f['numero'], 8, '0', STR_PAD_LEFT)
                    : '&mdash;';
                $cae = $caeOk
                    ? "<span class='badge ok'>{$f['numerocae']}</span>"
                    : "<span class='badge err'>SIN CAE</span>";
                echo "<tr><td>{$f['id']}</td><td>#{$f['idped']}</td>"
                   . "<td>{$f['tipocom']} {$f['letra']}</td><td>$nro</td>"
                   . "<td>$cae</td><td>{$f['fechacae']}</td>"
                   . "<td>" . htmlspecialchars($f['razonsocial'] ?? '&mdash;') . "</td></tr>";
            }
            echo "</table>";
        } else {
            while ($f = $r3->fetch_assoc()) {
                $cae = $f['numerocae'] ?: 'SIN CAE';
                echo "  #{$f['id']} pedido#{$f['idped']} {$f['tipocom']}{$f['letra']} | CAE:{$cae} | {$f['fecha']}\n";
            }
        }
    } else {
        info('No hay facturas registradas aun.');
    }
}
fin_estado:

// ═════════════════════════════════════════════════════════════════════════════
// TEST A — Ping GET
// MercadoPago hace un GET cuando registras la URL del webhook.
// Debe responder HTTP 200 con {"status":"ok"}.
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'ping'])) {
    if (!isCli()) echo "<h2>TEST A &middot; Ping GET</h2>";
    info('Enviando GET a ' . htmlspecialchars(webhookUrl()) . ' ...');
    $r    = httpWebhook([], 'GET');
    $json = json_decode($r['body'], true);
    $ok   = $r['code'] === 200 && ($json['status'] ?? '') === 'ok';
    printHtml($ok,
        "HTTP {$r['code']} &rarr; " . ($ok ? 'Webhook disponible' : 'Respuesta inesperada'),
        $r['error'] ?: $r['body']
    );
    if (!$ok && $r['code'] === 0) {
        warn('curl no pudo conectarse. Verificar que el servidor web esta activo y la URL es correcta.');
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST B — Tipo de notificacion ignorada
// MercadoPago puede enviar "merchant_order", "plan", etc.
// El webhook debe aceptar (HTTP 200) e ignorar sin error.
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'ignorar'])) {
    if (!isCli()) echo "<h2>TEST B &middot; Tipo de notificaci&oacute;n ignorada</h2>";
    info('Enviando type=merchant_order (no es "payment", debe ignorarse sin error) ...');
    $r    = httpWebhook(['type' => 'merchant_order', 'data' => ['id' => '999999']]);
    $json = json_decode($r['body'], true);
    $ok   = $r['code'] === 200 && isset($json['success']) && $json['success'] === true;
    printHtml($ok,
        "HTTP {$r['code']} &rarr; " . ($ok ? 'Ignorada correctamente' : 'Respuesta inesperada'),
        $r['body']
    );
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST C — Payment ID con formato invalido
// Envia type=payment con un ID no numerico.
// El webhook llama a MP, MP responde con error. El webhook debe manejar el
// error sin romper (respuesta controlada, sin excepcion no capturada).
// NO crea ningun dato en la BD.
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'formato'])) {
    if (!isCli()) echo "<h2>TEST C &middot; Payment ID inv&aacute;lido</h2>";
    info('Enviando type=payment con id="ID-INVALIDO-ABC" &mdash; MP devolvera error, el webhook debe manejarlo ...');
    $r    = httpWebhook(['type' => 'payment', 'data' => ['id' => 'ID-INVALIDO-ABC']]);
    $json = json_decode($r['body'], true);
    // El webhook puede responder 200 (manejado) o 4xx (rechazado). Lo que NO debe pasar es un 500 no controlado.
    $ok   = in_array($r['code'], [200, 400, 422]) && $r['code'] !== 500;
    printHtml($ok,
        "HTTP {$r['code']} &rarr; " . ($ok ? 'Manejo controlado (no es un crash 500)' : 'Error inesperado'),
        $r['body']
    );
    info('Este test no crea datos en BD. El webhook consulta a MP y MP devuelve error por el ID invalido.');
}

// ═════════════════════════════════════════════════════════════════════════════
// TEST D — Flujo completo con payment_id real de sandbox
//
// Este es el unico test que ejercita el flujo real del webhook:
//   1. Webhook consulta la API de MercadoPago con el payment_id
//   2. MP devuelve los datos del pago (monto, estado, external_reference=idped)
//   3. Webhook verifica el monto contra el pedido en BD
//   4. Guarda el pago en tabla `pagos`
//   5. Actualiza el pedido a estado=1 (confirmado)
//   6. Envia email al cliente
//   7. Crea factura en `facturas` + detalle en `facturas_det`
//   8. Autoriza con AFIP (real o ficticio segun datoscuit.test)
//
// Como obtener un payment_id de sandbox:
//   1. Ir al panel de MercadoPago -> Developers -> tu app
//   2. Usar las credenciales SANDBOX (MP_TOKEN_SANDBOX)
//   3. Crear un pago desde el frontend de la app con un pedido real (estado=0)
//   4. Completar el pago con una tarjeta de prueba (ver listado en docs de MP)
//   5. Ir a la cuenta sandbox en mercadopago.com.ar -> Actividad -> copiar el ID
// ═════════════════════════════════════════════════════════════════════════════
if (in_array($seccion, ['todo', 'completo'])) {
    if (!isCli()) echo "<h2>TEST D &middot; Flujo completo (payment_id real de sandbox)</h2>";

    // Leer y validar el payment_id (solo numeros para evitar inyecciones)
    $mpId = isset($_GET['mp_payment_id']) ? trim($_GET['mp_payment_id']) : '';
    if ($mpId && !preg_match('/^\d+$/', $mpId)) {
        warn('El mp_payment_id debe ser numerico.');
        $mpId = '';
    }

    if (!isCli()) {
        $mpIdHtml = htmlspecialchars($mpId);
        echo "<div class='form-box'>"
           . "<p><strong>Pega el payment_id real de sandbox de MercadoPago:</strong></p>"
           . "<form method='get'>"
           . "<input type='hidden' name='seccion' value='completo'>"
           . "<input type='text' name='mp_payment_id' value='$mpIdHtml' placeholder='Ej: 95847263810' required>"
           . " <button type='submit'>&#x25BA; Ejecutar TEST D</button>"
           . "</form>"
           . "<p style='font-size:12px;color:#666;margin-top:10px;'>"
           . "El pedido asociado al pago debe tener <code>estado=0</code> en la BD.<br>"
           . "Si ya fue procesado antes, el webhook detectara el duplicado y no creara factura nueva."
           . "</p></div>";
    }

    if (!$mpId) {
        if (!isCli()) {
            echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:14px;border-radius:4px;'>"
               . "<strong>Como obtener el payment_id de sandbox:</strong><ol>"
               . "<li>Desde el frontend de la app, crear un pedido y proceder al checkout</li>"
               . "<li>Completar el pago con una tarjeta de prueba del panel MP Sandbox</li>"
               . "<li>Ir a <a href='https://www.mercadopago.com.ar/activities' target='_blank'>mercadopago.com.ar/activities</a> "
               . "con la cuenta sandbox e identificar el pago</li>"
               . "<li>Copiar el ID numerico y pegarlo en el campo de arriba</li>"
               . "</ol></div>";
        } else {
            warn('Pasa el mp_payment_id: ?seccion=completo&mp_payment_id=XXXXXXXXXX');
        }
        goto fin_completo;
    }

    if (!$conn) $conn = db();

    // ── Estado ANTES ───────────────────────────────────────────────────────
    seccion("Estado ANTES de llamar al webhook (mp_payment_id=$mpId)");
    if ($conn) {
        $stmt = $conn->prepare("SELECT id, idped, estado FROM pagos WHERE mp_payment_id = ? LIMIT 1");
        $stmt->bind_param('s', $mpId);
        $stmt->execute();
        $pagoExistente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($pagoExistente) {
            warn("Este payment_id YA esta registrado en pagos (id #{$pagoExistente['id']}, "
               . "pedido #{$pagoExistente['idped']}, estado={$pagoExistente['estado']})",
               'El webhook detectara el duplicado y no duplicara la factura.');
        } else {
            printHtml(true, 'Payment ID no existe en pagos &rarr; sera procesado como nuevo');
        }
    }

    // ── Llamada al webhook ─────────────────────────────────────────────────
    seccion('Llamando a webhook.php con el payment_id ...');
    $t0  = microtime(true);
    $r   = httpWebhook(['type' => 'payment', 'data' => ['id' => $mpId]]);
    $dt  = round((microtime(true) - $t0) * 1000);
    $ok  = in_array($r['code'], [200, 201]);
    printHtml($ok,
        "HTTP {$r['code']} en {$dt}ms &rarr; " . ($ok ? 'OK' : 'Error'),
        $r['error'] ?: jsonBonito($r['body'])
    );

    // ── Estado DESPUES ─────────────────────────────────────────────────────
    if ($conn) {
        seccion('Estado DESPUES de llamar al webhook');

        // Pago registrado
        $stmt2 = $conn->prepare("SELECT * FROM pagos WHERE mp_payment_id = ? LIMIT 1");
        $stmt2->bind_param('s', $mpId);
        $stmt2->execute();
        $pago = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if ($pago) {
            printHtml(true, "Pago guardado en pagos (id #{$pago['id']}, pedido #{$pago['idped']}, estado={$pago['estado']})",
                jsonBonito($pago));

            // Estado del pedido
            $rp = $conn->query("SELECT id, estado, fechapago FROM pedidos WHERE id = {$pago['idped']} LIMIT 1");
            if ($rp && $fp = $rp->fetch_assoc()) {
                $eOk = (int)$fp['estado'] >= 1;
                printHtml($eOk,
                    "Pedido #{$fp['id']}: estado={$fp['estado']}, fechapago={$fp['fechapago']}",
                    $eOk ? '' : 'El estado no cambio &mdash; revisa el log'
                );
            }

            // Factura creada
            $rf = $conn->query("
                SELECT f.*, d.razonsocial, d.test
                FROM facturas f
                LEFT JOIN datoscuit d ON d.id = f.idcuit
                WHERE f.idped = {$pago['idped']}
                ORDER BY f.id DESC LIMIT 1
            ");
            if ($rf && $ff = $rf->fetch_assoc()) {
                $caeOk = !empty($ff['numerocae']);
                $nro   = str_pad($ff['ptovta'], 4, '0', STR_PAD_LEFT)
                       . '-' . str_pad($ff['numero'], 8, '0', STR_PAD_LEFT);
                $modoAfip = $ff['test'] ? ' [modo SANDBOX: CAE ficticio]' : ' [modo PRODUCCION]';
                printHtml($caeOk,
                    "Factura #{$ff['id']}: {$ff['tipocom']} {$ff['letra']} $nro | "
                    . ($caeOk
                        ? "CAE {$ff['numerocae']} (vence {$ff['fechacae']}){$modoAfip}"
                        : "SIN CAE &mdash; la autorizacion AFIP fallo"),
                    $caeOk ? '' : 'Revisar certificados en config/certs/ y el log de hoy'
                );
            } else {
                warn("No se encontro factura para pedido #{$pago['idped']}",
                     'Puede que el flujo de facturacion haya fallado. Revisar el log.');
            }
        } else {
            warn('El pago NO quedo registrado en pagos',
                 'El webhook probablemente no pudo consultar el pago en la API de MP. Revisar el log.');
        }
    }

    // ── Log de hoy ────────────────────────────────────────────────────────
    seccion('Ultimas 30 lineas del log de hoy');
    $logHoy = __DIR__ . '/../../log/wh_' . date('ymd') . '.log';
    if (file_exists($logHoy)) {
        $lineas  = file($logHoy);
        $ultimas = array_slice($lineas, -30);
        $texto   = implode('', $ultimas);
        if (!isCli()) echo "<pre>" . htmlspecialchars($texto) . "</pre>";
        else           echo $texto;
    } else {
        info("No hay log de hoy ($logHoy)");
    }
}
fin_completo:

if (!isCli()) echo "</body></html>";
