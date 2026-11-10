<?php
// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - Stadium</title>
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
                <h5 class="mb-0 d-inline">Proveedores</h5>
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
                <h2>Gestión de Proveedores</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="imprimirProveedores()">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                    <button class="btn btn-info" onclick="exportarProveedores()">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                    <button class="btn btn-primary" onclick="mostrarFormulario()">
                        <i class="bi bi-plus"></i> Nuevo Proveedor
                    </button>
                </div>
            </div>

            <!-- Proveedores Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Proveedores</h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de búsqueda -->
                    <div class="mb-3">
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="filtro" placeholder="Buscar por razón social, contacto, CUIT..." onkeyup="filtrarProveedores()">
                        </div>
                    </div>
                    
                    <!-- Tabla de proveedores -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaProveedores">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Razón Social</th>
                                    <th>Contacto</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Ciudad</th>
                                    <th>CUIT</th>
                                    <th>Condición IVA</th>
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

    <!-- Modal para agregar/editar proveedor -->
    <div class="modal fade" id="modalProveedor" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModal">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProveedor">
                    <div class="modal-body">
                        <input type="hidden" id="proveedorId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" class="form-control" id="razonsocial" maxlength="20" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contacto</label>
                                <input type="text" class="form-control" id="contacto" maxlength="20">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="ciudad" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CUIT</label>
                                <input type="text" class="form-control" id="cuit" maxlength="11">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Condición IVA *</label>
                                <select class="form-select" id="condicioniva" required>
                                    <option value="1">Consumidor Final</option>
                                    <option value="2">Excento</option>
                                    <option value="3">Responsable Inscripto</option>
                                    <option value="4">Monotributo</option>
                                </select>
                            </div>
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
        const CONDICION_IVA = {
            1: 'Consumidor Final',
            2: 'Excento',
            3: 'Responsable Inscripto',
            4: 'Monotributo'
        };

        function condicionIvaTexto(valor) {
            return CONDICION_IVA[valor] || '-';
        }

        let proveedores = [];
        const modal = new bootstrap.Modal(document.getElementById('modalProveedor'));

        // Cargar proveedores al iniciar
        document.addEventListener('DOMContentLoaded', cargarProveedores);

        async function cargarProveedores() {
            try {
                const response = await fetch('api/proveedores.php');
                proveedores = await response.json();
                mostrarProveedores();
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        function mostrarProveedores() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            proveedores.forEach(proveedor => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${proveedor.id}</td>
                    <td>${proveedor.razonsocial}</td>
                    <td>${proveedor.contacto}</td>
                    <td>${proveedor.direccion}</td>
                    <td>${proveedor.telefono}</td>
                    <td>${proveedor.ciudad}</td>
                    <td>${proveedor.cuit}</td>
                    <td>${condicionIvaTexto(proveedor.condicioniva)}</td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verProveedor(${proveedor.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning me-1" onclick="editarProveedor(${proveedor.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarProveedor(${proveedor.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function filtrarProveedores() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            const filas = document.querySelectorAll('#cuerpoTabla tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        }

        function mostrarFormulario(proveedor = null) {
            document.getElementById('formProveedor').reset();
            
            if (proveedor) {
                document.getElementById('tituloModal').textContent = 'Editar Proveedor';
                document.getElementById('proveedorId').value = proveedor.id;
                document.getElementById('razonsocial').value = proveedor.razonsocial;
                document.getElementById('contacto').value = proveedor.contacto;
                document.getElementById('direccion').value = proveedor.direccion;
                document.getElementById('telefono').value = proveedor.telefono;
                document.getElementById('ciudad').value = proveedor.ciudad;
                document.getElementById('cuit').value = proveedor.cuit;
                document.getElementById('condicioniva').value = proveedor.condicioniva || 1;
            } else {
                document.getElementById('tituloModal').textContent = 'Nuevo Proveedor';
                document.getElementById('proveedorId').value = '';
            }
            
            modal.show();
        }

        async function editarProveedor(id) {
            try {
                const response = await fetch(`api/proveedores.php?id=${id}`);
                const proveedor = await response.json();
                mostrarFormulario(proveedor);
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function verProveedor(id) {
            try {
                const response = await fetch(`api/proveedores.php?id=${id}`);
                const proveedor = await response.json();
                
                Swal.fire({
                    title: 'Detalle del Proveedor',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${proveedor.id}</p>
                            <p><strong>Razón Social:</strong> ${proveedor.razonsocial}</p>
                            <p><strong>Contacto:</strong> ${proveedor.contacto}</p>
                            <p><strong>Dirección:</strong> ${proveedor.direccion}</p>
                            <p><strong>Teléfono:</strong> ${proveedor.telefono}</p>
                            <p><strong>Ciudad:</strong> ${proveedor.ciudad}</p>
                            <p><strong>CUIT:</strong> ${proveedor.cuit}</p>
                            <p><strong>Condición IVA:</strong> ${condicionIvaTexto(proveedor.condicioniva)}</p>
                        </div>
                    `,
                    icon: 'info'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function eliminarProveedor(id) {
            const result = await CRUDAlerts.confirmDelete('proveedor');

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`api/proveedores.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        CRUDAlerts.deleted('Proveedor');
                        cargarProveedores();
                    } else {
                        CRUDAlerts.deleteError(data.message);
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }

        document.getElementById('formProveedor').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const datos = {
                razonsocial: document.getElementById('razonsocial').value,
                contacto: document.getElementById('contacto').value,
                direccion: document.getElementById('direccion').value,
                telefono: document.getElementById('telefono').value,
                ciudad: document.getElementById('ciudad').value,
                cuit: document.getElementById('cuit').value,
                condicioniva: parseInt(document.getElementById('condicioniva').value)
            };
            
            const id = document.getElementById('proveedorId').value;
            const url = id ? `api/proveedores.php?id=${id}` : 'api/proveedores.php';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (id) {
                        CRUDAlerts.updated('Proveedor');
                    } else {
                        CRUDAlerts.created('Proveedor');
                    }
                    modal.hide();
                    cargarProveedores();
                } else {
                    if (id) {
                        CRUDAlerts.updateError(data.message);
                    } else {
                        CRUDAlerts.createError(data.message);
                    }
                }
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        });

        // Función para imprimir la lista de proveedores
        function imprimirProveedores() {
            window.open('?page=proveedores_prn', '_blank');
        }

        // Función para exportar proveedores a Excel
        function exportarProveedores() {
            window.open('?page=proveedores_exp', '_self');
        }

    
    </script>
</body>
</html>