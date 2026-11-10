<?php
// config/mail.php
// Configuración de PHPMailer

// Detectar entorno (mismo criterio que database.php)
$is_local_mail = in_array($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost', [
    'localhost', '127.0.0.1', '::1', 'gus', 'localhost:3000', 'localhost:8080'
]) || php_sapi_name() === 'cli';

if ($is_local_mail) {
    // LOCAL: usar Mailtrap o SMTP de prueba
    define('MAIL_HOST',       'vps-5227651-x.dattaweb.com');
    define('MAIL_PORT',       465);
    define('MAIL_USERNAME',   'admin@planbsistemas.com.ar');
    define('MAIL_PASSWORD',   'tu_password');
    define('MAIL_ENCRYPTION', 'ssl');
    define('MAIL_FROM_EMAIL', 'admin@planbsistemas.com.ar');
    define('MAIL_FROM_NAME',  'Stadium (dev)');
} else {
    // PRODUCCIÓN: SMTP del hosting
    define('MAIL_HOST',       'vps-5227651-x.dattaweb.com');
    define('MAIL_PORT',       465);
    define('MAIL_USERNAME',   'admin@planbsistemas.com.ar');
    define('MAIL_PASSWORD',   'tu_password');
    define('MAIL_ENCRYPTION', 'ssl');
    define('MAIL_FROM_EMAIL', 'admin@planbsistemas.com.ar');
    define('MAIL_FROM_NAME',  'Stadium');
}
