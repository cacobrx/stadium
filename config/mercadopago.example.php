<?php
// config/mercadopago.php
// Configuración centralizada de MercadoPago
// NUNCA versionar este archivo con tokens reales en producción.
// Idealmente usar variables de entorno: getenv('MP_TOKEN_SANDBOX')

// ── Tokens de acceso ──────────────────────────────────────────────────────
if (!defined('MP_TOKEN_SANDBOX')) {
    //define('MP_TOKEN_SANDBOX',    'CAMBIAR');
    define('MP_TOKEN_PRODUCTION', 'CAMBIAR');
}
if (!defined('MP_TOKEN_PRODUCTION')) {
    define('MP_TOKEN_PRODUCTION', 'CAMBIAR');
}

// ── Tokens usados por api/mercadopago/* (antes hardcodeados en esos archivos) ──
if (!defined('MP_TOKEN_SANDBOX_CHECKOUT')) {
    define('MP_TOKEN_SANDBOX_CHECKOUT', 'CAMBIAR');
}
if (!defined('MP_TOKEN_PRODUCTION_CHECKOUT')) {
    define('MP_TOKEN_PRODUCTION_CHECKOUT', 'CAMBIAR');
}
if (!defined('MP_TOKEN_SANDBOX_VINCULAR')) {
    define('MP_TOKEN_SANDBOX_VINCULAR', 'CAMBIAR');
}

// ── Webhook Secret (para verificación HMAC-SHA256) ────────────────────────
// Obtener desde: https://www.mercadopago.com/developers/panel/app → Webhooks
// Si está vacío, el webhook rechazará TODAS las notificaciones (fail-closed)
if (!defined('MP_WEBHOOK_SECRET')) {
    define('MP_WEBHOOK_SECRET', ''); // TODO: configurar con el secret real del panel de MP
}

// ── URLs base ─────────────────────────────────────────────────────────────
if (!defined('MP_APP_URL')) {
    define('MP_APP_URL', 'https://stadiumdeportes.com');
}
if (!defined('MP_BACKEND_URL')) {
    define('MP_BACKEND_URL', 'https://stadium.planbsistemas.com.ar');
}

// ── Límites de seguridad ──────────────────────────────────────────────────
// Monto máximo permitido por pedido (en ARS)
if (!defined('MP_MAX_CHECKOUT_AMOUNT')) {
    define('MP_MAX_CHECKOUT_AMOUNT', 5000000); // $5.000.000 ARS
}
// Monto mínimo (MP rechaza montos muy bajos ~$15 ARS)
if (!defined('MP_MIN_CHECKOUT_AMOUNT')) {
    define('MP_MIN_CHECKOUT_AMOUNT', 100); // $100 ARS
}

// ── Statement descriptor (nombre que ve el comprador en su resumen) ───────
if (!defined('MP_STATEMENT_DESCRIPTOR')) {
    define('MP_STATEMENT_DESCRIPTOR', 'STADIUM'); // max 22 chars
}

// ── Expiración de preferencias (en horas) ─────────────────────────────────
if (!defined('MP_PREFERENCE_EXPIRATION_HOURS')) {
    define('MP_PREFERENCE_EXPIRATION_HOURS', 24);
}

// ── Moneda ────────────────────────────────────────────────────────────────
if (!defined('MP_CURRENCY_ID')) {
    define('MP_CURRENCY_ID', 'ARS');
}

// ── Antigüedad máxima de webhook (segundos) para protección anti-replay ───
if (!defined('MP_MAX_WEBHOOK_AGE_SECONDS')) {
    define('MP_MAX_WEBHOOK_AGE_SECONDS', 300); // 5 minutos
}

/**
 * Obtener el token de acceso según el modo
 * @param string $modo 'sandbox' o 'production'
 * @return string Token de acceso de MercadoPago
 */
function mp_get_token(string $modo = 'sandbox'): string {
    return ($modo === 'production') ? MP_TOKEN_PRODUCTION : MP_TOKEN_SANDBOX;
}

/**
 * Generar las URLs de retorno para una preferencia
 * @param string $token Token único del pedido
 * @return array ['success' => ..., 'failure' => ..., 'pending' => ...]
 */
function mp_get_back_urls(string $token): array {
    $base = MP_APP_URL;
    return [
        'success' => "{$base}/checkout/success?token={$token}",
        'failure' => "{$base}/checkout/failure?token={$token}",
        'pending' => "{$base}/checkout/pending?token={$token}",
    ];
}

/**
 * Obtener la URL de notificación del webhook
 * @return string URL completa del webhook
 */
function mp_get_notification_url(): string {
    return MP_BACKEND_URL . '/api/pedidos/webhook.php';
}
