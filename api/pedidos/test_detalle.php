<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test – Tracking Pedido</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
  header { background: linear-gradient(135deg,#1e40af,#7c3aed); padding: 24px 32px; }
  header h1 { margin: 0; font-size: 1.6rem; font-weight: 700; color: #fff; }
  header p  { margin: 4px 0 0; color: #bfdbfe; font-size: .9rem; }
  .container { max-width: 900px; margin: 32px auto; padding: 0 16px; }

  /* Formulario */
  .card { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
  .card h2 { margin: 0 0 16px; font-size: 1rem; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; }
  .form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
  label  { display: block; font-size: .82rem; color: #94a3b8; margin-bottom: 6px; }
  input[type=number], input[type=text] {
    background: #0f172a; color: #e2e8f0; border: 1px solid #334155;
    border-radius: 8px; padding: 10px 14px; font-size: .95rem; width: 100%;
    outline: none; transition: border-color .2s;
  }
  input:focus { border-color: #3b82f6; }
  .field { flex: 1; min-width: 140px; }
  .btn {
    padding: 11px 24px; border: none; border-radius: 8px; font-size: .95rem;
    font-weight: 600; cursor: pointer; transition: opacity .2s;
  }
  .btn:hover { opacity: .85; }
  .btn-primary { background: #2563eb; color: #fff; }
  .btn-secondary { background: #475569; color: #fff; }
  .url-preview { font-size: .78rem; color: #64748b; margin-top: 8px; word-break: break-all; }

  /* Tabs */
  .tabs { display: flex; gap: 8px; margin-bottom: 16px; }
  .tab-btn {
    background: #0f172a; border: 1px solid #334155; color: #94a3b8;
    border-radius: 8px; padding: 8px 18px; cursor: pointer; font-size: .85rem; transition: all .2s;
  }
  .tab-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }

  /* Resultado visual */
  .result-card { background: #1e293b; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
  .block-title { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin: 0 0 12px; }

  /* Header del pedido */
  .pedido-header {
    background: #0f172a; border-radius: 10px; padding: 20px;
    display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px;
    margin-bottom: 20px;
  }
  .ph-item { }
  .ph-label { font-size: .72rem; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
  .ph-value { font-size: .95rem; color: #e2e8f0; font-weight: 500; }

  /* Badge estado */
  .badge {
    display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: .75rem;
    font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
  }
  .badge-pendiente  { background:#92400e22; color:#fbbf24; border:1px solid #92400e88; }
  .badge-confirmado { background:#1e3a5f; color:#60a5fa; border:1px solid #1d4ed888; }
  .badge-preparando { background:#312e81; color:#a78bfa; border:1px solid #5b21b688; }
  .badge-enviado    { background:#134e4a; color:#34d399; border:1px solid #065f4688; }
  .badge-entregado  { background:#14532d; color:#4ade80; border:1px solid #15803d88; }
  .badge-cancelado  { background:#450a0a; color:#f87171; border:1px solid #b91c1c88; }

  /* Cliente block */
  .cliente-block {
    background: #0f172a; border-radius: 10px; padding: 16px;
    display: flex; align-items: center; gap: 16px; margin-bottom: 20px;
  }
  .cli-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg,#1e40af,#7c3aed);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1.1rem; color: #fff; flex-shrink: 0;
  }
  .cli-info small { display: block; font-size: .75rem; color: #64748b; }
  .cli-info strong { font-size: 1rem; color: #e2e8f0; }

  /* Items table */
  .items-table { width: 100%; border-collapse: collapse; font-size: .87rem; }
  .items-table th {
    text-align: left; padding: 8px 10px; border-bottom: 1px solid #334155;
    color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .06em;
  }
  .items-table td { padding: 10px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
  .items-table tr:last-child td { border-bottom: none; }
  .items-table tr:hover td { background: #0f172a44; }
  .item-img { width: 48px; height: 48px; border-radius: 6px; object-fit: cover; background: #0f172a; }
  .item-img-placeholder { width: 48px; height: 48px; border-radius: 6px; background: #334155; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
  .item-nombre { font-weight: 600; color: #e2e8f0; }
  .item-det { font-size: .78rem; color: #64748b; margin-top: 2px; display: -webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; max-width: 280px; }
  .text-right { text-align: right; }
  .mono { font-family: monospace; }

  /* Totales */
  .totales { background: #0f172a; border-radius: 10px; padding: 16px 20px; margin-top: 16px; }
  .totales-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: .9rem; color: #94a3b8; }
  .totales-row.total { color: #e2e8f0; font-weight: 700; font-size: 1.05rem; border-top: 1px solid #334155; margin-top: 6px; padding-top: 10px; }

  /* Pagos */
  .pagos-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
  .pago-card {
    background: #0f172a; border-radius: 10px; padding: 14px 18px;
    display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: center;
    border-left: 4px solid #334155;
  }
  .pago-card.approved  { border-left-color: #4ade80; }
  .pago-card.pending   { border-left-color: #fbbf24; }
  .pago-card.rejected  { border-left-color: #f87171; }
  .pago-card.cancelled { border-left-color: #94a3b8; }
  .pago-card.refunded  { border-left-color: #a78bfa; }
  .pago-card.in_process{ border-left-color: #60a5fa; }
  .pago-icon { font-size: 1.4rem; }
  .pago-meta { }
  .pago-meta strong { display: block; font-size: .95rem; color: #e2e8f0; }
  .pago-meta small { font-size: .75rem; color: #64748b; }
  .pago-amount { font-family: monospace; font-size: 1.05rem; font-weight: 700; color: #e2e8f0; text-align: right; }

  /* JSON raw */
  pre { margin: 0; background: #0f172a; border-radius: 10px; padding: 16px; font-size: .82rem; color: #67e8f9; overflow: auto; max-height: 550px; }

  /* Error */
  .error-box { background: #450a0a44; border: 1px solid #b91c1c; border-radius: 10px; padding: 16px; color: #f87171; font-size: .9rem; }

  /* Estado inicial */
  #result-area { display: none; }
  #loading { display: none; text-align: center; padding: 40px; color: #64748b; }
</style>
</head>
<body>

<header>
  <h1>📦 Test – Tracking de Pedido</h1>
  <p>Consulta los datos completos de un pedido por su ID</p>
</header>

<div class="container">

  <!-- Formulario -->
  <div class="card">
    <h2>Parámetros</h2>
    <div class="form-row">
      <div class="field">
        <label for="pedido-id">ID del Pedido</label>
        <input type="number" id="pedido-id" value="6" min="1" placeholder="Ej: 6">
      </div>
      <button class="btn btn-primary" onclick="consultar()">🔍 Consultar</button>
      <button class="btn btn-secondary" onclick="limpiar()">✕ Limpiar</button>
    </div>
    <p class="url-preview" id="url-preview">&nbsp;</p>
  </div>

  <!-- Loading -->
  <div id="loading">⏳ Cargando…</div>

  <!-- Resultado -->
  <div id="result-area">
    <div class="result-card">
      <div class="tabs">
        <button class="tab-btn active" id="tab-visual" onclick="showTab('visual')">Vista previa</button>
        <button class="tab-btn" id="tab-json" onclick="showTab('json')">JSON raw</button>
      </div>

      <!-- Vista visual -->
      <div id="pane-visual">

        <!-- Error box -->
        <div id="error-box" class="error-box" style="display:none"></div>

        <!-- Datos pedido -->
        <div id="pedido-visual" style="display:none">

          <p class="block-title">Datos del Pedido</p>
          <div class="pedido-header" id="ph-grid"></div>

          <p class="block-title">Cliente</p>
          <div class="cliente-block" id="cli-block"></div>

          <p class="block-title" id="pagos-title" style="display:none">Pagos</p>
          <div class="pagos-list" id="pagos-list"></div>

          <p class="block-title">Items</p>
          <table class="items-table">
            <thead>
              <tr>
                <th style="width:56px"></th>
                <th>Producto</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody id="items-tbody"></tbody>
          </table>

          <div class="totales" id="totales-block"></div>

        </div>
      </div>

      <!-- JSON raw -->
      <div id="pane-json" style="display:none">
        <pre id="json-raw"></pre>
      </div>
    </div>
  </div>

</div>

<script>
const API = 'detalle.php';

function fmtMoney(val) {
  return '$' + Number(val).toLocaleString('es-AR', { minimumFractionDigits: 2 });
}
function badgeHtml(estado) {
  return `<span class="badge badge-${estado.toLowerCase()}">${estado}</span>`;
}
function initials(nombre, apellido) {
  return ((nombre||'').charAt(0) + (apellido||'').charAt(0)).toUpperCase() || '?';
}

document.getElementById('pedido-id').addEventListener('input', updatePreview);
updatePreview();

function updatePreview() {
  const id = document.getElementById('pedido-id').value;
  document.getElementById('url-preview').textContent = id
    ? `GET ${API}?id=${id}`
    : 'Ingresá un ID para ver la URL';
}

function showTab(tab) {
  document.getElementById('pane-visual').style.display = tab === 'visual' ? '' : 'none';
  document.getElementById('pane-json').style.display   = tab === 'json'   ? '' : 'none';
  document.getElementById('tab-visual').classList.toggle('active', tab === 'visual');
  document.getElementById('tab-json').classList.toggle('active', tab === 'json');
}

function limpiar() {
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('pedido-id').value = '';
  updatePreview();
}

async function consultar() {
  const id = document.getElementById('pedido-id').value.trim();
  if (!id) { alert('Ingresá un ID de pedido'); return; }

  document.getElementById('loading').style.display = 'block';
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('error-box').style.display = 'none';
  document.getElementById('pedido-visual').style.display = 'none';

  try {
    const res  = await fetch(`${API}?id=${encodeURIComponent(id)}`);
    const data = await res.json();

    document.getElementById('loading').style.display = 'none';
    document.getElementById('result-area').style.display = 'block';
    document.getElementById('json-raw').textContent = JSON.stringify(data, null, 2);

    if (!data.success) {
      document.getElementById('error-box').style.display = 'block';
      document.getElementById('error-box').textContent = '⚠ ' + (data.error || 'Error desconocido');
      return;
    }

    renderVisual(data.data);
    document.getElementById('pedido-visual').style.display = 'block';

  } catch(e) {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('result-area').style.display = 'block';
    document.getElementById('error-box').style.display = 'block';
    document.getElementById('error-box').textContent = '⚠ Error de red: ' + e.message;
  }
}

function renderVisual(data) {
  const { pedido, cliente, items } = data;

  // Header grid
  const ph = [
    { label: 'Nro. Pedido',  value: `<span class="mono">${pedido.numeroPedido}</span>` },
    { label: 'Estado',       value: badgeHtml(pedido.estado) },
    { label: 'Fecha',        value: pedido.fecha || '—' },
    { label: 'Creación',     value: pedido.fechaCreacion ? new Date(pedido.fechaCreacion).toLocaleString('es-AR') : '—' },
    { label: 'Modo de pago', value: pedido.modoPago || '—' },
    { label: 'Tipo de envío',value: pedido.nombreEnvio || pedido.tipoEnvio || '—' },
    { label: 'Cerrado',      value: pedido.cerrado ? '✔ Sí' : 'No' },
    { label: 'Bultos',       value: pedido.bultos ?? '—' },
    { label: 'Nro. Remito',  value: pedido.nroRemito || '—' },
    { label: 'Observaciones',value: pedido.observaciones || '<em style="color:#475569">ninguna</em>' },
  ];
  document.getElementById('ph-grid').innerHTML = ph.map(i =>
    `<div class="ph-item"><div class="ph-label">${i.label}</div><div class="ph-value">${i.value}</div></div>`
  ).join('');

  // Cliente
  document.getElementById('cli-block').innerHTML = `
    <div class="cli-avatar">${initials(cliente.nombre, cliente.apellido)}</div>
    <div class="cli-info">
      <small>ID #${cliente.id}</small>
      <strong>${cliente.nombre} ${cliente.apellido}</strong>
      <small>${cliente.email || ''} ${cliente.telefono ? '· ' + cliente.telefono : ''}</small>
    </div>
  `;

  // Items
  const tbody = document.getElementById('items-tbody');
  tbody.innerHTML = items.map(item => {
    const imgHtml = item.imagen
      ? `<img class="item-img" src="${item.imagen}" alt="${item.nombre}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
        + `<div class="item-img-placeholder" style="display:none">📦</div>`
      : `<div class="item-img-placeholder">📦</div>`;
    return `
      <tr>
        <td>${imgHtml}</td>
        <td>
          <div class="item-nombre">${item.nombre || 'Producto'}</div>
          <div class="item-det">${item.descripcion || item.detalle || ''}</div>
          ${item.sku ? `<div style="font-size:.72rem;color:#475569;margin-top:2px">SKU: ${item.sku}</div>` : ''}
        </td>
        <td class="text-right mono">${fmtMoney(item.precio)}</td>
        <td class="text-right mono">${item.cantidad}</td>
        <td class="text-right mono">${fmtMoney(item.total)}</td>
      </tr>
    `;
  }).join('');

  // Pagos
  const pagosList = document.getElementById('pagos-list');
  const pagosTitle = document.getElementById('pagos-title');
  if (data.pagos && data.pagos.length > 0) {
    pagosTitle.style.display = '';
    const iconMap = { approved:'✅', pending:'⏳', rejected:'❌', cancelled:'⛔', refunded:'↩️', in_process:'🔄' };
    const labelMap = { approved:'Aprobado', pending:'Pendiente', rejected:'Rechazado', cancelled:'Cancelado', refunded:'Reembolsado', in_process:'En proceso' };
    const typeMap = { credit_card:'Tarjeta de crédito', debit_card:'Tarjeta de débito', ticket:'Efectivo / Cupón', bank_transfer:'Transferencia', account_money:'Dinero en cuenta', atm:'ATM', prepaid_card:'Prepaga' };
    pagosList.innerHTML = data.pagos.map(p => {
      const icon  = iconMap[p.estado]  || '💳';
      const label = labelMap[p.estado] || p.estado;
      const tipo  = typeMap[p.mpPaymentType] || p.mpPaymentType || '—';
      const fecha = p.fecha ? new Date(p.fecha).toLocaleString('es-AR') : '—';
      return `
        <div class="pago-card ${p.estado}">
          <div class="pago-icon">${icon}</div>
          <div class="pago-meta">
            <strong>${label}${p.mpPaymentId ? ' · #'+p.mpPaymentId : ''}</strong>
            <small>${tipo}${p.mpStatusDetail ? ' · '+p.mpStatusDetail : ''} · ${fecha}</small>
            ${p.mpExternalRef ? '<small>Ref: '+p.mpExternalRef+'</small>' : ''}
          </div>
          <div class="pago-amount">${fmtMoney(p.monto)}</div>
        </div>
      `;
    }).join('');
  } else {
    pagosTitle.style.display = '';
    pagosList.innerHTML = '<div style="color:#475569;font-size:.88rem;padding:10px 0">Sin pagos registrados</div>';
  }

  // Totales
  const totalPagado = data.totalPagado || 0;
  const saldo = pedido.total - totalPagado;
  document.getElementById('totales-block').innerHTML = `
    <div class="totales-row"><span>Subtotal</span><span class="mono">${fmtMoney(pedido.subtotal)}</span></div>
    <div class="totales-row"><span>Costo de envío ${pedido.nombreEnvio ? '('+pedido.nombreEnvio+')' : ''}</span><span class="mono">${fmtMoney(pedido.envio)}</span></div>
    <div class="totales-row total"><span>TOTAL</span><span class="mono">${fmtMoney(pedido.total)}</span></div>
    ${totalPagado > 0 ? `
    <div class="totales-row" style="color:#4ade80"><span>Total pagado (aprobado)</span><span class="mono">${fmtMoney(totalPagado)}</span></div>
    <div class="totales-row" style="color:${saldo > 0.01 ? '#f87171' : '#4ade80'}"><span>Saldo pendiente</span><span class="mono">${fmtMoney(saldo)}</span></div>
    ` : ''}
  `;
}
</script>
</body>
</html>
