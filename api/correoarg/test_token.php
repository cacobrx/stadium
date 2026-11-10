<?php
/*
 * test_token.php
 * Formulario de ejemplo para obtener un token de Correo Argentino
 */
require_once __DIR__ . '/token.php';

$resultado    = null;
$error        = null;
$req_info     = null;
$raw_response = null;
$produccion   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produccion = isset($_POST['produccion']) ? (int)$_POST['produccion'] : 0;
    try {
        $t  = new Nuevo_token($produccion);
        $req_info     = $t->getRequestInfo();
        $raw_response = $t->getRawResponse();
        $tk = $t->getToken();
        if ($tk) {
            $resultado = $tk;
        } else {
            $error = 'La API no devolvió un token. Verificar credenciales y URL.';
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
    <title>Test Token — Correo Argentino</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: system-ui, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 16px;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.12);
            padding: 36px 40px;
            width: 100%;
            max-width: 540px;
        }
        h1 { font-size: 1.3rem; margin: 0 0 24px; color: #1a2e4a; }
        label { display: block; font-size: .9rem; font-weight: 600; margin-bottom: 6px; color: #374151; }
        select, button {
            width: 100%; padding: 10px 14px;
            border: 1px solid #d1d5db; border-radius: 6px;
            font-size: .95rem; outline: none;
        }
        select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        button {
            margin-top: 18px; background: #1a2e4a; color: #fff;
            border: none; cursor: pointer; font-weight: 600;
            transition: background .2s;
        }
        button:hover { background: #243d60; }

        /* Resultado */
        .result {
            margin-top: 24px; padding: 16px;
            border-radius: 6px; font-size: .88rem; word-break: break-all;
        }
        .result.ok  { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
        .result.err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        .token-label { font-weight: 700; margin-bottom: 8px; }
        .token-value {
            font-family: monospace; background: #d1fae5;
            border-radius: 4px; padding: 10px; display: block;
            margin-top: 6px; user-select: all;
        }
        .copy-btn {
            margin-top: 10px; width: auto; padding: 6px 14px;
            background: #059669; font-size: .82rem;
        }
        .copy-btn:hover { background: #047857; }
        .env-badge {
            display: inline-block; font-size: .75rem; font-weight: 700;
            border-radius: 999px; padding: 2px 10px; margin-bottom: 12px;
        }
        .env-badge.test { background: #fef9c3; color: #854d0e; }
        .env-badge.prod { background: #fee2e2; color: #991b1b; }

        /* Paneles de debug */
        .debug-section { margin-top: 28px; }
        .debug-section h2 {
            font-size: .85rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; color: #6b7280; margin: 0 0 8px;
        }
        .debug-block {
            background: #1e293b; color: #e2e8f0;
            border-radius: 6px; padding: 14px 16px;
            font-family: monospace; font-size: .82rem;
            white-space: pre-wrap; word-break: break-all;
            max-height: 260px; overflow-y: auto;
        }
        .debug-block .key   { color: #93c5fd; }
        .debug-block .str   { color: #86efac; }
        .debug-block .num   { color: #fca5a5; }
        .http-row { margin-bottom: 4px; }
        .http-method { color: #fde68a; font-weight: 700; }
        .http-url    { color: #67e8f9; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔑 Obtener Token — Correo Argentino</h1>

    <form method="POST">
        <label for="produccion">Entorno</label>
        <select name="produccion" id="produccion">
            <option value="0" <?= ($produccion == 0 && $_SERVER['REQUEST_METHOD'] === 'POST') || $_SERVER['REQUEST_METHOD'] !== 'POST' ? 'selected' : '' ?>>
                🟡 Test / Sandbox
            </option>
            <option value="1" <?= $produccion == 1 ? 'selected' : '' ?>>
                🔴 Producción
            </option>
        </select>

        <button type="submit">Solicitar Token</button>
    </form>

    <?php if ($req_info !== null): ?>
    <div class="debug-section">
        <h2>📤 Request enviado</h2>
        <div class="debug-block"><span class="http-method"><?= htmlspecialchars($req_info['method']) ?></span> <span class="http-url"><?= htmlspecialchars($req_info['url']) ?></span>
<span class="key">Headers:</span>
<?php foreach ($req_info['headers'] as $h): ?><?= htmlspecialchars('  ' . $h) ?>
<?php endforeach; ?>
<?php if (!empty($req_info['body'])): ?>
<span class="key">Body:</span>
<?= htmlspecialchars(json_encode(json_decode($req_info['body']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?>
<?php endif; ?></div>
    </div>

    <div class="debug-section">
        <h2>📥 Respuesta cruda de la API</h2>
        <div class="debug-block"><?php
            $decoded = json_decode($raw_response);
            echo htmlspecialchars(
                $decoded !== null
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $raw_response
            );
        ?></div>
    </div>
    <?php endif; ?>

    <?php if ($resultado !== null): ?>
    <div class="result ok">
        <span class="env-badge <?= $produccion ? 'prod' : 'test' ?>">
            <?= $produccion ? 'PRODUCCIÓN' : 'TEST' ?>
        </span>
        <div class="token-label">✅ Token obtenido:</div>
        <code class="token-value" id="token-val"><?= htmlspecialchars($resultado) ?></code>
        <button class="copy-btn" onclick="copiar()">📋 Copiar token</button>
    </div>
    <?php elseif ($error !== null): ?>
    <div class="result err">
        <div class="token-label">❌ Error:</div>
        <p style="margin:6px 0 0"><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
function copiar() {
    const val = document.getElementById('token-val').textContent.trim();
    navigator.clipboard.writeText(val).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '✅ Copiado';
        setTimeout(() => btn.textContent = '📋 Copiar token', 2000);
    });
}
</script>
</body>
</html>
