<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - POST /api/pedidos/crear</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        h1 { color: #569cd6; margin-bottom: 6px; font-size: 1.2rem; }
        .endpoint { color: #9cdcfe; font-size: 0.85rem; margin-bottom: 20px; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; height: calc(100vh - 120px); }

        .panel { display: flex; flex-direction: column; gap: 8px; }
        label { color: #c8c8c8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }

        textarea {
            flex: 1;
            background: #252526;
            color: #ce9178;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            padding: 12px;
            font-family: monospace;
            font-size: 0.82rem;
            resize: none;
            outline: none;
            transition: border-color 0.2s;
        }
        textarea:focus { border-color: #569cd6; }
        textarea.response { color: #9cdcfe; }
        textarea.error { color: #f48771; }
        textarea.ok    { color: #4ec9b0; }

        .btns { display: flex; gap: 8px; align-items: center; }
        button {
            padding: 8px 22px;
            border: none;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        button:hover { opacity: 0.85; }
        #btnEnviar  { background: #0e639c; color: #fff; }
        #btnLimpiar { background: #3c3c3c; color: #d4d4d4; }
        #btnFormato { background: #4c4c4c; color: #d4d4d4; }

        #status {
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 3px;
            background: #2d2d2d;
            min-width: 60px;
            text-align: center;
        }
        .s2xx { color: #4ec9b0; }
        .s4xx { color: #ce9178; }
        .s5xx { color: #f48771; }

        .loading { color: #6a9955; }

        #time { font-size: 0.75rem; color: #6a9955; margin-left: auto; }

        /* Modo: URL de destino */
        .url-bar {
            display: flex; gap: 8px; align-items: center; margin-bottom: 12px;
        }
        .url-bar input {
            flex: 1;
            background: #252526;
            color: #d4d4d4;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            padding: 6px 10px;
            font-family: monospace;
            font-size: 0.82rem;
            outline: none;
        }
        .url-bar input:focus { border-color: #569cd6; }
        .url-bar span { color: #6a9955; font-size: 0.8rem; white-space: nowrap; }
    </style>
</head>
<body>

<h1>Test &mdash; POST /api/pedidos/crear</h1>
<p class="endpoint">Formulario para probar el endpoint de creación de pedidos con MercadoPago</p>

<div class="url-bar">
    <span>URL:</span>
    <input type="text" id="urlDestino" value="api/pedidos/crear.php">
    <button id="btnEnviar" onclick="enviar()">&#9654; Enviar</button>
    <button id="btnFormato" onclick="formatear()">{ } Formatear</button>
    <button id="btnLimpiar" onclick="limpiar()">&#215; Limpiar resp.</button>
    <span id="status"></span>
    <span id="time"></span>
</div>

<div class="grid">
    <div class="panel">
        <label>JSON de entrada (request body):</label>
        <textarea id="jsonInput" spellcheck="false">{
  "modo": "mercadopago",
  "mp_mode": "sandbox",
  "items": [
    {
      "sku": "CAM-AZUL-M",
      "titulo": "Camiseta Azul Talle M",
      "cantidad": 2,
      "precioUnitario": 1500.00
    },
    {
      "sku": "PAN-NEGRO-L",
      "titulo": "Pantalón Negro Talle L",
      "cantidad": 1,
      "precioUnitario": 2800.00
    }
  ],
  "pagador": {
    "email": "test@test.com",
    "nombre": "Juan",
    "apellido": "Pérez",
    "telefono": {
      "codigoArea": "0341",
      "numero": "4551234"
    },
    "identificacion": {
      "tipo": "DNI",
      "numero": "30.123.456"
    },
    "direccion": {
      "calle": "Av. Corrientes",
      "numero": "1234",
      "codigoPostal": "2000"
    }
  },
  "envio": {
    "tipo": "domicilio",
    "nombre": "Envío a domicilio",
    "costo": 500
  },
  "back_urls": {
    "success": "http://localhost/stadium/pago-ok.php",
    "failure": "http://localhost/stadium/pago-error.php",
    "pending": "http://localhost/stadium/pago-pendiente.php"
  }
}</textarea>
    </div>

    <div class="panel">
        <label>Respuesta JSON:</label>
        <textarea id="jsonOutput" class="response" readonly spellcheck="false">// La respuesta aparecerá aquí...</textarea>
    </div>
</div>

<script>
async function enviar() {
    const url    = document.getElementById('urlDestino').value.trim();
    const input  = document.getElementById('jsonInput').value.trim();
    const output = document.getElementById('jsonOutput');
    const status = document.getElementById('status');
    const time   = document.getElementById('time');

    // Validar JSON antes de enviar
    let parsed;
    try {
        parsed = JSON.parse(input);
    } catch (e) {
        output.value = '// JSON inválido:\n' + e.message;
        output.className = 'response error';
        status.textContent = 'JSON ERR';
        status.className = 's4xx';
        time.textContent = '';
        return;
    }

    status.textContent = '...';
    status.className = 'loading';
    time.textContent = '';
    output.value = '// Enviando...';
    output.className = 'response loading';

    const t0 = Date.now();
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(parsed)
        });

        const elapsed = Date.now() - t0;
        const rawText = await res.text();

        // Intentar parsear como JSON
        let pretty;
        try {
            pretty = JSON.stringify(JSON.parse(rawText), null, 2);
        } catch (_) {
            pretty = rawText; // Si no es JSON, mostrar crudo
        }

        output.value = pretty;
        status.textContent = res.status;
        time.textContent = elapsed + ' ms';

        if (res.status >= 200 && res.status < 300) {
            status.className = 's2xx';
            output.className = 'response ok';
        } else if (res.status >= 400 && res.status < 500) {
            status.className = 's4xx';
            output.className = 'response error';
        } else {
            status.className = 's5xx';
            output.className = 'response error';
        }

    } catch (err) {
        const elapsed = Date.now() - t0;
        output.value = '// Error de red o CORS:\n' + err.message;
        output.className = 'response error';
        status.textContent = 'NET ERR';
        status.className = 's5xx';
        time.textContent = elapsed + ' ms';
    }
}

function formatear() {
    const ta = document.getElementById('jsonInput');
    try {
        ta.value = JSON.stringify(JSON.parse(ta.value), null, 2);
    } catch (e) {
        alert('JSON inválido: ' + e.message);
    }
}

function limpiar() {
    const output = document.getElementById('jsonOutput');
    output.value = '// La respuesta aparecerá aquí...';
    output.className = 'response';
    document.getElementById('status').textContent = '';
    document.getElementById('status').className = '';
    document.getElementById('time').textContent = '';
}

// Ctrl+Enter para enviar
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        enviar();
    }
});
</script>

</body>
</html>
