<?php
// config/correoargentino.php
// Configuración centralizada de Correo Argentino (API de sesión/token)
// NUNCA versionar este archivo con credenciales reales en producción.

// ── Entorno de pruebas ────────────────────────────────────────────────────
if (!defined('CA_URL_TEST')) {
    define('CA_URL_TEST',     'https://apisesionh.bancoroela.com.ar/auth/sesion');
}
if (!defined('CA_USUARIO_TEST')) {
    define('CA_USUARIO_TEST', 'CAMBIAR');
}
if (!defined('CA_CLAVE_TEST')) {
    define('CA_CLAVE_TEST',   'CAMBIAR');
}

// ── Entorno de producción ─────────────────────────────────────────────────
if (!defined('CA_URL_PRODUCCION')) {
    define('CA_URL_PRODUCCION',     'https://api.correoargentino.com.ar/micorreo/v1');
}
if (!defined('CA_USUARIO_PRODUCCION')) {
    define('CA_USUARIO_PRODUCCION', 'CAMBIAR');
}
if (!defined('CA_CLAVE_PRODUCCION')) {
    define('CA_CLAVE_PRODUCCION',   'CAMBIAR');
}

// ── Datos del remitente (fijos para todos los envíos) ─────────────────────
if (!defined('CA_CUSTOMER_ID')) {
    define('CA_CUSTOMER_ID',       'CAMBIAR');  // ID de cuenta MiCorreo
}
if (!defined('CA_CP_ORIGEN')) {
    define('CA_CP_ORIGEN',         '1757');         // CP del depósito/local de despacho
}
