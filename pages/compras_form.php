<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
require_once __DIR__ . '/../includes/bootstrap.php';

// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$idCompra = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $idCompra ? 'Editar Compra' : 'Nueva Compra'; ?> - Stadium</title>
    <!-- Cargar tema inmediatamente para evitar flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('stadium-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
    </script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>

        .total-destacado {
            font-weight: bold;
            font-size: 1.15em;
            color: var(--bs-success);
        }

        #productosTabla tbody tr:last-child td {
            border-bottom: none;
        }

        .agregar-section {
            background-color: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <a href="index.php?page=compras" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <h5 class="mb-0"><?php echo $idCompra ? 'Editar Compra' : 'Nueva Compra'; ?></h5>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                    <i class="bi bi-sun-fill" id="themeIcon"></i>
                </button>
                <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <form id="formCompra">
                <input type="hidden" id="compraId" value="<?php echo $idCompra ?? ''; ?>">

                <!-- Cabecera de la compra -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cart3 me-2"></i><?php echo $idCompra ? 'Editar Compra #' . $idCompra : 'Nueva Compra'; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Proveedor *</label>
                                <select class="form-select" id="proveedor" required>
                                    <option value="">Seleccionar proveedor...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Importe Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control fw-bold text-success" id="importe" value="0.00" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección agregar artículo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Agregar Artículo</h5>
                    </div>
                    <div class="card-body agregar-section">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Artículo</label>
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
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="precioInput">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Subtotal</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="totalInput" readonly>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-success w-100" onclick="agregarProducto()" title="Agregar artículo">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de artículos de la compra -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Artículos de la Compra</h5>
                        <span class="badge bg-primary" id="cantidadItems">0 ítems</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="productosTabla">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Artículo</th>
                                        <th class="text-center" style="width:110px">Cantidad</th>
                                        <th class="text-end" style="width:140px">Precio Unit.</th>
                                        <th class="text-end" style="width:140px">Total</th>
                                        <th class="text-center" style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoProductos">
                                    <tr id="filaVacia">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                            No hay artículos agregados
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-success">
                                    <tr>
                                        <th colspan="3" class="text-end fs-6">Total General:</th>
                                        <th class="text-end total-destacado" id="totalGeneral">$0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="index.php?page=compras" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary px-4" id="btnGuardar">
                        <i class="bi bi-floppy me-1"></i> Guardar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let articulos = [];
        let productosDelaCompra = [];
        const compraId = document.getElementById('compraId').value;

        document.addEventListener('DOMContentLoaded', async function () {
            loadTheme();
            document.getElementById('fecha').value = new Date().toISOString().split('T')[0];

            document.getElementById('articuloSelect').addEventListener('change', actualizarPrecio);
            document.getElementById('cantidadInput').addEventListener('input', calcularSubtotal);
            document.getElementById('precioInput').addEventListener('input', calcularSubtotal);

            await Promise.all([cargarProveedores(), cargarArticulos()]);

            if (compraId) {
                await cargarCompra(compraId);
            }
        });

        async function cargarProveedores() {
            try {
                const response = await fetch('./api/proveedores.php');
                const proveedores = await response.json();
                const select = document.getElementById('proveedor');
                select.innerHTML = '<option value="">Seleccionar proveedor...</option>';
                proveedores.forEach(p => {
                    const nombre = p.razonsocial || p.contacto || `Proveedor #${p.id}`;
                    select.innerHTML += `<option value="${p.id}">${nombre}</option>`;
                });
            } catch (error) {
                console.error('Error cargando proveedores:', error);
            }
        }

        async function cargarArticulos() {
            try {
                const response = await fetch('./api/inventario.php');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                if (!data.success) throw new Error('Error en respuesta del servidor');

                articulos = data.inventario;
                const select = document.getElementById('articuloSelect');
                select.innerHTML = '<option value="">Seleccionar artículo del inventario...</option>';

                if (Array.isArray(articulos) && articulos.length > 0) {
                    articulos.forEach(item => {
                        let nombreCompleto = item.producto_nombre;
                        if (item.atributos && item.atributos.length > 0) {
                            nombreCompleto += ' - ' + item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                        }
                        if (item.sku_variante) nombreCompleto += ` [${item.sku_variante}]`;

                        const precio = parseFloat(item.precio || 0);
                        const stock  = parseInt(item.stock || 0);
                        const stockTxt = stock > 0 ? ` (Stock: ${stock})` : ' (Sin stock)';

                        const sinStock = stock <= 0;
                        select.innerHTML += `<option value="${item.id}" data-precio="${precio}" data-stock="${stock}" ${sinStock ? 'style="color:red;"' : ''}>${nombreCompleto} - $${precio.toFixed(2)}${stockTxt}</option>`;
                    });
                } else {
                    select.innerHTML += '<option disabled>No hay artículos disponibles</option>';
                }
            } catch (error) {
                console.error('Error cargando inventario:', error);
                document.getElementById('articuloSelect').innerHTML = '<option value="">Error al cargar inventario</option>';
            }
        }

        async function cargarCompra(id) {
            try {
                const [respCompra, respDet] = await Promise.all([
                    fetch(`./api/compras.php?id=${id}`),
                    fetch(`./api/compras_det.php?idcom=${id}`)
                ]);

                const compra   = await respCompra.json();
                const detalles = await respDet.json();

                if (compra.error) throw new Error(compra.error);

                document.getElementById('fecha').value    = compra.fecha;
                document.getElementById('proveedor').value = compra.proveedor;

                productosDelaCompra = [];
                if (Array.isArray(detalles)) {
                    detalles.forEach(det => {
                        const item = articulos.find(a => a.id == det.idart);
                        let nombre = '';
                        if (item) {
                            nombre = item.producto_nombre;
                            if (item.atributos && item.atributos.length > 0) {
                                nombre += ' - ' + item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                            }
                            if (item.sku_variante) nombre += ` [${item.sku_variante}]`;
                        } else {
                            nombre = det.producto_nombre || `Variante ID: ${det.idart}`;
                        }

                        productosDelaCompra.push({
                            id:       det.id,
                            idart:    parseInt(det.idart),
                            nombre:   nombre,
                            cantidad: parseInt(det.cantidad),
                            precio:   parseFloat(det.precio),
                            total:    parseFloat(det.total)
                        });
                    });
                }

                actualizarTablaProductos();
            } catch (error) {
                console.error('Error cargando compra:', error);
                CRUDAlerts.connectionError();
            }
        }

        // ── Artículo helper ──────────────────────────────────────────────────

        function actualizarPrecio() {
            const opt = document.getElementById('articuloSelect').selectedOptions[0];
            document.getElementById('precioInput').value = (opt && opt.value) ? parseFloat(opt.dataset.precio || 0).toFixed(2) : '';
            // Reflejar color de la opción en el select
            const select = document.getElementById('articuloSelect');
            const stock = opt && opt.value ? parseInt(opt.dataset.stock || 0) : 1;
            select.style.color = (stock <= 0) ? 'red' : '';
            calcularSubtotal();
        }

        function calcularSubtotal() {
            const cant   = parseFloat(document.getElementById('cantidadInput').value) || 0;
            const precio = parseFloat(document.getElementById('precioInput').value) || 0;
            document.getElementById('totalInput').value = (cant * precio).toFixed(2);
        }

        function agregarProducto() {
            const select   = document.getElementById('articuloSelect');
            const cantidad = parseFloat(document.getElementById('cantidadInput').value) || 0;
            const precio   = parseFloat(document.getElementById('precioInput').value) || 0;

            if (!select.value || cantidad <= 0 || precio <= 0) {
                Swal.fire('Error', 'Debe seleccionar un artículo, ingresar cantidad y precio válidos.', 'error');
                return;
            }

            productosDelaCompra.push({
                idart:    parseInt(select.value),
                nombre:   select.selectedOptions[0].text,
                cantidad: cantidad,
                precio:   precio,
                total:    cantidad * precio
            });

            actualizarTablaProductos();
            limpiarFormularioProducto();
        }

        function eliminarProducto(index) {
            productosDelaCompra.splice(index, 1);
            actualizarTablaProductos();
        }

        function limpiarFormularioProducto() {
            document.getElementById('articuloSelect').value    = '';
            document.getElementById('cantidadInput').value     = '1';
            document.getElementById('precioInput').value       = '';
            document.getElementById('totalInput').value        = '';
        }

        function actualizarTablaProductos() {
            const tbody       = document.getElementById('cuerpoProductos');
            const filaVacia   = document.getElementById('filaVacia');
            let totalGeneral  = 0;

            // Limpiar filas dinámicas (no la fila vacía)
            tbody.querySelectorAll('tr.fila-producto').forEach(r => r.remove());

            if (productosDelaCompra.length === 0) {
                filaVacia.style.display = '';
            } else {
                filaVacia.style.display = 'none';
                productosDelaCompra.forEach((p, idx) => {
                    totalGeneral += p.total;
                    const tr = document.createElement('tr');
                    tr.className = 'fila-producto';
                    tr.innerHTML = `
                        <td>${p.nombre}</td>
                        <td class="text-center">${p.cantidad}</td>
                        <td class="text-end">$${p.precio.toFixed(2)}</td>
                        <td class="text-end fw-bold">$${p.total.toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(${idx})" title="Quitar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('totalGeneral').textContent = `$${totalGeneral.toFixed(2)}`;
            document.getElementById('importe').value            = totalGeneral.toFixed(2);
            document.getElementById('cantidadItems').textContent = `${productosDelaCompra.length} ítem${productosDelaCompra.length !== 1 ? 's' : ''}`;
        }

        // ── Guardar ──────────────────────────────────────────────────────────

        document.getElementById('formCompra').addEventListener('submit', async function (e) {
            e.preventDefault();

            if (productosDelaCompra.length === 0) {
                Swal.fire('Error', 'Debe agregar al menos un artículo a la compra.', 'error');
                return;
            }

            const datos = {
                fecha:     document.getElementById('fecha').value,
                proveedor: parseInt(document.getElementById('proveedor').value),
                importe:   parseFloat(document.getElementById('importe').value) || 0,
                productos: productosDelaCompra.map(p => ({
                    idart:    p.idart,
                    cantidad: p.cantidad,
                    precio:   p.precio,
                    total:    p.total
                }))
            };

            const method = compraId ? 'PUT' : 'POST';
            const url    = compraId ? `./api/compras.php?id=${compraId}` : './api/compras.php';

            const btn = document.getElementById('btnGuardar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            try {
                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const text = await response.text();
                let data;
                try { data = JSON.parse(text); } catch { throw new Error('Respuesta inválida: ' + text); }

                if (data.success) {
                    const alerta = compraId ? CRUDAlerts.updated('Compra') : CRUDAlerts.created('Compra');
                    alerta.then(() => window.location.href = 'index.php?page=compras');
                } else {
                    Swal.fire('Error', data.message || 'No se pudo guardar la compra.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-floppy me-1"></i> Guardar Compra';
                }
            } catch (error) {
                console.error('Error guardando compra:', error);
                CRUDAlerts.connectionError();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-floppy me-1"></i> Guardar Compra';
            }
        });
    </script>
</body>
</html>
