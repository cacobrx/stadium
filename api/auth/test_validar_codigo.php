<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test – validar.php (código)</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f0f2f5;
      color: #222;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 40px 16px;
    }

    .card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,.08);
      width: 100%;
      max-width: 560px;
      overflow: hidden;
    }

    .card-header {
      background: #1a1a2e;
      color: #fff;
      padding: 22px 28px;
    }
    .card-header h1 { font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    .card-header p  { font-size: 12px; color: #aab; margin-top: 4px; }

    .card-body { padding: 28px; }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
      color: #444;
    }

    .field { margin-bottom: 16px; }

    .input-row {
      display: flex;
      gap: 8px;
    }

    input[type="email"],
    input[type="text"] {
      flex: 1;
      padding: 11px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color .2s;
      outline: none;
      width: 100%;
    }
    input:focus { border-color: #4361ee; }

    /* Código: dígitos grandes */
    .codigo-wrap {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    #codigo {
      letter-spacing: 6px;
      font-size: 22px;
      font-weight: 700;
      text-align: center;
      max-width: 180px;
      flex: none;
    }

    button {
      padding: 11px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, opacity .2s;
    }
    .btn-primary  { background: #4361ee; color: #fff; white-space: nowrap; }
    .btn-primary:hover  { background: #3451d1; }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .btn-secondary { background: #e8f5e9; color: #2e7d32; }
    .btn-secondary:hover { background: #c8e6c9; }
    .btn-clear { background: #f0f0f0; color: #666; padding: 11px 14px; }
    .btn-clear:hover { background: #e0e0e0; }

    /* Historial */
    .recientes select {
      width: 100%;
      padding: 8px 10px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 13px;
      color: #555;
      background: #fafafa;
      cursor: pointer;
      margin-top: 6px;
    }

    /* Resultado */
    .result { margin-top: 24px; display: none; }

    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .badge.ok  { background: #d1fae5; color: #065f46; }
    .badge.err { background: #fee2e2; color: #991b1b; }

    .msg-box {
      background: #f4f4f4;
      border-left: 4px solid #4361ee;
      border-radius: 6px;
      padding: 10px 14px;
      font-size: 13px;
      color: #333;
      margin-bottom: 16px;
    }
    .msg-box.err { border-color: #dc2626; background: #fff5f5; color: #991b1b; }
    .msg-box.ok  { border-color: #059669; background: #f0fdf4; color: #065f46; }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 16px;
    }
    .info-item {
      background: #f8f9ff;
      border-radius: 8px;
      padding: 10px 14px;
    }
    .info-item .key { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
    .info-item .val { font-size: 15px; font-weight: 700; margin-top: 2px; color: #1a1a2e; }
    .info-item .val.verde  { color: #059669; }
    .info-item .val.rojo   { color: #dc2626; }

    details { margin-top: 8px; }
    summary { font-size: 12px; color: #888; cursor: pointer; user-select: none; }
    summary:hover { color: #4361ee; }

    pre {
      background: #1e1e2e;
      color: #cdd6f4;
      border-radius: 8px;
      padding: 14px;
      font-size: 12px;
      overflow-x: auto;
      margin-top: 8px;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .spinner {
      display: inline-block;
      width: 14px; height: 14px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .divider {
      border: none;
      border-top: 1px solid #eee;
      margin: 20px 0;
    }

    .endpoint {
      font-size: 11px;
      color: #aaa;
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1px solid #eee;
      word-break: break-all;
    }
    .endpoint span { color: #4361ee; }

    .link-generar {
      font-size: 12px;
      color: #4361ee;
      text-decoration: none;
      margin-left: 8px;
    }
    .link-generar:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <h1>🔑 Test – Validar Código</h1>
    <p>Prueba de api/auth/validar.php</p>
  </div>

  <div class="card-body">

    <!-- Email -->
    <div class="field">
      <label for="email">
        Email del cliente
        <a href="test_validar.php" class="link-generar" target="_blank">↗ Generar código</a>
      </label>
      <div class="input-row">
        <input type="email" id="email" placeholder="cliente@ejemplo.com" autocomplete="off">
        <button class="btn-clear" onclick="limpiar()" title="Limpiar">✕</button>
      </div>
      <div class="recientes">
        <select id="selectRecientes" onchange="elegirReciente(this.value)">
          <option value="">▾ Emails usados recientemente...</option>
        </select>
      </div>
    </div>

    <!-- Código -->
    <div class="field">
      <label for="codigo">Código de verificación (6 dígitos)</label>
      <div class="codigo-wrap">
        <input type="text" id="codigo" placeholder="000000" maxlength="6" inputmode="numeric" pattern="\d{6}">
        <button class="btn-primary" id="btnValidar" onclick="validar()">Validar</button>
      </div>
    </div>

    <!-- Debug PHP -->
    <label style="display:flex;align-items:center;gap:6px;margin-top:-8px;margin-bottom:8px;font-weight:400;color:#c87941;font-size:12px;cursor:pointer;">
      <input type="checkbox" id="chkDbgPhp" style="width:14px;height:14px;cursor:pointer;">
      Mostrar error PHP real (<code style="font-size:11px;">?_dbg=stadium</code>)
    </label>

    <!-- Resultado -->
    <div class="result" id="result">

      <hr class="divider">
      <span class="badge" id="badge"></span>
      <div class="msg-box" id="msgBox"></div>

      <div class="info-grid" id="infoGrid" style="display:none;"></div>

      <details>
        <summary>Ver JSON completo</summary>
        <pre id="jsonOut"></pre>
      </details>
    </div>

    <div class="endpoint">
      POST → <span id="endpointUrl"></span>
    </div>

  </div>
</div>

<script>
  const isLocal  = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
  const ENDPOINT = isLocal
    ? 'http://localhost/stadium/api/auth/validar.php'
    : 'https://stadium.planbsistemas.com.ar/api/auth/validar.php';
  document.getElementById('endpointUrl').textContent = ENDPOINT;

  cargarRecientes();

  // Enter en cualquier campo lanza la validación
  ['email', 'codigo'].forEach(id =>
    document.getElementById(id).addEventListener('keydown', e => {
      if (e.key === 'Enter') validar();
    })
  );

  // Solo números en el campo código
  document.getElementById('codigo').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
  });

  async function validar() {
    const email  = document.getElementById('email').value.trim();
    const codigo = document.getElementById('codigo').value.trim();

    if (!email)  { alert('Ingresá un email'); return; }
    if (!codigo) { alert('Ingresá el código'); return; }

    const btn = document.getElementById('btnValidar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Validando…';

    document.getElementById('result').style.display = 'none';

    try {
      const url = ENDPOINT + (document.getElementById('chkDbgPhp').checked ? '?_dbg=stadium' : '');
      const res  = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, codigo })
      });
      const data = await res.json();
      mostrarResultado(data, res.status);
      guardarReciente(email);

    } catch (err) {
      mostrarResultado({ success: false, message: 'Error de red: ' + err.message }, 0);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Validar';
    }
  }

  function mostrarResultado(data, status) {
    const resultDiv = document.getElementById('result');
    const badge     = document.getElementById('badge');
    const msgBox    = document.getElementById('msgBox');
    const infoGrid  = document.getElementById('infoGrid');
    const jsonOut   = document.getElementById('jsonOut');

    resultDiv.style.display = 'block';

    if (data.success) {
      badge.className    = 'badge ok';
      badge.textContent  = '✓ OK ' + status;
      msgBox.className   = 'msg-box ok';
    } else {
      badge.className    = 'badge err';
      badge.textContent  = '✗ Error ' + status;
      msgBox.className   = 'msg-box err';
    }
    msgBox.textContent = data.message ?? '—';

    if (data.success && data.data) {
      const d = data.data;
      infoGrid.style.display = 'grid';
      infoGrid.innerHTML = `
        <div class="info-item">
          <div class="key">Cliente</div>
          <div class="val">${d.nombre || '—'}</div>
        </div>
        <div class="info-item">
          <div class="key">ID cliente</div>
          <div class="val">#${d.idcli}</div>
        </div>
        <div class="info-item" style="grid-column: span 2;">
          <div class="key">Email verificado</div>
          <div class="val verde">✓ ${d.email}</div>
        </div>
      `;
    } else {
      infoGrid.style.display = 'none';
    }

    jsonOut.textContent = JSON.stringify(data, null, 2);
  }

  function limpiar() {
    document.getElementById('email').value  = '';
    document.getElementById('codigo').value = '';
    document.getElementById('result').style.display = 'none';
    document.getElementById('email').focus();
  }

  // ── Historial localStorage ─────────────────────────────────────────────────
  function guardarReciente(email) {
    let lista = JSON.parse(localStorage.getItem('validar_emails') || '[]');
    lista = [email, ...lista.filter(e => e !== email)].slice(0, 8);
    localStorage.setItem('validar_emails', JSON.stringify(lista));
    cargarRecientes();
  }

  function cargarRecientes() {
    const lista = JSON.parse(localStorage.getItem('validar_emails') || '[]');
    const sel   = document.getElementById('selectRecientes');
    const wrap  = sel.parentElement;

    if (!lista.length) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    sel.innerHTML = '<option value="">▾ Emails usados recientemente...</option>';
    lista.forEach(e => {
      const opt = document.createElement('option');
      opt.value = e; opt.textContent = e;
      sel.appendChild(opt);
    });
  }

  function elegirReciente(email) {
    if (!email) return;
    document.getElementById('email').value = email;
    document.getElementById('selectRecientes').value = '';
    document.getElementById('codigo').focus();
  }
</script>

</body>
</html>
