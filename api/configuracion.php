<?php
// api/configuracion.php — Configuración general (tabla configuracion, clave/valor)
// GET            → devuelve la configuración (la clave SMTP nunca se devuelve)
// PUT            → guarda valores { clave: valor, ... }; mail_password vacío = no se modifica
// POST action=test_mail        → envía un correo de prueba con los datos del formulario
// POST action=test_presupuesto → envía el aviso de presupuesto (igual a api/presupuestos/crear.php) con datos de ejemplo
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

    $action = $body['action'] ?? '';
    if ($method === 'POST' && in_array($action, ['test_mail', 'test_presupuesto'], true)) {
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

        if ($action === 'test_presupuesto') {
            // Mismo correo que envía api/presupuestos/crear.php, con datos de ejemplo
            $origen = 1;
            $origen_desc = '';
            $stmt = $conn->prepare("SELECT descripcion FROM tablas WHERE codtab = 'ORI' AND valor = ? LIMIT 1");
            $stmt->bind_param('i', $origen);
            $stmt->execute();
            $stmt->bind_result($origen_desc);
            $stmt->fetch();
            $stmt->close();

            $nro = 'PRES-' . date('Y') . '-PRUEBA';
            $cli_nombre = 'Cliente de Prueba';
            $cli_email  = $para;
            $html = htmlPresupuestoWeb([
                'nro_presupuesto' => $nro,
                'fecha'           => date('Y-m-d'),
                'origen'          => $origen_desc ?: (string) $origen,
                'nombre'          => $cli_nombre,
                'empresa'         => 'Empresa Demo S.A.',
                'telefono'        => '+54 11 1234-5678',
                'email'           => $cli_email,
                'tipoProyecto'    => 'Cancha de fútbol 5',
                'ubicacion'       => 'Buenos Aires, Argentina',
                'requerimientos'  => "Este es un presupuesto de PRUEBA enviado desde Configuración del sistema.\nNo se registró en la base de datos.",
                'plazo'           => '30 días',
                'productos'       => [
                    ['id' => 1, 'nombre' => 'Arco de fútbol 5 reglamentario', 'cantidad' => 2],
                    ['id' => 2, 'nombre' => 'Red para arco de fútbol 5', 'cantidad' => 2],
                    ['id' => 3, 'nombre' => 'Pelota de fútbol N° 5', 'cantidad' => 10],
                ],
            ]);
            $asunto = "Nuevo presupuesto {$nro} - {$cli_nombre}";
            $r = enviarCorreo($para, $asunto, $html, $cli_email, $cli_nombre, $cfg);
        } else {
            $html = '<p>Este es un correo de prueba enviado desde <strong>Stadium</strong>.</p>'
                  . '<p>Si lo recibiste, la configuración de correo funciona correctamente.</p>';
            $r = enviarCorreo($para, 'Prueba de correo - Stadium', $html, null, null, $cfg);
        }
        responder($r['ok'] ? ['success' => true] : ['success' => false, 'error' => $r['error']]);
    }

    responder(['success' => false, 'error' => 'Método no permitido'], 405);
} catch (Throwable $e) {
    error_log('api/configuracion.php: ' . $e->getMessage());
    responder(['success' => false, 'error' => $e->getMessage()], 500);
}
