<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
require_once __DIR__ . '/../includes/bootstrap.php';

// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

// Obtener ID de presupuesto si es edición
$idPresupuesto = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $idPresupuesto ? 'Editar Presupuesto' : 'Nuevo Presupuesto'; ?> - Stadium</title>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
    </script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>
    <div class="main-content" id="mainContent">
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button type="button" class="btn btn-outline-primary me-2" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <a href="presupuestos" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <h5 class="mb-0 d-inline"><?php echo $idPresupuesto ? 'Editar Presupuesto' : 'Nuevo Presupuesto'; ?></h5>
            </div>
        </div>
        <div class="content-area mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $idPresupuesto ? 'Editar Presupuesto' : 'Nuevo Presupuesto'; ?></h5>
                </div>
                <div class="card-body">
                    <form id="formPresupuesto">
                        <input type="hidden" id="presupuestoId" value="<?php echo $idPresupuesto ? $idPresupuesto : ''; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cliente *</label>
                                <select class="form-select" id="idcli">
                                    <option value="">Seleccionar cliente...</option>
                                </select>
                            </div>
                               <div class="col-md-6"></div>
                               <!-- Datos editables del cliente -->
                               <div class="col-md-3">
                                   <label class="form-label">Nombre y Apellido</label>
                                   <input type="text" class="form-control" id="cli_nombre" placeholder="Nombre y Apellido">
                               </div>
                               <div class="col-md-3">
                                   <label class="form-label">Empresa</label>
                                   <input type="text" class="form-control" id="cli_empresa" placeholder="Empresa">
                               </div>
                               <div class="col-md-3">
                                   <label class="form-label">Teléfono</label>
                                   <input type="text" class="form-control" id="cli_telefono" placeholder="Teléfono">
                               </div>
                               <div class="col-md-3">
                                   <label class="form-label">Email</label>
                                   <input type="email" class="form-control" id="cli_email" placeholder="Email">
                               </div>
                               <div class="col-md-3">
                                   <label class="form-label">Tipo de Proyecto</label>
                                   <input type="text" class="form-control" id="tipoProyecto" maxlength="30" placeholder="Tipo de Proyecto">
                               </div>
                               <div class="col-md-3">
                                   <label class="form-label">Origen</label>
                                   <select class="form-select" id="origen">
                                       <option value="">Seleccionar origen...</option>
                                   </select>
                               </div>
                            <div class="col-md-4">
                                <label class="form-label">Nro Presupuesto</label>
                                <input type="text" class="form-control" id="nro_presupuesto" readonly tabindex="-1" style="background:#f8f9fa;cursor:not-allowed;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="estado">
                                    <option value="0">Borrador</option>
                                    <option value="1">Enviado</option>
                                    <option value="2">Aprobado</option>
                                    <option value="3">Rechazado</option>
                                    <option value="4">Vencido</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Validez (días)</label>
                                <input type="number" class="form-control" id="validez" value="30" min="1">
                                <small class="text-muted">Días de validez del presupuesto</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total</label>
                                <input type="number" step="0.01" class="form-control" id="total" value="0.00" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" readonly>
                                <small class="text-muted">Se calcula automáticamente</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones (visibles para el cliente)</label>
                                <textarea class="form-control" id="observaciones" rows="2" placeholder="Condiciones de entrega, formas de pago, etc."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ubicación / Dirección de Entrega</label>
                                <textarea class="form-control" id="ubicacion" rows="2" placeholder="Dirección donde se entregará..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas Internas (no visibles para el cliente)</label>
                                <textarea class="form-control" id="notas_internas" rows="2" placeholder="Notas privadas del presupuesto..."></textarea>
                            </div>
                        </div>
                        <hr>
                        <h6><i class="bi bi-box"></i> Productos del Presupuesto</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="form-label">Tipo de Producto</label>
                                <select class="form-select" id="tipoProducto" onchange="cargarProductosPorTipo()">
                                    <option value="producto">Productos</option>
                                    <option value="equipamiento">Equipamiento</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Producto</label>
                                <select class="form-select" id="productoSelect">
                                    <option value="">Seleccionar producto...</option>
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
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-10">
                                <label class="form-label">Detalle Adicional</label>
                                <input type="text" class="form-control" id="detalleInput" placeholder="Descripción adicional, especificaciones, etc.">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-success d-block w-100" onclick="agregarProducto()">
                                    <i class="bi bi-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Producto</th>
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
                                <tfoot class="table-info">
                                    <tr>
                                        <th colspan="7">Total General:</th>
                                        <th id="totalGeneral">$0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-4">
                            <a href="presupuestos" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Presupuesto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal editar producto -->
    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarProductoLabel"><i class="bi bi-pencil"></i> Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editIdx">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <p class="form-control-plaintext" id="editNombre"></p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control" id="editCantidad" min="1" step="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio Unitario *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="editPrecio" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IVA %</label>
                            <select class="form-select" id="editIva">
                                <option value="0">0%</option>
                                <option value="10.5">10.5%</option>
                                <option value="21">21%</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subtotal (sin IVA)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="editSubtotalPreview" readonly tabindex="-1" style="background:#f8f9fa">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Total (con IVA)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control fw-bold" id="editTotalPreview" readonly tabindex="-1" style="background:#f8f9fa">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Detalle Adicional</label>
                            <input type="text" class="form-control" id="editDetalle" placeholder="Descripción adicional...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarEdicionProducto()"><i class="bi bi-check-lg"></i> Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        const editingId = <?php echo $idPresupuesto ? $idPresupuesto : 'null'; ?>;
        let clientes = [];
        let productos = [];
        let equipamiento = [];
        let productosDelPresupuesto = [];

        document.addEventListener('DOMContentLoaded', async () => {
            // Cargar datos en el orden correcto
            await cargarClientes();
            await cargarProductos();
            await cargarEquipamiento();
            await cargarOrigenes();
            
            if (editingId) {
                cargarPresupuesto(editingId);
            } else {
                document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
                calcularFechaVencimiento();
                await asignarNroPresupuesto();
            }
        async function asignarNroPresupuesto() {
            try {
                const anio = new Date().getFullYear();
                const res = await fetch('./api/presupuestos.php?ultimo_nro=1&anio=' + anio);
                const data = await res.json();
                let siguiente = 1;
                if (data && data.ultimo_nro) {
                    siguiente = parseInt(data.ultimo_nro, 10) + 1;
                }
                const nro = 'PRES-' + anio + '-' + String(siguiente).padStart(3, '0');
                document.getElementById('nro_presupuesto').value = nro;
            } catch (e) {
                document.getElementById('nro_presupuesto').value = '';
            }
        }

            // listeners
            document.getElementById('productoSelect').addEventListener('change', actualizarPrecio);
            document.getElementById('cantidadInput').addEventListener('input', calcularSubtotal);
            document.getElementById('precioInput').addEventListener('input', calcularSubtotal);
            document.getElementById('ivaSelect').addEventListener('change', calcularSubtotal);
            document.getElementById('fecha').addEventListener('change', calcularFechaVencimiento);
            document.getElementById('validez').addEventListener('input', calcularFechaVencimiento);
        });

        async function cargarOrigenes() {
            try {
                const res = await fetch('./api/tablas.php?tipo=ORI');
                const data = await res.json();
                const select = document.getElementById('origen');
                select.innerHTML = '<option value="">Seleccionar origen...</option>';
                (data.registros || []).forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.valor;
                    opt.textContent = o.descripcion;
                    select.appendChild(opt);
                });
            } catch (e) {
                console.error('Error cargando orígenes', e);
            }
        }

        async function cargarClientes() {
            try {
                const res = await fetch('./api/clientes.php');
                clientes = await res.json();
                const select = document.getElementById('idcli');
                select.innerHTML = '<option value="">Seleccionar cliente...</option>';
                clientes.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.apellido} ${c.nombre}</option>`;
                });
                   // Listener para autocompletar datos al seleccionar cliente
                   select.addEventListener('change', function() {
                       const cli = clientes.find(x => x.id == select.value);
                       document.getElementById('cli_nombre').value = cli ? (cli.apellido + ' ' + cli.nombre) : '';
                       document.getElementById('cli_empresa').value = cli ? (cli.empresa || '') : '';
                       document.getElementById('cli_telefono').value = cli ? (cli.telefono || '') : '';
                       document.getElementById('cli_email').value = cli ? (cli.email || '') : '';
                   });
            } catch (err) {
                console.error('Error cargando clientes', err);
            }
        }

        async function cargarProductos() {
            try {
                const res = await fetch('./api/productos.php');
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                const data = await res.json();
                
                // La API devuelve {success: true, productos: [...]}
                if (data.success && data.productos) {
                    productos = Array.isArray(data.productos) ? data.productos : [];
                } else {
                    productos = [];
                    console.warn('Respuesta inesperada de la API de productos:', data);
                }
                
                console.log('Productos cargados:', productos.length);
                // Mostrar productos por defecto al cargar
                if (document.getElementById('tipoProducto').value === 'producto') {
                    mostrarProductos();
                }
            } catch (err) {
                console.error('Error cargando productos', err);
                Swal.fire('Error', 'No se pudieron cargar los productos: ' + err.message, 'error');
            }
        }

        async function cargarEquipamiento() {
            try {
                const res = await fetch('./api/equipamiento_profesional.php');
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                const data = await res.json();
                
                // Verificar el formato de la respuesta
                if (Array.isArray(data)) {
                    equipamiento = data;
                } else if (data.success && Array.isArray(data.equipamiento)) {
                    equipamiento = data.equipamiento;
                } else if (Array.isArray(data.items)) {
                    equipamiento = data.items;
                } else {
                    equipamiento = [];
                    console.warn('Respuesta inesperada de la API de equipamiento:', data);
                }
                
                console.log('Equipamiento cargado:', equipamiento.length);
            } catch (err) {
                console.error('Error cargando equipamiento', err);
                Swal.fire('Error', 'No se pudo cargar el equipamiento: ' + err.message, 'error');
            }
        }

        function cargarProductosPorTipo() {
            const tipo = document.getElementById('tipoProducto').value;
            if (tipo === 'producto') {
                mostrarProductos();
            } else {
                mostrarEquipamiento();
            }
            document.getElementById('productoSelect').value = '';
            document.getElementById('precioInput').value = '';
        }

        function mostrarProductos() {
            const select = document.getElementById('productoSelect');
            select.innerHTML = '<option value="">Seleccionar producto...</option>';
            
            console.log('Mostrando productos:', productos.length);
            
            if (productos.length === 0) {
                select.innerHTML += '<option value="" disabled>No hay productos disponibles</option>';
                return;
            }
            
            productos.forEach(p => {
                const precio = parseFloat(p.precio || 0);
                const stock = parseInt(p.stock || 0);
                const stockTexto = stock > 0 ? ` (Stock: ${stock})` : ' (Sin stock)';
                select.innerHTML += `<option value="${p.id}" data-precio="${precio}" data-nombre="${p.nombre}" data-sku="${p.sku || ''}">${p.sku ? '[' + p.sku + '] ' : ''}${p.nombre} - $${precio.toFixed(2)}${stockTexto}</option>`;
            });
        }

        function mostrarEquipamiento() {
            const select = document.getElementById('productoSelect');
            select.innerHTML = '<option value="">Seleccionar equipamiento...</option>';
            
            console.log('Mostrando equipamiento:', equipamiento.length);
            
            if (equipamiento.length === 0) {
                select.innerHTML += '<option value="" disabled>No hay equipamiento disponible</option>';
                return;
            }
            
            equipamiento.forEach(e => {
                const precio = parseFloat(e.precio || 0);
                const codigo = 'EQ-' + String(e.id).padStart(5, '0');
                select.innerHTML += `<option value="${e.id}" data-precio="${precio}" data-nombre="${e.nombre}" data-codigo="${codigo}">[${codigo}] ${e.nombre} - $${precio.toFixed(2)}</option>`;
            });
        }

        function actualizarPrecio() {
            const select = document.getElementById('productoSelect');
            const opt = select.selectedOptions[0];
            if (opt && opt.value) {
                const precio = parseFloat(opt.dataset.precio) || 0;
                document.getElementById('precioInput').value = precio.toFixed(2);
            } else {
                document.getElementById('precioInput').value = '';
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
        }

        function calcularFechaVencimiento() {
            const fecha = document.getElementById('fecha').value;
            const validez = parseInt(document.getElementById('validez').value) || 30;
            
            if (fecha) {
                const fechaInicio = new Date(fecha);
                fechaInicio.setDate(fechaInicio.getDate() + validez);
                document.getElementById('fecha_vencimiento').value = fechaInicio.toISOString().split('T')[0];
            }
        }

        function agregarProducto() {
            const tipoProducto = document.getElementById('tipoProducto').value;
            const productoSelect = document.getElementById('productoSelect');
            const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 0;
            const precio = parseFloat(document.getElementById('precioInput').value) || 0;
            const iva = parseFloat(document.getElementById('ivaSelect').value) || 0;
            const detalle = document.getElementById('detalleInput').value || '';

            if (!productoSelect.value || cantidad <= 0 || precio <= 0) {
                Swal.fire('Error', 'Debe seleccionar un producto, cantidad y precio válidos', 'error');
                return;
            }

            const opt = productoSelect.selectedOptions[0];
            const nombre = opt.dataset.nombre || opt.textContent;
            const codigo = opt.dataset.sku || opt.dataset.codigo || '';
            const subtotal = cantidad * precio;
            const impuestos = subtotal * (iva / 100);
            const total = subtotal + impuestos;

            productosDelPresupuesto.push({
                idart: parseInt(productoSelect.value),
                tipo_producto: tipoProducto,
                nombre: nombre,
                codigo: codigo,
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
            let totalGeneral = 0;
            productosDelPresupuesto.forEach((p, idx) => {
                totalGeneral += p.total;
                const tipoBadge = p.tipo_producto === 'equipamiento' ? 
                    '<span class="badge bg-info">Equipamiento</span>' : 
                    '<span class="badge bg-primary">Producto</span>';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${tipoBadge}</td>
                    <td>${p.codigo ? '[' + p.codigo + '] ' : ''}${p.nombre}</td>
                    <td>${p.detalle || '-'}</td>
                    <td class="text-center">${p.cantidad}</td>
                    <td class="text-end">$${p.precio.toFixed(2)}</td>
                    <td class="text-center">${p.iva}%</td>
                    <td class="text-end">$${p.subtotal.toFixed(2)}</td>
                    <td class="text-end fw-bold">$${p.total.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-warning me-1" onclick="abrirEditarProducto(${idx})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${idx})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('totalGeneral').textContent = `$${totalGeneral.toFixed(2)}`;
            document.getElementById('total').value = totalGeneral.toFixed(2);
        }

        function abrirEditarProducto(idx) {
            const p = productosDelPresupuesto[idx];
            document.getElementById('editIdx').value = idx;
            document.getElementById('editNombre').textContent = (p.codigo ? '[' + p.codigo + '] ' : '') + p.nombre;
            document.getElementById('editCantidad').value = p.cantidad;
            document.getElementById('editPrecio').value = p.precio.toFixed(2);
            document.getElementById('editIva').value = p.iva;
            document.getElementById('editDetalle').value = p.detalle || '';
            recalcularPreviewEdit();
            new bootstrap.Modal(document.getElementById('modalEditarProducto')).show();
        }

        function recalcularPreviewEdit() {
            const cantidad = parseFloat(document.getElementById('editCantidad').value) || 0;
            const precio   = parseFloat(document.getElementById('editPrecio').value)   || 0;
            const iva      = parseFloat(document.getElementById('editIva').value)      || 0;
            const subtotal = cantidad * precio;
            const total    = subtotal * (1 + iva / 100);
            document.getElementById('editSubtotalPreview').value = subtotal.toFixed(2);
            document.getElementById('editTotalPreview').value    = total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['editCantidad', 'editPrecio', 'editIva'].forEach(id =>
                document.getElementById(id).addEventListener('input', recalcularPreviewEdit)
            );
        });

        function guardarEdicionProducto() {
            const idx      = parseInt(document.getElementById('editIdx').value);
            const cantidad = parseFloat(document.getElementById('editCantidad').value) || 0;
            const precio   = parseFloat(document.getElementById('editPrecio').value)   || 0;
            const iva      = parseFloat(document.getElementById('editIva').value)      || 0;
            const detalle  = document.getElementById('editDetalle').value.trim();

            if (cantidad <= 0 || precio < 0) {
                Swal.fire('Error', 'Cantidad y precio deben ser válidos', 'error');
                return;
            }

            const subtotal = cantidad * precio;
            const total    = subtotal * (1 + iva / 100);

            productosDelPresupuesto[idx] = {
                ...productosDelPresupuesto[idx],
                cantidad, precio, iva, detalle, subtotal, total
            };

            bootstrap.Modal.getInstance(document.getElementById('modalEditarProducto')).hide();
            actualizarTablaProductos();
        }

        function eliminarProducto(index) {
            productosDelPresupuesto.splice(index, 1);
            actualizarTablaProductos();
        }

        function limpiarFormularioProducto() {
            document.getElementById('productoSelect').value = '';
            document.getElementById('cantidadInput').value = '1';
            document.getElementById('precioInput').value = '';
            document.getElementById('detalleInput').value = '';
            document.getElementById('ivaSelect').value = '21';
        }

        async function cargarPresupuesto(id) {
            try {
                const res = await fetch(`./api/presupuestos.php?id=${id}`);
                const presupuesto = await res.json();
                if (presupuesto.error) throw new Error(presupuesto.error);
                
                // Completar campos
                document.getElementById('fecha').value = presupuesto.fecha.split('T')[0] || presupuesto.fecha;
                document.getElementById('idcli').value = presupuesto.idcli;
                document.getElementById('nro_presupuesto').value = presupuesto.nro_presupuesto || '';
                document.getElementById('estado').value = presupuesto.estado;
                document.getElementById('validez').value = presupuesto.validez || 30;
                document.getElementById('total').value = presupuesto.total || '0.00';
                document.getElementById('fecha_vencimiento').value = presupuesto.fecha_vencimiento || '';
                document.getElementById('observaciones').value = presupuesto.observaciones || '';
                document.getElementById('ubicacion').value = presupuesto.ubicacion || '';
                document.getElementById('notas_internas').value = presupuesto.notas_internas || '';

                   // Cargar datos editables del cliente si existen
                   document.getElementById('cli_nombre').value = presupuesto.cli_nombre || '';
                   document.getElementById('cli_empresa').value = presupuesto.cli_empresa || '';
                   document.getElementById('cli_telefono').value = presupuesto.cli_telefono || '';
                   document.getElementById('cli_email').value = presupuesto.cli_email || '';
                   document.getElementById('tipoProyecto').value = presupuesto.tipoProyecto || '';
                   document.getElementById('origen').value = presupuesto.origen ?? '';

                // Cargar productos
                await cargarProductosPresupuesto(id);
            } catch (err) {
                console.error('Error cargando presupuesto', err);
                CRUDAlerts.error('Error', 'No se pudo cargar el presupuesto: ' + err.message);
            }
        }

        async function cargarProductosPresupuesto(idpres) {
            try {
                const res = await fetch(`./api/presupuestos.php?detalles=1&idpres=${idpres}`);
                if (!res.ok) throw new Error('Error cargando detalles');
                const detalles = await res.json();
                productosDelPresupuesto = [];
                
                if (Array.isArray(detalles)) {
                    detalles.forEach(det => {
                        const nombre = det.producto_nombre || `ID: ${det.idart}`;
                        const codigo = det.producto_codigo || '';
                        const subtotal = parseFloat(det.cantidad) * parseFloat(det.precio);
                        const total = parseFloat(det.total);
                        const iva = parseFloat(det.iva) || 21;
                        
                        productosDelPresupuesto.push({
                            idart: parseInt(det.idart),
                            tipo_producto: det.tipo_producto || 'producto',
                            nombre: nombre,
                            codigo: codigo,
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
                console.error('Error cargando productos del presupuesto', err);
            }
        }

        // Manejar envío del formulario
        document.getElementById('formPresupuesto').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (productosDelPresupuesto.length === 0) {
                Swal.fire('Error', 'Debe agregar al menos un producto al presupuesto', 'error');
                return;
            }

            const cliNombre = document.getElementById('cli_nombre').value.trim();
            const idcliVal = document.getElementById('idcli').value;
            if ((!idcliVal || idcliVal === '0') && !cliNombre) {
                Swal.fire('Error', 'Debe seleccionar un cliente o completar nombre y apellido', 'error');
                return;
            }

            const datos = {
                fecha: document.getElementById('fecha').value,
                idcli: idcliVal ? parseInt(idcliVal) : 0,
                nro_presupuesto: document.getElementById('nro_presupuesto').value || null,
                estado: parseInt(document.getElementById('estado').value),
                validez: parseInt(document.getElementById('validez').value) || 30,
                total: parseFloat(document.getElementById('total').value) || 0.00,
                observaciones: document.getElementById('observaciones').value,
                ubicacion: document.getElementById('ubicacion').value,
                notas_internas: document.getElementById('notas_internas').value,
                productos: productosDelPresupuesto,
                cli_nombre: cliNombre,
                cli_empresa: document.getElementById('cli_empresa').value,
                cli_telefono: document.getElementById('cli_telefono').value,
                cli_email: document.getElementById('cli_email').value,
                tipoProyecto: document.getElementById('tipoProyecto').value,
                origen: document.getElementById('origen').value ? parseInt(document.getElementById('origen').value) : null
            };

            const id = document.getElementById('presupuestoId').value;
            const url = id ? `./api/presupuestos.php?id=${id}` : './api/presupuestos.php';
            const method = id ? 'PUT' : 'POST';

            try {
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
                    const msg = (data && data.error) ? data.error : text || 'Error en la petición';
                    Swal.fire('Error', `Error ${res.status}: ${msg}`, 'error');
                    return;
                }

                if (data && data.success) {
                    if (id) CRUDAlerts.updated('Presupuesto'); else CRUDAlerts.created('Presupuesto');
                    window.location.href = 'presupuestos';
                } else {
                    const msg = data && data.error ? data.error : (text || 'Error en la operación');
                    Swal.fire('Error', msg, 'error');
                }
            } catch (err) {
                console.error('Error guardando presupuesto', err);
                CRUDAlerts.connectionError();
            }
        });
    </script>
</body>
</html>
