<?php
/**
 * includes/afip.php — Autorización AFIP WSAA + WSFE (FECAESolicitar)
 *
 * Uso:
 *   require_once __DIR__ . '/afip.php';
 *   $resultado = afip_autorizar_factura($conn, $idFactura, $cuitRow);
 *
 * Si datoscuit.test = 1 → CAE ficticio (12345678) + numero correlativo en BD.
 * Si datoscuit.test = 0 → Llama WSAA + WSFE de AFIP con los certificados reales.
 *
 * Certificados de producción en: config/certs/{cuit}.pem  y  config/certs/{cuit}.key
 * El TA de WSAA se cachea en tmp/afip_ta_{cuit}.json (válido 12hs).
 */

// ── Función principal pública ─────────────────────────────────────────────────
/**
 * Autoriza una factura recién insertada (numero=0) y actualiza numero, numerocae, fechacae.
 *
 * @param  mysqli $conn      Conexión DB abierta.
 * @param  int    $idFactura ID de la factura en tabla facturas.
 * @param  array  $cuitRow   Fila de datoscuit (id, cuit, puntoventa, test).
 * @return array  ['success', 'test', 'numero', 'cae', 'fechacae']
 */
function afip_autorizar_factura(mysqli $conn, int $idFactura, array $cuitRow): array
{
    $isTest = (int)$cuitRow['test'] === 1;
    $ptovta = (int)$cuitRow['puntoventa'];
    $cuit   = $cuitRow['cuit'];
    $idcuit = (int)$cuitRow['id'];

    // Datos básicos de la factura + cliente (para DocTipo/DocNro)
    $stmt = $conn->prepare(
        "SELECT f.tipocom, f.letra, f.fecha, f.idcli,
                COALESCE(c.cuit, '') AS cli_cuit
         FROM facturas f
         LEFT JOIN clientes c ON c.id = f.idcli
         WHERE f.id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $idFactura);
    $stmt->execute();
    $fRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fRow) {
        throw new Exception("afip_autorizar_factura: Factura #{$idFactura} no encontrada");
    }

    $tipocom = $fRow['tipocom'];
    $letra   = $fRow['letra'];
    $fecha   = $fRow['fecha'] ?: date('Y-m-d');
    $cliCuit = preg_replace('/[^0-9]/', '', $fRow['cli_cuit'] ?? '');

    // Siguiente número correlativo (excluye la factura actual que tiene numero=0)
    $stmtN = $conn->prepare(
        "SELECT COALESCE(MAX(numero), 0) AS last
         FROM facturas
         WHERE ptovta = ? AND tipocom = ? AND letra = ? AND idcuit = ?
           AND id != ? AND numero > 0"
    );
    $stmtN->bind_param('issii', $ptovta, $tipocom, $letra, $idcuit, $idFactura);
    $stmtN->execute();
    $numRow          = $stmtN->get_result()->fetch_assoc();
    $stmtN->close();
    $siguienteNumero = (int)$numRow['last'] + 1;

    // ── Modo TEST ─────────────────────────────────────────────────────────────
    if ($isTest) {
        $numerocae = '12345678';
        $fechacae  = date('Y-m-d', strtotime('+10 days'));
        $numero    = $siguienteNumero;

        $stmtU = $conn->prepare(
            "UPDATE facturas SET numero = ?, numerocae = ?, fechacae = ? WHERE id = ?"
        );
        $stmtU->bind_param('issi', $numero, $numerocae, $fechacae, $idFactura);
        $stmtU->execute();
        $stmtU->close();

        return [
            'success'  => true,
            'test'     => true,
            'numero'   => $numero,
            'cae'      => $numerocae,
            'fechacae' => $fechacae,
        ];
    }

    // ── Modo PRODUCCIÓN — llamar AFIP ─────────────────────────────────────────
    $certDir  = __DIR__ . '/../config/certs/';
    $certFile = $certDir . $cuit . '.pem';
    $keyFile  = $certDir . $cuit . '.key';

    if (!file_exists($certFile) || !file_exists($keyFile)) {
        throw new Exception(
            "Certificado AFIP no encontrado para CUIT {$cuit}. " .
            "Copiá los archivos en config/certs/{$cuit}.pem y config/certs/{$cuit}.key"
        );
    }

    // WSAA: obtener Token + Sign
    [$token, $sign] = afip_wsaa_ta($cuit, $certFile, $keyFile);

    $cbteTipo = afip_cbte_tipo($tipocom, $letra);
    $wsfeUrl  = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx';

    // Número definitivo: el mayor entre BD y AFIP (evita duplicados)
    $ultimoAfip      = afip_wsfe_ultimo_numero($token, $sign, $cuit, $ptovta, $cbteTipo, $wsfeUrl);
    $numero          = max($siguienteNumero, $ultimoAfip);

    // Calcular importes desde facturas_det
    $totRow = $conn->query(
        "SELECT COALESCE(SUM(cantidad * precio), 0)                         AS neto,
                COALESCE(SUM(cantidad * precio * alicuota / 100.0), 0)      AS iva
         FROM facturas_det WHERE idfis = {$idFactura}"
    )->fetch_assoc();
    $impNeto  = round((float)$totRow['neto'], 2);
    $impIVA   = round((float)$totRow['iva'],  2);
    $impTotal = round($impNeto + $impIVA,      2);

    // DocTipo / DocNro del cliente
    $docTipo = 99; // Consumidor Final
    $docNro  = 0;
    if (strlen($cliCuit) === 11) {
        $docTipo = 80; // CUIT
        $docNro  = (int)$cliCuit;
    }

    // Solicitar CAE
    $caeData = afip_wsfe_solicitar_cae(
        $token, $sign, $cuit, $ptovta, $cbteTipo, $numero,
        $impTotal, $impNeto, $impIVA,
        $docTipo, $docNro, $fecha, $wsfeUrl
    );

    $numero    = $caeData['numero'];
    $numerocae = $caeData['cae'];
    $fechacae  = $caeData['fechacae'];

    $stmtU2 = $conn->prepare(
        "UPDATE facturas SET numero = ?, numerocae = ?, fechacae = ? WHERE id = ?"
    );
    $stmtU2->bind_param('issi', $numero, $numerocae, $fechacae, $idFactura);
    $stmtU2->execute();
    $stmtU2->close();

    return [
        'success'  => true,
        'test'     => false,
        'numero'   => $numero,
        'cae'      => $numerocae,
        'fechacae' => $fechacae,
    ];
}

// ── WSAA: Obtener Ticket de Autorización ─────────────────────────────────────
/**
 * Obtiene el TA (Token + Sign) del WSAA de AFIP.
 * Cachea el TA en /tmp/afip_ta_{cuit}.json (válido hasta 10min antes del vencimiento).
 */
function afip_wsaa_ta(string $cuit, string $certFile, string $keyFile): array
{
    $cacheFile = sys_get_temp_dir() . '/afip_ta_' . preg_replace('/[^0-9]/', '', $cuit) . '.json';

    // Intentar usar TA cacheado
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (
            !empty($cached['token']) &&
            !empty($cached['sign']) &&
            !empty($cached['expiry']) &&
            strtotime($cached['expiry']) > time() + 600
        ) {
            return [$cached['token'], $cached['sign']];
        }
    }

    // Construir LoginTicketRequest XML
    $tz   = new DateTimeZone('UTC');
    $now  = new DateTime('now', $tz);
    $from = $now->format('Y-m-d\TH:i:s');
    $now->modify('+12 hours');
    $to  = $now->format('Y-m-d\TH:i:s');
    $uid = mt_rand(1000000, 9999999);

    $xmlTicket = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
  <header>
    <uniqueId>{$uid}</uniqueId>
    <generationTime>{$from}</generationTime>
    <expirationTime>{$to}</expirationTime>
  </header>
  <service>wsfe</service>
</loginTicketRequest>
XML;

    // Firmar con PKCS7 (detached, sin atributos)
    $tmpIn  = tempnam(sys_get_temp_dir(), 'afip_in_');
    $tmpOut = tempnam(sys_get_temp_dir(), 'afip_out_');
    file_put_contents($tmpIn, $xmlTicket);

    $ok = openssl_pkcs7_sign(
        $tmpIn,
        $tmpOut,
        'file://' . realpath($certFile),
        ['file://' . realpath($keyFile), ''],
        [],
        PKCS7_DETACHED | PKCS7_NOATTR
    );

    @unlink($tmpIn);

    if (!$ok || !file_exists($tmpOut)) {
        @unlink($tmpOut);
        throw new Exception('WSAA: Error al firmar el TA con openssl_pkcs7_sign — ' . openssl_error_string());
    }

    $smime = file_get_contents($tmpOut);
    @unlink($tmpOut);

    $cmsB64 = afip_extract_cms_b64($smime);
    if (empty($cmsB64)) {
        throw new Exception('WSAA: No se pudo extraer el CMS del S/MIME firmado');
    }

    // Llamar WSAA via SOAP (non-WSDL para evitar latencia en descarga del WSDL)
    $wsaaUrl = 'https://wsaa.afip.gov.ar/ws/services/LoginCms';
    $client  = new SoapClient(null, [
        'location'          => $wsaaUrl,
        'uri'               => 'http://ar.gov.afip.dif.FEV1/',
        'soap_version'      => SOAP_1_2,
        'trace'             => false,
        'exceptions'        => true,
        'connection_timeout' => 30,
    ]);

    $resp  = $client->loginCms(['in0' => $cmsB64]);
    $xmlTA = $resp->loginCmsReturn ?? '';

    $xTA = simplexml_load_string($xmlTA);
    if (!$xTA || empty($xTA->credentials->token)) {
        throw new Exception('WSAA: Respuesta inválida — ' . htmlspecialchars($xmlTA));
    }

    $token  = (string)$xTA->credentials->token;
    $sign   = (string)$xTA->credentials->sign;
    $expiry = (string)$xTA->header->expirationTime;

    // Cachear TA
    file_put_contents($cacheFile, json_encode(compact('token', 'sign', 'expiry')));

    return [$token, $sign];
}

/**
 * Extrae el base64 del bloque de firma PKCS7 de un mensaje S/MIME (multipart/signed).
 */
function afip_extract_cms_b64(string $smime): string
{
    // 1. Intentar bloque PEM directo (si no es multipart)
    if (preg_match('/-----BEGIN PKCS7-----(.*?)-----END PKCS7-----/s', $smime, $m)) {
        return str_replace(["\r", "\n", " "], '', $m[1]);
    }

    // 2. Parsear multipart/signed: buscar la parte con Content-Type: application/x-pkcs7-signature
    $boundary = '';
    if (preg_match('/;\s*boundary\s*=\s*"?([^"\r\n;]+)"?/i', $smime, $bm)) {
        $boundary = trim($bm[1]);
    }

    if ($boundary) {
        $delim = '--' . $boundary;
        $parts = explode($delim, $smime);
        foreach ($parts as $part) {
            if (stripos($part, 'pkcs7') !== false) {
                // Separar cabeceras del cuerpo
                $pos = strpos($part, "\r\n\r\n");
                if ($pos === false) {
                    $pos = strpos($part, "\n\n");
                    $sep = "\n\n";
                } else {
                    $sep = "\r\n\r\n";
                }
                if ($pos !== false) {
                    $body = substr($part, $pos + strlen($sep));
                    // Quitar trailing boundary marker si quedó
                    $body = rtrim($body, "\r\n-");
                    return str_replace(["\r", "\n", " "], '', $body);
                }
            }
        }
    }

    // 3. Último recurso: buscar el último bloque de base64 largo en el mensaje
    preg_match_all('/\r?\n\r?\n([A-Za-z0-9+\/=\r\n]{200,})/s', $smime, $matches);
    if (!empty($matches[1])) {
        $last = end($matches[1]);
        return str_replace(["\r", "\n", " "], '', $last);
    }

    return '';
}

// ── WSFE: Último número autorizado ───────────────────────────────────────────
function afip_wsfe_ultimo_numero(
    string $token,
    string $sign,
    string $cuit,
    int    $ptovta,
    int    $cbteTipo,
    string $wsfeUrl
): int {
    $client = new SoapClient($wsfeUrl . '?wsdl', [
        'soap_version'      => SOAP_1_2,
        'trace'             => false,
        'exceptions'        => true,
        'connection_timeout' => 30,
        'cache_wsdl'        => WSDL_CACHE_DISK,
    ]);

    $cuitNum = preg_replace('/[^0-9]/', '', $cuit);
    $result  = $client->FECompUltimoAutorizado([
        'Auth'     => ['Token' => $token, 'Sign' => $sign, 'Cuit' => $cuitNum],
        'PtoVta'   => $ptovta,
        'CbteTipo' => $cbteTipo,
    ]);

    $r = $result->FECompUltimoAutorizadoResult;

    if (!empty($r->Errors)) {
        $err = $r->Errors->Err ?? $r->Errors;
        throw new Exception('WSFE FECompUltimoAutorizado: ' . json_encode($err));
    }

    return (int)($r->CbteNro ?? 0) + 1;
}

// ── WSFE: Solicitar CAE ──────────────────────────────────────────────────────
function afip_wsfe_solicitar_cae(
    string $token,
    string $sign,
    string $cuit,
    int    $ptovta,
    int    $cbteTipo,
    int    $numero,
    float  $impTotal,
    float  $impNeto,
    float  $impIVA,
    int    $docTipo,
    int    $docNro,
    string $fecha,
    string $wsfeUrl
): array {
    $client = new SoapClient($wsfeUrl . '?wsdl', [
        'soap_version'      => SOAP_1_2,
        'trace'             => false,
        'exceptions'        => true,
        'connection_timeout' => 30,
        'cache_wsdl'        => WSDL_CACHE_DISK,
    ]);

    $cuitNum   = preg_replace('/[^0-9]/', '', $cuit);
    $fechaAfip = str_replace('-', '', $fecha); // YYYYMMDD

    $req = [
        'Auth'    => ['Token' => $token, 'Sign' => $sign, 'Cuit' => $cuitNum],
        'FeCAEReq' => [
            'FeCabReq' => [
                'CantReg'  => 1,
                'PtoVta'   => $ptovta,
                'CbteTipo' => $cbteTipo,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => [
                    'Concepto'   => 1, // Productos
                    'DocTipo'    => $docTipo,
                    'DocNro'     => $docNro,
                    'CbteDesde'  => $numero,
                    'CbteHasta'  => $numero,
                    'CbteFch'    => $fechaAfip,
                    'ImpTotal'   => $impTotal,
                    'ImpTotConc' => 0.00,
                    'ImpNeto'    => $impNeto,
                    'ImpOpEx'    => 0.00,
                    'ImpIVA'     => $impIVA,
                    'ImpTrib'    => 0.00,
                    'MonId'      => 'PES',
                    'MonCotiz'   => 1,
                    'Iva'        => [
                        'AlicIva' => [
                            'Id'      => 5,         // 21%
                            'BaseImp' => $impNeto,
                            'Importe' => $impIVA,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = $client->FECAESolicitar($req);
    $det    = $result->FECAESolicitarResult->FeDetResp->FECAEDetResponse ?? null;

    if (!$det) {
        throw new Exception('WSFE FECAESolicitar: respuesta vacía o inesperada');
    }

    if ((string)$det->Resultado !== 'A') {
        $obs = $det->Observaciones->Obs ?? null;
        $msg = $obs ? ($obs->Code . ': ' . $obs->Msg) : 'Resultado: ' . $det->Resultado;
        throw new Exception("WSFE CAE rechazado — {$msg}");
    }

    $fechacae = DateTime::createFromFormat('Ymd', (string)$det->CAEFchVto);

    return [
        'numero'   => $numero,
        'cae'      => (string)$det->CAE,
        'fechacae' => $fechacae ? $fechacae->format('Y-m-d') : date('Y-m-d', strtotime('+10 days')),
    ];
}

// ── Helper: CbteTipo AFIP según tipo y letra de comprobante ──────────────────
function afip_cbte_tipo(string $tipocom, string $letra): int
{
    $letra = strtoupper(trim($letra));
    $tipo  = strtoupper($tipocom);

    $esCredito = strpos($tipo, 'CR') !== false || strpos($tipo, 'CRED') !== false;
    $esDebito  = strpos($tipo, 'D') !== false && strpos($tipo, 'DEBI') !== false;

    if ($esCredito) {
        $mapa = ['A' => 3, 'B' => 8, 'C' => 13, 'M' => 53];
        return $mapa[$letra] ?? 8;
    }
    if ($esDebito) {
        $mapa = ['A' => 2, 'B' => 7, 'C' => 12, 'M' => 52];
        return $mapa[$letra] ?? 7;
    }
    // Factura (por defecto)
    $mapa = ['A' => 1, 'B' => 6, 'C' => 11, 'M' => 51];
    return $mapa[$letra] ?? 6;
}
