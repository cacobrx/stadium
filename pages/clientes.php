<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
require_once __DIR__ . '/../includes/bootstrap.php';

// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Stadium</title>
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
                <h5 class="mb-0 d-inline ms-3">Clientes</h5>
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
                <h2>Gestión de Clientes</h2>
                <button class="btn btn-primary" onclick="mostrarFormulario()">
                    <i class="bi bi-plus"></i> Nuevo Cliente
                </button>
            </div>

            <!-- Clientes Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Clientes</h5>
                </div>
                    <div class="card-body">
                        <!-- Filtro de búsqueda -->
                        <div class="mb-3">
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="filtro" placeholder="Buscar por apellido, nombre, CUIT..." onkeyup="filtrarClientes()">
                            </div>
                        </div>
                        
                        <!-- Tabla de clientes -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaClientes">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Razón Social / Apellido</th>
                                        <th>Nombre</th>
                                        <th>CUIT</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th>Ciudad</th>
                                        <th>Provincia</th>
                                        <th>Cond. IVA</th>
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
    </div>

    <!-- Modal para agregar/editar cliente -->
    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModal">Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCliente">
                    <div class="modal-body" style="overflow-y: auto; max-height: 75vh;">
                        <input type="hidden" id="clienteId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Razón Social / Apellido *</label>
                                <input type="text" class="form-control" id="apellido" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CUIT</label>
                                <input type="text" class="form-control" id="cuit" maxlength="11">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Condición IVA *</label>
                                <select class="form-select" id="condicioniva" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="1">Consumidor Final</option>
                                    <option value="2">Exento</option>
                                    <option value="3">Responsable Inscripto</option>
                                    <option value="4">Monotributo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saldo Inicial</label>
                                <input type="number" step="0.01" class="form-control" id="saldoini" value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Actualiza Precio</label>
                                <select class="form-select" id="actualizaprecio">
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" rows="2"></textarea>
                            </div>

                            <!-- ===== PREFERENCIAS ===== -->
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="recibirOfertas" value="1">
                                    <label class="form-check-label" for="recibirOfertas">Recibir Ofertas</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="recibirWhatsApp" value="1">
                                    <label class="form-check-label" for="recibirWhatsApp">Recibir WhatsApp</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="newsletter" value="1">
                                    <label class="form-check-label" for="newsletter">Newsletter</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Idioma</label>
                                <select class="form-select" id="idioma">
                                    <option value="es">Español</option>
                                    <option value="en">Inglés</option>
                                    <option value="pt">Portugués</option>
                                </select>
                            </div>

                            <!-- ===== SECCIÓN DIRECCIONES ===== -->
                            <div class="col-12 mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold mb-0"><i class="bi bi-geo-alt"></i> Direcciones</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirFormDireccion()">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>

                                <!-- Mini-formulario inline para agregar/editar dirección -->
                                <div id="formDireccionContainer" class="card card-body mb-2 d-none">
                                    <div class="row g-2">
                                        <input type="hidden" id="dirId">
                                        <div class="col-md-4">
                                            <label class="form-label form-label-sm">Etiqueta</label>
                                            <input type="text" class="form-control form-control-sm" id="dirDescripcion" placeholder="Ej: Casa, Depósito..." maxlength="100">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label form-label-sm">Dirección</label>
                                            <input type="text" class="form-control form-control-sm" id="dirDireccion" placeholder="Calle y número">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label form-label-sm">Piso</label>
                                            <input type="text" class="form-control form-control-sm" id="dirPiso" maxlength="10" placeholder="Ej: 3">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label form-label-sm">Departamento</label>
                                            <input type="text" class="form-control form-control-sm" id="dirDpto" maxlength="10" placeholder="Ej: B">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Ciudad</label>
                                            <input type="text" class="form-control form-control-sm" id="dirCiudad" maxlength="100">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label form-label-sm">Cód. Postal</label>
                                            <input type="text" class="form-control form-control-sm" id="dirCpostal" maxlength="10" placeholder="Ej: 1425">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label form-label-sm">Provincia</label>
                                            <select class="form-select form-select-sm" id="dirProvincia">
                                                <option value="">Seleccionar...</option>
                                                <option value="CABA">CABA</option>
                                                <option value="Buenos Aires">Buenos Aires</option>
                                                <option value="Catamarca">Catamarca</option>
                                                <option value="Chaco">Chaco</option>
                                                <option value="Chubut">Chubut</option>
                                                <option value="Córdoba">Córdoba</option>
                                                <option value="Corrientes">Corrientes</option>
                                                <option value="Entre Ríos">Entre Ríos</option>
                                                <option value="Formosa">Formosa</option>
                                                <option value="Jujuy">Jujuy</option>
                                                <option value="La Pampa">La Pampa</option>
                                                <option value="La Rioja">La Rioja</option>
                                                <option value="Mendoza">Mendoza</option>
                                                <option value="Misiones">Misiones</option>
                                                <option value="Neuquén">Neuquén</option>
                                                <option value="Río Negro">Río Negro</option>
                                                <option value="Salta">Salta</option>
                                                <option value="San Juan">San Juan</option>
                                                <option value="San Luis">San Luis</option>
                                                <option value="Santa Cruz">Santa Cruz</option>
                                                <option value="Santa Fe">Santa Fe</option>
                                                <option value="Santiago del Estero">Santiago del Estero</option>
                                                <option value="Tierra del Fuego">Tierra del Fuego</option>
                                                <option value="Tucumán">Tucumán</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dirPrincipal">
                                                <label class="form-check-label form-label-sm" for="dirPrincipal">Principal</label>
                                            </div>
                                        </div>
                                        <div class="col-12 d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelarFormDireccion()">Cancelar</button>
                                            <button type="button" class="btn btn-sm btn-success" onclick="guardarDireccion()">
                                                <i class="bi bi-check"></i> Guardar Dirección
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lista de direcciones -->
                                <div id="listaDirecciones">
                                    <p class="text-muted small" id="sinDirecciones">No se han agregado direcciones.</p>
                                    <ul class="list-group list-group-flush" id="ulDirecciones"></ul>
                                </div>
                            </div>
                            <!-- ===== FIN SECCIÓN DIRECCIONES ===== -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funciones del sidebar y tema
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let clientes = [];
        const modal = new bootstrap.Modal(document.getElementById('modalCliente'));

        // -------------------------------------------------------
        // Estado de direcciones en el modal
        // -------------------------------------------------------
        let modoEdicion = false;          // true = editando cliente existente
        let clienteIdActual = null;       // ID del cliente en modo edición
        let pendingDirs = [];             // Direcciones pendientes para cliente nuevo
        // Índice en pendingDirs que se está editando (-1 = nuevo)
        let pendingDirIdx = -1;

        // -------------------------------------------------------
        // Cargar y mostrar clientes
        // -------------------------------------------------------
        document.addEventListener('DOMContentLoaded', cargarClientes);

        async function cargarClientes() {
            try {
                const response = await fetch('./api/clientes.php');
                clientes = await response.json();
                mostrarClientes();
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        function mostrarClientes() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            clientes.forEach(cliente => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${cliente.id}</td>
                    <td>${cliente.apellido}</td>
                    <td>${cliente.nombre}</td>
                    <td>${cliente.cuit}</td>
                    <td>${cliente.telefono}</td>
                    <td>${cliente.direccion}</td>
                    <td>${cliente.ciudad}</td>
                    <td>${cliente.provincia || ''}</td>
                    <td>${getCondicionIVA(cliente.condicioniva)}</td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verCliente(${cliente.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning me-1" onclick="editarCliente(${cliente.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarCliente(${cliente.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function getCondicionIVA(codigo) {
            const condiciones = { 1: 'Consumidor Final', 2: 'Exento', 3: 'Responsable Inscripto', 4: 'Monotributo' };
            return condiciones[codigo] || 'N/A';
        }

        function filtrarClientes() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            document.querySelectorAll('#cuerpoTabla tr').forEach(fila => {
                fila.style.display = fila.textContent.toLowerCase().includes(filtro) ? '' : 'none';
            });
        }

        // -------------------------------------------------------
        // Abrir modal
        // -------------------------------------------------------
        async function mostrarFormulario(cliente = null) {
            document.getElementById('formCliente').reset();
            cancelarFormDireccion();
            pendingDirs = [];
            renderizarDirecciones();

            if (cliente) {
                modoEdicion     = true;
                clienteIdActual = cliente.id;
                document.getElementById('tituloModal').textContent = 'Editar Cliente';
                document.getElementById('clienteId').value        = cliente.id;
                document.getElementById('apellido').value         = cliente.apellido;
                document.getElementById('nombre').value           = cliente.nombre;
                document.getElementById('telefono').value         = cliente.telefono;
                document.getElementById('email').value            = cliente.email || '';
                document.getElementById('condicioniva').value     = cliente.condicioniva;
                document.getElementById('cuit').value             = cliente.cuit;
                document.getElementById('observaciones').value    = cliente.observaciones;
                document.getElementById('saldoini').value         = cliente.saldoini;
                document.getElementById('actualizaprecio').value  = cliente.actualizaprecio;
                document.getElementById('recibirOfertas').checked  = cliente.recibirOfertas == 1;
                document.getElementById('recibirWhatsApp').checked = cliente.recibirWhatsApp == 1;
                document.getElementById('newsletter').checked      = cliente.newsletter == 1;
                document.getElementById('idioma').value            = cliente.idioma || 'es';
            } else {
                modoEdicion     = false;
                clienteIdActual = null;
                document.getElementById('tituloModal').textContent = 'Nuevo Cliente';
                document.getElementById('clienteId').value = '';
            }

            modal.show();

            // Usar las direcciones que ya vienen en el fetch del cliente
            if (cliente) {
                pendingDirs = Array.isArray(cliente.direcciones) ? cliente.direcciones : [];
                renderizarDirecciones();
            }
        }

        async function editarCliente(id) {
            try {
                const { ok, status, data } = await fetchJSON(`./api/clientes.php?id=${id}`);
                if (!ok || !data || !data.id) {
                    Swal.fire('Error al cargar cliente', data?.error || `HTTP ${status}`, 'error');
                    return;
                }
                await mostrarFormulario(data);
            } catch (error) {
                Swal.fire('Error al cargar cliente', error.message, 'error');
            }
        }

        // -------------------------------------------------------
        // Ver cliente
        // -------------------------------------------------------
        async function verCliente(id) {
            try {
                const response = await fetch(`./api/clientes.php?id=${id}`);
                const cliente  = await response.json();

                let dirsHtml = '';
                if (cliente.direcciones && cliente.direcciones.length > 0) {
                    dirsHtml = '<ul class="list-unstyled mb-0">';
                    cliente.direcciones.forEach(d => {
                        const badge = d.principal ? '<span class="badge bg-success ms-1">Principal</span>' : '';
                        dirsHtml += `<li><strong>${d.descripcion}${badge}:</strong> ${d.direccion}, ${d.ciudad} ${d.provincia}</li>`;
                    });
                    dirsHtml += '</ul>';
                } else {
                    dirsHtml = '<em>Sin direcciones registradas</em>';
                }

                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

                Swal.fire({
                    title: 'Detalle del Cliente',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${cliente.id}</p>
                            <p><strong>Razón Social / Apellido:</strong> ${cliente.apellido}</p>
                            <p><strong>Nombre:</strong> ${cliente.nombre}</p>
                            <p><strong>CUIT:</strong> ${cliente.cuit}</p>
                            <p><strong>Teléfono:</strong> ${cliente.telefono}</p>
                            <p><strong>Email:</strong> ${cliente.email || 'No especificado'}</p>
                            <p><strong>Condición IVA:</strong> ${getCondicionIVA(cliente.condicioniva)}</p>
                            <p><strong>Saldo Inicial:</strong> $${parseFloat(cliente.saldoini).toFixed(2)}</p>
                            <p><strong>Actualiza Precio:</strong> ${cliente.actualizaprecio == 1 ? 'Sí' : 'No'}</p>
                            <p><strong>Observaciones:</strong> ${cliente.observaciones}</p>
                            <p><strong>Recibir Ofertas:</strong> ${cliente.recibirOfertas == 1 ? 'Sí' : 'No'}</p>
                            <p><strong>Recibir WhatsApp:</strong> ${cliente.recibirWhatsApp == 1 ? 'Sí' : 'No'}</p>
                            <p><strong>Newsletter:</strong> ${cliente.newsletter == 1 ? 'Sí' : 'No'}</p>
                            <p><strong>Idioma:</strong> ${cliente.idioma || 'es'}</p>
                            <p><strong>Fecha Alta:</strong> ${new Date(cliente.fechacreate).toLocaleString()}</p>
                            <p><strong>Token:</strong> <code style="font-size:12px;word-break:break-all;">${cliente.token || '<em>Sin token</em>'}</code></p>
                            <hr>
                            <p><strong>Direcciones:</strong></p>
                            ${dirsHtml}
                        </div>
                    `,
                    icon: 'info',
                    background: isDark ? '#212529' : '#fff',
                    color: isDark ? '#dee2e6' : '#212529'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        // -------------------------------------------------------
        // Guardar cliente (crear / actualizar)
        // -------------------------------------------------------
        document.getElementById('formCliente').addEventListener('submit', async function(e) {
            e.preventDefault();

            const datos = {
                apellido:        document.getElementById('apellido').value,
                nombre:          document.getElementById('nombre').value,
                telefono:        document.getElementById('telefono').value,
                email:           document.getElementById('email').value,
                condicioniva:    parseInt(document.getElementById('condicioniva').value),
                cuit:            document.getElementById('cuit').value,
                observaciones:   document.getElementById('observaciones').value,
                saldoini:        parseFloat(document.getElementById('saldoini').value) || 0.00,
                actualizaprecio: parseInt(document.getElementById('actualizaprecio').value),
                recibirOfertas:  document.getElementById('recibirOfertas').checked  ? 1 : 0,
                recibirWhatsApp: document.getElementById('recibirWhatsApp').checked ? 1 : 0,
                newsletter:      document.getElementById('newsletter').checked      ? 1 : 0,
                idioma:          document.getElementById('idioma').value
            };

            const id     = document.getElementById('clienteId').value;
            const url    = id ? `./api/clientes.php?id=${id}` : './api/clientes.php';
            const method = id ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });
                const data = await response.json();

                if (data.success) {
                    const nuevoId = id ? parseInt(id) : data.id;

                    // Para cliente nuevo: guardar las direcciones pendientes
                    if (!id && pendingDirs.length > 0) {
                        for (const dir of pendingDirs) {
                            await fetch('./api/clientes_direcciones.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ ...dir, cliente_id: nuevoId })
                            });
                        }
                    }

                    id ? CRUDAlerts.updated('Cliente') : CRUDAlerts.created('Cliente');
                    modal.hide();
                    cargarClientes();
                } else {
                    id ? CRUDAlerts.updateError(data.message) : CRUDAlerts.createError(data.message);
                }
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        });

        // -------------------------------------------------------
        // Eliminar cliente
        // -------------------------------------------------------
        async function eliminarCliente(id) {
            const result = await CRUDAlerts.confirmDelete('cliente');
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./api/clientes.php?id=${id}`, { method: 'DELETE' });
                    const data     = await response.json();
                    if (data.success) {
                        CRUDAlerts.deleted('Cliente');
                        cargarClientes();
                    } else {
                        CRUDAlerts.deleteError(data.message);
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }

        // -------------------------------------------------------
        // GESTIÓN DE DIRECCIONES EN EL MODAL
        // -------------------------------------------------------

        async function cargarDireccionesModal(clienteId) {
            try {
                const { data } = await fetchJSON(`./api/clientes_direcciones.php?cliente_id=${clienteId}`);
                pendingDirs = Array.isArray(data) ? data : [];
                renderizarDirecciones();
            } catch (err) {
                console.error('Error cargando direcciones:', err.message);
                pendingDirs = [];
                renderizarDirecciones();
                Swal.fire('Error al cargar direcciones', err.message, 'error');
            }
        }

        function renderizarDirecciones() {
            const ul  = document.getElementById('ulDirecciones');
            const msg = document.getElementById('sinDirecciones');
            ul.innerHTML = '';

            if (!pendingDirs || pendingDirs.length === 0) {
                msg.classList.remove('d-none');
                return;
            }
            msg.classList.add('d-none');

            pendingDirs.forEach((dir, idx) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start py-2 px-0';

                const esPrincipal = dir.principal == 1;
                const badge = esPrincipal
                    ? '<span class="badge bg-success ms-1">Principal</span>'
                    : `<button type="button" class="btn btn-link btn-sm p-0 ms-1 text-secondary" title="Marcar como principal" onclick="marcarPrincipalDir(${idx})"><i class="bi bi-star"></i></button>`;

                li.innerHTML = `
                    <div>
                        <strong>${dir.descripcion || 'Sin etiqueta'}</strong>${badge}<br>
                        <small class="text-muted">${dir.direccion || '—'}${dir.piso ? ' Piso ' + dir.piso : ''}${dir.departamento ? ' Dpto. ' + dir.departamento : ''}${dir.ciudad ? ', ' + dir.ciudad : ''}${dir.cpostal ? ' CP ' + dir.cpostal : ''}${dir.provincia ? ' - ' + dir.provincia : ''}</small>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="abrirFormDireccion(${idx})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarDir(${idx})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                ul.appendChild(li);
            });
        }

        function abrirFormDireccion(idx = -1) {
            pendingDirIdx = idx;
            const container = document.getElementById('formDireccionContainer');
            container.classList.remove('d-none');
            // Hacer scroll para que los botones queden visibles dentro del modal
            setTimeout(() => {
                const modalBody = document.querySelector('#modalCliente .modal-body');
                modalBody.scrollTop = modalBody.scrollHeight;
            }, 50);

            if (idx >= 0 && pendingDirs[idx]) {
                const d = pendingDirs[idx];
                document.getElementById('dirId').value          = d.id || '';
                document.getElementById('dirDescripcion').value = d.descripcion || '';
                document.getElementById('dirDireccion').value   = d.direccion   || '';
                document.getElementById('dirPiso').value        = d.piso        || '';
                document.getElementById('dirDpto').value        = d.departamento|| '';
                document.getElementById('dirCpostal').value     = d.cpostal     || '';
                document.getElementById('dirCiudad').value      = d.ciudad      || '';
                document.getElementById('dirProvincia').value   = d.provincia   || '';
                document.getElementById('dirPrincipal').checked = d.principal == 1;
            } else {
                document.getElementById('dirId').value          = '';
                document.getElementById('dirDescripcion').value = '';
                document.getElementById('dirDireccion').value   = '';
                document.getElementById('dirPiso').value        = '';
                document.getElementById('dirDpto').value        = '';
                document.getElementById('dirCpostal').value     = '';
                document.getElementById('dirCiudad').value      = '';
                document.getElementById('dirProvincia').value   = '';
                document.getElementById('dirPrincipal').checked = pendingDirs.length === 0;
            }
        }

        function cancelarFormDireccion() {
            document.getElementById('formDireccionContainer').classList.add('d-none');
            pendingDirIdx = -1;
        }

        // -------------------------------------------------------
        // Helper: fetch que siempre devuelve JSON y muestra error real
        // -------------------------------------------------------
        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, options);
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                // La respuesta no es JSON (HTML de error PHP, 404, etc.)
                throw new Error(`HTTP ${res.status}: ${text.substring(0, 300)}`);
            }
            return { ok: res.ok, status: res.status, data };
        }

        async function guardarDireccion() {
            const dirData = {
                descripcion:  document.getElementById('dirDescripcion').value.trim() || 'Principal',
                direccion:    document.getElementById('dirDireccion').value.trim(),
                piso:         document.getElementById('dirPiso').value.trim(),
                departamento: document.getElementById('dirDpto').value.trim(),
                cpostal:      document.getElementById('dirCpostal').value.trim(),
                ciudad:       document.getElementById('dirCiudad').value.trim(),
                provincia:    document.getElementById('dirProvincia').value,
                principal:    document.getElementById('dirPrincipal').checked ? 1 : 0
            };

            if (modoEdicion && clienteIdActual) {
                // Modo edición: llamar a la API directamente
                const dirId = document.getElementById('dirId').value;
                try {
                    let result;
                    if (dirId) {
                        result = await fetchJSON(`./api/clientes_direcciones.php?id=${dirId}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(dirData)
                        });
                    } else {
                        result = await fetchJSON('./api/clientes_direcciones.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ...dirData, cliente_id: clienteIdActual })
                        });
                    }
                    if (result.data.success) {
                        cancelarFormDireccion();
                        await cargarDireccionesModal(clienteIdActual);
                    } else {
                        Swal.fire('Error', result.data.message || 'No se pudo guardar la dirección', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error al guardar dirección', err.message, 'error');
                }
            } else {
                // Modo nuevo cliente: operar sobre pendingDirs local
                if (dirData.principal) {
                    pendingDirs.forEach(d => d.principal = 0);
                }
                if (pendingDirIdx >= 0) {
                    pendingDirs[pendingDirIdx] = { ...pendingDirs[pendingDirIdx], ...dirData };
                } else {
                    pendingDirs.push(dirData);
                }
                cancelarFormDireccion();
                renderizarDirecciones();
            }
        }

        async function eliminarDir(idx) {
            const dir = pendingDirs[idx];

            if (modoEdicion && dir && dir.id) {
                const conf = await Swal.fire({
                    title: '¿Eliminar dirección?',
                    text: dir.descripcion,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });
                if (!conf.isConfirmed) return;

                try {
                    const { data } = await fetchJSON(`./api/clientes_direcciones.php?id=${dir.id}`, { method: 'DELETE' });
                    if (data.success) {
                        await cargarDireccionesModal(clienteIdActual);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (err) {
                    Swal.fire('Error al eliminar dirección', err.message, 'error');
                }
            } else {
                // Eliminar de la lista local
                pendingDirs.splice(idx, 1);
                renderizarDirecciones();
            }
        }

        async function marcarPrincipalDir(idx) {
            const dir = pendingDirs[idx];

            if (modoEdicion && dir && dir.id) {
                try {
                    const { data } = await fetchJSON('./api/clientes_direcciones.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ accion: 'marcar_principal', id: dir.id })
                    });
                    if (data.success) {
                        await cargarDireccionesModal(clienteIdActual);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (err) {
                    Swal.fire('Error al marcar principal', err.message, 'error');
                }
            } else {
                // En lista local
                pendingDirs.forEach((d, i) => d.principal = i === idx ? 1 : 0);
                renderizarDirecciones();
            }
        }
    </script>
</body>
</html>
