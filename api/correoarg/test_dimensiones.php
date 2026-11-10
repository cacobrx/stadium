<?php
/*
 * test_dimensiones.php
 * Formulario de test para dimensiones.php + cotización integrada
 */

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = dirname($_SERVER['SCRIPT_NAME']);
$api_url  = $scheme . '://' . $host . $base . '/dimensiones.php';

$req_body     = null;
$raw_response = null;
$http_code    = null;
$resultado    = null;
$error        = null;

// Items por defecto
$items_json    = '[{"producto_sku":"STMOL-2079","cantidad":1},{"producto_sku":"STPRO-629","cantidad":5}]';
$json_completo = "{\n    \"items\": [\n        {\"producto_sku\": \"STMOL-2079\", \"cantidad\": 1}\n    ],\n    \"produccion\": 1,\n    \"customerId\": \"0000550137\",\n    \"postalCodeOrigin\": \"1757\",\n    \"postalCodeDestination\": \"6720\"\n}";
require_once __DIR__ . '/../../config/correoargentino.php';
$customerId = CA_CUSTOMER_ID;
$cpOrigen   = CA_CP_ORIGEN;
$cpDestino  = '';
$deliveredType = '';
$produccion = 0;
$cotizar    = false;
$modo       = 'formulario';   // 'formulario' | 'json'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modo = $_POST['modo'] ?? 'formulario';

    if ($modo === 'json') {
        // Modo JSON completo: usar el body tal cual
        $json_completo = trim($_POST['json_completo'] ?? '{}');
        $parsed        = json_decode($json_completo, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'JSON inválido: ' . json_last_error_msg();
        } else {
            $req_body   = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $produccion = (int)($parsed['produccion'] ?? 0);
            $cotizar    = !empty($parsed['postalCodeDestination']);
        }
    } else {
        // Modo formulario
        $produccion    = (int)($_POST['produccion']    ?? 0);
        $items_json    = trim($_POST['items_json']     ?? '[]');
        $customerId    = trim($_POST['customerId']     ?? '');
        $cpOrigen      = trim($_POST['cpOrigen']       ?? '');
        $cpDestino     = trim($_POST['cpDestino']      ?? '');
        $deliveredType = trim($_POST['deliveredType']  ?? '');
        $cotizar       = !empty($_POST['cotizar']) && $cpDestino !== '';

        $body_arr = ['items' => json_decode($items_json, true) ?? []];
        if ($cotizar) {
            $body_arr['produccion']            = $produccion;
            $body_arr['customerId']            = $customerId;
            $body_arr['postalCodeOrigin']      = $cpOrigen;
            $body_arr['postalCodeDestination'] = $cpDestino;
            if ($deliveredType !== '') $body_arr['deliveredType'] = $deliveredType;
        }
        $req_body = json_encode($body_arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $req_body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $raw_response = curl_exec($curl);
        $http_code    = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_err     = curl_error($curl);
        curl_close($curl);

        if ($curl_err) throw new RuntimeException('cURL: ' . $curl_err);

        $resultado = json_decode($raw_response);
        if (!$resultado) $error = "Respuesta inválida: $raw_response";

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
    <title>Test Dimensiones — Correo Argentino</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f4f6f9;
               display: flex; justify-content: center; padding: 36px 16px; margin: 0; }
        .card { background: #fff; border-radius: 10px;
                box-shadow: 0 2px 14px rgba(0,0,0,.11);
                padding: 34px 38px; width: 100%; max-width: 680px; }
        h1 { font-size: 1.2rem; margin: 0 0 20px; color: #1a2e4a; }
        h3 { font-size: .82rem; text-transform: uppercase; letter-spacing: .05em;
             color: #6b7280; margin: 22px 0 10px;
             border-top: 1px solid #e5e7eb; padding-top: 14px; }
        label { display: block; font-size: .87rem; font-weight: 600;
                margin-bottom: 4px; color: #374151; }
        input, select, textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #d1d5db;
            border-radius: 6px; font-size: .92rem; outline: none; margin-bottom: 12px;
            font-family: inherit;
        }
        textarea { font-family: monospace; font-size: .82rem; resize: vertical; }
        input:focus, select:focus, textarea:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .row3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; }

        .toggle-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .toggle-row input[type=checkbox] { width: auto; margin: 0; }
        .toggle-row label { margin: 0; font-weight: 600; font-size: .88rem; }

        button[type=submit] {
            width: 100%; margin-top: 16px; padding: 11px;
            background: #1a2e4a; color: #fff; border: none;
            border-radius: 6px; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        button[type=submit]:hover { background: #243d60; }

        /* Sección cotización */
        .cotizar-section { background: #f8fafc; border: 1px solid #e2e8f0;
                           border-radius: 8px; padding: 16px; margin-top: 6px; }
        .cotizar-section.oculta { display: none; }

        /* Debug */
        .debug-section { margin-top: 26px; }
        .debug-section h2 { font-size: .8rem; font-weight: 700; text-transform: uppercase;
                            letter-spacing: .05em; color: #6b7280; margin: 0 0 6px; }
        .debug-block { background: #1e293b; color: #e2e8f0; border-radius: 6px;
                       padding: 12px 14px; font-family: monospace; font-size: .79rem;
                       white-space: pre-wrap; word-break: break-all;
                       max-height: 300px; overflow-y: auto; }
        .http-method { color: #fde68a; font-weight: 700; }
        .http-url    { color: #67e8f9; }
        .key         { color: #93c5fd; }

        /* Resultado dimensiones */
        .dim-grid { display: grid; grid-template-columns: repeat(4,1fr);
                    gap: 12px; margin-top: 16px; }
        .dim-box { background: #f0f9ff; border: 1px solid #bae6fd;
                   border-radius: 8px; padding: 14px 10px; text-align: center; }
        .dim-box .val { font-size: 1.6rem; font-weight: 800; color: #0369a1; }
        .dim-box .lbl { font-size: .75rem; color: #64748b; margin-top: 4px; }

        /* Advertencias */
        .advertencias { background: #fffbeb; border: 1px solid #fcd34d;
                        border-radius: 6px; padding: 12px 14px; margin-top: 14px;
                        font-size: .84rem; color: #92400e; }
        .advertencias ul { margin: 6px 0 0; padding-left: 18px; }

        /* Tabla items */
        .items-table { width: 100%; border-collapse: collapse; font-size: .83rem;
                       margin-top: 14px; }
        .items-table th { background: #1a2e4a; color: #fff; padding: 7px 10px;
                          text-align: left; font-weight: 600; }
        .items-table td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        .items-table tr:nth-child(even) td { background: #f9fafb; }
        .sin-dato { color: #ef4444; font-style: italic; }

        /* Rates */
        .rates-table { width: 100%; border-collapse: collapse; font-size: .87rem;
                       margin-top: 12px; }
        .rates-table th { background: #064e3b; color: #fff; padding: 8px 12px; text-align: left; }
        .rates-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .rates-table tr:nth-child(even) td { background: #f0fdf4; }
        .badge-D { background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 2px 7px; font-weight: 700; font-size: .76rem; }
        .badge-S { background: #d1fae5; color: #065f46; border-radius: 4px; padding: 2px 7px; font-weight: 700; font-size: .76rem; }

        .result-ok  { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 6px; padding: 14px; margin-top: 20px; }
        .result-err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
                      border-radius: 6px; padding: 14px; margin-top: 20px;
                      font-size: .87rem; word-break: break-all; }
        strong.block { display: block; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="card">
    <h1>📐 Test Dimensiones de Carrito — Correo Argentino</h1>

    <form method="POST" id="frm">
        <input type="hidden" name="modo" id="modo_hidden" value="<?= htmlspecialchars($modo) ?>">

        <!-- Selector de modo -->
        <div style="display:flex;gap:0;margin-bottom:18px;border:1px solid #d1d5db;border-radius:7px;overflow:hidden">
            <button type="button" id="btn_modo_form" onclick="setModo('formulario')"
                style="flex:1;padding:9px;font-size:.88rem;font-weight:600;border:none;cursor:pointer;transition:background .15s"
                class="<?= $modo==='formulario' ? 'modo-activo' : 'modo-inactivo' ?>">
                📋 Formulario
            </button>
            <button type="button" id="btn_modo_json" onclick="setModo('json')"
                style="flex:1;padding:9px;font-size:.88rem;font-weight:600;border:none;border-left:1px solid #d1d5db;cursor:pointer;transition:background .15s"
                class="<?= $modo==='json' ? 'modo-activo' : 'modo-inactivo' ?>">
                {} JSON completo
            </button>
        </div>

        <!-- MODO FORMULARIO -->
        <div id="panel_formulario" <?= $modo==='json' ? 'style="display:none"' : '' ?>>
            <h3 style="margin-top:0">Items del carrito</h3>
            <label for="items_json">Array JSON — usar <code>producto_sku</code> o <code>producto_id</code></label>
            <textarea name="items_json" id="items_json" rows="6"><?= htmlspecialchars($items_json) ?></textarea>

            <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                <button type="button" style="width:auto;padding:5px 12px;font-size:.8rem;background:#475569;color:#fff;border:none;border-radius:5px;cursor:pointer"
                    onclick="addItem()">+ Agregar ítem</button>
                <button type="button" style="width:auto;padding:5px 12px;font-size:.8rem;background:#64748b;color:#fff;border:none;border-radius:5px;cursor:pointer"
                    onclick="formatJson()">Formatear JSON</button>
            </div>

            <div class="toggle-row">
                <input type="checkbox" name="cotizar" id="cotizar" value="1"
                    <?= $cotizar && $modo==='formulario' ? 'checked' : '' ?> onchange="toggleCotizar(this)">
                <label for="cotizar">También cotizar envío</label>
            </div>

            <div class="cotizar-section <?= ($cotizar && $modo==='formulario') ? '' : 'oculta' ?>" id="cotizarSection">
            <label for="produccion">Entorno</label>
            <select name="produccion" id="produccion">
                <option value="0" <?= $produccion==0 ? 'selected' : '' ?>>🟡 Test / Sandbox</option>
                <option value="1" <?= $produccion==1 ? 'selected' : '' ?>>🔴 Producción</option>
            </select>

            <label for="customerId">Customer ID</label>
            <input type="text" name="customerId" id="customerId"
                   value="<?= htmlspecialchars($customerId) ?>">

            <div class="row2">
                <div>
                    <label for="cpOrigen">CP Origen</label>
                    <input type="text" name="cpOrigen" id="cpOrigen"
                           value="<?= htmlspecialchars($cpOrigen) ?>">
                </div>
                <div>
                    <label for="cpDestino">CP Destino</label>
                    <input type="text" name="cpDestino" id="cpDestino"
                           value="<?= htmlspecialchars($cpDestino) ?>">
                </div>
            </div>

            <label for="deliveredType">Tipo de entrega</label>
            <select name="deliveredType" id="deliveredType">
                <option value=""  <?= $deliveredType===''  ? 'selected' : '' ?>>— Ambas —</option>
                <option value="D" <?= $deliveredType==='D' ? 'selected' : '' ?>>D — Domicilio</option>
                <option value="S" <?= $deliveredType==='S' ? 'selected' : '' ?>>S — Sucursal</option>
            </select>
            </div>
        </div><!-- /panel_formulario -->

        <!-- MODO JSON COMPLETO -->
        <div id="panel_json" <?= $modo!=='json' ? 'style="display:none"' : '' ?>>
            <h3 style="margin-top:0">JSON completo del request</h3>
            <label for="json_completo">Pega aquí el objeto JSON con <code>items</code>, <code>postalCodeDestination</code>, etc.</label>
            <textarea name="json_completo" id="json_completo" rows="14"
                style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($json_completo) ?></textarea>
            <div style="display:flex;gap:8px;margin-bottom:4px">
                <button type="button" style="width:auto;padding:5px 12px;font-size:.8rem;background:#64748b;color:#fff;border:none;border-radius:5px;cursor:pointer"
                    onclick="formatJsonCompleto()">Formatear JSON</button>
            </div>
        </div><!-- /panel_json -->

        <button type="submit" onclick="return validarAntes(this)">Calcular dimensiones y cotizar</button>
    </form>

    <?php if ($req_body): ?>
    <div class="debug-section">
        <h2>📤 Request enviado a dimensiones.php</h2>
        <div class="debug-block"><span class="http-method">POST</span> <span class="http-url"><?= htmlspecialchars($api_url) ?></span>
<span class="key">Body:</span>
<?= htmlspecialchars($req_body) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($resultado && !$error): ?>
    <div class="result-ok">
        <strong class="block">📦 Dimensiones consolidadas (HTTP <?= $http_code ?>)</strong>

        <div class="dim-grid">
            <div class="dim-box">
                <div class="val"><?= number_format($resultado->dimensiones->weight ?? 0, 0, ',', '.') ?></div>
                <div class="lbl">Peso (g)</div>
            </div>
            <div class="dim-box">
                <div class="val"><?= $resultado->dimensiones->height ?? 0 ?></div>
                <div class="lbl">Alto (cm)</div>
            </div>
            <div class="dim-box">
                <div class="val"><?= $resultado->dimensiones->width ?? 0 ?></div>
                <div class="lbl">Ancho (cm)</div>
            </div>
            <div class="dim-box">
                <div class="val"><?= $resultado->dimensiones->length ?? 0 ?></div>
                <div class="lbl">Largo (cm)</div>
            </div>
        </div>

        <?php if (!empty($resultado->advertencias)): ?>
        <div class="advertencias">
            ⚠️ <strong>Advertencias:</strong>
            <ul>
            <?php foreach ($resultado->advertencias as $adv): ?>
                <li><?= htmlspecialchars($adv) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($resultado->items)): ?>
        <h3 style="margin-top:20px">Detalle de ítems</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>SKU</th><th>Nombre</th><th>Cant.</th>
                    <th>Peso unit.</th><th>Peso total</th><th>Alto</th><th>Ancho</th><th>Largo</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resultado->items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it->producto_sku ?? $it->producto_id) ?></td>
                <td><?= htmlspecialchars($it->nombre) ?></td>
                <td><?= $it->cantidad ?></td>
                <td><?= isset($it->peso_unit_kg) && $it->peso_unit_kg > 0
                        ? $it->peso_unit_kg . ' kg<br><small style="color:#64748b">' . round($it->peso_unit_g) . ' g</small>'
                        : '<span class="sin-dato">—</span>' ?></td>
                <td><?= isset($it->peso_total_g) && $it->peso_total_g > 0
                        ? '<strong>' . number_format($it->peso_total_g, 0, ',', '.') . ' g</strong>'
                        : '<span class="sin-dato">—</span>' ?></td>
                <td><?= $it->alto_cm        > 0 ? $it->alto_cm        . ' cm' : '<span class="sin-dato">—</span>' ?></td>
                <td><?= $it->ancho_cm       > 0 ? $it->ancho_cm       . ' cm' : '<span class="sin-dato">—</span>' ?></td>
                <td><?= $it->profundidad_cm > 0 ? $it->profundidad_cm . ' cm' : '<span class="sin-dato">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($resultado->rates) && in_array($resultado->rates->http_code, [200,202])
                  && !empty($resultado->rates->data->rates)): ?>
        <h3 style="margin-top:20px">💰 Cotizaciones</h3>
        <table class="rates-table">
            <thead>
                <tr><th>Entrega</th><th>Producto</th><th>Precio</th><th>Días</th></tr>
            </thead>
            <tbody>
            <?php foreach ($resultado->rates->data->rates as $r): ?>
            <tr>
                <td><span class="badge-<?= $r->deliveredType ?>">
                    <?= $r->deliveredType === 'D' ? '🏠 Domicilio' : '🏪 Sucursal' ?>
                </span></td>
                <td><?= htmlspecialchars($r->productName) ?></td>
                <td><strong>$<?= number_format($r->price, 2, ',', '.') ?></strong></td>
                <td><?= $r->deliveryTimeMin ?>–<?= $r->deliveryTimeMax ?> días</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif (!empty($resultado->rates)): ?>
        <div class="advertencias" style="margin-top:14px">
            ⚠️ Cotización HTTP <?= $resultado->rates->http_code ?>:
            <?= htmlspecialchars(json_encode($resultado->rates->data, JSON_UNESCAPED_UNICODE)) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>📥 Respuesta cruda de dimensiones.php</h2>
        <div class="debug-block"><?php
            $dec = json_decode($raw_response);
            echo htmlspecialchars(
                $dec ? json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $raw_response
            );
        ?></div>
    </div>

    <?php elseif ($error): ?>
    <div class="result-err">
        <strong class="block">❌ Error</strong>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
</div>

<style>
.modo-activo   { background: #1a2e4a; color: #fff; }
.modo-inactivo { background: #f1f5f9; color: #374151; }
</style>
<script>
function toggleCotizar(cb) {
    document.getElementById('cotizarSection').classList.toggle('oculta', !cb.checked);
}

function formatJson() {
    const ta = document.getElementById('items_json');
    try { ta.value = JSON.stringify(JSON.parse(ta.value), null, 2); }
    catch(e) { alert('JSON inválido: ' + e.message); }
}

function formatJsonCompleto() {
    const ta = document.getElementById('json_completo');
    try { ta.value = JSON.stringify(JSON.parse(ta.value), null, 2); }
    catch(e) { alert('JSON inválido: ' + e.message); }
}

function addItem() {
    const ta = document.getElementById('items_json');
    try {
        const arr = JSON.parse(ta.value);
        arr.push({ producto_sku: '', cantidad: 1 });
        ta.value = JSON.stringify(arr, null, 2);
    } catch(e) {
        ta.value = JSON.stringify([{ producto_sku: '', cantidad: 1 }], null, 2);
    }
}

function validarAntes(btn) {
    const modo = document.getElementById('modo_hidden').value;
    if (modo === 'formulario') {
        const ta = document.getElementById('items_json');
        try {
            const parsed = JSON.parse(ta.value);
            if (!Array.isArray(parsed)) {
                // Parece un objeto completo — ofrecer cambiar de modo
                if (parsed.items && Array.isArray(parsed.items)) {
                    if (confirm(
                        '⚠️ El campo "Items JSON" contiene un objeto completo, no un array.\n\n' +
                        '¿Querés pasar automáticamente al modo "JSON completo" con ese contenido?'
                    )) {
                        document.getElementById('json_completo').value = JSON.stringify(parsed, null, 2);
                        setModo('json');
                        document.getElementById('frm').submit();
                    }
                    return false;
                } else {
                    alert('❌ El campo "Items JSON" debe ser un array JSON, por ejemplo:\n[\n  {"producto_sku": "ABC-123", "cantidad": 1}\n]');
                    return false;
                }
            }
        } catch(e) {
            alert('❌ JSON inválido en el campo Items: ' + e.message);
            return false;
        }
    }
    if (modo === 'json') {
        const ta = document.getElementById('json_completo');
        try { JSON.parse(ta.value); }
        catch(e) { alert('❌ JSON inválido: ' + e.message); return false; }
    }
    return true;
}

function setModo(modo) {
    document.getElementById('modo_hidden').value = modo;
    const esJson = modo === 'json';
    document.getElementById('panel_formulario').style.display = esJson ? 'none' : '';
    document.getElementById('panel_json').style.display       = esJson ? '' : 'none';
    document.getElementById('btn_modo_form').className = esJson ? 'modo-inactivo' : 'modo-activo';
    document.getElementById('btn_modo_json').className = esJson ? 'modo-activo' : 'modo-inactivo';
}
</script>
</body>
</html>
