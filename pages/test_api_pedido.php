<?php
// pages/test_api_pedido.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API - Crear Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .item-row { border-bottom: 1px solid #dee2e6; padding: 15px 0; }
        .item-row:last-child { border-bottom: none; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Prueba de Carga de Pedido (API)</h4>
                        <a href="pedidos" class="btn btn-outline-light btn-sm">Volver a Pedidos</a>
                    </div>
                    <div class="card-body">
                        <form id="formPedido">
                            <!-- Cabecera -->
                            <h5 class="border-bottom pb-2 mb-3">Cabecera del Pedido</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">ID Cliente</label>
                                    <input type="number" class="form-control" name="idcli" value="1" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" class="form-control" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nro Remito</label>
                                    <input type="text" class="form-control" name="nroremito" placeholder="Ej: R0001-00001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bultos</label>
                                    <input type="number" class="form-control" name="bultos" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">¿Retiro?</label>
                                    <select class="form-select" name="retiro">
                                        <option value="0">No (Envío)</option>
                                        <option value="1">Sí (Retira)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Observaciones</label>
                                    <input type="text" class="form-control" name="observaciones">
                                </div>
                            </div>

                            <!-- Detalles -->
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h5 class="mb-0">Items del Pedido</h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                                    <i class="bi bi-plus-circle"></i> Agregar Item
                                </button>
                            </div>

                            <div id="itemsContainer">
                                <!-- Las filas de items se agregan aquí -->
                            </div>

                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-md-6 offset-md-6 text-end">
                                    <h4>Total: $<span id="totalGeneral">0.00</span></h4>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="bi bi-cloud-arrow-up"></i> Enviar Pedido a la API
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-dark text-white">JSON Resultante (Debug)</div>
                    <div class="card-body">
                        <pre id="jsonPreview" class="bg-light p-3 border rounded" style="max-height: 200px; overflow: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script id="itemTemplate" type="text/template">
        <div class="item-row row g-2">
            <div class="col-md-1">
                <label class="form-label d-md-none">ID Art</label>
                <input type="number" class="form-control" placeholder="ID Art" name="idart[]" required>
            </div>
            <div class="col-md-4">
                <label class="form-label d-md-none">Detalle</label>
                <input type="text" class="form-control" placeholder="Descripción del artículo" name="detalle_item[]">
            </div>
            <div class="col-md-2">
                <label class="form-label d-md-none">Cant</label>
                <input type="number" class="form-control" placeholder="Cant" name="cantidad[]" value="1" oninput="calcularTotales()" required>
            </div>
            <div class="col-md-2">
                <label class="form-label d-md-none">Precio</label>
                <input type="number" step="0.01" class="form-control" placeholder="Precio" name="precio[]" oninput="calcularTotales()" required>
            </div>
            <div class="col-md-2">
                <label class="form-label d-md-none">Subtotal</label>
                <input type="number" step="0.01" class="form-control bg-light" name="subtotal[]" readonly>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm mt-md-1" onclick="this.closest('.item-row').remove(); calcularTotales();">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </script>

    <script>
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const template = document.getElementById('itemTemplate').innerHTML;
            const div = document.createElement('div');
            div.innerHTML = template;
            container.appendChild(div.firstElementChild);
        }

        function calcularTotales() {
            const rows = document.querySelectorAll('.item-row');
            let totalGeneral = 0;

            rows.forEach(row => {
                const cant = parseFloat(row.querySelector('[name="cantidad[]"]').value) || 0;
                const precio = parseFloat(row.querySelector('[name="precio[]"]').value) || 0;
                const subtotal = cant * precio;
                row.querySelector('[name="subtotal[]"]').value = subtotal.toFixed(2);
                totalGeneral += subtotal;
            });

            document.getElementById('totalGeneral').innerText = totalGeneral.toFixed(2);
            updatePreview();
        }

        function updatePreview() {
            const data = getFormData();
            document.getElementById('jsonPreview').innerText = JSON.stringify(data, null, 2);
        }

        function getFormData() {
            const form = document.getElementById('formPedido');
            const fd = new FormData(form);
            
            const pedido = {
                idcli: fd.get('idcli'),
                fecha: fd.get('fecha'),
                nroremito: fd.get('nroremito'),
                bultos: fd.get('bultos'),
                retiro: fd.get('retiro'),
                observaciones: fd.get('observaciones'),
                total: document.getElementById('totalGeneral').innerText,
                detalles: []
            };

            const itemRows = document.querySelectorAll('.item-row');
            itemRows.forEach(row => {
                pedido.detalles.push({
                    idart: row.querySelector('[name="idart[]"]').value,
                    detalle: row.querySelector('[name="detalle_item[]"]').value,
                    cantidad: row.querySelector('[name="cantidad[]"]').value,
                    precio: row.querySelector('[name="precio[]"]').value,
                    total: row.querySelector('[name="subtotal[]"]').value,
                    terminado: 0
                });
            });

            return { pedido };
        }

        document.getElementById('formPedido').onsubmit = async (e) => {
            e.preventDefault();
            
            const data = getFormData();
            
            Swal.fire({
                title: 'Enviando...',
                text: 'Procesando el pedido mediante la API',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('api/pedidos_crear.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const text = await response.text();
                console.log('Respuesta cruda:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('La respuesta de la API no es un JSON válido: ' + text.substring(0, 100));
                }

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la API',
                        text: result.message || 'Error desconocido'
                    });
                }
            } catch (error) {
                console.error('Error detallado:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: error.message || 'No se pudo conectar con la API'
                });
            }
        };

        // Agregar un item inicial
        window.onload = addItem;
    </script>
</body>
</html>
