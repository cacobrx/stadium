<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API - Crear Pedido</title>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 1100px;
            margin: 0 auto;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 { font-size: 26px; margin-bottom: 6px; }
        .header p  { opacity: .85; font-size: 14px; }

        .content { padding: 30px; }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media(max-width: 700px) { .grid2 { grid-template-columns: 1fr; } }

        .panel {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .panel h3 {
            color: #11998e;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
            margin-top: 12px;
        }

        label:first-of-type { margin-top: 0; }

        input, select, textarea {
            width: 100%;
            padding: 9px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: border-color .2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #11998e;
        }

        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }

        /* Items dinámicos */
        #itemsContainer .item-row {
            display: grid;
            grid-template-columns: 1fr 1.5fr 80px 100px 36px;
            gap: 8px;
            align-items: end;
            margin-bottom: 10px;
        }

        #itemsContainer .item-row label { margin-top: 0; }

        .btn-icon {
            width: 36px;
            height: 38px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }

        .btn-remove { background: #ffdddd; color: #dc3545; }
        .btn-remove:hover { background: #dc3545; color: white; }
        .btn-add {
            background: #d4edda;
            color: #28a745;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background .2s;
        }
        .btn-add:hover { background: #28a745; color: white; }

        .button-group { display: flex; gap: 10px; margin-top: 20px; }

        .btn-primary {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            transition: all .3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(17,153,142,.4); }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .btn-secondary {
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            background: #e0e0e0;
            color: #333;
        }
        .btn-secondary:hover { background: #ccc; }

        /* JSON preview */
        #jsonPreview {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            min-height: 120px;
            resize: vertical;
            background: #1e1e1e;
            color: #9cdcfe;
            border-color: #444;
        }

        /* Resultado */
        .result {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            min-height: 100px;
        }
        .result.success { background: #d4edda; border-color: #28a745; }
        .result.error   { background: #f8d7da; border-color: #dc3545; }
        .result-pre {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .pay-link {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 22px;
            background: #009ee3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all .3s;
        }
        .pay-link:hover { background: #0082c3; transform: translateY(-2px); }

        .loader {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 13px;
            color: #333;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🛒 Test API — Crear Pedido</h1>
        <p>Endpoint: <code>https://stadium.planbsistemas.com.ar/api/pedidos/crear.php</code></p>
    </div>

    <div class="content">

        <div class="info-box">
            <strong>📍 POST</strong> &nbsp;|&nbsp; Content-Type: application/json &nbsp;|&nbsp;
            Crea el pedido en BD y genera la preferencia de MercadoPago.
        </div>

        <div class="grid2">

            <!-- Columna izquierda: formulario -->
            <div>

                <!-- Configuración -->
                <div class="panel">
                    <h3>⚙️ Modo de pago</h3>
                    <div class="row2">
                        <div>
                            <label>Modo pedido</label>
                            <select id="modo">
                                <option value="mercadopago">MercadoPago</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label>Modo MP</label>
                            <select id="mp_mode">
                                <option value="sandbox">🧪 Sandbox</option>
                                <option value="production">🚀 Producción</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="panel">
                    <h3>📦 Items</h3>
                    <div id="itemsContainer">
                        <div class="item-row">
                            <div><label>SKU</label><input type="text" class="sku" value="FOT001"></div>
                            <div><label>Título</label><input type="text" class="titulo" value="Foto 10x15 cm"></div>
                            <div><label>Cant.</label><input type="number" class="cantidad" value="2" min="1"></div>
                            <div><label>Precio unit.</label><input type="number" class="precio" value="250.00" step="0.01"></div>
                            <div><label>&nbsp;</label><button class="btn-icon btn-remove" onclick="removeItem(this)" title="Eliminar">✕</button></div>
                        </div>
                    </div>
                    <button class="btn-add" onclick="addItem()">+ Agregar item</button>
                </div>

                <!-- Pagador -->
                <div class="panel">
                    <h3>👤 Pagador</h3>
                    <div class="row2">
                        <div><label>Nombre</label><input type="text" id="nombre" value="Juan"></div>
                        <div><label>Apellido</label><input type="text" id="apellido" value="Pérez"></div>
                    </div>
                    <label>Email</label>
                    <input type="email" id="email" value="juan.perez@example.com">
                    <div class="row2">
                        <div><label>Tipo doc.</label>
                            <select id="tipodoc">
                                <option value="DNI">DNI</option>
                                <option value="CUIL">CUIL</option>
                                <option value="CUIT">CUIT</option>
                                <option value="Pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div><label>Nro. doc.</label><input type="text" id="nrodoc" value="30123456"></div>
                    </div>
                    <div class="row2">
                        <div><label>Cód. área</label><input type="text" id="codarea" value="11"></div>
                        <div><label>Teléfono</label><input type="text" id="telefono" value="1533344455"></div>
                    </div>
                    <label>Calle</label>
                    <input type="text" id="calle" value="Calle Falsa">
                    <div class="row2">
                        <div><label>Número</label><input type="text" id="nrodir" value="123"></div>
                        <div><label>Código postal</label><input type="text" id="codpostal" value="1234"></div>
                    </div>
                </div>

                <!-- Envío -->
                <div class="panel">
                    <h3>🚚 Envío</h3>
                    <div class="row2">
                        <div>
                            <label>Tipo</label>
                            <select id="tipoenvio">
                                <option value="domicilio">Domicilio</option>
                                <option value="sucursal">Sucursal</option>
                                <option value="retiro">Retiro en local</option>
                            </select>
                        </div>
                        <div><label>Costo</label><input type="number" id="costoenvio" value="0" step="0.01"></div>
                    </div>
                    <label>Nombre envío</label>
                    <input type="text" id="nombreenvio" value="Envío a domicilio">
                </div>

                <!-- Back URLs -->
                <div class="panel">
                    <h3>🔗 Back URLs MercadoPago</h3>
                    <label>Success</label>
                    <input type="text" id="urlSuccess" value="https://stadium.planbsistemas.com.ar/pago-ok">
                    <label>Failure</label>
                    <input type="text" id="urlFailure" value="https://stadium.planbsistemas.com.ar/pago-error">
                    <label>Pending</label>
                    <input type="text" id="urlPending" value="https://stadium.planbsistemas.com.ar/pago-pendiente">
                </div>

            </div>

            <!-- Columna derecha: preview + resultado -->
            <div>

                <div class="panel">
                    <h3>📄 JSON generado</h3>
                    <textarea id="jsonPreview" rows="18" readonly></textarea>
                </div>

                <div class="panel">
                    <h3>📦 Respuesta</h3>
                    <div id="result" class="result">
                        <div class="result-pre">Esperando request...</div>
                    </div>
                </div>

                <div class="panel">
                    <h3>🗂️ JSON completo</h3>
                    <textarea id="jsonResponse" rows="18" readonly style="font-family:'Courier New',monospace;font-size:12px;background:#1e1e1e;color:#ce9178;border-color:#444;resize:vertical;">Esperando request...</textarea>
                </div>

                <!-- Botón oficial MercadoPago -->
                <div class="panel" id="mp-panel" style="display:none;">
                    <h3>💳 Botón de pago MercadoPago</h3>
                    <div id="mp-button-container"></div>
                </div>

            </div>
        </div>

        <div class="button-group">
            <button class="btn-primary" id="sendBtn" onclick="sendRequest()">
                <span id="btnText">🚀 Crear Pedido</span>
            </button>
            <button class="btn-secondary" onclick="updatePreview()">🔄 Actualizar preview</button>
            <button class="btn-secondary" onclick="clearResult()">🗑️ Limpiar</button>
        </div>

    </div><!-- /content -->
</div>

<script>
const ENDPOINT = (location.hostname === 'localhost' || location.hostname === '127.0.0.1')
    ? 'http://localhost/stadium/api/pedidos/crear.php'
    : 'https://stadium.planbsistemas.com.ar/api/pedidos/crear.php';

// ── Construir JSON desde el formulario ──────────────────────────────────────
function buildJSON() {
    const items = [];
    document.querySelectorAll('#itemsContainer .item-row').forEach(row => {
        const sku      = row.querySelector('.sku').value.trim();
        const titulo   = row.querySelector('.titulo').value.trim();
        const cantidad = parseInt(row.querySelector('.cantidad').value) || 1;
        const precio   = parseFloat(row.querySelector('.precio').value) || 0;
        if (sku) items.push({ sku, titulo, cantidad, precioUnitario: precio });
    });

    return {
        modo:    document.getElementById('modo').value,
        mp_mode: document.getElementById('mp_mode').value,
        items,
        pagador: {
            email:    document.getElementById('email').value.trim(),
            nombre:   document.getElementById('nombre').value.trim(),
            apellido: document.getElementById('apellido').value.trim(),
            telefono: {
                codigoArea: document.getElementById('codarea').value.trim(),
                numero:     document.getElementById('telefono').value.trim()
            },
            identificacion: {
                tipo:   document.getElementById('tipodoc').value,
                numero: document.getElementById('nrodoc').value.trim()
            },
            direccion: {
                calle:        document.getElementById('calle').value.trim(),
                numero:       document.getElementById('nrodir').value.trim(),
                codigoPostal: document.getElementById('codpostal').value.trim()
            }
        },
        envio: {
            tipo:   document.getElementById('tipoenvio').value,
            nombre: document.getElementById('nombreenvio').value.trim(),
            costo:  parseFloat(document.getElementById('costoenvio').value) || 0
        },
        back_urls: {
            success: document.getElementById('urlSuccess').value.trim(),
            failure: document.getElementById('urlFailure').value.trim(),
            pending: document.getElementById('urlPending').value.trim()
        }
    };
}

function updatePreview() {
    document.getElementById('jsonPreview').value = JSON.stringify(buildJSON(), null, 2);
}

// Actualizar preview cuando cambia cualquier input
document.addEventListener('input', updatePreview);
document.addEventListener('change', updatePreview);

// ── Items dinámicos ─────────────────────────────────────────────────────────
function addItem() {
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
        <div><label>SKU</label><input type="text" class="sku" placeholder="SKU"></div>
        <div><label>Título</label><input type="text" class="titulo" placeholder="Título"></div>
        <div><label>Cant.</label><input type="number" class="cantidad" value="1" min="1"></div>
        <div><label>Precio unit.</label><input type="number" class="precio" value="0" step="0.01"></div>
        <div><label>&nbsp;</label><button class="btn-icon btn-remove" onclick="removeItem(this)" title="Eliminar">✕</button></div>
    `;
    document.getElementById('itemsContainer').appendChild(row);
    updatePreview();
}

function removeItem(btn) {
    const rows = document.querySelectorAll('#itemsContainer .item-row');
    if (rows.length === 1) { alert('Debe haber al menos un item.'); return; }
    btn.closest('.item-row').remove();
    updatePreview();
}

// ── Enviar request ──────────────────────────────────────────────────────────
async function sendRequest() {
    const btn    = document.getElementById('sendBtn');
    const btnTxt = document.getElementById('btnText');
    const resDiv = document.getElementById('result');

    btn.disabled = true;
    btnTxt.innerHTML = '<span class="loader"></span> Procesando...';
    resDiv.className = 'result';
    resDiv.innerHTML = '<div class="result-pre">⏳ Enviando request...</div>';

    try {
        const payload = buildJSON();
        const response = await fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        document.getElementById('jsonResponse').value = JSON.stringify(data, null, 2);

        if (data.success) {
            resDiv.className = 'result success';
            const d = data.data || {};
            const payLink = d.pay_link || d.sandbox_init_point || d.init_point || null;
            const modeIcon = (d.mp_mode === 'production') ? '🚀' : '🧪';

            resDiv.innerHTML = `
                <div class="result-pre">✅ Pedido creado

<strong>ID Pedido:</strong>  ${d.idped ?? '—'}
<strong>ID Cliente:</strong> ${d.idcli ?? '—'}
<strong>Total:</strong>      $${d.total ?? '—'}
<strong>Estado:</strong>     ${d.estado ?? '—'}  (0 = pendiente)
<strong>Items:</strong>      ${d.items_count ?? '—'}
<strong>Pref. MP ID:</strong> ${d.preference_id ?? '—'}
<strong>Modo MP:</strong>    ${modeIcon} ${d.mp_mode ?? '—'}
${d.mp_error ? '\n⚠️ Error MP: ' + d.mp_error : ''}
</div>
                ${payLink ? `
                <div style="margin-top:12px;padding:12px;background:${d.mp_mode==='production'?'#fff3cd':'#d1ecf1'};border-radius:8px;">
                    <strong>${modeIcon} Link de pago:</strong><br>
                    <code style="display:block;margin-top:6px;padding:6px;background:white;border-radius:4px;font-size:12px;word-break:break-all;">${payLink}</code>
                </div>
                <a href="${payLink}" target="_blank" class="pay-link">${modeIcon} Ir a MercadoPago</a>
                ` : ''}
            `;

            // Renderizar botón oficial MP si hay preference_id y public key
            if (d.preference_id && d.mp_public_key) {
                renderMpButton(d.preference_id, d.mp_public_key);
            } else {
                document.getElementById('mp-panel').style.display = 'none';
            }
        } else {
            resDiv.className = 'result error';
            resDiv.innerHTML = `<div class="result-pre">❌ Error:\n${data.message || data.error || 'Error desconocido'}\n\n${JSON.stringify(data, null, 2)}</div>`;
        }
    } catch (err) {
        resDiv.className = 'result error';
        resDiv.innerHTML = `<div class="result-pre">❌ Error de conexión:\n${err.message}</div>`;
    } finally {
        btn.disabled = false;
        btnTxt.textContent = '🚀 Crear Pedido';
    }
}

function clearResult() {
    const resDiv = document.getElementById('result');
    resDiv.className = 'result';
    resDiv.innerHTML = '<div class="result-pre">Esperando request...</div>';
    document.getElementById('jsonResponse').value = 'Esperando request...';
    document.getElementById('mp-panel').style.display = 'none';
    document.getElementById('mp-button-container').innerHTML = '';
}

// ── Botón oficial MercadoPago (Wallet Brick) ─────────────────────────
 let mpBrickController = null;

async function renderMpButton(preferenceId, publicKey) {
    if (!publicKey || publicKey.includes('xxxxxxxx')) {
        document.getElementById('mp-panel').style.display = 'none';
        document.getElementById('mp-button-container').innerHTML =
            '<p style="color:#856404;font-size:13px">⚠️ Configurá las public keys en crear.php para mostrar el botón oficial.</p>';
        document.getElementById('mp-panel').style.display = 'block';
        return;
    }

    // Destruir brick anterior si existe
    if (mpBrickController) {
        try { await mpBrickController.unmount(); } catch(e) {}
        mpBrickController = null;
    }
    document.getElementById('mp-button-container').innerHTML = '';
    document.getElementById('mp-panel').style.display = 'block';

    try {
        const mp = new MercadoPago(publicKey, { locale: 'es-AR' });
        const bricksBuilder = mp.bricks();
        mpBrickController = await bricksBuilder.create('wallet', 'mp-button-container', {
            initialization: { preferenceId },
            customization: {
                texts: { valueProp: 'smart_option' },
                visual: { buttonBackground: 'default', borderRadius: '8px' }
            }
        });
    } catch(err) {
        document.getElementById('mp-button-container').innerHTML =
            `<p style="color:#dc3545;font-size:13px">⚠️ Error al cargar el botón MP: ${err.message}</p>`;
    }
}

// Inicializar preview
updatePreview();
</script>
</body>
</html>
