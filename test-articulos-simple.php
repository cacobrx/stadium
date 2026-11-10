<?php
// test-articulos-simple.php - Versión simple para debugging
session_start();

// Simular usuario logueado para pruebas
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_email'] = 'gustavo.bragagnolo@gmail.com';
    $_SESSION['user_nombre'] = 'Gustavo';
    $_SESSION['login_time'] = time();
}

// Función simple para obtener usuario actual
function getCurrentUser() {
    return [
        'nombre' => $_SESSION['user_nombre'] ?? 'Usuario Test'
    ];
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos - Stadium (Test)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: #212529;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar-header {
            background: #FE3333;
            padding: 1rem;
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }
        .top-nav {
            background: white;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .content-area {
            padding: 2rem;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-trophy"></i> Stadium</h4>
        </div>
        <ul class="list-unstyled p-3">
            <li class="mb-2">
                <a href="index.php?page=dashboard" class="text-white text-decoration-none">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="mb-2">
                <a href="index.php?page=articulos" class="text-white text-decoration-none fw-bold">
                    <i class="bi bi-box"></i> Artículos
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Artículos</h5>
            <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gestión de Artículos</h2>
                <button class="btn btn-primary" onclick="openProductModal()">
                    <i class="bi bi-plus"></i> Nuevo Artículo
                </button>
            </div>

            <!-- Test de conexión API -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6>Estado del Sistema</h6>
                </div>
                <div class="card-body">
                    <div id="systemStatus">Verificando conexión...</div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Artículos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>SKU</th>
                                    <th>Nombre</th>
                                    <th>Marca</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center">Cargando productos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Artículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <div class="mb-3">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio</label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let productModal;

        document.addEventListener('DOMContentLoaded', function() {
            productModal = new bootstrap.Modal(document.getElementById('productModal'));
            testSystem();
            loadProducts();
        });

        async function testSystem() {
            const statusDiv = document.getElementById('systemStatus');
            
            try {
                // Test API productos
                const response = await fetch('./api/productos.php');
                const data = await response.json();
                
                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success">
                            ✅ API funcionando correctamente. ${data.productos.length} productos encontrados.
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-warning">
                            ⚠️ API responde pero con error: ${data.error}
                        </div>
                    `;
                }
            } catch (error) {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        ❌ Error de conexión: ${error.message}
                    </div>
                `;
            }
        }

        async function loadProducts() {
            try {
                const response = await fetch('./api/productos.php');
                const data = await response.json();
                
                if (data.success) {
                    renderProductsTable(data.productos);
                } else {
                    console.error('Error loading products:', data.error);
                    document.querySelector('#productsTable tbody').innerHTML = 
                        `<tr><td colspan="8" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                }
            } catch (error) {
                console.error('Error:', error);
                document.querySelector('#productsTable tbody').innerHTML = 
                    `<tr><td colspan="8" class="text-center text-danger">Error de conexión: ${error.message}</td></tr>`;
            }
        }

        function renderProductsTable(productos) {
            const tbody = document.querySelector('#productsTable tbody');
            tbody.innerHTML = '';

            if (productos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay productos</td></tr>';
                return;
            }

            productos.forEach(producto => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        ${producto.imagen_principal ? 
                            `<img src="${producto.imagen_principal}" alt="${producto.nombre}" class="image-preview">` : 
                            '<i class="bi bi-image text-muted"></i>'
                        }
                    </td>
                    <td>${producto.sku}</td>
                    <td>${producto.nombre}</td>
                    <td>${producto.marca_nombre || 'Sin marca'}</td>
                    <td>$${parseFloat(producto.precio).toFixed(2)}</td>
                    <td>${producto.stock}</td>
                    <td>
                        <span class="badge ${producto.esta_activo ? 'bg-success' : 'bg-secondary'}">
                            ${producto.esta_activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct(${producto.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${producto.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openProductModal() {
            productModal.show();
        }

        function saveProduct() {
            alert('Función de guardar producto - Por implementar');
        }

        function editProduct(id) {
            alert('Editar producto ID: ' + id);
        }

        function deleteProduct(id) {
            if (confirm('¿Eliminar producto?')) {
                alert('Eliminar producto ID: ' + id);
            }
        }
    </script>
</body>
</html>