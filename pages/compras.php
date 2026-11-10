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
    <title>Compras - Stadium</title>
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
        
        /* Estilos específicos para compras */
        .compra-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .compra-row:hover {
            background-color: var(--bs-light) !important;
        }
        
        .detalle-row {
            background-color: var(--bs-gray-100);
            border-left: 3px solid var(--bs-primary);
        }
        
        .expand-icon {
            transition: transform 0.3s ease;
        }
        
        .compra-row {
            cursor: pointer;
        }
        
        .compra-row:hover {
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
                <h5 class="mb-0 d-inline">Compras</h5>
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
                <h2>Gestión de Compras</h2>
                <a href="index.php?page=compras_form" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Nueva Compra
                </a>
            </div>

            <!-- Compras Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Compras</h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de búsqueda -->
                    <div class="mb-3">
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="filtro" placeholder="Buscar por proveedor, fecha..." onkeyup="filtrarCompras()">
                        </div>
                    </div>
                    
                    <!-- Tabla de compras -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaCompras">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50px">
                                        <i class="bi bi-chevron-right"></i>
                                    </th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Total</th>
                                    <th>Cant. Artículos</th>
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
        let compras = [];
        let proveedores = [];
        let articulos = [];

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarProveedores();
            cargarArticulos();
            cargarCompras();
        });

        async function cargarProveedores() {
            try {
                const response = await fetch('./api/proveedores.php');
                proveedores = await response.json();
                const select = document.getElementById('proveedor');
                select.innerHTML = '<option value="">Seleccionar proveedor...</option>';
                proveedores.forEach(proveedor => {
                    const nombre = proveedor.razonsocial || proveedor.contacto || `Proveedor #${proveedor.id}`;
                    select.innerHTML += `<option value="${proveedor.id}">${nombre}</option>`;
                });
            } catch (error) {
                console.error('Error cargando proveedores:', error);
            }
        }

        async function cargarArticulos() {
            try {
                const response = await fetch('./api/inventario.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (!data.success) throw new Error('Error en respuesta del servidor');
                
                articulos = data.inventario;
                
                // Cargar solo en el select de artículos para agregar
                const select = document.getElementById('articuloSelect');
                select.innerHTML = '<option value="">Seleccionar artículo del inventario...</option>';
                
                if (Array.isArray(articulos) && articulos.length > 0) {
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
                    console.log(`Cargados ${articulos.length} productos correctamente`);
                } else {
                    console.warn('No se encontraron artículos en la base de datos');
                    select.innerHTML += '<option value="">No hay artículos disponibles</option>';
                }
            } catch (error) {
                console.error('Error cargando inventario:', error);
                const select = document.getElementById('articuloSelect');
                select.innerHTML = '<option value="">Error al cargar inventario</option>';
            }
        }

        async function cargarCompras() {
            try {
                const response = await fetch('./api/compras.php');
                compras = await response.json();
                mostrarCompras();
            } catch (error) {
                console.error('Error cargando compras:', error);
                CRUDAlerts.connectionError();
            }
        }

        function mostrarCompras() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            compras.forEach(compra => {
                const proveedor = proveedores.find(p => p.id == compra.proveedor);
                const proveedorNombre = proveedor ? (proveedor.razonsocial || proveedor.contacto || `Proveedor #${proveedor.id}`) : 'Proveedor no encontrado';
                
                const cantidadArticulos = compra.cantidad_articulos || 0;
                
                const tr = document.createElement('tr');
                tr.className = 'compra-row';
                tr.id = `compra-${compra.id}`;
                
                tr.innerHTML = `
                    <td class="text-center">
                        <i class="bi bi-chevron-right expand-icon" id="icon-${compra.id}"></i>
                    </td>
                    <td>${compra.id}</td>
                    <td>${new Date(compra.fecha).toLocaleDateString()}</td>
                    <td>${proveedorNombre}</td>
                    <td class="total-destacado">$${parseFloat(compra.importe || 0).toFixed(2)}</td>
                    <td class="text-center"><span class="badge bg-primary">${cantidadArticulos} artículo${cantidadArticulos !== 1 ? 's' : ''}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verCompra(${compra.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a href="index.php?page=compras_form&id=${compra.id}" class="btn btn-sm btn-warning me-1" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="eliminarCompra(${compra.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                
                tr.onclick = (e) => {
                    if (!e.target.closest('.btn')) {
                        toggleDetalle(compra.id);
                    }
                };
                
                tbody.appendChild(tr);
                
                // Crear fila de detalle
                const detalleRow = document.createElement('tr');
                detalleRow.id = `detalle-${compra.id}`;
                detalleRow.innerHTML = `
                    <td colspan="6">
                        <div class="detalle-container" id="detalle-container-${compra.id}">
                            <div class="p-3 detalle-row">
                                <h6><i class="bi bi-list-ul"></i> Detalle de la Compra #${compra.id}</h6>
                                <div id="detalle-items-${compra.id}">
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

        async function toggleDetalle(compraId) {
            const container = document.getElementById(`detalle-container-${compraId}`);
            const icon = document.getElementById(`icon-${compraId}`);
            const row = document.getElementById(`compra-${compraId}`);
            
            if (!container.classList.contains('show')) {
                // Mostrar detalle
                container.classList.add('show');
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
                row.classList.add('expanded');
                
                // Cargar detalles de la compra
                await cargarDetalleCompra(compraId);
            } else {
                // Ocultar detalle
                container.classList.remove('show');
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
                row.classList.remove('expanded');
            }
        }

        async function cargarDetalleCompra(compraId) {
            try {
                console.log('Cargando detalle para compra ID:', compraId);
                const response = await fetch(`./api/compras_det.php?idcom=${compraId}`);
                const detalles = await response.json();
                console.log('Detalles recibidos:', detalles);
                
                const container = document.getElementById(`detalle-items-${compraId}`);
                
                if (!Array.isArray(detalles) || detalles.length === 0) {
                    console.log('No hay detalles o no es un array');
                    container.innerHTML = '<div class="text-muted">No hay detalles para esta compra.</div>';
                    return;
                }
                
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                let totalGeneral = 0;
                detalles.forEach(detalle => {
                    totalGeneral += parseFloat(detalle.total);
                    
                    // Buscar en inventario para obtener el nombre completo
                    const itemInventario = articulos.find(a => a.id == detalle.idart);
                    let nombreCompleto = '';
                    
                    if (itemInventario) {
                        // Construir nombre con producto + atributos
                        nombreCompleto = itemInventario.producto_nombre;
                        if (itemInventario.atributos && itemInventario.atributos.length > 0) {
                            const atributosTexto = itemInventario.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                            nombreCompleto += ` - ${atributosTexto}`;
                        }
                        if (itemInventario.sku_variante) {
                            nombreCompleto += ` [${itemInventario.sku_variante}]`;
                        }
                    } else {
                        // Fallback al nombre del detalle o ID
                        nombreCompleto = detalle.producto_nombre || `Variante ID: ${detalle.idart}`;
                    }
                    
                    html += `
                        <tr>
                            <td>${detalle.id}</td>
                            <td>${nombreCompleto}</td>
                            <td class="text-center">${detalle.cantidad}</td>
                            <td class="text-end">$${parseFloat(detalle.precio).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(detalle.total).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                            <tfoot class="table-info">
                                <tr>
                                    <th colspan="4" class="text-end">Total General:</th>
                                    <th class="text-end">$${totalGeneral.toFixed(2)}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                
                container.innerHTML = html;
            } catch (error) {
                document.getElementById(`detalle-items-${compraId}`).innerHTML = 
                    '<div class="text-danger">Error cargando detalles de la compra.</div>';
            }
        }

        function filtrarCompras() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            const filas = document.querySelectorAll('#cuerpoTabla tr.compra-row');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const visible = texto.includes(filtro);
                fila.style.display = visible ? '' : 'none';
                
                // También ocultar/mostrar la fila de detalle correspondiente
                const id = fila.id.replace('compra-', '');
                const detalleRow = document.getElementById(`detalle-${id}`);
                if (detalleRow) {
                    detalleRow.style.display = visible ? '' : 'none';
                }
            });
        }

        async function verCompra(id) {
            try {
                const response = await fetch(`./api/compras.php?id=${id}`);
                const compra = await response.json();
                const proveedor = proveedores.find(p => p.id == compra.proveedor);
                const proveedorNombre = proveedor ? (proveedor.razonsocial || proveedor.contacto || `Proveedor #${proveedor.id}`) : 'Proveedor no encontrado';
                
                Swal.fire({
                    title: 'Detalle de la Compra',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${compra.id}</p>
                            <p><strong>Fecha:</strong> ${new Date(compra.fecha).toLocaleDateString()}</p>
                            <p><strong>Proveedor:</strong> ${proveedorNombre}</p>
                            <p><strong>Importe:</strong> $${parseFloat(compra.importe || 0).toFixed(2)}</p>
                        </div>
                    `,
                    icon: 'info',
                    width: '500px'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function eliminarCompra(id) {
            const result = await CRUDAlerts.confirmDelete('compra');

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./api/compras.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        CRUDAlerts.deleted('Compra');
                        cargarCompras();
                    } else {
                        CRUDAlerts.deleteError(data.message);
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }

    </script>
</body>
</html>