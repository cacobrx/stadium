<?php
/*
 * token_manager.php
 * Lógica compartida de gestión de tokens de Correo Argentino
 */

if (!defined('CA_SERVICIO')) {
    define('CA_SERVICIO', 'correoarg');
}

/**
 * Devuelve un token vigente desde la BD o solicita uno nuevo a la API.
 * Usa servicios distintos para test y producción para evitar mezclarlos.
 */
function getTokenVigente(int $produccion): array {
    $db       = get_database_connection();
    $servicio = CA_SERVICIO . ($produccion ? '_prod' : '_test');

    $stmt = $db->prepare(
        "SELECT token, expire FROM token
          WHERE servicio = ? AND token != '' AND expire > DATE_ADD(NOW(), INTERVAL 1 MINUTE)
          ORDER BY expire DESC LIMIT 1"
    );
    $stmt->bind_param('s', $servicio);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['token'])) {
        return ['token' => $row['token'], 'expire' => $row['expire'], 'nuevo' => false];
    }

    // No hay token vigente → solicitar uno nuevo
    $t      = new Nuevo_token($produccion);
    $tk     = $t->getToken();
    $expire = $t->getExpire();

    if (empty($tk)) {
        throw new RuntimeException('La API de Correo Argentino no devolvió token. Respuesta: ' . $t->getRawResponse());
    }

    if (empty($expire)) {
        $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));
    } else {
        $expire = date('Y-m-d H:i:s', strtotime($expire));
    }

    $db->query("DELETE FROM token WHERE servicio = '" . $db->real_escape_string($servicio) . "'");
    $stmt = $db->prepare("INSERT INTO token (servicio, token, expire) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $servicio, $tk, $expire);
    $stmt->execute();
    $stmt->close();

    return [
        'token'  => $tk,
        'expire' => $expire,
        'nuevo'  => true,
    ];
}
