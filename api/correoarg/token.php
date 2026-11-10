<?php
/*
 * Creado el 02/03/2026 13:11
 * Autor: gus
 * Archivo: token.php
 */
class Nuevo_token {
    var $token        = "";
    var $expire="";
    var $request_info = [];   // parámetros enviados
    var $raw_response = "";   // respuesta cruda de la API

    function __construct($produccion=0) {
        require_once __DIR__ . '/../../config/correoargentino.php';

        if ($produccion == 0) {
            $url     = CA_URL_TEST;
            $usuario = CA_USUARIO_TEST;
            $clave   = CA_CLAVE_TEST;
        } else {
            $url     = CA_URL_PRODUCCION;
            $usuario = CA_USUARIO_PRODUCCION;
            $clave   = CA_CLAVE_PRODUCCION;
        }

        $endpoint = rtrim($url, '/') . '/token';

        $this->request_info = [
            'url'     => $endpoint,
            'method'  => 'POST',
            'headers' => ['Accept: application/json', 'Authorization: Basic ' . base64_encode($usuario . ':' . str_repeat('*', strlen($clave)))],
            'body'    => '(sin cuerpo — credenciales en Basic Auth)',
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_USERPWD        => $usuario . ':' . $clave,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $this->raw_response = $response;
        $dd = json_decode($response);
        $this->token = $dd->token ?? '';
        $this->expire = $dd->expire ?? '';
    }

    function getToken()       { return $this->token; }
    function getExpire()      { return $this->expire; }
    function getRequestInfo() { return $this->request_info; }
    function getRawResponse() { return $this->raw_response; }
}
