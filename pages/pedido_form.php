<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
require_once __DIR__ . '/../includes/bootstrap.php';

// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

// Obtener ID de pedido si es edición
$idPedido = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $idPedido ? 'Editar Pedido' : 'Nuevo Pedido'; ?> - Stadium</title>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
    </script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
        .estado-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>
    <div class="main-content" id="mainContent">
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <a href="pedidos" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline"><?php echo $idPedido ? 'Editar Pedido' : 'Nuevo Pedido'; ?></h5>
            </div>
        </div>
        <div class="content-area mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $idPedido ? 'Editar Pedido' : 'Nuevo Pedido'; ?></h5>
                </div>
                <div class="card-body">
                    <form id="formPedido">
                        <input type="hidden" id="pedidoId" value="<?php echo $idPedido ? $idPedido : ''; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cliente *</label>
                                <select class="form-select" id="idcli" required>
                                    <option value="">Seleccionar cliente...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <!-- Select oculto para el submit del form -->
                                <select class="d-none" id="estado">
                                    <option value="0">Pendiente</option>
                                    <option value="1">Confirmado</option>
                                    <option value="2">Preparando</option>
                                    <option value="3">Enviado</option>
                                    <option value="4">Entregado</option>
                                    <option value="5">Cancelado</option>
                                </select>
                                <!-- Dropdown visual con círculos de color -->
                                <div class="dropdown" id="estadoDropdown">
                                    <button class="btn btn-outline-secondary w-100 text-start d-flex align-items-center justify-content-between"
                                            type="button" id="estadoBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span id="estadoLabel"><span class="estado-dot" style="background:#ffc107"></span> Pendiente</span>
                                        <i class="bi bi-chevron-down ms-2"></i>
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="estadoBtn">
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(0,'#ffc107','Pendiente');return false;">
                                            <span class="estado-dot" style="background:#ffc107"></span> Pendiente</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(1,'#0dcaf0','Confirmado');return false;">
                                            <span class="estado-dot" style="background:#0dcaf0"></span> Confirmado</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(2,'#0d6efd','Preparando');return false;">
                                            <span class="estado-dot" style="background:#0d6efd"></span> Preparando</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(3,'#6c757d','Enviado');return false;">
                                            <span class="estado-dot" style="background:#6c757d"></span> Enviado</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(4,'#198754','Entregado');return false;">
                                            <span class="estado-dot" style="background:#198754"></span> Entregado</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setEstado(5,'#dc3545','Cancelado');return false;">
                                            <span class="estado-dot" style="background:#dc3545"></span> Cancelado</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nro Remito</label>
                                <input type="number" class="form-control" id="nroremito" min="0">
                            </div>
                             <div class="col-md-6">
                                 <label class="form-label"><i class="bi bi-truck me-1"></i> Costo de Envío ($)</label>
                                 <input type="number" step="0.01" class="form-control" id="envio" value="0.00" min="0" oninput="actualizarTablaProductos()">
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label">Total Pedido ($)</label>
                                 <input type="number" step="0.01" class="form-control fw-bold" id="total" value="0.00" readonly>
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label">Bultos</label>
                                 <input type="number" class="form-control" id="bultos" value="0" min="0">
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label">Fecha Entrega</label>
                                 <input type="date" class="form-control" id="fechaentrega">
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label">Retiro</label>
                                 <select class="form-select" id="retiro">
                                     <option value="0">No (Envío a domicilio)</option>
                                     <option value="1">Sí (Retiro en local)</option>
                                 </select>
                             </div>
                             <div class="col-12">
                                 <label class="form-label">Observaciones</label>
                                 <textarea class="form-control" id="observaciones" rows="3"></textarea>
                             </div>
                             <div class="col-12">
                                 <label class="form-label">Ubicación</label>
                                 <textarea class="form-control" id="ubicacion" rows="2"></textarea>
                             </div>
                             <div class="col-md-6">
                                 <label class="form-label"><i class="bi bi-truck"></i> ID Tracking (envío)</label>
                                 <input type="text" class="form-control" id="tracking_id" placeholder="Ej: OC123456789AR, CA123456789" maxlength="30">
                                 <div class="form-text">Código de seguimiento del transporte (OCA, Correo Argentino, etc.)</div>
                             </div>
                         </div>
                         <hr>
                         <h6><i class="bi bi-box"></i> Productos del Pedido</h6>
                         <div class="row g-3 mb-3">
                             <div class="col-md-4">
                                 <label class="form-label">Variante de Producto (Inventario)</label>
                                 <select class="form-select" id="articuloSelect">
                                     <option value="">Seleccionar artículo del inventario...</option>
                                 </select>
                             </div>
                             <div class="col-md-2">
                                 <label class="form-label">Cantidad</label>
                                 <input type="number" class="form-control" id="cantidadInput" min="1" value="1">
                             </div>
                             <div class="col-md-2">
                                 <label class="form-label">Precio Unit.</label>
                                 <input type="number" step="0.01" class="form-control" id="precioInput" min="0">
                             </div>
                             <div class="col-md-2">
                                 <label class="form-label">IVA %</label>
                                 <select class="form-select" id="ivaSelect">
                                     <option value="0">0%</option>
                                     <option value="10.5">10.5%</option>
                                     <option value="21" selected>21%</option>
                                 </select>
                             </div>
                             <div class="col-md-2">
                                 <label class="form-label">&nbsp;</label>
                                 <button type="button" class="btn btn-success d-block w-100" onclick="agregarProducto()">
                                     <i class="bi bi-plus"></i> Agregar
                                 </button>
                             </div>
                         </div>
                         <div class="col-12">
                             <label class="form-label">Detalle Adicional</label>
                             <input type="text" class="form-control" id="detalleInput" placeholder="Descripción adicional (opcional)">
                         </div>
                         <div class="table-responsive mt-3">
                             <table class="table table-sm table-bordered align-middle">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Artículo</th>
                                         <th>Detalle</th>
                                         <th>Cant.</th>
                                         <th>Precio</th>
                                         <th>IVA %</th>
                                         <th>Subtotal</th>
                                         <th>Total</th>
                                         <th>Acción</th>
                                     </tr>
                                 </thead>
                                 <tbody id="productosTabla">
                                 </tbody>
                                 <tfoot class="table-group-divider">
                                     <tr>
                                         <th colspan="6" class="text-end">Subtotal Productos:</th>
                                         <th class="text-end" id="subtotalProductos">$0.00</th>
                                         <th></th>
                                     </tr>
                                     <tr>
                                         <th colspan="6" class="text-end">Costo de Envío:</th>
                                         <th class="text-end text-primary" id="costoEnvioDisplay">$0.00</th>
                                         <th></th>
                                     </tr>
                                     <tr class="table-info fs-6">
                                         <th colspan="6" class="text-end">Total General (Productos + Envío):</th>
                                         <th class="text-end text-success fw-bold" id="totalGeneral">$0.00</th>
                                         <th></th>
                                     </tr>
                                 </tfoot>
                             </table>
                         </div>             
                        <div class="mt-4">
                            <a href="pedidos" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        const ESTADO_MAP = [
            { color: '#ffc107', label: 'Pendiente'  },
            { color: '#0dcaf0', label: 'Confirmado' },
            { color: '#0d6efd', label: 'Preparando' },
            { color: '#6c757d', label: 'Enviado'    },
            { color: '#198754', label: 'Entregado'  },
            { color: '#dc3545', label: 'Cancelado'  },
        ];

        function setEstado(value, color, label) {
            document.getElementById('estado').value = value;
            document.getElementById('estadoLabel').innerHTML =
                `<span class="estado-dot" style="background:${color}"></span> ${label}`;
        }

        const editingId = <?php echo $idPedido ? $idPedido : 'null'; ?>;
        let clientes = [];
        let articulos = [];
        let productosDelPedido = [];

        document.addEventListener('DOMContentLoaded', () => {
            cargarClientes();
            cargarArticulos();
            if (editingId) {
                cargarPedido(editingId);
            } else {
                document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
            }

            // listeners
            document.getElementById('articuloSelect').addEventListener('change', actualizarPrecio);
            document.getElementById('cantidadInput').addEventListener('input', calcularSubtotal);
            document.getElementById('precioInput').addEventListener('input', calcularSubtotal);
            document.getElementById('ivaSelect').addEventListener('change', calcularSubtotal);
        });

        async function cargarClientes() {
            try {
                const res = await fetch('./api/clientes.php');
                clientes = await res.json();
                const select = document.getElementById('idcli');
                select.innerHTML = '<option value="">Seleccionar cliente...</option>';
                clientes.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.apellido} ${c.nombre}</option>`;
                });
            } catch (err) {
                console.error('Error cargando clientes', err);
            }
        }

        async function cargarArticulos() {
            try {
                const res = await fetch('api/inventario.php');
                if (!res.ok) throw new Error('Error cargando inventario');
                const data = await res.json();
                if (!data.success) throw new Error('Error en respuesta del servidor');
                
                articulos = data.inventario;
                const select = document.getElementById('articuloSelect');
                select.innerHTML = '<option value="">Seleccionar artículo del inventario...</option>';
                
                articulos.forEach(item => {
                    // Construir nombre con producto + atributos
                    let nombreCompleto = item.producto_nombre;
                    
                    // Agregar atributos si existen
                    if (item.atributos && item.atributos.length > 0) {
                        const atributosTexto = item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                        nombreCompleto += ` - ${atributosTexto}`;
                    }
                    
                    // Agregar SKU de la variante
                    if (item.sku_variante) {
                        nombreCompleto += ` [${item.sku_variante}]`;
                    }
                    
                    // Usar precio de la variante, si no existe usar 0
                    const precio = parseFloat(item.precio || 0);
                    const precioFormateado = precio.toFixed(2);
                    
                    // Stock de la variante
                    const stock = parseInt(item.stock || 0);
                    const stockTexto = stock > 0 ? ` (Stock: ${stock})` : ' (Sin stock)';
                    const stockClass = stock > 0 ? '' : ' style="color: red;"';
                    
                    select.innerHTML += `<option value="${item.id}" data-precio="${precio}" data-stock="${stock}" data-sku="${item.sku_variante}"${stockClass}>${nombreCompleto} - $${precioFormateado}${stockTexto}</option>`;
                });
            } catch (err) {
                console.error('Error cargando inventario', err);
                showError('Error al cargar el inventario: ' + err.message);
                const select = document.getElementById('articuloSelect');
                select.innerHTML = '<option value="">Error al cargar inventario</option>';
            }
        }

        function actualizarPrecio() {
            const select = document.getElementById('articuloSelect');
            const opt = select.selectedOptions[0];
            if (opt && opt.value) {
                const precio = parseFloat(opt.dataset.precio) || 0;
                const stock = parseInt(opt.dataset.stock) || 0;
                
                document.getElementById('precioInput').value = precio.toFixed(2);
                
                const cantidadInput = document.getElementById('cantidadInput');
                if (stock > 0) {
                    cantidadInput.max = stock;
                    if (parseInt(cantidadInput.value) > stock) {
                        cantidadInput.value = stock;
                    }
                } else {
                    // Sin stock, permitir igual pero avisar
                    cantidadInput.max = '';
                    showWarning('Este artículo no tiene stock disponible');
                }
            } else {
                document.getElementById('precioInput').value = '';
                document.getElementById('cantidadInput').removeAttribute('max');
            }
            calcularSubtotal();
        }

        function calcularSubtotal() {
            const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 0;
            const precio = parseFloat(document.getElementById('precioInput').value) || 0;
            const iva = parseFloat(document.getElementById('ivaSelect').value) || 0;
            const subtotal = cantidad * precio;
            const impuestos = subtotal * (iva / 100);
            const total = subtotal + impuestos;
            // opcional: mostrar en consola
            // console.log({cantidad, precio, subtotal, impuestos, total});
        }

        function agregarProducto() {
            const articuloSelect = document.getElementById('articuloSelect');
            const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 0;
            const precio = parseFloat(document.getElementById('precioInput').value) || 0;
            const iva = parseFloat(document.getElementById('ivaSelect').value) || 0;
            const detalle = document.getElementById('detalleInput').value || '';

            if (!articuloSelect.value || cantidad <= 0 || precio <= 0) {
                Swal.fire('Error', 'Debe seleccionar un artículo, cantidad y precio válidos', 'error');
                return;
            }

            const nombre = articuloSelect.selectedOptions[0].textContent;
            const subtotal = cantidad * precio;
            const impuestos = subtotal * (iva / 100);
            const total = subtotal + impuestos;

            productosDelPedido.push({
                idart: parseInt(articuloSelect.value),
                nombre: nombre,
                detalle: detalle,
                cantidad: cantidad,
                precio: precio,
                iva: iva,
                subtotal: subtotal,
                total: total
            });

            actualizarTablaProductos();
            limpiarFormularioProducto();
        }

        function actualizarTablaProductos() {
            const tbody = document.getElementById('productosTabla');
            tbody.innerHTML = '';
            let subtotalProductos = 0;
            productosDelPedido.forEach((p, idx) => {
                subtotalProductos += p.total;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${p.nombre}</td>
                    <td>${p.detalle || '-'}</td>
                    <td class="text-center">${p.cantidad}</td>
                    <td class="text-end">$${p.precio.toFixed(2)}</td>
                    <td class="text-center">${p.iva}%</td>
                    <td class="text-end">$${p.subtotal.toFixed(2)}</td>
                    <td class="text-end fw-bold">$${p.total.toFixed(2)}</td>
                    <td class="text-center"><button class="btn btn-sm btn-danger" onclick="eliminarProducto(${idx})"><i class="bi bi-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            });

            const costoEnvio = parseFloat(document.getElementById('envio').value) || 0;
            const totalGeneral = subtotalProductos + costoEnvio;

            document.getElementById('subtotalProductos').textContent = `$${subtotalProductos.toFixed(2)}`;
            document.getElementById('costoEnvioDisplay').textContent = `$${costoEnvio.toFixed(2)}`;
            document.getElementById('totalGeneral').textContent = `$${totalGeneral.toFixed(2)}`;
            document.getElementById('total').value = totalGeneral.toFixed(2);
        }

        function eliminarProducto(index) {
            productosDelPedido.splice(index, 1);
            actualizarTablaProductos();
        }

        function limpiarFormularioProducto() {
            document.getElementById('articuloSelect').value = '';
            document.getElementById('cantidadInput').value = '1';
            document.getElementById('precioInput').value = '';
            document.getElementById('detalleInput').value = '';
            document.getElementById('ivaSelect').value = '21';
        }

        async function cargarPedido(id) {
            try {
                const res = await fetch(`./api/pedidos.php?id=${id}`);
                const pedido = await res.json();
                if (pedido.error) throw new Error(pedido.error);
                // completar campos
                document.getElementById('fecha').value = pedido.fecha.split('T')[0] || pedido.fecha;
                document.getElementById('idcli').value = pedido.idcli;
                const estadoVal = parseInt(pedido.estado);
                const em = ESTADO_MAP[estadoVal];
                if (em) setEstado(estadoVal, em.color, em.label);
                else document.getElementById('estado').value = estadoVal;
                document.getElementById('nroremito').value = pedido.nroremito || '';
                document.getElementById('envio').value = parseFloat(pedido.envio || 0).toFixed(2);
                document.getElementById('total').value = pedido.total || '0.00';
                document.getElementById('bultos').value = pedido.bultos || 0;
                document.getElementById('fechaentrega').value = pedido.fechaentrega || '';
                document.getElementById('retiro').value = pedido.retiro || 0;
                document.getElementById('observaciones').value = pedido.observaciones || '';
                document.getElementById('ubicacion').value = pedido.ubicacion || '';
                document.getElementById('tracking_id').value = pedido.tracking_id || '';

                // cargar productos
                await cargarProductosPedido(id);
            } catch (err) {
                console.error('Error cargando pedido', err);
                CRUDAlerts.error('Error', 'No se pudo cargar el pedido: ' + err.message);
            }
        }

        async function cargarProductosPedido(idped) {
            try {
                const res = await fetch(`./api/pedidos_det.php?idped=${idped}`);
                if (!res.ok) throw new Error('Error cargando detalles');
                const detalles = await res.json();
                productosDelPedido = [];
                if (Array.isArray(detalles)) {
                    detalles.forEach(det => {
                        // Buscar en inventario (articulos ahora contiene las variantes)
                        const itemInventario = articulos.find(a => a.id == det.idart);
                        
                        let nombre = '';
                        if (itemInventario) {
                            // Construir nombre con producto + atributos
                            nombre = itemInventario.producto_nombre;
                            if (itemInventario.atributos && itemInventario.atributos.length > 0) {
                                const atributosTexto = itemInventario.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                                nombre += ` - ${atributosTexto}`;
                            }
                            if (itemInventario.sku_variante) {
                                nombre += ` [${itemInventario.sku_variante}]`;
                            }
                        } else {
                            // Usar nombre del detalle si viene del API (compatibilidad)
                            nombre = det.producto_nombre || `Variante ID: ${det.idart}`;
                        }
                        
                        const subtotal = parseFloat(det.cantidad) * parseFloat(det.precio);
                        const total = parseFloat(det.total);
                        // intentar calcular IVA aproximado
                        let iva = 0;
                        if (subtotal > 0) {
                            const dif = total - subtotal;
                            iva = Math.round(((dif / subtotal) * 100) * 10) / 10;
                        }
                        productosDelPedido.push({
                            idart: parseInt(det.idart),
                            nombre: nombre,
                            detalle: det.detalle || '',
                            cantidad: parseFloat(det.cantidad),
                            precio: parseFloat(det.precio),
                            iva: iva,
                            subtotal: subtotal,
                            total: total
                        });
                    });
                }
                actualizarTablaProductos();
            } catch (err) {
                console.error('Error cargando productos del pedido', err);
            }
        }

        // manejar envío del formulario
        document.getElementById('formPedido').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (productosDelPedido.length === 0) {
                Swal.fire('Error', 'Debe agregar al menos un producto al pedido', 'error');
                return;
            }

            const subtotalProductos = productosDelPedido.reduce((acc, p) => acc + p.total, 0);
            const costoEnvio = parseFloat(document.getElementById('envio').value) || 0.00;
            const totalPedido = subtotalProductos + costoEnvio;

            const datos = {
                fecha: document.getElementById('fecha').value,
                idcli: parseInt(document.getElementById('idcli').value),
                estado: parseInt(document.getElementById('estado').value),
                nroremito: parseInt(document.getElementById('nroremito').value) || 0,
                subtotal: subtotalProductos,
                envio: costoEnvio,
                total: totalPedido,
                bultos: parseInt(document.getElementById('bultos').value) || 0,
                fechaentrega: document.getElementById('fechaentrega').value || null,
                retiro: parseInt(document.getElementById('retiro').value),
                observaciones: document.getElementById('observaciones').value,
                ubicacion: document.getElementById('ubicacion').value,
                tracking_id: document.getElementById('tracking_id').value || null,
                productos: productosDelPedido
            };

            const id = document.getElementById('pedidoId').value;
            const url = id ? `./api/pedidos.php?id=${id}` : './api/pedidos.php';
            const method = id ? 'PUT' : 'POST';

            try {
                console.log('Enviando pedido', { url, method, datos });
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const text = await res.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.warn('Respuesta no JSON:', text);
                }

                if (!res.ok) {
                    const msg = (data && data.message) ? data.message : text || 'Error en la petición';
                    console.error('Error en response:', res.status, msg);
                    Swal.fire('Error', `Error ${res.status}: ${msg}`, 'error');
                    return;
                }

                if (data && data.success) {
                    if (id) CRUDAlerts.updated('Pedido'); else CRUDAlerts.created('Pedido');
                    window.location.href = 'pedidos';
                } else {
                    const msg = data && data.message ? data.message : (text || 'Error en la operación');
                    console.error('Operación no exitosa:', msg, data);
                    Swal.fire('Error', msg, 'error');
                }
            } catch (err) {
                console.error('Error guardando pedido', err);
                CRUDAlerts.connectionError();
            }
        });
    </script>
</body>
</html>
