<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test – validar.php</title>
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

    label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #444; }

    .input-row {
      display: flex;
      gap: 8px;
    }

    input[type="email"] {
      flex: 1;
      padding: 11px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color .2s;
      outline: none;
    }
    input[type="email"]:focus { border-color: #4361ee; }

    button {
      padding: 11px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, opacity .2s;
    }

    .btn-primary { background: #4361ee; color: #fff; }
    .btn-primary:hover { background: #3451d1; }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }

    .btn-clear {
      background: #f0f0f0;
      color: #666;
      padding: 11px 14px;
    }
    .btn-clear:hover { background: #e0e0e0; }

    /* Historial de emails recientes */
    .recientes { margin-top: 6px; }
    .recientes select {
      width: 100%;
      padding: 8px 10px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 13px;
      color: #555;
      background: #fafafa;
      cursor: pointer;
    }

    /* Respuesta */
    .result {
      margin-top: 24px;
      display: none;
    }

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
    .info-item .key  { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
    .info-item .val  { font-size: 15px; font-weight: 700; margin-top: 2px; color: #1a1a2e; }
    .info-item .val.verde  { color: #059669; }
    .info-item .val.rojo   { color: #dc2626; }
    .info-item .val.naranja { color: #d97706; }

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

    details { margin-top: 8px; }
    summary {
      font-size: 12px;
      color: #888;
      cursor: pointer;
      user-select: none;
    }
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

    .endpoint {
      font-size: 11px;
      color: #aaa;
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1px solid #eee;
      word-break: break-all;
    }
    .endpoint span { color: #4361ee; }
  </style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <h1>🔐 Test – Validación de Email</h1>
    <p>Prueba de api/auth/generar.php</p>
  </div>

  <div class="card-body">

    <label for="email">Email del cliente</label>
    <div class="input-row">
      <input type="email" id="email" placeholder="cliente@ejemplo.com" autocomplete="off">
      <button class="btn-primary" id="btnEnviar" onclick="enviar()">Enviar</button>
      <button class="btn-clear" onclick="limpiar()" title="Limpiar">✕</button>
    </div>

    <!-- Emails recientes (localStorage) -->
    <div class="recientes" id="recientesWrap" style="display:none; margin-top:8px;">
      <select id="selectRecientes" onchange="elegirReciente(this.value)">
        <option value="">▾ Emails usados recientemente...</option>
      </select>
    </div>
    <!-- Opción debug -->
    <label style="display:flex;align-items:center;gap:6px;margin-top:12px;font-weight:400;color:#888;font-size:12px;cursor:pointer;">
      <input type="checkbox" id="chkDebug" style="width:14px;height:14px;cursor:pointer;">
      Mostrar log SMTP (debug nivel 2)
    </label>
    <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-weight:400;color:#c87941;font-size:12px;cursor:pointer;">
      <input type="checkbox" id="chkDbgPhp" style="width:14px;height:14px;cursor:pointer;">
      Mostrar error PHP real (<code style="font-size:11px;">?_dbg=stadium</code>)
    </label>
    <!-- Resultado -->
    <div class="result" id="result">

      <span class="badge" id="badge"></span>

      <div class="msg-box" id="msgBox"></div>

      <div class="info-grid" id="infoGrid" style="display:none;"></div>

      <details>
        <summary>Ver JSON completo</summary>
        <pre id="jsonOut"></pre>
      </details>

      <details id="smtpDebugBlock" style="display:none; margin-top:12px;">
        <summary>Ver log SMTP (debug nivel 2)</summary>
        <pre id="smtpOut" style="background:#0d1117;color:#79c0ff;"></pre>
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
    ? 'http://localhost/stadium/api/auth/generar.php'
    : 'https://stadium.planbsistemas.com.ar/api/auth/generar.php';
  document.getElementById('endpointUrl').textContent = ENDPOINT;

  // Cargar historial al iniciar
  cargarRecientes();

  async function enviar() {
    const email = document.getElementById('email').value.trim();
    if (!email) { alert('Ingresá un email'); return; }

    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Enviando…';

    const resultDiv = document.getElementById('result');
    resultDiv.style.display = 'none';

    try {
      const params = [];
      if (document.getElementById('chkDebug').checked)   params.push('debug=1');
      if (document.getElementById('chkDbgPhp').checked)  params.push('_dbg=stadium');
      const url  = ENDPOINT + (params.length ? '?' + params.join('&') : '');
      const res  = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });

      const data = await res.json();
      mostrarResultado(data, res.status);
      guardarReciente(email);

    } catch (err) {
      mostrarResultado({ success: false, message: 'Error de red: ' + err.message }, 0);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enviar';
    }
  }

  function mostrarResultado(data, status) {
    const resultDiv       = document.getElementById('result');
    const badge           = document.getElementById('badge');
    const msgBox          = document.getElementById('msgBox');
    const infoGrid        = document.getElementById('infoGrid');
    const jsonOut         = document.getElementById('jsonOut');
    const smtpOut         = document.getElementById('smtpOut');
    const smtpDebugBlock  = document.getElementById('smtpDebugBlock');

    resultDiv.style.display = 'block';

    // Badge status
    if (data.success) {
      badge.className = 'badge ok';
      badge.textContent = '✓ OK ' + status;
      msgBox.className = 'msg-box';
    } else {
      badge.className = 'badge err';
      badge.textContent = '✗ Error ' + status;
      msgBox.className = 'msg-box err';
    }

    msgBox.textContent = data.message ?? '—';

    // Grid de info
    if (data.success && data.data) {
      infoGrid.style.display = 'grid';
      const d = data.data;

      const claseEmail = data.emailEnviado ? 'verde' : 'rojo';
      const labelEmail = data.emailEnviado ? '✓ Enviado' : '✗ No enviado';

      const errorSmtp = data.mailError
        ? `<div class="info-item" style="grid-column: span 2; background:#fff5f5;">
            <div class="key" style="color:#dc2626;">Error SMTP</div>
            <div class="val" style="font-size:12px;font-weight:500;color:#991b1b;word-break:break-word;">${data.mailError}</div>
           </div>`
        : '';

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
          <div class="key">Email</div>
          <div class="val ${claseEmail}">${labelEmail}</div>
        </div>
        ${errorSmtp}
      `;
    } else {
      infoGrid.style.display = 'none';
    }

    jsonOut.textContent = JSON.stringify(data, null, 2);

    // Log SMTP debug
    if (data.smtpDebug) {
      smtpOut.textContent  = data.smtpDebug;
      smtpDebugBlock.style.display = 'block';
      smtpDebugBlock.open  = true;
    } else {
      smtpOut.textContent  = '';
      smtpDebugBlock.style.display = 'none';
    }
  }

  function limpiar() {
    document.getElementById('email').value = '';
    document.getElementById('result').style.display = 'none';
    document.getElementById('email').focus();
  }

  // Tecla Enter en el input
  document.getElementById('email').addEventListener('keydown', e => {
    if (e.key === 'Enter') enviar();
  });

  // ── Historial en localStorage ──────────────────────────────────────────────
  function guardarReciente(email) {
    let lista = JSON.parse(localStorage.getItem('validar_emails') || '[]');
    lista = [email, ...lista.filter(e => e !== email)].slice(0, 8);
    localStorage.setItem('validar_emails', JSON.stringify(lista));
    cargarRecientes();
  }

  function cargarRecientes() {
    const lista = JSON.parse(localStorage.getItem('validar_emails') || '[]');
    const wrap  = document.getElementById('recientesWrap');
    const sel   = document.getElementById('selectRecientes');

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
    document.getElementById('email').focus();
  }
</script>

</body>
</html>
