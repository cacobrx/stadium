<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Direcciones API</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; color: #333; padding: 20px; }
        h1 { margin-bottom: 24px; color: #1a1a2e; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .card h2 {
            font-size: 1rem;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }
        .card h2.crear  { color: #2e7d32; }
        .card h2.modif  { color: #1565c0; }
        .card h2.delete { color: #c62828; }
        label { display: block; font-size: .8rem; color: #555; margin: 8px 0 3px; }
        input, textarea {
            width: 100%; padding: 7px 9px; border: 1px solid #ccc;
            border-radius: 6px; font-size: .88rem;
        }
        textarea { resize: vertical; min-height: 60px; }
        .token-row { margin-bottom: 16px; }
        button {
            margin-top: 14px; width: 100%; padding: 10px;
            border: none; border-radius: 6px;
            font-size: .92rem; font-weight: bold; cursor: pointer; color: #fff;
        }
        .btn-crear  { background: #2e7d32; }
        .btn-modif  { background: #1565c0; }
        .btn-delete { background: #c62828; }
        button:hover { opacity: .88; }
        .resp {
            margin-top: 12px; padding: 10px 12px;
            border-radius: 6px; font-size: .82rem;
            background: #f5f5f5; border: 1px solid #ddd;
            white-space: pre-wrap; word-break: break-all;
            min-height: 48px; max-height: 180px; overflow-y: auto;
        }
        .resp.ok  { background: #e8f5e9; border-color: #81c784; }
        .resp.err { background: #ffebee; border-color: #e57373; }

        /* Token global compartido */
        #shared-token-wrap {
            background: #fff3e0;
            border: 1px solid #ffb74d;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        #shared-token-wrap h3 { font-size: .95rem; color: #e65100; margin-bottom: 8px; }
        #shared-token { font-size: .88rem; }
    </style>
</head>
<body>

<h1>Test &mdash; Direcciones API</h1>

<!-- Token compartido -->
<div id="shared-token-wrap">
    <h3>Bearer Token (se aplica a todos los formularios)</h3>
    <input type="text" id="shared-token" placeholder="Pegar el token del cliente aquí…">
</div>

<div class="grid">

    <!-- ── CREAR ──────────────────────────────────────────────── -->
    <div class="card">
        <h2 class="crear">POST /api/direcciones/crear.php</h2>

        <label>Descripción</label>
        <input type="text" id="c-descripcion" value="Trabajo">

        <label>Dirección</label>
        <input type="text" id="c-direccion" value="Rivadavia 423">

        <label>Piso</label>
        <input type="text" id="c-piso" value="">

        <label>Departamento</label>
        <input type="text" id="c-departamento" value="">

        <label>Código Postal</label>
        <input type="text" id="c-cpostal" value="6720">

        <label>Ciudad</label>
        <input type="text" id="c-ciudad" value="San Andres de Giles">

        <label>Provincia</label>
        <input type="text" id="c-provincia" value="Buenos Aires">

        <label>Indicaciones</label>
        <textarea id="c-indicaciones">Llamar no anda el timbre</textarea>

        <label>Principal (0 / 1)</label>
        <input type="number" id="c-principal" value="0" min="0" max="1">

        <button class="btn-crear" onclick="crearDireccion()">Crear dirección</button>
        <div class="resp" id="resp-crear">—</div>
    </div>

    <!-- ── MODIFICAR ─────────────────────────────────────────── -->
    <div class="card">
        <h2 class="modif">POST /api/direcciones/modifica.php</h2>

        <label>ID de la dirección</label>
        <input type="number" id="m-id" value="8">

        <label>Descripción</label>
        <input type="text" id="m-descripcion" value="Principal">

        <label>Dirección</label>
        <input type="text" id="m-direccion" value="Quintanas 581">

        <label>Piso</label>
        <input type="text" id="m-piso" value="">

        <label>Departamento</label>
        <input type="text" id="m-departamento" value="">

        <label>Código Postal</label>
        <input type="text" id="m-cpostal" value="6720">

        <label>Ciudad</label>
        <input type="text" id="m-ciudad" value="San Andres de Giles">

        <label>Provincia</label>
        <input type="text" id="m-provincia" value="Buenos Aires">

        <label>Indicaciones</label>
        <textarea id="m-indicaciones"></textarea>

        <label>Principal (0 / 1)</label>
        <input type="number" id="m-principal" value="1" min="0" max="1">

        <button class="btn-modif" onclick="modificarDireccion()">Modificar dirección</button>
        <div class="resp" id="resp-modif">—</div>
    </div>

    <!-- ── ELIMINAR ───────────────────────────────────────────── -->
    <div class="card">
        <h2 class="delete">POST /api/direcciones/detele.php</h2>

        <label>ID de la dirección a eliminar</label>
        <input type="number" id="d-id" value="8">

        <button class="btn-delete" onclick="eliminarDireccion()">Eliminar dirección</button>
        <div class="resp" id="resp-delete">—</div>
    </div>

</div>

<script>
    function getToken() {
        return document.getElementById('shared-token').value.trim();
    }

    function mostrarResp(id, data, ok) {
        const el = document.getElementById(id);
        el.textContent = JSON.stringify(data, null, 2);
        el.className = 'resp ' + (ok ? 'ok' : 'err');
    }

    async function llamar(endpoint, body, respId) {
        const token = getToken();
        if (!token) {
            mostrarResp(respId, { error: 'Ingresá el token primero' }, false);
            return;
        }
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(body)
            });
            const json = await res.json();
            mostrarResp(respId, json, res.ok && json.success);
        } catch (e) {
            mostrarResp(respId, { error: e.message }, false);
        }
    }

    function crearDireccion() {
        llamar('crear.php', {
            descripcion:   document.getElementById('c-descripcion').value,
            direccion:     document.getElementById('c-direccion').value,
            piso:          document.getElementById('c-piso').value,
            departamento:  document.getElementById('c-departamento').value,
            cpostal:       document.getElementById('c-cpostal').value,
            ciudad:        document.getElementById('c-ciudad').value,
            provincia:     document.getElementById('c-provincia').value,
            indicaciones: document.getElementById('c-indicaciones').value,
            principal:     parseInt(document.getElementById('c-principal').value) || 0
        }, 'resp-crear');
    }

    function modificarDireccion() {
        llamar('modifica.php', {
            id:            parseInt(document.getElementById('m-id').value),
            descripcion:   document.getElementById('m-descripcion').value,
            direccion:     document.getElementById('m-direccion').value,
            piso:          document.getElementById('m-piso').value,
            departamento:  document.getElementById('m-departamento').value,
            cpostal:       document.getElementById('m-cpostal').value,
            ciudad:        document.getElementById('m-ciudad').value,
            provincia:     document.getElementById('m-provincia').value,
            indicaciones: document.getElementById('m-indicaciones').value,
            principal:     parseInt(document.getElementById('m-principal').value) || 0
        }, 'resp-modif');
    }

    function eliminarDireccion() {
        const id = parseInt(document.getElementById('d-id').value);
        if (!id) {
            mostrarResp('resp-delete', { error: 'Ingresá un ID válido' }, false);
            return;
        }
        if (!confirm('¿Eliminar la dirección ID ' + id + '?')) return;
        llamar('detele.php', { id }, 'resp-delete');
    }
</script>

</body>
</html>
