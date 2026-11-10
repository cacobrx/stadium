<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test – Pedidos / lista.php</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f0f2f5;
      color: #222;
      padding: 32px 16px;
    }

    .layout {
      display: flex;
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
      align-items: flex-start;
    }

    .panel-left  { flex: 0 0 360px; }
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
      padding: 18px 24px;
    }
    .card-header h1 { font-size: 16px; font-weight: 700; }
    .card-header p  { font-size: 12px; color: #aab; margin-top: 3px; }

    .card-body { padding: 22px 24px; }

    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 5px;
      color: #444;
    }
    .field { margin-bottom: 14px; }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 10px 13px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      outline: none;
      background: #fff;
    }
    input:focus, select:focus { border-color: #4361ee; }

    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    .btn-row { display: flex; gap: 10px; margin-top: 4px; }
    button {
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s, opacity .2s;
    }
    .btn-primary  { background: #4361ee; color: #fff; flex: 1; }
    .btn-primary:hover  { background: #3451d1; }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .btn-clear    { background: #f0f0f0; color: #666; }
    .btn-clear:hover { background: #e0e0e0; }

    .hint { font-size: 11px; color: #999; margin-top: 4px; font-style: italic; }

    .url-display {
      background: #f5f6ff;
      border: 1px solid #e0e3ff;
      border-radius: 6px;
      padding: 7px 12px;
      font-family: monospace;
      font-size: 11px;
      color: #4361ee;
      word-break: break-all;
      margin-bottom: 14px;
    }

    /* Status */
    .status-bar {
      padding: 11px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 14px;
      display: none;
    }
    .status-ok    { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .status-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
    .status-wait  { background: #fff8e1; color: #e65100; border: 1px solid #ffcc80; }

    /* Tabs */
    .tabs { display: flex; gap: 4px; margin-bottom: 0; }
    .tab {
      padding: 9px 18px;
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

    /* Pedidos */
    .pedido-card {
      border: 1px solid #e8eaed;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 12px;
    }
    .ped-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 10px;
    }
    .ped-num { font-weight: 700; font-size: 15px; color: #222; }
    .ped-fecha { font-size: 12px; color: #888; margin-top: 2px; }
    .badge {
      display: inline-block;
      padding: 3px 11px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .4px;
    }
    .estado-pendiente  { background: #fff8e1; color: #e65100; }
    .estado-confirmado { background: #e8f5e9; color: #2e7d32; }
    .estado-preparando { background: #e3f2fd; color: #1565c0; }
    .estado-enviado    { background: #ede7f6; color: #6a1b9a; }
    .estado-entregado  { background: #e8f5e9; color: #2e7d32; }
    .estado-cancelado  { background: #ffebee; color: #c62828; }

    .ped-info {
      display: flex;
      gap: 20px;
      font-size: 13px;
      margin-bottom: 10px;
    }
    .ped-info .lbl { color: #888; }
    .ped-info .val { font-weight: 700; color: #333; }

    .productos-preview {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .prod-chip {
      display: flex;
      align-items: center;
      gap: 6px;
      background: #f8f9ff;
      border: 1px solid #e8eaed;
      border-radius: 6px;
      padding: 5px 9px;
      font-size: 12px;
      color: #444;
      max-width: 220px;
    }
    .prod-chip img {
      width: 32px;
      height: 32px;
      object-fit: cover;
      border-radius: 4px;
      background: #eee;
    }
    .prod-chip .no-img {
      width: 32px;
      height: 32px;
      border-radius: 4px;
      background: #e0e3ff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }
    .prod-nombre {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* Paginación */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 16px;
      flex-wrap: wrap;
    }
    .page-btn {
      padding: 7px 13px;
      border-radius: 8px;
      border: 1.5px solid #ddd;
      background: #fff;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      color: #444;
    }
    .page-btn:hover { border-color: #4361ee; color: #4361ee; }
    .page-btn.active { background: #4361ee; color: #fff; border-color: #4361ee; }
    .page-btn:disabled { opacity: .4; cursor: default; }
    .page-info { font-size: 12px; color: #888; }

    /* JSON raw */
    .json-box {
      background: #1e1e2e;
      color: #cdd6f4;
      border-radius: 10px;
      padding: 16px 18px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-all;
      min-height: 180px;
      max-height: 560px;
      overflow-y: auto;
    }
    .json-box.empty { color: #585b70; font-style: italic; }
    .jkey  { color: #89b4fa; }
    .jstr  { color: #a6e3a1; }
    .jnum  { color: #fab387; }
    .jbool { color: #cba6f7; }
    .jnull { color: #f38ba8; }
    .copy-btn {
      float: right;
      padding: 4px 11px;
      font-size: 11px;
      border-radius: 6px;
      background: #2a2a3e;
      color: #cdd6f4;
      border: 1px solid #44475a;
      cursor: pointer;
    }
    .copy-btn:hover { background: #44475a; }

    /* Historial */
    .hist-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 7px 8px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      border: 1px solid transparent;
      margin-bottom: 3px;
    }
    .hist-item:hover { background: #f5f6ff; border-color: #e0e3ff; }
    .hist-tok { font-family: monospace; color: #444; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .hist-ts  { color: #aaa; font-size:11px; margin-left:8px; white-space:nowrap; }

    .empty-box {
      text-align: center;
      padding: 40px 20px;
      color: #aaa;
      font-size: 13px;
    }

    @media (max-width: 768px) {
      .layout { flex-direction: column; }
      .panel-left { flex: none; width: 100%; }
    }
  </style>
</head>
<body>

<div class="layout">

  <!-- Panel izquierdo -->
  <div class="panel-left">
    <div class="card">
      <div class="card-header">
        <h1>🧪 Test — Pedidos / lista.php</h1>
        <p>GET /api/pedidos/lista.php</p>
      </div>
      <div class="card-body">

        <div class="field">
          <label>Token del cliente</label>
          <input type="text" id="token" placeholder="Bearer token del cliente..." autocomplete="off">
          <p class="hint">Se envía como <code>Authorization: Bearer &lt;token&gt;</code></p>
        </div>

        <div class="row2">
          <div class="field">
            <label>Página</label>
            <input type="number" id="page" value="1" min="1">
          </div>
          <div class="field">
            <label>Por página</label>
            <select id="limit">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>

        <div class="url-display" id="urlDisplay"></div>

        <div class="btn-row">
          <button class="btn-primary" id="btnEnviar" onclick="consultar()">▶ Consultar pedidos</button>
          <button class="btn-clear" onclick="limpiar()">✕</button>
        </div>

      </div>
    </div>

    <!-- Historial -->
    <div class="card">
      <div class="card-header" style="padding:14px 24px;">
        <h1 style="font-size:14px;">🕑 Historial</h1>
      </div>
      <div class="card-body" style="padding:14px 16px;">
        <div id="historyList"><p style="color:#aaa;font-size:12px;">Sin consultas aún.</p></div>
        <button class="btn-clear" style="width:100%;margin-top:8px;font-size:12px;" onclick="limpiarHistorial()">Borrar historial</button>
      </div>
    </div>
  </div>

  <!-- Panel derecho -->
  <div class="panel-right">
    <div id="statusBar" class="status-bar"></div>

    <div class="tabs">
      <button class="tab active" onclick="switchTab('pedidos')">📦 Pedidos</button>
      <button class="tab" onclick="switchTab('json')">{ } JSON raw</button>
    </div>

    <!-- Tab pedidos -->
    <div id="tabPedidos" class="tab-content active">
      <div class="card">
        <div class="card-body" id="pedidosBody">
          <div class="empty-box">Realizá una consulta para ver los pedidos.</div>
        </div>
      </div>
    </div>

    <!-- Tab JSON -->
    <div id="tabJson" class="tab-content">
      <div class="card">
        <div class="card-body" style="padding:14px 16px;">
          <button class="copy-btn" onclick="copiarJson()">📋 Copiar</button>
          <div id="jsonBox" class="json-box empty">// La respuesta JSON aparecerá aquí...</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  const API_URL = 'lista.php';
  let history = [];
  let lastData = null;
  let currentPage = 1;
  let currentToken = '';

  // ─── URL preview ──────────────────────────────────────────────────────────
  function updateUrl() {
    const t = document.getElementById('token').value.trim();
    const p = document.getElementById('page').value;
    const l = document.getElementById('limit').value;
    document.getElementById('urlDisplay').textContent =
      `${location.origin}/stadium/api/pedidos/lista.php?page=${p}&limit=${l}` +
      ` [Authorization: Bearer ${t || '...'}]`;
  }

  document.getElementById('token').addEventListener('input', updateUrl);
  document.getElementById('page').addEventListener('input', updateUrl);
  document.getElementById('limit').addEventListener('change', updateUrl);

  // ─── Consultar ────────────────────────────────────────────────────────────
  async function consultar(overridePage = null) {
    const token = document.getElementById('token').value.trim();
    if (!token) { mostrarStatus('error', '⚠ Ingresá un token.'); return; }

    if (overridePage !== null) {
      document.getElementById('page').value = overridePage;
      updateUrl();
    }

    const page  = parseInt(document.getElementById('page').value) || 1;
    const limit = parseInt(document.getElementById('limit').value) || 10;
    currentToken = token;
    currentPage  = page;

    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Consultando...';
    mostrarStatus('wait', '⏳ Enviando solicitud...');

    const t0 = Date.now();
    try {
      const resp = await fetch(`${API_URL}?page=${page}&limit=${limit}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const ms      = Date.now() - t0;
      const rawText = await resp.text();
      let   data;
      try { data = JSON.parse(rawText); } catch { data = null; }

      if (data && data.success) {
        lastData = data;
        mostrarStatus('ok', `✅ ${data.pagination.totalItems} pedidos · Página ${data.pagination.currentPage}/${data.pagination.totalPages} · ${ms}ms · HTTP ${resp.status}`);
        renderPedidos(data);
      } else {
        const msg = data?.error ?? rawText.substring(0, 200);
        mostrarStatus('error', `❌ Error ${resp.status}: ${msg}`);
        renderPedidos(null);
      }

      renderJson(rawText);
      agregarHistorial(token, resp.status, ms, data?.success, data?.pagination?.totalItems);

    } catch (err) {
      mostrarStatus('error', '❌ Error de red: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = '▶ Consultar pedidos';
  }

  // ─── Render pedidos ───────────────────────────────────────────────────────
  function renderPedidos(data) {
    const el = document.getElementById('pedidosBody');
    if (!data || !data.data || data.data.length === 0) {
      el.innerHTML = '<div class="empty-box">No hay pedidos para mostrar.</div>';
      return;
    }

    const pag = data.pagination;
    const items = data.data;

    let html = items.map(ped => {
      const preview = ped.productosPreview.map(p => `
        <div class="prod-chip">
          ${p.imagen ? `<img src="${escHtml(p.imagen)}" alt="${escHtml(p.nombre)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="no-img" style="display:none">📦</div>` : '<div class="no-img">📦</div>'}
          <span class="prod-nombre" title="${escHtml(p.nombre)}">${escHtml(p.nombre)}</span>
        </div>`).join('');

      return `
        <div class="pedido-card">
          <div class="ped-header">
            <div>
              <div class="ped-num">${escHtml(ped.numeroPedido)}</div>
              <div class="ped-fecha">${new Date(ped.fechaCreacion).toLocaleString('es-AR')}</div>
            </div>
            <span class="badge estado-${escHtml(ped.estado)}">${escHtml(ped.estado)}</span>
          </div>
          <div class="ped-info">
            <div><span class="lbl">Total: </span><span class="val">$${ped.total.toLocaleString('es-AR')}</span></div>
            <div><span class="lbl">Items: </span><span class="val">${ped.cantidadItems}</span></div>
            <div><span class="lbl">ID: </span><span class="val">${ped.id}</span></div>
          </div>
          ${preview ? `<div class="productos-preview">${preview}</div>` : ''}
        </div>`;
    }).join('');

    // Paginación
    let pagHtml = `<div class="pagination">`;
    pagHtml += `<button class="page-btn" onclick="consultar(${pag.currentPage - 1})" ${pag.currentPage <= 1 ? 'disabled' : ''}>‹ Anterior</button>`;
    for (let i = 1; i <= pag.totalPages; i++) {
      if (pag.totalPages <= 7 || i === 1 || i === pag.totalPages || Math.abs(i - pag.currentPage) <= 1) {
        pagHtml += `<button class="page-btn ${i === pag.currentPage ? 'active' : ''}" onclick="consultar(${i})">${i}</button>`;
      } else if (Math.abs(i - pag.currentPage) === 2) {
        pagHtml += `<span style="color:#aaa">…</span>`;
      }
    }
    pagHtml += `<button class="page-btn" onclick="consultar(${pag.currentPage + 1})" ${pag.currentPage >= pag.totalPages ? 'disabled' : ''}>Siguiente ›</button>`;
    pagHtml += `<span class="page-info">${pag.totalItems} pedidos en total</span></div>`;

    el.innerHTML = html + pagHtml;
  }

  // ─── JSON raw ─────────────────────────────────────────────────────────────
  function renderJson(raw) {
    const el = document.getElementById('jsonBox');
    el.classList.remove('empty');
    try { el.innerHTML = colorizeJson(JSON.stringify(JSON.parse(raw), null, 2)); }
    catch { el.textContent = raw; }
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
    navigator.clipboard.writeText(document.getElementById('jsonBox').textContent).then(() => {
      const b = document.querySelector('.copy-btn');
      b.textContent = '✓ Copiado';
      setTimeout(() => b.textContent = '📋 Copiar', 1500);
    });
  }

  // ─── Status ───────────────────────────────────────────────────────────────
  function mostrarStatus(type, msg) {
    const el = document.getElementById('statusBar');
    el.className = 'status-bar status-' + type;
    el.textContent = msg;
    el.style.display = 'block';
  }

  // ─── Historial ────────────────────────────────────────────────────────────
  function agregarHistorial(token, code, ms, ok, total) {
    history.unshift({ token, code, ms, ok, total, ts: new Date().toLocaleTimeString('es-AR') });
    if (history.length > 8) history.pop();
    renderHistorial();
  }

  function renderHistorial() {
    const el = document.getElementById('historyList');
    if (!history.length) { el.innerHTML = '<p style="color:#aaa;font-size:12px;">Sin consultas aún.</p>'; return; }
    el.innerHTML = history.map((h, i) => `
      <div class="hist-item" onclick="usarHistorial(${i})" title="Reutilizar token">
        <span class="hist-tok">${escHtml(h.token)}</span>
        <span style="color:${h.ok ? '#2e7d32' : '#c62828'};font-size:11px;font-weight:700;margin:0 5px;">${h.code}${h.total != null ? ` (${h.total})` : ''}</span>
        <span class="hist-ts">${h.ts}</span>
      </div>`).join('');
  }

  function usarHistorial(i) {
    document.getElementById('token').value = history[i].token;
    updateUrl();
  }

  function limpiarHistorial() { history = []; renderHistorial(); }

  // ─── Tabs ─────────────────────────────────────────────────────────────────
  function switchTab(name) {
    document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('active', ['pedidos','json'][i] === name));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab' + name.charAt(0).toUpperCase() + name.slice(1)).classList.add('active');
  }

  // ─── Utilidades ───────────────────────────────────────────────────────────
  function limpiar() {
    document.getElementById('token').value = '';
    document.getElementById('page').value = 1;
    document.getElementById('statusBar').style.display = 'none';
    document.getElementById('jsonBox').textContent = '// La respuesta JSON aparecerá aquí...';
    document.getElementById('jsonBox').classList.add('empty');
    document.getElementById('pedidosBody').innerHTML = '<div class="empty-box">Realizá una consulta para ver los pedidos.</div>';
    updateUrl();
  }

  function escHtml(s) {
    if (s == null) return '—';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  document.getElementById('token').addEventListener('keydown', e => { if (e.key === 'Enter') consultar(); });

  // Precargar token desde querystring
  const qsToken = new URLSearchParams(location.search).get('token');
  if (qsToken) { document.getElementById('token').value = qsToken; }

  updateUrl();
</script>
</body>
</html>
