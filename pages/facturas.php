<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobantes Fiscales - Stadium</title>
    <script>
        (function() {
            const t = localStorage.getItem('stadium-theme');
            if (t) document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script><?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?></script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
        .det-table th, .det-table td { vertical-align: middle; font-size: .9rem; }
        .det-table input, .det-table select { font-size: .875rem; }
        .subtotal-cell { min-width: 90px; }
        .totales-box { background: var(--bs-secondary-bg); border-radius: .5rem; padding: 1rem; }
        #facturaModal .modal-dialog { max-width: 960px; }
        .comprobante-num { font-family: monospace; font-size: .875rem; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/menu.php'; ?>

<div class="main-content" id="mainContent">
    <!-- Top nav -->
    <div class="top-nav d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña"><i class="bi bi-box-arrow-up-right"></i></button>
            <h5 class="mb-0 d-inline">Comprobantes Fiscales</h5>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                <i class="bi bi-sun-fill" id="themeIcon"></i>
            </button>
            <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
        </div>
    </div>

    <!-- Content -->
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt me-2"></i>Comprobantes Fiscales</h2>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="bi bi-plus"></i> Nuevo Comprobante
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Listado</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="facturasTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Comprobante</th>
                                <th>Cliente</th>
                                <th>Razón Social Fiscal</th>
                                <th>N° CAE</th>
                                <th class="text-end">Neto</th>
                                <th class="text-end">Total c/IVA</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="facturasTbody">
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-hourglass-split"></i> Cargando...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="facturaModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Comprobante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fId">

                <!-- ── Datos del encabezado ── -->
                <h6 class="text-primary border-bottom pb-1 mb-3"><i class="bi bi-card-list me-1"></i>Datos del Comprobante</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fFecha">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipo Comprobante <span class="text-danger">*</span></label>
                        <select class="form-select" id="fTipocom">
                            <option value="">-- Seleccionar --</option>
                            <option value="FACTURA">Factura</option>
                            <option value="NOTA DE CREDITO">Nota de Crédito</option>
                            <option value="NOTA DE DEBITO">Nota de Débito</option>
                            <option value="RECIBO">Recibo</option>
                            <option value="TICKET">Ticket</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Letra</label>
                        <select class="form-select" id="fLetra">
                            <option value="">-</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="M">M</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Pto. Venta</label>
                        <input type="number" class="form-control" id="fPtovta" value="1" min="1">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Número</label>
                        <input type="number" class="form-control" id="fNumero" value="0" min="0">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" id="fIdcli">
                            <option value="">-- Sin cliente --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Datos Fiscales (CUIT)</label>
                        <select class="form-select" id="fIdcuit">
                            <option value="">-- Sin datos fiscales --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pedido asociado (ID)</label>
                        <input type="number" class="form-control" id="fIdped" placeholder="ID del pedido (opcional)">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">N° CAE</label>
                        <input type="text" class="form-control" id="fNumerocae" placeholder="Número de CAE">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vto. CAE</label>
                        <input type="date" class="form-control" id="fFechacae">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Doc. Referencia</label>
                        <input type="text" class="form-control" id="fDocreferencia" placeholder="Referencia de documento">
                    </div>
                </div>

                <!-- ── Detalle ── -->
                <h6 class="text-primary border-bottom pb-1 mb-3 mt-2"><i class="bi bi-list-ul me-1"></i>Detalle de Ítems</h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered det-table" id="detTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:220px">Variante</th>
                                <th>Descripción</th>
                                <th style="width:90px">Cantidad</th>
                                <th style="width:110px">Precio unit.</th>
                                <th style="width:100px">Alícuota IVA</th>
                                <th style="width:110px" class="text-end">Subtotal</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="detTbody"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addDetRow()">
                    <i class="bi bi-plus-circle"></i> Agregar ítem
                </button>

                <!-- Totales -->
                <div class="totales-box">
                    <div class="row justify-content-end text-end">
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between"><span class="text-muted">Neto:</span> <strong id="totNeto">$ 0,00</strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">IVA:</span> <strong id="totIva">$ 0,00</strong></div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between fs-5"><span>Total:</span> <strong id="totTotal">$ 0,00</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFactura()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script><?php include __DIR__ . '/../includes/dashboard-functions.js'; ?></script>
<script>
let facturaModal;
let currentEditId = null;
let articulosList = []; // cache de artículos
let defaultCuitId = null; // CUIT por defecto

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    loadTheme();
    facturaModal = new bootstrap.Modal(document.getElementById('facturaModal'));
    await Promise.all([loadFacturas(), preloadSelects()]);
});

// ── Cargar tabla principal ────────────────────────────────────────────────────
async function loadFacturas() {
    try {
        const res  = await fetch('./api/facturas.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.success) renderTable(data.facturas);
        else tbodyMsg(data.error, 'danger', 9);
    } catch (e) {
        tbodyMsg('Error de conexión', 'danger', 9);
    }
}

function tbodyMsg(msg, type, cols) {
    document.getElementById('facturasTbody').innerHTML =
        `<tr><td colspan="${cols}" class="text-center text-${type} py-4">${escH(msg)}</td></tr>`;
}

function renderTable(facturas) {
    const tbody = document.getElementById('facturasTbody');
    if (!facturas.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Sin comprobantes. Agregue uno nuevo.</td></tr>';
        return;
    }
    tbody.innerHTML = facturas.map(f => `
        <tr>
            <td>${f.id}</td>
            <td>${f.fecha ? fmtDate(f.fecha) : '<span class="text-muted">—</span>'}</td>
            <td><span class="comprobante-num">${compNum(f)}</span></td>
            <td>${escH(f.cliente_nombre || '—')}</td>
            <td>${escH(f.cuit_razonsocial || '—')}</td>
            <td><small>${escH(f.numerocae || '—')}</small></td>
            <td class="text-end">${fmtMoney(f.total_neto)}</td>
            <td class="text-end"><strong>${fmtMoney(f.total_con_iva)}</strong></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editFactura(${f.id})" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteFactura(${f.id})" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// ── Precargar selects (clientes, cuits, artículos) ────────────────────────────
async function preloadSelects() {
    const [rCli, rCuit, rArt] = await Promise.all([
        fetch('./api/facturas.php?clientes=1', { credentials: 'same-origin' }).then(r => r.json()),
        fetch('./api/facturas.php?cuits=1',    { credentials: 'same-origin' }).then(r => r.json()),
        fetch('./api/facturas.php?articulos=1',{ credentials: 'same-origin' }).then(r => r.json()),
    ]);

    if (rCli.success) {
        const sel = document.getElementById('fIdcli');
        rCli.clientes.forEach(c => {
            sel.insertAdjacentHTML('beforeend', `<option value="${c.id}">${escH(c.nombre.trim())}</option>`);
        });
    }
    if (rCuit.success) {
        const sel = document.getElementById('fIdcuit');
        rCuit.cuits.forEach((c, idx) => {
            const label = `${escH(c.razonsocial)} (${escH(c.cuit)}) - PV ${c.ptovta}${c.pordefecto == 1 ? ' ★' : ''}`;
            sel.insertAdjacentHTML('beforeend',
                `<option value="${c.id}">${label}</option>`);
            if (c.pordefecto == 1) defaultCuitId = c.id;        // preferir el marcado
            else if (idx === 0 && !defaultCuitId) defaultCuitId = c.id; // fallback al primero
        });
    }
    if (rArt.success) articulosList = rArt.articulos;
}

// ── Modal nuevo ───────────────────────────────────────────────────────────────
function openModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Nuevo Comprobante';
    document.getElementById('fId').value = '';
    document.getElementById('fFecha').value    = todayIso();
    document.getElementById('fTipocom').value  = 'FACTURA';
    document.getElementById('fLetra').value    = 'B';
    document.getElementById('fPtovta').value   = '1';
    document.getElementById('fNumero').value   = '0';
    document.getElementById('fIdcli').value    = '';
    document.getElementById('fIdcuit').value   = defaultCuitId || '';
    document.getElementById('fIdped').value    = '';
    document.getElementById('fNumerocae').value = '';
    document.getElementById('fFechacae').value  = '';
    document.getElementById('fDocreferencia').value = '';
    document.getElementById('detTbody').innerHTML = '';
    calcTotals();
    addDetRow(); // una fila vacía
    facturaModal.show();
}

// ── Editar ────────────────────────────────────────────────────────────────────
async function editFactura(id) {
    try {
        const res  = await fetch(`./api/facturas.php?id=${id}`, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) { Swal.fire('Error', data.error, 'error'); return; }

        const f = data.factura;
        currentEditId = id;
        document.getElementById('modalTitle').textContent = 'Editar Comprobante';
        document.getElementById('fId').value            = f.id;
        document.getElementById('fFecha').value         = f.fecha         || '';
        document.getElementById('fTipocom').value       = f.tipocom       || '';
        document.getElementById('fLetra').value         = f.letra         || '';
        document.getElementById('fPtovta').value        = f.ptovta        || 1;
        document.getElementById('fNumero').value        = f.numero        || 0;
        document.getElementById('fIdcli').value         = f.idcli         || '';
        document.getElementById('fIdcuit').value        = f.idcuit        || '';
        document.getElementById('fIdped').value         = f.idped         || '';
        document.getElementById('fNumerocae').value     = f.numerocae     || '';
        document.getElementById('fFechacae').value      = f.fechacae      || '';
        document.getElementById('fDocreferencia').value = f.docreferencia || '';

        // Cargar detalles
        document.getElementById('detTbody').innerHTML = '';
        (f.detalles || []).forEach(d => addDetRow(d));
        if (!f.detalles || !f.detalles.length) addDetRow();

        calcTotals();
        facturaModal.show();
    } catch (e) {
        console.error(e);
        CRUDAlerts.connectionError();
    }
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
async function deleteFactura(id) {
    const r = await CRUDAlerts.confirmDelete('comprobante');
    if (!r.isConfirmed) return;
    try {
        const res  = await fetch('./api/facturas.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) { loadFacturas(); CRUDAlerts.deleted('Comprobante'); }
        else CRUDAlerts.deleteError(data.error);
    } catch (e) { CRUDAlerts.connectionError(); }
}

// ── Guardar ───────────────────────────────────────────────────────────────────
async function saveFactura() {
    const tipocom = document.getElementById('fTipocom').value;
    if (!tipocom) { Swal.fire('Validación', 'Seleccione el tipo de comprobante.', 'warning'); return; }

    const payload = {
        fecha:          document.getElementById('fFecha').value         || null,
        tipocom,
        letra:          document.getElementById('fLetra').value,
        ptovta:         parseInt(document.getElementById('fPtovta').value)  || 1,
        numero:         parseInt(document.getElementById('fNumero').value)  || 0,
        idcli:          parseInt(document.getElementById('fIdcli').value)  || null,
        idcuit:         parseInt(document.getElementById('fIdcuit').value) || null,
        idped:          parseInt(document.getElementById('fIdped').value)  || null,
        numerocae:      document.getElementById('fNumerocae').value,
        fechacae:       document.getElementById('fFechacae').value     || null,
        docreferencia:  document.getElementById('fDocreferencia').value,
        detalles: getDetalles(),
    };

    const method = currentEditId ? 'PUT' : 'POST';
    if (currentEditId) payload.id = currentEditId;

    try {
        const res  = await fetch('./api/facturas.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            facturaModal.hide();
            loadFacturas();
            currentEditId ? CRUDAlerts.updated('Comprobante') : CRUDAlerts.created('Comprobante');
        } else {
            currentEditId ? CRUDAlerts.updateError(data.error) : CRUDAlerts.createError(data.error);
        }
    } catch (e) { CRUDAlerts.connectionError(); }
}

// ── Detalle ───────────────────────────────────────────────────────────────────
function buildArtOptions(selectedId) {
    let opts = '<option value="">-- Variante --</option>';
    articulosList.forEach(a => {
        const sel   = (a.id == selectedId) ? 'selected' : '';
        const label = (a.sku ? a.sku + ' | ' : '') + escH(a.nombre);
        opts += `<option value="${a.id}" data-precio="${a.precio}" ${sel}>${label}</option>`;
    });
    return opts;
}

function addDetRow(d = null) {
    const tbody = document.getElementById('detTbody');
    const tr    = document.createElement('tr');
    const qty   = d ? parseFloat(d.cantidad) : 1;
    const prec  = d ? parseFloat(d.precio)   : 0;
    const alic  = d ? parseFloat(d.alicuota) : 21;
    const idart = d ? (d.idart || '') : '';
    const desc  = d ? (d.descripcion || '') : '';

    const alicOpts = [0, 10.5, 21, 27].map(v =>
        `<option value="${v}" ${v == alic ? 'selected' : ''}>${v}%</option>`
    ).join('');

    tr.innerHTML = `
        <td>
            <select class="form-select form-select-sm art-sel" onchange="onArtChange(this)">
                ${buildArtOptions(idart)}
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm det-desc" value="${escAttr(desc)}" placeholder="Descripción"></td>
        <td><input type="number" class="form-control form-control-sm det-qty" value="${qty}" min="0.0001" step="any" onchange="calcTotals()"></td>
        <td><input type="number" class="form-control form-control-sm det-precio" value="${prec}" min="0" step="any" onchange="calcTotals()"></td>
        <td>
            <select class="form-select form-select-sm det-alic" onchange="calcTotals()">
                ${alicOpts}
            </select>
        </td>
        <td class="text-end subtotal-cell"><span class="det-subtotal">$ 0,00</span></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDetRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calcTotals();
}

function onArtChange(sel) {
    const opt  = sel.selectedOptions[0];
    const prec = parseFloat(opt.dataset.precio || 0);
    const row  = sel.closest('tr');
    row.querySelector('.det-precio').value = prec;
    if (!row.querySelector('.det-desc').value) {
        row.querySelector('.det-desc').value = opt.text.replace(/^\w+ - /, '').split(' - ')[0];
    }
    calcTotals();
}

function removeDetRow(btn) {
    btn.closest('tr').remove();
    calcTotals();
}

function getDetalles() {
    const rows = document.querySelectorAll('#detTbody tr');
    const arr  = [];
    rows.forEach(tr => {
        const idart = tr.querySelector('.art-sel').value || null;
        const desc  = tr.querySelector('.det-desc').value.trim();
        const qty   = parseFloat(tr.querySelector('.det-qty').value) || 0;
        const prec  = parseFloat(tr.querySelector('.det-precio').value) || 0;
        const alic  = parseFloat(tr.querySelector('.det-alic').value) || 0;
        if (qty || prec || desc || idart) {
            arr.push({ idart, descripcion: desc, cantidad: qty, precio: prec, alicuota: alic });
        }
    });
    return arr;
}

function calcTotals() {
    let neto = 0, iva = 0;
    document.querySelectorAll('#detTbody tr').forEach(tr => {
        const qty   = parseFloat(tr.querySelector('.det-qty')?.value   || 0);
        const prec  = parseFloat(tr.querySelector('.det-precio')?.value || 0);
        const alic  = parseFloat(tr.querySelector('.det-alic')?.value  || 0);
        const sub   = qty * prec;
        const ivaRow = sub * alic / 100;
        neto += sub;
        iva  += ivaRow;
        tr.querySelector('.det-subtotal').textContent = fmtMoney(sub + ivaRow);
    });
    document.getElementById('totNeto').textContent  = fmtMoney(neto);
    document.getElementById('totIva').textContent   = fmtMoney(iva);
    document.getElementById('totTotal').textContent = fmtMoney(neto + iva);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function escH(s)    { if (!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s) { return escH(s || ''); }

function fmtDate(d) {
    if (!d) return '';
    const [y, m, dd] = d.split('-');
    return `${dd}/${m}/${y}`;
}

function fmtMoney(n) {
    return '$ ' + parseFloat(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function todayIso() {
    const d = new Date();
    return d.toISOString().split('T')[0];
}

function compNum(f) {
    const tipo   = f.tipocom ? f.tipocom.substring(0, 2).toUpperCase() : '??';
    const letra  = f.letra || '';
    const pv     = String(f.ptovta || 1).padStart(4, '0');
    const num    = String(f.numero || 0).padStart(8, '0');
    return `${tipo}${letra} ${pv}-${num}`;
}
</script>
</body>
</html>
