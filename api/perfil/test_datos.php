<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test – Perfil / datos.php</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f0f2f5;
      color: #222;
      min-height: 100vh;
      padding: 40px 16px;
    }

    h2 { font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #333; }

    .layout {
      display: flex;
      gap: 20px;
      max-width: 1100px;
      margin: 0 auto;
      align-items: flex-start;
    }

    /* Panel izquierdo */
    .panel-left  { flex: 0 0 380px; }
    .panel-right { flex: 1; min-width: 0; }

    .card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,.08);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .card-header {
      background: #1a1a2e;
      color: #fff;
      padding: 20px 26px;
    }
    .card-header h1 { font-size: 17px; font-weight: 700; letter-spacing: .5px; }
    .card-header p  { font-size: 12px; color: #aab; margin-top: 4px; }

    .card-body { padding: 24px 26px; }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
      color: #444;
    }
    .field { margin-bottom: 16px; }

    input[type="text"] {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color .2s;
      outline: none;
    }
    input[type="text"]:focus { border-color: #4361ee; }

    select {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      background: #fff;
      outline: none;
    }

    .btn-row { display: flex; gap: 10px; margin-top: 4px; }

    button {
      padding: 11px 18px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, opacity .2s;
    }
    .btn-primary   { background: #4361ee; color: #fff; flex: 1; }
    .btn-primary:hover   { background: #3451d1; }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .btn-clear     { background: #f0f0f0; color: #666; }
    .btn-clear:hover { background: #e0e0e0; }

    .hint {
      font-size: 12px;
      color: #888;
      margin-top: 5px;
      font-style: italic;
    }

    /* Info: método de envío */
    .method-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .5px;
      background: #e8eaff;
      color: #4361ee;
      margin-left: 8px;
    }

    /* Status bar */
    .status-bar {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 16px;
      display: none;
    }
    .status-ok    { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .status-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
    .status-wait  { background: #fff8e1; color: #e65100; border: 1px solid #ffcc80; }

    /* Resultado JSON */
    .json-box {
      background: #1e1e2e;
      color: #cdd6f4;
      border-radius: 10px;
      padding: 18px 20px;
      font-family: 'Courier New', monospace;
      font-size: 13px;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-all;
      overflow-x: auto;
      min-height: 200px;
      max-height: 520px;
      overflow-y: auto;
    }
    .json-box.empty { color: #585b70; font-style: italic; }

    /* Colores JSON */
    .jkey   { color: #89b4fa; }
    .jstr   { color: #a6e3a1; }
    .jnum   { color: #fab387; }
    .jbool  { color: #cba6f7; }
    .jnull  { color: #f38ba8; }

    /* Tarjetas de datos estructurados */
    .data-section { margin-bottom: 16px; }
    .data-section h3 {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #888;
      margin-bottom: 10px;
      padding-bottom: 6px;
      border-bottom: 1px solid #f0f0f0;
    }

    .data-grid {
      display: grid;
      grid-template-columns: 140px 1fr;
      gap: 8px 12px;
      font-size: 13px;
    }
    .dkey { color: #888; font-weight: 500; }
    .dval { color: #222; font-weight: 600; word-break: break-all; }
    .dval.ok   { color: #2e7d32; }
    .dval.warn { color: #e65100; }

    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }
    .badge-green { background: #e8f5e9; color: #2e7d32; }
    .badge-red   { background: #ffebee; color: #c62828; }
    .badge-blue  { background: #e3f2fd; color: #1565c0; }

    .dir-card {
      border: 1px solid #e8eaed;
      border-radius: 8px;
      padding: 12px 14px;
      margin-bottom: 8px;
      font-size: 13px;
    }
    .dir-card .dir-label {
      font-weight: 700;
      color: #333;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }
    .stat-item {
      text-align: center;
      background: #f8f9ff;
      border-radius: 8px;
      padding: 12px 8px;
    }
    .stat-item .val { font-size: 20px; font-weight: 800; color: #4361ee; }
    .stat-item .lbl { font-size: 11px; color: #888; margin-top: 3px; }

    /* Historial */
    .history-list { list-style: none; }
    .history-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 10px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      border: 1px solid transparent;
      margin-bottom: 4px;
      transition: background .15s;
    }
    .history-list li:hover { background: #f5f6ff; border-color: #e0e3ff; }
    .history-list li .token-text { font-family: monospace; color: #444; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .history-list li .ts { color: #aaa; font-size: 11px; white-space: nowrap; margin-left: 8px; }

    /* Tabs */
    .tabs { display: flex; gap: 4px; margin-bottom: 16px; }
    .tab {
      padding: 8px 16px;
      border-radius: 8px 8px 0 0;
      border: none;
      background: #e8eaed;
      color: #666;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .tab.active { background: #fff; color: #4361ee; box-shadow: 0 -2px 0 #4361ee inset; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .copy-btn {
      float: right;
      padding: 5px 12px;
      font-size: 11px;
      border-radius: 6px;
      background: #2a2a3e;
      color: #cdd6f4;
      border: 1px solid #44475a;
      cursor: pointer;
    }
    .copy-btn:hover { background: #44475a; }

    .url-display {
      background: #f5f6ff;
      border: 1px solid #e0e3ff;
      border-radius: 6px;
      padding: 8px 12px;
      font-family: monospace;
      font-size: 12px;
      color: #4361ee;
      word-break: break-all;
      margin-bottom: 14px;
    }

    @media (max-width: 768px) {
      .layout { flex-direction: column; }
      .panel-left { flex: none; width: 100%; }
      .stat-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<div class="layout">

  <!-- Panel izquierdo: formulario -->
  <div class="panel-left">
    <div class="card">
      <div class="card-header">
        <h1>🧪 Test — Perfil / datos.php</h1>
        <p>GET /api/perfil/datos.php</p>
      </div>
      <div class="card-body">

        <div class="field">
          <label>Token del cliente</label>
          <input type="text" id="token" placeholder="Ingresá el token del cliente..." autocomplete="off">
          <p class="hint">Se envía via campo <code>token</code> en el método seleccionado.</p>
        </div>

        <div class="field">
          <label>Método de envío <span id="methodBadge" class="method-badge">GET</span></label>
          <select id="method" onchange="updateBadge()">
            <option value="GET">GET — query string (?token=...)</option>
            <option value="POST_FORM">POST — form-urlencoded</option>
            <option value="POST_JSON">POST — JSON body</option>
            <option value="BEARER">Authorization: Bearer token</option>
          </select>
        </div>

        <div class="url-display" id="urlDisplay">http://localhost/stadium/api/perfil/datos.php</div>

        <div class="btn-row">
          <button class="btn-primary" id="btnEnviar" onclick="enviar()">▶ Consultar perfil</button>
          <button class="btn-clear" onclick="limpiar()">✕ Limpiar</button>
        </div>

      </div>
    </div>

    <!-- Historial -->
    <div class="card">
      <div class="card-header" style="padding:16px 26px;">
        <h1 style="font-size:14px;">🕑 Historial de consultas</h1>
      </div>
      <div class="card-body" style="padding:16px;">
        <ul class="history-list" id="historyList">
          <li style="color:#aaa; font-size:12px; cursor:default;">Sin consultas aún.</li>
        </ul>
        <button class="btn-clear" style="width:100%;margin-top:8px;font-size:12px;" onclick="limpiarHistorial()">Borrar historial</button>
      </div>
    </div>
  </div>

  <!-- Panel derecho: resultado -->
  <div class="panel-right">
    <div id="statusBar" class="status-bar"></div>

    <div class="tabs">
      <button class="tab active" onclick="switchTab('visual')">📊 Vista estructurada</button>
      <button class="tab" onclick="switchTab('json')">{ } JSON raw</button>
    </div>

    <!-- Tab: Vista estructurada -->
    <div id="tabVisual" class="tab-content active">
      <div class="card">
        <div class="card-body" id="visualBody">
          <p style="color:#aaa; font-size:13px; text-align:center; padding:40px 0;">
            Realizá una consulta para ver los resultados aquí.
          </p>
        </div>
      </div>
    </div>

    <!-- Tab: JSON raw -->
    <div id="tabJson" class="tab-content">
      <div class="card">
        <div class="card-body" style="padding:16px;">
          <button class="copy-btn" onclick="copiarJson()">📋 Copiar</button>
          <div id="jsonBox" class="json-box empty">// La respuesta JSON aparecerá aquí...</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  const BASE_URL = 'datos.php';
  let history = [];

  // ─── Badge y URL preview ───────────────────────────────────────────────────
  function updateBadge() {
    const m = document.getElementById('method').value;
    const b = document.getElementById('methodBadge');
    const map = { GET:'GET', POST_FORM:'POST', POST_JSON:'POST JSON', BEARER:'Bearer' };
    b.textContent = map[m];
    updateUrlDisplay();
  }

  function updateUrlDisplay() {
    const token = document.getElementById('token').value.trim();
    const m = document.getElementById('method').value;
    const url = m === 'GET' && token ? `${location.origin}/stadium/api/perfil/datos.php?token=${encodeURIComponent(token)}` : `${location.origin}/stadium/api/perfil/datos.php`;
    document.getElementById('urlDisplay').textContent = url;
  }

  document.getElementById('token').addEventListener('input', updateUrlDisplay);
  document.getElementById('method').addEventListener('change', updateUrlDisplay);

  // ─── Enviar consulta ───────────────────────────────────────────────────────
  async function enviar() {
    const token = document.getElementById('token').value.trim();
    if (!token) {
      mostrarStatus('error', '⚠ Ingresá un token para continuar.');
      return;
    }

    const method = document.getElementById('method').value;
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Consultando...';
    mostrarStatus('wait', '⏳ Enviando solicitud...');

    const t0 = Date.now();
    try {
      let response;

      if (method === 'GET') {
        response = await fetch(`${BASE_URL}?token=${encodeURIComponent(token)}`);
      } else if (method === 'POST_FORM') {
        const form = new FormData();
        form.append('token', token);
        response = await fetch(BASE_URL, { method: 'POST', body: form });
      } else if (method === 'POST_JSON') {
        response = await fetch(BASE_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token })
        });
      } else { // BEARER
        response = await fetch(BASE_URL, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
      }

      const ms     = Date.now() - t0;
      const rawText = await response.text();
      let   data;

      try { data = JSON.parse(rawText); } catch(e) { data = null; }

      if (data && data.success) {
        mostrarStatus('ok', `✅ Respuesta exitosa · ${ms} ms · HTTP ${response.status}`);
        renderVisual(data.data);
      } else {
        const msg = data?.error ?? rawText.substring(0, 200);
        mostrarStatus('error', `❌ Error ${response.status}: ${msg}`);
        renderVisual(null);
      }

      renderJson(rawText);
      agregarHistorial(token, response.status, ms, data?.success);

    } catch(err) {
      mostrarStatus('error', '❌ Error de red: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = '▶ Consultar perfil';
  }

  // ─── Vista estructurada ────────────────────────────────────────────────────
  function renderVisual(d) {
    const el = document.getElementById('visualBody');
    if (!d) {
      el.innerHTML = '<p style="color:#c62828;font-size:13px;padding:20px 0;">No hay datos para mostrar.</p>';
      return;
    }

    const yesno = v => v
      ? '<span class="badge badge-green">✓ Sí</span>'
      : '<span class="badge badge-red">✗ No</span>';

    const fmt = v => v ?? '—';

    // Direcciones
    let dirsHtml = '<p style="color:#aaa;font-size:12px;">Sin direcciones registradas.</p>';
    if (d.direcciones && d.direcciones.length) {
      dirsHtml = d.direcciones.map(dir => `
        <div class="dir-card">
          <div class="dir-label">
            📍 ${escHtml(dir.descripcion ?? 'Sin nombre')}
            ${dir.principal ? '<span class="badge badge-blue">Principal</span>' : ''}
          </div>
          <div style="color:#555;font-size:13px;line-height:1.6;">
            ${escHtml(dir.direccion)}${dir.piso ? `, Piso ${escHtml(dir.piso)}` : ''}${dir.departamento ? ` Dto. ${escHtml(dir.departamento)}` : ''}<br>
            ${escHtml(dir.ciudad)}, ${escHtml(dir.provincia)}
            ${dir.cpostal ? `· CP ${escHtml(dir.cpostal)}` : ''}
          </div>
        </div>`).join('');
    }

    // Preferencias
    const pref = d.preferencias ?? {};
    const prefsHtml = `
      <div class="data-grid">
        <span class="dkey">Recibir ofertas</span><span class="dval">${yesno(pref.recibirOfertas)}</span>
        <span class="dkey">WhatsApp</span><span class="dval">${yesno(pref.recibirWhatsApp)}</span>
        <span class="dkey">Newsletter</span><span class="dval">${yesno(pref.newsletter)}</span>
        <span class="dkey">Idioma</span><span class="dval"><span class="badge badge-blue">${escHtml(pref.idioma ?? 'es')}</span></span>
      </div>`;

    // Estadísticas
    const st = d.estadisticas ?? {};
    const statsHtml = `
      <div class="stat-grid">
        <div class="stat-item"><div class="val">${st.totalPedidos ?? 0}</div><div class="lbl">Total pedidos</div></div>
        <div class="stat-item"><div class="val">${st.pedidosEnCurso ?? 0}</div><div class="lbl">En curso</div></div>
        <div class="stat-item"><div class="val">$${(st.totalGastado ?? 0).toLocaleString('es-AR')}</div><div class="lbl">Gastado</div></div>
      </div>`;

    el.innerHTML = `
      <div class="data-section">
        <h3>Datos personales</h3>
        <div class="data-grid">
          <span class="dkey">ID</span><span class="dval">${escHtml(String(d.id))}</span>
          <span class="dkey">Nombre</span><span class="dval">${escHtml(d.nombre)} ${escHtml(d.apellido)}</span>
          <span class="dkey">Email</span><span class="dval">${escHtml(d.email)}</span>
          <span class="dkey">Teléfono</span><span class="dval">${escHtml(fmt(d.telefono))}</span>
          <span class="dkey">Registrado</span><span class="dval">${fmt(d.fechaRegistro)}</span>
          <span class="dkey">Último acceso</span><span class="dval">${fmt(d.fechaUltimoAcceso)}</span>
          <span class="dkey">Email verificado</span><span class="dval">${yesno(d.emailVerificado)}</span>
        </div>
      </div>

      <div class="data-section">
        <h3>Estadísticas</h3>
        ${statsHtml}
      </div>

      <div class="data-section">
        <h3>Direcciones (${(d.direcciones ?? []).length})</h3>
        ${dirsHtml}
      </div>

      <div class="data-section">
        <h3>Preferencias</h3>
        ${prefsHtml}
      </div>`;
  }

  // ─── JSON raw ──────────────────────────────────────────────────────────────
  function renderJson(raw) {
    const el = document.getElementById('jsonBox');
    el.classList.remove('empty');
    try {
      const pretty = JSON.stringify(JSON.parse(raw), null, 2);
      el.innerHTML = colorizeJson(pretty);
    } catch {
      el.textContent = raw;
    }
  }

  function colorizeJson(str) {
    return str.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(\.\d+)?([eE][+-]?\d+)?)/g, m => {
      if (/^"/.test(m)) return m.endsWith(':') ? `<span class="jkey">${escHtml(m)}</span>` : `<span class="jstr">${escHtml(m)}</span>`;
      if (/true|false/.test(m)) return `<span class="jbool">${m}</span>`;
      if (/null/.test(m))  return `<span class="jnull">${m}</span>`;
      return `<span class="jnum">${m}</span>`;
    });
  }

  function copiarJson() {
    const text = document.getElementById('jsonBox').textContent;
    navigator.clipboard.writeText(text).then(() => {
      const b = document.querySelector('.copy-btn');
      b.textContent = '✓ Copiado';
      setTimeout(() => b.textContent = '📋 Copiar', 1500);
    });
  }

  // ─── Status bar ────────────────────────────────────────────────────────────
  function mostrarStatus(type, msg) {
    const el = document.getElementById('statusBar');
    el.className = 'status-bar status-' + type;
    el.textContent = msg;
    el.style.display = 'block';
  }

  // ─── Historial ─────────────────────────────────────────────────────────────
  function agregarHistorial(token, httpCode, ms, ok) {
    history.unshift({ token, httpCode, ms, ok, ts: new Date().toLocaleTimeString('es-AR') });
    if (history.length > 10) history.pop();
    renderHistorial();
  }

  function renderHistorial() {
    const ul = document.getElementById('historyList');
    if (!history.length) {
      ul.innerHTML = '<li style="color:#aaa;font-size:12px;cursor:default;">Sin consultas aún.</li>';
      return;
    }
    ul.innerHTML = history.map((h, i) => `
      <li onclick="usarHistorial(${i})" title="Clic para reutilizar">
        <span class="token-text">${escHtml(h.token)}</span>
        <span style="color:${h.ok ? '#2e7d32' : '#c62828'};font-size:11px;font-weight:700;margin:0 6px;">HTTP${h.httpCode}</span>
        <span class="ts">${h.ts} · ${h.ms}ms</span>
      </li>`).join('');
  }

  function usarHistorial(i) {
    document.getElementById('token').value = history[i].token;
    updateUrlDisplay();
  }

  function limpiarHistorial() {
    history = [];
    renderHistorial();
  }

  // ─── Tabs ──────────────────────────────────────────────────────────────────
  function switchTab(name) {
    document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('active', ['visual','json'][i] === name));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab' + name.charAt(0).toUpperCase() + name.slice(1)).classList.add('active');
  }

  // ─── Utilidades ────────────────────────────────────────────────────────────
  function limpiar() {
    document.getElementById('token').value = '';
    document.getElementById('statusBar').style.display = 'none';
    document.getElementById('jsonBox').textContent = '// La respuesta JSON aparecerá aquí...';
    document.getElementById('jsonBox').classList.add('empty');
    document.getElementById('visualBody').innerHTML =
      '<p style="color:#aaa;font-size:13px;text-align:center;padding:40px 0;">Realizá una consulta para ver los resultados aquí.</p>';
    updateUrlDisplay();
  }

  function escHtml(s) {
    if (s == null) return '—';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Enviar con Enter
  document.getElementById('token').addEventListener('keydown', e => {
    if (e.key === 'Enter') enviar();
  });

  updateUrlDisplay();

  // Precargar token desde querystring (para testing rápido)
  const qsToken = new URLSearchParams(location.search).get('token');
  if (qsToken) { document.getElementById('token').value = qsToken; updateUrlDisplay(); }
</script>
</body>
</html>
