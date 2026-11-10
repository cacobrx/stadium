<?php
/*
 * test_rates.php
 * Formulario de test para el endpoint /rates de Correo Argentino
 * Delega toda la lógica (token + llamada a CA) a rates.php
 */

require_once __DIR__ . '/../../config/correoargentino.php';

// ─── URL interna de la API ────────────────────────────────────────────────────
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = dirname($_SERVER['SCRIPT_NAME']);   // /stadium/api/correoarg
$api_url  = $scheme . '://' . $host . $base . '/rates.php';

// ─── Estado inicial ───────────────────────────────────────────────────────────
$produccion   = 0;
$req_info     = null;
$raw_response = null;
$http_code    = null;
$resultado    = null;
$error        = null;

$form = [
    'customerId'             => CA_CUSTOMER_ID,
    'postalCodeOrigin'       => CA_CP_ORIGEN,
    'postalCodeDestination'  => '1704',
    'deliveredType'          => '',
    'weight'                 => 2500,
    'height'                 => 10,
    'width'                  => 20,
    'length'                 => 30,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produccion = (int)($_POST['produccion'] ?? 0);

    $form['customerId']            = trim($_POST['customerId']            ?? '');
    $form['postalCodeOrigin']      = trim($_POST['postalCodeOrigin']      ?? '');
    $form['postalCodeDestination'] = trim($_POST['postalCodeDestination'] ?? '');
    $form['deliveredType']         = trim($_POST['deliveredType']         ?? '');
    $form['weight']                = (int)($_POST['weight']  ?? 0);
    $form['height']                = (int)($_POST['height']  ?? 0);
    $form['width']                 = (int)($_POST['width']   ?? 0);
    $form['length']                = (int)($_POST['length']  ?? 0);

    // Construir JSON para rates.php
    $body_arr = [
        'produccion'            => $produccion,
        'customerId'            => $form['customerId'],
        'postalCodeOrigin'      => $form['postalCodeOrigin'],
        'postalCodeDestination' => $form['postalCodeDestination'],
        'dimensions'            => [
            'weight' => $form['weight'],
            'height' => $form['height'],
            'width'  => $form['width'],
            'length' => $form['length'],
        ],
    ];
    if ($form['deliveredType'] !== '') {
        $body_arr['deliveredType'] = $form['deliveredType'];
    }
    $body = json_encode($body_arr, JSON_UNESCAPED_UNICODE);

    $req_info = [
        'url'    => $api_url,
        'method' => 'POST',
        'body'   => $body,
    ];

    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $raw_response = curl_exec($curl);
        $http_code    = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_err     = curl_error($curl);
        curl_close($curl);

        if ($curl_err) {
            throw new RuntimeException('Error cURL: ' . $curl_err);
        }

        $decoded = json_decode($raw_response);
        if ($http_code === 200 && $decoded && isset($decoded->rates)) {
            $resultado = $decoded;
        } else {
            $error = "HTTP $http_code — " . $raw_response;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test /rates — Correo Argentino</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: system-ui, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            padding: 36px 16px;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 14px rgba(0,0,0,.11);
            padding: 34px 38px;
            width: 100%;
            max-width: 620px;
        }
        h1 { font-size: 1.25rem; margin: 0 0 22px; color: #1a2e4a; }
        h3 { font-size: .85rem; text-transform: uppercase; letter-spacing: .05em;
             color: #6b7280; margin: 22px 0 10px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        label { display: block; font-size: .88rem; font-weight: 600;
                margin-bottom: 4px; color: #374151; }
        input[type=text], input[type=number], select {
            width: 100%; padding: 8px 12px;
            border: 1px solid #d1d5db; border-radius: 6px;
            font-size: .93rem; outline: none; margin-bottom: 12px;
        }
        input:focus, select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .row4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
        button[type=submit] {
            width: 100%; margin-top: 18px; padding: 11px;
            background: #1a2e4a; color: #fff; border: none;
            border-radius: 6px; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        button[type=submit]:hover { background: #243d60; }

        /* Token badge */
        .token-badge {
            display: flex; align-items: center; gap: 10px;
            background: #f0fdf4; border: 1px solid #86efac;
            border-radius: 6px; padding: 10px 14px;
            font-size: .83rem; color: #065f46; margin-bottom: 20px;
        }
        .token-badge.nuevo { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
        .token-badge .dot { width: 10px; height: 10px; border-radius: 50%;
                            background: #22c55e; flex-shrink: 0; }
        .token-badge.nuevo .dot { background: #f59e0b; }
        .token-expire { font-family: monospace; margin-left: auto; font-size: .8rem; opacity: .8; }

        /* Paneles debug */
        .debug-section { margin-top: 26px; }
        .debug-section h2 {
            font-size: .82rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; color: #6b7280; margin: 0 0 7px;
        }
        .debug-block {
            background: #1e293b; color: #e2e8f0;
            border-radius: 6px; padding: 13px 15px;
            font-family: monospace; font-size: .8rem;
            white-space: pre-wrap; word-break: break-all;
            max-height: 280px; overflow-y: auto;
        }
        .http-method { color: #fde68a; font-weight: 700; }
        .http-url    { color: #67e8f9; }
        .key         { color: #93c5fd; }

        /* Tabla de resultados */
        .rates-table {
            width: 100%; border-collapse: collapse;
            margin-top: 14px; font-size: .88rem;
        }
        .rates-table th {
            background: #1a2e4a; color: #fff;
            padding: 8px 12px; text-align: left; font-weight: 600;
        }
        .rates-table td {
            padding: 8px 12px; border-bottom: 1px solid #e5e7eb;
        }
        .rates-table tr:last-child td { border-bottom: none; }
        .rates-table tr:nth-child(even) td { background: #f9fafb; }
        .badge-D { background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 2px 8px; font-weight: 700; font-size: .78rem; }
        .badge-S { background: #d1fae5; color: #065f46; border-radius: 4px; padding: 2px 8px; font-weight: 700; font-size: .78rem; }

        .result-ok  { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46;
                      border-radius: 6px; padding: 14px; margin-top: 22px; }
        .result-err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
                      border-radius: 6px; padding: 14px; margin-top: 22px;
                      font-size: .88rem; word-break: break-all; }
        .result-err strong { display: block; margin-bottom: 6px; }
        .validTo { font-size: .8rem; color: #6b7280; margin-top: 8px; }
    </style>
</head>
<body>
<div class="card">
    <h1>📦 Test /rates — Correo Argentino</h1>

    <form method="POST">
        <label for="produccion">Entorno</label>
        <select name="produccion" id="produccion">
            <option value="0" <?= ($produccion == 0) ? 'selected' : '' ?>>🟡 Test / Sandbox</option>
            <option value="1" <?= ($produccion == 1) ? 'selected' : '' ?>>🔴 Producción</option>
        </select>

        <h3>Datos del envío</h3>

        <label for="customerId">Customer ID</label>
        <input type="text" name="customerId" id="customerId"
               value="<?= htmlspecialchars($form['customerId']) ?>" required>

        <div class="row2">
            <div>
                <label for="postalCodeOrigin">CP Origen</label>
                <input type="text" name="postalCodeOrigin" id="postalCodeOrigin"
                       value="<?= htmlspecialchars($form['postalCodeOrigin']) ?>" required>
            </div>
            <div>
                <label for="postalCodeDestination">CP Destino</label>
                <input type="text" name="postalCodeDestination" id="postalCodeDestination"
                       value="<?= htmlspecialchars($form['postalCodeDestination']) ?>" required>
            </div>
        </div>

        <label for="deliveredType">Tipo de entrega</label>
        <select name="deliveredType" id="deliveredType">
            <option value=""  <?= $form['deliveredType'] === ''  ? 'selected' : '' ?>>— Ambas opciones —</option>
            <option value="D" <?= $form['deliveredType'] === 'D' ? 'selected' : '' ?>>D — Domicilio</option>
            <option value="S" <?= $form['deliveredType'] === 'S' ? 'selected' : '' ?>>S — Sucursal</option>
        </select>

        <h3>Dimensiones</h3>
        <div class="row4">
            <div>
                <label for="weight">Peso (g)</label>
                <input type="number" name="weight" id="weight"
                       value="<?= $form['weight'] ?>" min="1" max="25000" required>
            </div>
            <div>
                <label for="height">Alto (cm)</label>
                <input type="number" name="height" id="height"
                       value="<?= $form['height'] ?>" min="1" max="150" required>
            </div>
            <div>
                <label for="width">Ancho (cm)</label>
                <input type="number" name="width" id="width"
                       value="<?= $form['width'] ?>" min="1" max="150" required>
            </div>
            <div>
                <label for="length">Largo (cm)</label>
                <input type="number" name="length" id="length"
                       value="<?= $form['length'] ?>" min="1" max="150" required>
            </div>
        </div>

        <button type="submit">Cotizar envío</button>
    </form>

    <?php if ($req_info): ?>
    <div class="debug-section">
        <h2>📤 Request enviado a rates.php</h2>
        <div class="debug-block"><span class="http-method"><?= $req_info['method'] ?></span> <span class="http-url"><?= htmlspecialchars($req_info['url']) ?></span>
<span class="key">Body:</span>
<?= htmlspecialchars(json_encode(json_decode($req_info['body']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
    </div>

    <div class="debug-section">
        <h2>📥 Respuesta de rates.php <small style="font-weight:400;text-transform:none">(HTTP <?= $http_code ?>)</small></h2>
        <div class="debug-block"><?php
            $dec = json_decode($raw_response);
            echo htmlspecialchars(
                $dec !== null
                    ? json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $raw_response
            );
        ?></div>
    </div>
    <?php endif; ?>

    <?php if ($resultado): ?>
    <div class="result-ok">
        <strong>✅ Cotizaciones recibidas</strong>
        <table class="rates-table">
            <thead>
                <tr>
                    <th>Entrega</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Días (min–max)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resultado->rates as $rate): ?>
                <tr>
                    <td><span class="badge-<?= htmlspecialchars($rate->deliveredType) ?>">
                        <?= $rate->deliveredType === 'D' ? '🏠 Domicilio' : '🏪 Sucursal' ?>
                    </span></td>
                    <td><?= htmlspecialchars($rate->productName) ?></td>
                    <td><strong>$<?= number_format($rate->price, 2, ',', '.') ?></strong></td>
                    <td><?= htmlspecialchars($rate->deliveryTimeMin) ?>–<?= htmlspecialchars($rate->deliveryTimeMax) ?> días</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!empty($resultado->validTo)): ?>
        <p class="validTo">Cotización válida hasta: <?= htmlspecialchars($resultado->validTo) ?></p>
        <?php endif; ?>
    </div>
    <?php elseif ($error): ?>
    <div class="result-err">
        <strong>❌ Error</strong>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
