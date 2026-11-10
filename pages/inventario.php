<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
if (!file_exists(__DIR__ . '/../includes/bootstrap.php')) {
    require_once __DIR__ . '/../includes/auth.php';
} else {
    require_once __DIR__ . '/../includes/bootstrap.php';
    require_once __DIR__ . '/../includes/auth.php';
}
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Stadium</title>
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
        .image-preview {
            max-width: 60px;
            max-height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .stock-badge {
            font-size: 0.9em;
            padding: 0.35em 0.65em;
        }
        .stock-bajo {
            background-color: #dc3545;
            color: white;
        }
        .stock-medio {
            background-color: #ffc107;
            color: black;
        }
        .stock-alto {
            background-color: #28a745;
            color: white;
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
                <h4 class="d-inline-block mb-0">Inventario de Variantes</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-primary" onclick="showCreateModal()">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Variante
                </button>
                <button class="btn btn-outline-secondary" onclick="toggleTheme()">
                    <i class="bi bi-moon-fill theme-icon"></i>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="container-fluid mt-4">
            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="searchInput" 
                                   placeholder="Buscar por producto, SKU o variante...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Producto</label>
                            <select class="form-select" id="filterProducto">
                                <option value="">Todos los productos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filterActiva">
                                <option value="">Todos</option>
                                <option value="1">Activas</option>
                                <option value="0">Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stock</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="filterStockBajo">
                                <label class="form-check-label" for="filterStockBajo">
                                    Solo stock bajo (&lt;10)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-secondary w-100" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de inventario -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Imagen</th>
                                    <th>Producto</th>
                                    <th>Marca</th>
                                    <th>SKU Variante</th>
                                    <th>Variante</th>
                                    <th style="width: 100px;" class="text-center">Stock</th>
                                    <th style="width: 120px;">Precio</th>
                                    <th style="width: 80px;" class="text-center">Estado</th>
                                    <th style="width: 160px;">Última Act.</th>
                                    <th style="width: 120px;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="inventarioTable">
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar -->
    <div class="modal fade" id="inventarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Variante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="inventarioForm">
                        <input type="hidden" id="inventarioId">
                        
                        <div class="mb-3">
                            <label for="productoId" class="form-label">Producto *</label>
                            <select class="form-select" id="productoId" required>
                                <option value="">Seleccionar producto...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="skuVariante" class="form-label">SKU Variante *</label>
                            <input type="text" class="form-control" id="skuVariante" required
                                   placeholder="Ej: NIK-001-40">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Atributos de Variante</label>
                            <div id="atributosContainer">
                                <!-- Los atributos se agregarán dinámicamente aquí -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="agregarAtributo()">
                                <i class="bi bi-plus-circle"></i> Agregar Atributo
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stock *</label>
                                <input type="number" class="form-control" id="stock" required 
                                       min="0" value="0">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="precio" class="form-label">Precio</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio" 
                                           step="0.01" min="0" placeholder="Opcional">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activa" checked>
                                <label class="form-check-label" for="activa">Variante activa</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveInventario()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modal;
        let inventarioData = [];
        let productosData = [];
        let atributoCounter = 0;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            modal = new bootstrap.Modal(document.getElementById('inventarioModal'));
            loadProductos();
            loadInventario();
            
            // Event listeners para filtros
            document.getElementById('searchInput').addEventListener('input', debounce(loadInventario, 500));
            document.getElementById('filterProducto').addEventListener('change', loadInventario);
            document.getElementById('filterActiva').addEventListener('change', loadInventario);
            document.getElementById('filterStockBajo').addEventListener('change', loadInventario);
        });

        // Agregar campo de atributo
        function agregarAtributo(nombre = '', valor = '') {
            const container = document.getElementById('atributosContainer');
            const index = atributoCounter++;
            
            const div = document.createElement('div');
            div.className = 'row mb-2 atributo-row';
            div.id = `atributo-${index}`;
            div.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm atributo-nombre" 
                           placeholder="Nombre (ej: Talle)" value="${nombre}">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm atributo-valor" 
                           placeholder="Valor (ej: 40)" value="${valor}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="eliminarAtributo(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        }

        // Eliminar campo de atributo
        function eliminarAtributo(index) {
            const element = document.getElementById(`atributo-${index}`);
            if (element) {
                element.remove();
            }
        }

        // Obtener atributos del formulario
        function getAtributosFromForm() {
            const atributos = [];
            const rows = document.querySelectorAll('.atributo-row');
            
            rows.forEach(row => {
                const nombre = row.querySelector('.atributo-nombre').value.trim();
                const valor = row.querySelector('.atributo-valor').value.trim();
                
                if (nombre && valor) {
                    atributos.push({ nombre, valor });
                }
            });
            
            return atributos;
        }

        // Limpiar contenedor de atributos
        function limpiarAtributos() {
            document.getElementById('atributosContainer').innerHTML = '';
            atributoCounter = 0;
        }

        // Cargar productos para los selectores
        async function loadProductos() {
            try {
                const response = await fetch('api/productos.php');
                
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.warn('API productos no devolvió JSON');
                    return;
                }
                
                const data = await response.json();
                
                // Verificar autenticación
                if (!data.success && (data.code === 'NOT_AUTHENTICATED' || response.status === 401)) {
                    console.error('No autenticado para cargar productos');
                    return;
                }
                
                if (data.success && data.productos) {
                    productosData = data.productos;
                    
                    // Llenar select de productos en modal
                    const select = document.getElementById('productoId');
                    select.innerHTML = '<option value="">Seleccionar producto...</option>';
                    data.productos.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id;
                        option.textContent = `${p.nombre} (${p.sku})`;
                        select.appendChild(option);
                    });
                    
                    // Llenar select de filtro
                    const filterSelect = document.getElementById('filterProducto');
                    filterSelect.innerHTML = '<option value="">Todos los productos</option>';
                    data.productos.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id;
                        option.textContent = p.nombre;
                        filterSelect.appendChild(option);
                    });
                } else {
                    console.error('Error cargando productos:', data.error);
                }
            } catch (error) {
                console.error('Error cargando productos:', error);
            }
        }

        // Cargar inventario
        async function loadInventario() {
            try {
                const params = new URLSearchParams();
                
                const search = document.getElementById('searchInput').value;
                if (search) params.append('search', search);
                
                const productoId = document.getElementById('filterProducto').value;
                if (productoId) params.append('producto_id', productoId);
                
                const activa = document.getElementById('filterActiva').value;
                if (activa !== '') params.append('activa', activa);
                
                const stockBajo = document.getElementById('filterStockBajo').checked;
                if (stockBajo) params.append('stock_bajo', '1');
                
                const url = `api/inventario.php?${params.toString()}`;
                console.log('Cargando inventario desde:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                
                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('La respuesta no es JSON. Content-Type:', contentType);
                    const text = await response.text();
                    console.error('Respuesta recibida:', text.substring(0, 500));
                    
                    // Si recibimos HTML, probablemente fuimos redirigidos
                    if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                        showError('Sesión expirada. Por favor, recarga la página.');
                        setTimeout(() => {
                            window.location.href = 'inicio';
                        }, 10000);
                        return;
                    }
                    
                    showError('Error: La respuesta del servidor no es JSON');
                    return;
                }
                
                const text = await response.text();
                console.log('Response text:', text.substring(0, 200));
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('Error al parsear JSON:', parseError);
                    console.error('Texto recibido:', text.substring(0, 500));
                    showError('Error al parsear respuesta del servidor');
                    return;
                }
                
                // Verificar si hay error de autenticación
                if (!data.success && (data.code === 'NOT_AUTHENTICATED' || response.status === 401)) {
                    showError('Sesión expirada. Redirigiendo al login...');
                    setTimeout(() => {
                        window.location.href = 'inicio';
                    }, 2000);
                    return;
                }
                
                if (data.success) {
                    inventarioData = data.inventario;
                    renderInventario(data.inventario);
                } else {
                    showError('Error al cargar inventario: ' + (data.error || 'Error desconocido'));
                }
            } catch (error) {
                showError('Error al cargar inventario: ' + error.message);
                console.error('Error completo:', error);
            }
        }

        // Renderizar tabla
        function renderInventario(items) {
            const tbody = document.getElementById('inventarioTable');
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">No se encontraron registros</td></tr>';
                return;
            }
            
            tbody.innerHTML = items.map(item => {
                const stockClass = item.stock < 10 ? 'stock-bajo' : (item.stock < 50 ? 'stock-medio' : 'stock-alto');
                const imgUrl = item.imagen_variante || item.imagen_producto || '../uploads/placeholder.png';
                const precio = item.precio ? `$${parseFloat(item.precio).toFixed(2)}` : 
                              (item.precio_producto ? `$${parseFloat(item.precio_producto).toFixed(2)}` : '-');
                const fechaAct = new Date(item.fecha_actualizacion).toLocaleString('es-AR');
                
                return `
                    <tr>
                        <td>
                            <img src="${imgUrl}" class="image-preview" alt="Imagen" onerror="this.src='../uploads/placeholder.png'">
                        </td>
                        <td>
                            <strong>${item.producto_nombre}</strong><br>
                            <small class="text-muted">${item.producto_sku}</small>
                        </td>
                        <td>${item.marca_nombre || '-'}</td>
                        <td><code>${item.sku_variante}</code></td>
                        <td>
                            ${item.atributos && item.atributos.length > 0 ? 
                                item.atributos.map(attr => `<span class="badge bg-secondary me-1">${attr.nombre}: ${attr.valor}</span>`).join('') : 
                                '<span class="text-muted">Sin atributos</span>'}
                        </td>
                        <td class="text-center">
                            <span class="badge stock-badge ${stockClass}">${item.stock}</span>
                        </td>
                        <td>${precio}</td>
                        <td class="text-center">
                            <span class="badge bg-${item.activa == 1 ? 'success' : 'secondary'}">
                                ${item.activa == 1 ? 'Activa' : 'Inactiva'}
                            </span>
                        </td>
                        <td><small>${fechaAct}</small></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning" onclick="editInventario(${item.id})" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteInventario(${item.id})" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Mostrar modal para crear
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Variante';
            document.getElementById('inventarioForm').reset();
            document.getElementById('inventarioId').value = '';
            document.getElementById('activa').checked = true;
            document.getElementById('productoId').disabled = false;
            
            // Limpiar y agregar un atributo vacío por defecto
            limpiarAtributos();
            agregarAtributo();
            
            modal.show();
        }

        // Editar inventario
        async function editInventario(id) {
            try {
                const response = await fetch(`api/inventario.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const item = data.inventario;
                    document.getElementById('modalTitle').textContent = 'Editar Variante';
                    document.getElementById('inventarioId').value = item.id;
                    document.getElementById('productoId').value = item.producto_id;
                    document.getElementById('productoId').disabled = true; // No permitir cambiar el producto
                    document.getElementById('skuVariante').value = item.sku_variante;
                    document.getElementById('stock').value = item.stock;
                    document.getElementById('precio').value = item.precio || '';
                    document.getElementById('activa').checked = item.activa == 1;
                    
                    // Cargar atributos existentes
                    limpiarAtributos();
                    if (item.atributos && item.atributos.length > 0) {
                        item.atributos.forEach(attr => {
                            agregarAtributo(attr.nombre, attr.valor);
                        });
                    } else {
                        agregarAtributo(); // Agregar uno vacío si no hay
                    }
                    
                    modal.show();
                } else {
                    showError('Error al cargar datos: ' + data.error);
                }
            } catch (error) {
                showError('Error al cargar datos');
                console.error(error);
            }
        }

        // Guardar inventario
        async function saveInventario() {
            const id = document.getElementById('inventarioId').value;
            const form = document.getElementById('inventarioForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const inventario = {
                producto_id: parseInt(document.getElementById('productoId').value),
                sku_variante: document.getElementById('skuVariante').value.trim(),
                stock: parseInt(document.getElementById('stock').value),
                precio: document.getElementById('precio').value ? parseFloat(document.getElementById('precio').value) : null,
                activa: document.getElementById('activa').checked ? 1 : 0,
                atributos: getAtributosFromForm()
            };
            
            if (id) {
                inventario.id = parseInt(id);
            }
            
            try {
                const method = id ? 'PUT' : 'POST';
                const response = await fetch(`api/inventario.php`, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(inventario)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.message || 'Operación exitosa');
                    modal.hide();
                    loadInventario();
                } else {
                    showError(data.error);
                }
            } catch (error) {
                showError('Error al guardar');
                console.error(error);
            }
        }

        // Eliminar inventario
        async function deleteInventario(id) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción eliminará la variante del inventario',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`api/inventario.php`, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccess('Variante eliminada exitosamente');
                        loadInventario();
                    } else {
                        showError(data.error);
                    }
                } catch (error) {
                    showError('Error al eliminar');
                    console.error(error);
                }
            }
        }

        // Limpiar filtros
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterProducto').value = '';
            document.getElementById('filterActiva').value = '';
            document.getElementById('filterStockBajo').checked = false;
            loadInventario();
        }

        // Utilidad: debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
</body>
</html>
