<?php
/*
 * test_tracking.php
 * Formulario de test para el endpoint /tracking de Correo Argentino.
 * Delega toda la lógica (token + llamada a CA) a tracking.php
 */

require_once __DIR__ . '/../../config/correoargentino.php';

// ─── URL interna de la API ────────────────────────────────────────────────────
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = dirname($_SERVER['SCRIPT_NAME']);  // /stadium/api/correoarg
$api_url  = $scheme . '://' . $host . $base . '/tracking.php';

// ─── Estado inicial ───────────────────────────────────────────────────────────
$produccion   = 0;
$req_info     = null;
$raw_response = null;
$http_code    = null;
$resultado    = null;
$error        = null;

// Precargar pedido_id desde ?id= en la URL
$form = [
    'pedido_id'  => isset($_GET['id']) ? (int)$_GET['id'] : '',
    'shippingId' => '',   // solo si se quiere consultar directo sin pedido
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produccion         = (int)($_POST['produccion'] ?? 0);
    $form['pedido_id']  = trim($_POST['pedido_id']  ?? '');
    $form['shippingId'] = trim($_POST['shippingId'] ?? '');

    if ($form['pedido_id'] === '' && $form['shippingId'] === '') {
        $error = 'Ingresá un Nº de Pedido o un Shipping ID directo.';
    } else {
        // Armar body: prioridad pedido_id
        $body_arr = ['produccion' => $produccion];
        if ($form['pedido_id'] !== '') {
            $body_arr['pedido_id'] = (int)$form['pedido_id'];
        } else {
            $body_arr['shippingId'] = $form['shippingId'];
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
            if ($http_code === 200 && $decoded !== null && !isset($decoded->error)) {
                $resultado = $decoded;
                // Actualizar form con el shippingId resuelto desde el pedido
                if (!empty($decoded->shippingId)) {
                    $form['shippingId'] = $decoded->shippingId;
                }
            } else {
                $decoded_err = json_decode($raw_response, true);
                $error = isset($decoded_err['error'])
                    ? $decoded_err['error']
                    : "HTTP $http_code — $raw_response";
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// ─── Helper: formatear fecha legible ─────────────────────────────────────────
function fmtFecha(?string $f): string {
    if (!$f) return '—';
    $ts = strtotime($f);
    return $ts ? date('d/m/Y H:i', $ts) : htmlspecialchars($f);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test /tracking — Correo Argentino</title>
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
            max-width: 680px;
        }
        h1 { font-size: 1.25rem; margin: 0 0 22px; color: #1a2e4a; }
        h3 { font-size: .85rem; text-transform: uppercase; letter-spacing: .05em;
             color: #6b7280; margin: 22px 0 10px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        label { display: block; font-size: .88rem; font-weight: 600;
                margin-bottom: 4px; color: #374151; }
        .hint  { font-size: .78rem; color: #9ca3af; margin-bottom: 10px; margin-top: -8px; }
        input[type=text], select {
            width: 100%; padding: 8px 12px;
            border: 1px solid #d1d5db; border-radius: 6px;
            font-size: .93rem; outline: none; margin-bottom: 14px;
        }
        input:focus, select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        button[type=submit] {
            width: 100%; margin-top: 6px; padding: 11px;
            background: #1a2e4a; color: #fff; border: none;
            border-radius: 6px; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        button[type=submit]:hover { background: #243d60; }

        /* Debug */
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
            max-height: 300px; overflow-y: auto;
        }
        .http-method { color: #fde68a; font-weight: 700; }
        .http-url    { color: #67e8f9; }
        .key         { color: #93c5fd; }

        /* Resultado OK */
        .result-ok {
            background: #ecfdf5; border: 1px solid #6ee7b7;
            border-radius: 8px; padding: 16px 20px; margin-top: 24px;
        }
        .result-ok > strong { display: block; color: #065f46; margin-bottom: 14px; font-size: 1rem; }

        /* Info general del envío */
        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;
            margin-bottom: 18px;
        }
        .info-item { font-size: .86rem; }
        .info-item .lbl { color: #6b7280; font-size: .78rem; }
        .info-item .val { font-weight: 600; color: #111827; }

        /* Timeline de eventos */
        .timeline { list-style: none; margin: 0; padding: 0; position: relative; }
        .timeline::before {
            content: ''; position: absolute; left: 14px; top: 8px; bottom: 8px;
            width: 2px; background: #d1fae5;
        }
        .timeline li {
            display: flex; gap: 16px; align-items: flex-start;
            padding: 6px 0; position: relative;
        }
        .tl-dot {
            width: 12px; height: 12px; border-radius: 50%;
            background: #10b981; border: 2px solid #fff;
            box-shadow: 0 0 0 2px #10b981;
            margin-top: 3px; flex-shrink: 0; z-index: 1;
        }
        .tl-dot.first { background: #1a2e4a; box-shadow: 0 0 0 2px #1a2e4a; }
        .tl-body { flex: 1; }
        .tl-desc { font-size: .88rem; font-weight: 600; color: #1a2e4a; }
        .tl-meta { font-size: .78rem; color: #6b7280; margin-top: 2px; }
        .tl-place { font-size: .78rem; color: #374151; margin-top: 1px; }

        /* Badge de estado */
        .state-badge {
            display: inline-block; border-radius: 4px;
            padding: 3px 10px; font-size: .8rem; font-weight: 700;
            margin-left: 8px; vertical-align: middle;
        }
        .state-DELIVERED   { background: #d1fae5; color: #065f46; }
        .state-IN_TRANSIT  { background: #dbeafe; color: #1e40af; }
        .state-PROCESSING  { background: #fef3c7; color: #92400e; }
        .state-EXCEPTION   { background: #fee2e2; color: #991b1b; }
        .state-default     { background: #f3f4f6; color: #374151; }

        /* Error */
        .result-err {
            background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
            border-radius: 6px; padding: 14px; margin-top: 22px;
            font-size: .88rem; word-break: break-all;
        }
        .result-err strong { display: block; margin-bottom: 6px; }
        .result-warn {
            background: #fffbeb; border: 1px solid #fcd34d; color: #92400e;
            border-radius: 6px; padding: 14px; margin-top: 22px; font-size: .88rem;
        }
        .result-warn strong { display: block; margin-bottom: 6px; }
        .result-warn a { color: #b45309; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔍 Test /tracking — Correo Argentino</h1>

    <form method="POST">
        <label for="produccion">Entorno</label>
        <select name="produccion" id="produccion">
            <option value="0" <?= ($produccion == 0) ? 'selected' : '' ?>>🟡 Test / Sandbox</option>
            <option value="1" <?= ($produccion == 1) ? 'selected' : '' ?>>🔴 Producción</option>
        </select>

        <h3>Consulta de seguimiento</h3>

        <label for="pedido_id">Nº de Pedido</label>
        <p class="hint">Se buscará automáticamente el Tracking ID guardado en el pedido.</p>
        <input type="text" name="pedido_id" id="pedido_id"
               value="<?= htmlspecialchars($form['pedido_id']) ?>"
               placeholder="ej: 123">

        <div style="display:flex;align-items:center;gap:10px;margin:4px 0 14px;color:#9ca3af;font-size:.82rem;">
            <hr style="flex:1;border:none;border-top:1px dashed #d1d5db;">
            <span>o bien, directo</span>
            <hr style="flex:1;border:none;border-top:1px dashed #d1d5db;">
        </div>

        <label for="shippingId">Shipping ID directo</label>
        <p class="hint">Solo si no tenés el Nº de Pedido (ej: 1627673L5832C9C501).</p>
        <input type="text" name="shippingId" id="shippingId"
               value="<?= htmlspecialchars($form['shippingId']) ?>"
               placeholder="ej: 1627673L5832C9C501">

        <button type="submit">🔎 Consultar tracking</button>
    </form>

    <?php if ($req_info): ?>
    <div class="debug-section">
        <h2>📤 Request enviado a tracking.php</h2>
        <div class="debug-block"><span class="http-method"><?= $req_info['method'] ?></span> <span class="http-url"><?= htmlspecialchars($req_info['url']) ?></span>
<span class="key">Body:</span>
<?= htmlspecialchars(json_encode(json_decode($req_info['body']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
    </div>

    <div class="debug-section">
        <h2>📥 Respuesta de tracking.php <small style="font-weight:400;text-transform:none">(HTTP <?= $http_code ?>)</small></h2>
        <div class="debug-block"><?php
            $dec_raw = json_decode($raw_response);
            echo htmlspecialchars(
                $dec_raw !== null
                    ? json_encode($dec_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $raw_response
            );
        ?></div>
    </div>
    <?php endif; ?>

    <?php if ($resultado): ?>
    <?php
        // Detectar estado global (varios esquemas posibles de la API de CA)
        $estado   = $resultado->status        ?? $resultado->state      ?? $resultado->statusCode ?? null;
        $estadoStr = $estado ? strtoupper($estado) : null;
        $badgeClass = match(true) {
            str_contains($estadoStr ?? '', 'DELIVER') => 'state-DELIVERED',
            str_contains($estadoStr ?? '', 'TRANSIT') => 'state-IN_TRANSIT',
            str_contains($estadoStr ?? '', 'PROCESS') => 'state-PROCESSING',
            str_contains($estadoStr ?? '', 'EXCEPT')  => 'state-EXCEPTION',
            $estadoStr !== null                        => 'state-default',
            default => ''
        };

        $eventos = $resultado->events
                ?? $resultado->trackingEvents
                ?? $resultado->movements
                ?? [];
    ?>
    <div class="result-ok">
        <strong>
            ✅ Seguimiento de envío
            <?php if ($estadoStr): ?>
            <span class="state-badge <?= $badgeClass ?>"><?= htmlspecialchars($estadoStr) ?></span>
            <?php endif; ?>
        </strong>

        <!-- Info general -->
        <div class="info-grid">
            <?php if (!empty($resultado->pedido_id)): ?>
            <div class="info-item">
                <div class="lbl">Pedido</div>
                <div class="val">#<?= (int)$resultado->pedido_id ?></div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="lbl">Shipping ID (tracking)</div>
                <div class="val"><?= htmlspecialchars($resultado->shippingId ?? $form['shippingId']) ?></div>
            </div>
            <?php if (!empty($resultado->trackingNumber)): ?>
            <div class="info-item">
                <div class="lbl">Nro de tracking</div>
                <div class="val"><?= htmlspecialchars($resultado->trackingNumber) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($resultado->serviceType)): ?>
            <div class="info-item">
                <div class="lbl">Servicio</div>
                <div class="val"><?= htmlspecialchars($resultado->serviceType) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($resultado->estimatedDelivery ?? $resultado->estimatedDate ?? null)): ?>
            <div class="info-item">
                <div class="lbl">Entrega estimada</div>
                <div class="val"><?= fmtFecha($resultado->estimatedDelivery ?? $resultado->estimatedDate) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Timeline de eventos -->
        <?php if (!empty($eventos)): ?>
        <ul class="timeline">
            <?php foreach ($eventos as $i => $ev): ?>
            <li>
                <div class="tl-dot <?= $i === 0 ? 'first' : '' ?>"></div>
                <div class="tl-body">
                    <div class="tl-desc"><?= htmlspecialchars(
                        $ev->description ?? $ev->status ?? $ev->eventDescription ?? '—'
                    ) ?></div>
                    <div class="tl-meta"><?= fmtFecha(
                        $ev->date ?? $ev->eventDate ?? $ev->timestamp ?? null
                    ) ?></div>
                    <?php
                    $lugar = $ev->location ?? $ev->office ?? $ev->place ?? null;
                    if ($lugar): ?>
                    <div class="tl-place">📍 <?= htmlspecialchars(is_string($lugar) ? $lugar : json_encode($lugar)) ?></div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p style="color:#6b7280;font-size:.88rem;margin:0;">No hay eventos de seguimiento disponibles todavía.</p>
        <?php endif; ?>
    </div>

    <?php elseif ($error): ?>
    <?php
        // Detectar si es el caso específico de "sin tracking"
        $decoded_err = json_decode($raw_response ?? '', true);
        $is_no_tracking = ($http_code === 422);
        $err_pedido_id  = $decoded_err['pedido_id'] ?? null;
        $err_hint       = $decoded_err['hint']       ?? null;
    ?>
    <?php if ($is_no_tracking): ?>
    <div class="result-warn">
        <strong>⚠️ El pedido no tiene tracking asignado</strong>
        <?= htmlspecialchars($error) ?>
        <?php if ($err_pedido_id): ?>
        <p style="margin:10px 0 0;">
            <a href="/stadium/pages/pedido_form?id=<?= (int)$err_pedido_id ?>">
                ✏️ Editar pedido #<?= (int)$err_pedido_id ?> para asignar el tracking
            </a>
        </p>
        <?php endif; ?>
        <?php if ($err_hint): ?><p style="margin:6px 0 0;font-size:.8rem;color:#a16207;"><?= htmlspecialchars($err_hint) ?></p><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="result-err">
        <strong>❌ Error</strong>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
