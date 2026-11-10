<?php
// api/configuracion.php — Configuración general (tabla configuracion, clave/valor)
// GET            → devuelve la configuración (la clave SMTP nunca se devuelve)
// PUT            → guarda valores { clave: valor, ... }; mail_password vacío = no se modifica
// POST action=test_mail → envía un correo de prueba con los datos del formulario
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json');

requireAuthAPI();

const CONFIG_CLAVES = [
    'mail_host', 'mail_port', 'mail_username', 'mail_password',
    'mail_encryption', 'mail_from_email', 'mail_from_name', 'mail_presupuestos_destino',
];

function responder(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/** Valida y normaliza los datos recibidos. Devuelve [valores, error]. */
function validarConfig(array $in): array {
    $out = [];
    foreach (CONFIG_CLAVES as $k) {
        if (array_key_exists($k, $in)) {
            $out[$k] = trim((string) $in[$k]);
        }
    }
    if (isset($out['mail_port']) && $out['mail_port'] !== '' && (!ctype_digit($out['mail_port']) || (int) $out['mail_port'] < 1 || (int) $out['mail_port'] > 65535)) {
        return [null, 'Puerto inválido'];
    }
    if (isset($out['mail_encryption']) && !in_array($out['mail_encryption'], ['ssl', 'tls', 'none'], true)) {
        return [null, 'Seguridad inválida (ssl, tls o none)'];
    }
    foreach (['mail_from_email', 'mail_presupuestos_destino'] as $k) {
        if (!empty($out[$k]) && !filter_var($out[$k], FILTER_VALIDATE_EMAIL)) {
            return [null, 'Email inválido: ' . $out[$k]];
        }
    }
    return [$out, null];
}

try {
    $conn = get_database_connection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $config = obtenerConfiguracion($conn);
        $tienePassword = !empty($config['mail_password']);
        unset($config['mail_password']);
        responder(['success' => true, 'config' => $config, 'mail_password_cargada' => $tienePassword]);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($method === 'PUT') {
        [$valores, $error] = validarConfig($body);
        if ($error) {
            responder(['success' => false, 'error' => $error], 422);
        }
        // Clave vacía = mantener la actual
        if (isset($valores['mail_password']) && $valores['mail_password'] === '') {
            unset($valores['mail_password']);
        }
        $stmt = $conn->prepare(
            "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($valores as $clave => $valor) {
            $stmt->bind_param('ss', $clave, $valor);
            $stmt->execute();
        }
        $stmt->close();
        responder(['success' => true]);
    }

    if ($method === 'POST' && ($body['action'] ?? '') === 'test_mail') {
        $para = trim($body['para'] ?? '');
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            responder(['success' => false, 'error' => 'Email de destino inválido'], 422);
        }
        [$valores, $error] = validarConfig($body['config'] ?? []);
        if ($error) {
            responder(['success' => false, 'error' => $error], 422);
        }
        // Configuración actual + valores del formulario (sin guardar)
        $cfg = obtenerConfigMail($conn);
        $map = [
            'mail_host' => 'host', 'mail_port' => 'port', 'mail_username' => 'username',
            'mail_password' => 'password', 'mail_encryption' => 'encryption',
            'mail_from_email' => 'from_email', 'mail_from_name' => 'from_name',
        ];
        foreach ($map as $clave => $campo) {
            if (isset($valores[$clave]) && ($valores[$clave] !== '' || $clave !== 'mail_password')) {
                $cfg[$campo] = $valores[$clave];
            }
        }
        $html = '<p>Este es un correo de prueba enviado desde <strong>Stadium</strong>.</p>'
              . '<p>Si lo recibiste, la configuración de correo funciona correctamente.</p>';
        $r = enviarCorreo($para, 'Prueba de correo - Stadium', $html, null, null, $cfg);
        responder($r['ok'] ? ['success' => true] : ['success' => false, 'error' => $r['error']]);
    }

    responder(['success' => false, 'error' => 'Método no permitido'], 405);
} catch (Throwable $e) {
    error_log('api/configuracion.php: ' . $e->getMessage());
    responder(['success' => false, 'error' => $e->getMessage()], 500);
}
