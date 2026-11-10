<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
if (!file_exists(__DIR__ . '/../includes/bootstrap.php')) {
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
    <title>Presupuestos - Stadium</title>
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
        
        /* Estilos específicos para presupuestos */
        .presupuesto-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .presupuesto-row:hover {
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
                <h5 class="mb-0 d-inline ms-3">Presupuestos</h5>
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
                <h2>Gestión de Presupuestos</h2>
                <a class="btn btn-primary" href="presupuesto_form">
                    <i class="bi bi-plus"></i> Nuevo Presupuesto
                </a>
            </div>

            <!-- Presupuestos Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Presupuestos</h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de búsqueda -->
                    <div class="mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="filtro" placeholder="Buscar por cliente, nro presupuesto..." onkeyup="filtrarPresupuestos()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filtroEstado" onchange="filtrarPresupuestos()">
                                    <option value="">Todos los estados</option>
                                    <option value="0">Borrador</option>
                                    <option value="1">Enviado</option>
                                    <option value="2">Aprobado</option>
                                    <option value="3">Rechazado</option>
                                    <option value="4">Vencido</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de presupuestos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaPresupuestos">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50px">
                                        <i class="bi bi-chevron-right"></i>
                                    </th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Nro Presup.</th>
                                    <th>Origen</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Validez</th>
                                    <th>Vencimiento</th>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funciones del sidebar y tema
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let presupuestos = [];
        let clientes = [];

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                await cargarClientes();
            } catch (e) {
                console.warn('Error al cargar clientes:', e);
            }
            await cargarPresupuestos();
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

        async function cargarPresupuestos() {
            try {
                const response = await fetch('./api/presupuestos.php');
                presupuestos = await response.json();
                mostrarPresupuestos();
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        function formatMoney(amount) {
            return parseFloat(amount).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function mostrarPresupuestos() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            presupuestos.forEach(presupuesto => {
                const clienteNombre = (presupuesto.idcli == 0 && presupuesto.cli_nombre)
                    ? presupuesto.cli_nombre
                    : (presupuesto.cliente_nombre || 'Cliente no encontrado');
                
                const tr = document.createElement('tr');
                tr.className = 'presupuesto-row';
                tr.id = `presupuesto-${presupuesto.id}`;
                tr.dataset.estado = presupuesto.estado;
                
                // Verificar si está vencido
                const fechaVencimiento = new Date(presupuesto.fecha_vencimiento);
                const hoy = new Date();
                const vencido = fechaVencimiento < hoy && presupuesto.estado != 2 && presupuesto.estado != 3;
                
                tr.innerHTML = `
                    <td class="text-center">
                        <i class="bi bi-chevron-right expand-icon" id="icon-${presupuesto.id}"></i>
                    </td>
                    <td>${presupuesto.id}</td>
                    <td>${new Date(presupuesto.fecha).toLocaleDateString()}</td>
                    <td>${clienteNombre}</td>
                    <td>${presupuesto.nro_presupuesto || '-'}</td>
                    <td>${presupuesto.origen_desc || '-'}</td>
                    <td>${getEstadoBadge(presupuesto.estado, vencido)}</td>
                    <td class="total-destacado">$${formatMoney(presupuesto.total)}</td>
                    <td class="text-center">${presupuesto.validez} días</td>
                    <td>${presupuesto.fecha_vencimiento ? new Date(presupuesto.fecha_vencimiento).toLocaleDateString() : '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verPresupuesto(${presupuesto.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>                        <a class="btn btn-sm btn-primary me-1" href="index.php?page=pdf_presupuesto&id=${presupuesto.id}" target="_blank" title="Exportar PDF">
                            <i class="bi bi-file-pdf"></i>
                        </a>                        <a class="btn btn-sm btn-warning me-1" href="presupuesto_form?id=${presupuesto.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-success me-1" onclick="convertirAPedido(${presupuesto.id})" title="Convertir a Pedido">
                            <i class="bi bi-cart-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarPresupuesto(${presupuesto.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                
                tr.onclick = (e) => {
                    if (!e.target.closest('.btn')) {
                        toggleDetalle(presupuesto.id);
                    }
                };
                
                tbody.appendChild(tr);
                
                // Crear fila de detalle
                const detalleRow = document.createElement('tr');
                detalleRow.id = `detalle-${presupuesto.id}`;
                detalleRow.innerHTML = `
                    <td colspan="11">
                        <div class="detalle-container" id="detalle-container-${presupuesto.id}">
                            <div class="p-3 detalle-row">
                                <h6><i class="bi bi-list-ul"></i> Detalle del Presupuesto #${presupuesto.id}</h6>
                                <div id="detalle-items-${presupuesto.id}">
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

        function getEstadoBadge(estado, vencido = false) {
            if (vencido && estado == 0) {
                return '<span class="badge bg-dark estado-badge">Vencido</span>';
            }
            
            const estados = {
                0: '<span class="badge bg-secondary estado-badge">Borrador</span>',
                1: '<span class="badge bg-info estado-badge">Enviado</span>',
                2: '<span class="badge bg-success estado-badge">Aprobado</span>',
                3: '<span class="badge bg-danger estado-badge">Rechazado</span>',
                4: '<span class="badge bg-dark estado-badge">Vencido</span>'
            };
            return estados[estado] || '<span class="badge bg-secondary estado-badge">Desconocido</span>';
        }

        async function toggleDetalle(presupuestoId) {
            const container = document.getElementById(`detalle-container-${presupuestoId}`);
            const icon = document.getElementById(`icon-${presupuestoId}`);
            const row = document.getElementById(`presupuesto-${presupuestoId}`);
            
            if (!container.classList.contains('show')) {
                // Mostrar detalle
                container.classList.add('show');
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
                row.classList.add('expanded');
                
                // Cargar detalles del presupuesto
                await cargarDetallePresupuesto(presupuestoId);
            } else {
                // Ocultar detalle
                container.classList.remove('show');
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
                row.classList.remove('expanded');
            }
        }

        async function cargarDetallePresupuesto(presupuestoId) {
            try {
                const response = await fetch(`./api/presupuestos.php?detalles=1&idpres=${presupuestoId}`);
                const detalles = await response.json();
                
                const container = document.getElementById(`detalle-items-${presupuestoId}`);
                
                if (detalles.length === 0) {
                    container.innerHTML = '<div class="text-muted">No hay detalles para este presupuesto.</div>';
                    return;
                }
                
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Detalle</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>IVA %</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                let totalGeneral = 0;
                detalles.forEach(detalle => {
                    totalGeneral += parseFloat(detalle.total);
                    
                    const nombreProducto = detalle.producto_nombre || `ID: ${detalle.idart}`;
                    const codigoProducto = detalle.producto_codigo || '-';
                    const tipoProducto = detalle.tipo_producto === 'equipamiento' ? 'Equipamiento' : 'Producto';
                    const subtotal = parseFloat(detalle.precio) * parseInt(detalle.cantidad);
                    
                    html += `
                        <tr>
                            <td>${codigoProducto}</td>
                            <td>${nombreProducto}</td>
                            <td><span class="badge bg-secondary">${tipoProducto}</span></td>
                            <td>${detalle.detalle || '-'}</td>
                            <td class="text-center">${detalle.cantidad}</td>
                            <td class="text-end">$${formatMoney(detalle.precio)}</td>
                            <td class="text-center">${parseFloat(detalle.iva).toFixed(2)}%</td>
                            <td class="text-end">$${formatMoney(subtotal)}</td>
                            <td class="text-end">$${formatMoney(detalle.total)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                            <tfoot class="table-info">
                                <tr>
                                    <th colspan="8" class="text-end">Total General:</th>
                                    <th class="text-end">$${formatMoney(totalGeneral)}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                
                container.innerHTML = html;
            } catch (error) {
                document.getElementById(`detalle-items-${presupuestoId}`).innerHTML = 
                    '<div class="text-danger">Error cargando detalles del presupuesto.</div>';
            }
        }

        function filtrarPresupuestos() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            const filtroEstado = document.getElementById('filtroEstado').value;
            const filas = document.querySelectorAll('#cuerpoTabla tr.presupuesto-row');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const estado = fila.dataset.estado;
                
                const coincideTexto = texto.includes(filtro);
                const coincideEstado = filtroEstado === '' || estado === filtroEstado;
                
                const visible = coincideTexto && coincideEstado;
                fila.style.display = visible ? '' : 'none';
                
                // También ocultar/mostrar la fila de detalle correspondiente
                const id = fila.id.replace('presupuesto-', '');
                const detalleRow = document.getElementById(`detalle-${id}`);
                if (detalleRow) {
                    detalleRow.style.display = visible ? '' : 'none';
                }
            });
        }

        async function verPresupuesto(id) {
            try {
                const response = await fetch(`./api/presupuestos.php?id=${id}`);
                const presupuesto = await response.json();
                const clienteNombre = (presupuesto.idcli == 0 && presupuesto.cli_nombre)
                    ? presupuesto.cli_nombre
                    : (presupuesto.cliente_nombre || 'Cliente no encontrado');
                
                Swal.fire({
                    title: 'Detalle del Presupuesto',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${presupuesto.id}</p>
                            <p><strong>Nro Presupuesto:</strong> ${presupuesto.nro_presupuesto || 'No asignado'}</p>
                            <p><strong>Fecha:</strong> ${new Date(presupuesto.fecha).toLocaleDateString()}</p>
                            <p><strong>Cliente:</strong> ${clienteNombre}</p>
                            <p><strong>Origen:</strong> ${presupuesto.origen_desc || 'No especificado'}</p>
                            ${presupuesto.cli_email ? `<p><strong>Email:</strong> ${presupuesto.cli_email}</p>` : ''}
                            <p><strong>Estado:</strong> ${getEstadoBadge(presupuesto.estado)}</p>
                            <p><strong>Total:</strong> $${formatMoney(presupuesto.total)}</p>
                            <p><strong>Validez:</strong> ${presupuesto.validez} días</p>
                            <p><strong>Fecha Vencimiento:</strong> ${presupuesto.fecha_vencimiento ? new Date(presupuesto.fecha_vencimiento).toLocaleDateString() : 'No definida'}</p>
                            <p><strong>Observaciones:</strong> ${presupuesto.observaciones || 'Ninguna'}</p>
                            <p><strong>Ubicación:</strong> ${presupuesto.ubicacion || 'No especificada'}</p>
                            <p><strong>Notas Internas:</strong> ${presupuesto.notas_internas || 'Ninguna'}</p>
                            <p><strong>Fecha Creación:</strong> ${new Date(presupuesto.fechacreate).toLocaleString()}</p>
                            ${presupuesto.fechamod ? `<p><strong>Última Modificación:</strong> ${new Date(presupuesto.fechamod).toLocaleString()}</p>` : ''}
                        </div>
                    `,
                    icon: 'info',
                    width: '600px'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function eliminarPresupuesto(id) {
            const result = await CRUDAlerts.confirmDelete('presupuesto');

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./api/presupuestos.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        CRUDAlerts.deleted('Presupuesto');
                        cargarPresupuestos();
                    } else {
                        CRUDAlerts.deleteError(data.message);
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }

        async function convertirAPedido(id) {
            const result = await Swal.fire({
                title: '¿Convertir a Pedido?',
                text: 'Se creará un nuevo pedido con los datos de este presupuesto',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, convertir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754'
            });

            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'info',
                    title: 'Funcionalidad en desarrollo',
                    text: 'La conversión a pedido estará disponible próximamente'
                });
            }
        }
    </script>
</body>
</html>
