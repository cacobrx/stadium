<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
if (!file_exists(__DIR__ . '/../includes/bootstrap.php')) {
    // fallback: require auth directly if bootstrap missing
    require_once __DIR__ . '/../includes/auth.php';
} else {
    require_once __DIR__ . '/../includes/bootstrap.php';
    // Verificar autenticación
    require_once __DIR__ . '/../includes/auth.php';
}
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Stadium</title>
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
        
        /* Estilos específicos para pedidos */
        .pedido-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .pedido-row:hover {
            background-color: var(--bs-light) !important;
        }
        
        .detalle-row {
            background-color: var(--bs-gray-100);
            border-left: 3px solid var(--bs-primary);
        }
        
        .estado-badge {
            font-size: 0.8em;
            padding: 0.3em 0.6em;
        }
        
        .expand-icon {
            transition: transform 0.3s ease;
        }
        
        .pedido-row {
            cursor: pointer;
        }
        
        .pedido-row:hover {
            background-color: #f8f9fa;
        }
        
        .expanded .expand-icon {
            transform: rotate(90deg);
        }
        
        .detalle-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            opacity: 0;
        }
        
        .detalle-container.show {
            max-height: 600px;
            opacity: 1;
        }
        
        .detalle-row {
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        
        .total-destacado {
            font-weight: bold;
            font-size: 1.1em;
            color: var(--bs-success);
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
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline ms-3">Pedidos</h5>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gestión de Pedidos</h2>
                <a class="btn btn-primary" href="pedido_form">
                    <i class="bi bi-plus"></i> Nuevo Pedido
                </a>
            </div>

            <!-- Pedidos Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Pedidos</h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de búsqueda -->
                    <div class="mb-3">
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="filtro" placeholder="Buscar por cliente, nro remito..." onkeyup="filtrarPedidos()">
                        </div>
                    </div>
                    
                    <!-- Tabla de pedidos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaPedidos">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50px">
                                        <i class="bi bi-chevron-right"></i>
                                    </th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Nro Remito</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla">
                                <!-- Los datos se cargan via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de pedido ahora está en pedido_form.php -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funciones del sidebar y tema
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let pedidos = [];
        let clientes = [];
        let articulos = [];

        // Cargar datos al iniciar (esperar clientes y artículos antes de pedidos)
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                await cargarClientes();
            } catch (e) {
                console.warn('Error al cargar clientes:', e);
            }
            try {
                await cargarArticulos();
            } catch (e) {
                console.warn('Error al cargar artículos:', e);
            }
            await cargarPedidos();
        });

        async function cargarClientes() {
            try {
                const response = await fetch('./api/clientes.php');
                clientes = await response.json();
                console.log(`Cargados ${clientes.length} clientes`);
            } catch (error) {
                console.error('Error cargando clientes:', error);
            }
        }

        async function cargarPedidos() {
            try {
                const response = await fetch('./api/pedidos.php');
                pedidos = await response.json();
                mostrarPedidos();
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        function mostrarPedidos() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            pedidos.forEach(pedido => {
                const cliente = clientes.find(c => c.id == pedido.idcli);
                // Fallbacks: si no encontramos el cliente en la lista, intentar usar campos que puedan venir en el objeto pedido
                let clienteNombre = null;
                if (cliente) {
                    clienteNombre = `${cliente.apellido} ${cliente.nombre}`;
                } else if (pedido.cliente_nombre && pedido.cliente_apellido) {
                    clienteNombre = `${pedido.cliente_apellido} ${pedido.cliente_nombre}`;
                } else if (pedido.nombre_cliente) {
                    clienteNombre = pedido.nombre_cliente;
                } else if (pedido.cliente) {
                    clienteNombre = pedido.cliente;
                } else if (pedido.cliente_full) {
                    clienteNombre = pedido.cliente_full;
                } else {
                    clienteNombre = 'Cliente no encontrado';
                    console.warn(`Pedido ${pedido.id} refiere a idcli=${pedido.idcli} pero no se encontró en clientes cargados`);
                }
                
                const tr = document.createElement('tr');
                tr.className = 'pedido-row';
                tr.id = `pedido-${pedido.id}`;
                
                tr.innerHTML = `
                    <td class="text-center">
                        <i class="bi bi-chevron-right expand-icon" id="icon-${pedido.id}"></i>
                    </td>
                    <td>${pedido.id}</td>
                    <td>${new Date(pedido.fecha).toLocaleDateString()}</td>
                    <td>${clienteNombre}</td>
                    <td>${getEstadoBadge(pedido.estado)}</td>
                    <td class="total-destacado">$${parseFloat(pedido.total).toFixed(2)}</td>
                    <td>${pedido.nroremito || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verPedido(${pedido.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a class="btn btn-sm btn-warning me-1" href="pedido_form?id=${pedido.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="eliminarPedido(${pedido.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                
                tr.onclick = (e) => {
                    console.log('Click en fila del pedido', pedido.id, e.target);
                    if (!e.target.closest('.btn')) {
                        console.log('Llamando toggleDetalle para pedido', pedido.id);
                        toggleDetalle(pedido.id);
                    } else {
                        console.log('Click en botón, no expandir');
                    }
                };
                
                tbody.appendChild(tr);
                
                // Crear fila de detalle
                const detalleRow = document.createElement('tr');
                detalleRow.id = `detalle-${pedido.id}`;
                detalleRow.innerHTML = `
                    <td colspan="8">
                        <div class="detalle-container" id="detalle-container-${pedido.id}">
                            <div class="p-3 detalle-row">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><i class="bi bi-list-ul"></i> Detalle del Pedido #${pedido.id}</h6>
                                    ${pedido.tracking_id ? `<span class="badge bg-secondary fs-6 px-3 py-2"><i class="bi bi-truck me-1"></i>Tracking: ${pedido.tracking_id}</span>` : ''}
                                </div>
                                <div id="detalle-items-${pedido.id}">
                                    <div class="text-center text-muted">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Cargando detalles...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(detalleRow);
            });
        }

        function getEstadoBadge(estado) {
            const estados = {
                0: '<span class="badge bg-warning text-dark estado-badge">Pendiente</span>',
                1: '<span class="badge bg-info text-dark estado-badge">Confirmado</span>',
                2: '<span class="badge bg-primary estado-badge">Preparando</span>',
                3: '<span class="badge bg-secondary estado-badge">Enviado</span>',
                4: '<span class="badge bg-success estado-badge">Entregado</span>',
                5: '<span class="badge bg-danger estado-badge">Cancelado</span>',
            };
            return estados[estado] ?? '<span class="badge bg-secondary estado-badge">Desconocido</span>';
        }

        async function toggleDetalle(pedidoId) {
            const container = document.getElementById(`detalle-container-${pedidoId}`);
            const icon = document.getElementById(`icon-${pedidoId}`);
            const row = document.getElementById(`pedido-${pedidoId}`);
            
            console.log(`Toggle detalle para pedido ${pedidoId}`);
            
            if (!container.classList.contains('show')) {
                // Mostrar detalle
                container.classList.add('show');
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
                row.classList.add('expanded');
                
                console.log('Mostrando detalle y cargando datos...');
                
                // Cargar detalles del pedido
                await cargarDetallePedido(pedidoId);
            } else {
                // Ocultar detalle
                container.classList.remove('show');
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
                row.classList.remove('expanded');
                
                console.log('Ocultando detalle');
            }
        }

        async function cargarDetallePedido(pedidoId) {
            try {
                console.log(`Cargando detalles para pedido ${pedidoId}`);
                const response = await fetch(`./api/pedidos_det.php?idped=${pedidoId}`);
                const detalles = await response.json();
                
                console.log('Detalles recibidos:', detalles);
                
                const container = document.getElementById(`detalle-items-${pedidoId}`);
                
                if (detalles.length === 0) {
                    container.innerHTML = '<div class="text-muted">No hay detalles para este pedido.</div>';
                    return;
                }
                
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Artículo</th>
                                    <th>Detalle</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                let totalGeneral = 0;
                detalles.forEach(detalle => {
                    totalGeneral += parseFloat(detalle.total);
                    
                    // Usar el nombre del producto que viene del API (JOIN con productos)
                    const nombreProducto = detalle.producto_nombre || `ID: ${detalle.idart}`;
                    const skuProducto = detalle.producto_sku ? ` [${detalle.producto_sku}]` : '';
                    
                    html += `
                        <tr>
                            <td>${detalle.id}</td>
                            <td>${nombreProducto}${skuProducto}</td>
                            <td>${detalle.detalle || '-'}</td>
                            <td class="text-center">${detalle.cantidad}</td>
                            <td class="text-end">$${parseFloat(detalle.precio).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(detalle.total).toFixed(2)}</td>
                            <td class="text-center">
                                ${detalle.terminado ? '<span class="badge bg-success">Terminado</span>' : '<span class="badge bg-warning">Pendiente</span>'}
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                            <tfoot class="table-info">
                                <tr>
                                    <th colspan="5" class="text-end">Total General:</th>
                                    <th class="text-end">$${totalGeneral.toFixed(2)}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                
                container.innerHTML = html;
            } catch (error) {
                document.getElementById(`detalle-items-${pedidoId}`).innerHTML = 
                    '<div class="text-danger">Error cargando detalles del pedido.</div>';
            }
        }

        function filtrarPedidos() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            const filas = document.querySelectorAll('#cuerpoTabla tr.pedido-row');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const visible = texto.includes(filtro);
                fila.style.display = visible ? '' : 'none';
                
                // También ocultar/mostrar la fila de detalle correspondiente
                const id = fila.id.replace('pedido-', '');
                const detalleRow = document.getElementById(`detalle-${id}`);
                if (detalleRow) {
                    detalleRow.style.display = visible ? '' : 'none';
                }
            });
        }

        async function verPedido(id) {
            try {
                const response = await fetch(`./api/pedidos.php?id=${id}`);
                const pedido = await response.json();
                const cliente = clientes.find(c => c.id == pedido.idcli);
                const clienteNombre = cliente ? `${cliente.apellido} ${cliente.nombre}` : 'Cliente no encontrado';
                
                Swal.fire({
                    title: 'Detalle del Pedido',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${pedido.id}</p>
                            <p><strong>Fecha:</strong> ${new Date(pedido.fecha).toLocaleDateString()}</p>
                            <p><strong>Cliente:</strong> ${clienteNombre}</p>
                            <p><strong>Estado:</strong> ${getEstadoBadge(pedido.estado)}</p>
                            <p><strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)}</p>
                            <p><strong>Nro Remito:</strong> ${pedido.nroremito || 'No asignado'}</p>
                            <p><strong>Bultos:</strong> ${pedido.bultos}</p>
                            <p><strong>Retiro:</strong> ${pedido.retiro ? 'Sí' : 'No'}</p>
                            <p><strong>Fecha Entrega:</strong> ${pedido.fechaentrega ? new Date(pedido.fechaentrega).toLocaleDateString() : 'No definida'}</p>
                            <p><strong>Observaciones:</strong> ${pedido.observaciones || 'Ninguna'}</p>
                            <p><strong>Ubicación:</strong> ${pedido.ubicacion || 'No especificada'}</p>
                            <p><strong>ID Tracking:</strong> ${pedido.tracking_id 
                                ? `<a href="#" class="text-decoration-none fw-bold"><i class="bi bi-truck me-1"></i>${pedido.tracking_id}</a>` 
                                : '<span class="text-muted">No asignado</span>'}</p>
                            <p><strong>Fecha Creación:</strong> ${new Date(pedido.fechacreate).toLocaleString()}</p>
                        </div>
                    `,
                    icon: 'info',
                    width: '600px'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function eliminarPedido(id) {
            const result = await CRUDAlerts.confirmDelete('pedido');

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./api/pedidos.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        CRUDAlerts.deleted('Pedido');
                        cargarPedidos();
                    } else {
                        CRUDAlerts.deleteError(data.message);
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }
        
        // Debug: Verificar que las funciones están definidas
        console.log('Funciones de pedidos cargadas:', {
            toggleDetalle: typeof toggleDetalle,
            cargarDetallePedido: typeof cargarDetallePedido,
            mostrarPedidos: typeof mostrarPedidos
        });
    </script>
</body>
</html>