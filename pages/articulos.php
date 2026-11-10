<?php
// Incluir bootstrap para iniciar sesión antes de cualquier salida
// Incluir bootstrap para iniciar sesión antes de cualquier salida
if (!file_exists(__DIR__ . '/../includes/bootstrap.php')) {
	require_once __DIR__ . '/../includes/auth.php';
} else {
	require_once __DIR__ . '/../includes/bootstrap.php';
	// Verificar autenticación
	require_once __DIR__ . '/../includes/auth.php';
}
requireAuth();
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Artículos - Stadium</title>
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
			max-width: 100px;
			max-height: 100px;
			object-fit: cover;
			border-radius: 4px;
		}
		.form-check-input:checked {
			background-color: var(--bs-primary);
			border-color: var(--bs-primary);
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
				<h5 class="mb-0 d-inline ms-3">Artículos</h5>
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
			<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
				<h2 class="mb-0">Gestión de Artículos</h2>
				<a class="btn btn-primary" href="index.php?page=articulos_form">
					<i class="bi bi-plus"></i> Nuevo Artículo
				</a>
			</div>

			<!-- Filtros -->
			<div class="card mb-3">
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-3">
							<label for="filterSearch" class="form-label">Buscar</label>
							<input type="text" class="form-control" id="filterSearch" placeholder="SKU, nombre...">
						</div>
						<div class="col-md-2">
							<label for="filterMarca" class="form-label">Marca</label>
							<select class="form-select" id="filterMarca">
								<option value="">Todas</option>
							</select>
						</div>
						<div class="col-md-2">
							<label for="filterEstado" class="form-label">Estado</label>
							<select class="form-select" id="filterEstado">
								<option value="">Todos</option>
								<option value="1">Activo</option>
								<option value="0">Inactivo</option>
							</select>
						</div>
						<div class="col-md-2">
							<label for="filterDestacado" class="form-label">Destacado</label>
							<select class="form-select" id="filterDestacado">
								<option value="">Todos</option>
								<option value="1">Sí</option>
								<option value="0">No</option>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label">&nbsp;</label>
							<div>
								<button class="btn btn-primary" onclick="applyFilters()">
									<i class="bi bi-funnel"></i> Filtrar
								</button>
								<button class="btn btn-outline-secondary" onclick="clearFilters()">
									<i class="bi bi-x-circle"></i> Limpiar
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

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
									<th>Destacado</th>
									<th>Acciones</th>
								</tr>
							</thead>
							<tbody>
								<!-- Data will be loaded here -->
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		<?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
	</script>
	<script>
		const appBasePath = (() => {
			const path = window.location.pathname || '/';
			if (path.includes('index.php')) {
				return path.substring(0, path.indexOf('index.php')) || '/';
			}
			if (path.endsWith('/')) {
				return path;
			}
			const lastSlash = path.lastIndexOf('/');
			return lastSlash >= 0 ? path.substring(0, lastSlash + 1) : '/';
		})();

		function resolveImageUrl(path) {
			if (!path) {
				return '';
			}
			if (/^https?:\/\//i.test(path) || path.startsWith('data:')) {
				return path;
			}
			const normalized = path.replace(/^\.\//, '').replace(/^\//, '');
			const base = appBasePath.endsWith('/') ? appBasePath : `${appBasePath}/`;
			return `${window.location.origin}${base}${normalized}`;
		}

		let allProducts = [];
		let filteredProducts = [];

		document.addEventListener('DOMContentLoaded', () => {
			loadTheme();
			loadProducts();
			loadMarcas();
			
			// Eventos de filtro en tiempo real
			document.getElementById('filterSearch').addEventListener('input', applyFilters);
		});

		async function loadProducts() {
			try {
				const response = await fetch('./api/productos.php');
				const data = await response.json();

				if (data.success) {
					allProducts = data.productos;
					filteredProducts = [...allProducts];
					renderProductsTable(filteredProducts);
				} else {
					console.error('Error al cargar productos:', data.error);
					CRUDAlerts.connectionError();
				}
			} catch (error) {
				console.error('Error al cargar productos:', error);
				CRUDAlerts.connectionError();
			}
		}

		async function loadMarcas() {
			try {
				const response = await fetch('./api/marcas.php');
				const data = await response.json();

				if (data.success) {
					const select = document.getElementById('filterMarca');
					data.marcas.forEach(marca => {
						const option = document.createElement('option');
						option.value = marca.id;
						option.textContent = marca.nombre;
						select.appendChild(option);
					});
				}
			} catch (error) {
				console.error('Error al cargar marcas:', error);
			}
		}

		function applyFilters() {
			const searchText = document.getElementById('filterSearch').value.toLowerCase();
			const marcaId = document.getElementById('filterMarca').value;
			const estado = document.getElementById('filterEstado').value;
			const destacado = document.getElementById('filterDestacado').value;

			filteredProducts = allProducts.filter(producto => {
				// Filtro de búsqueda
				if (searchText) {
					const matchSearch = 
						producto.sku.toLowerCase().includes(searchText) ||
						producto.nombre.toLowerCase().includes(searchText) ||
						(producto.marca_nombre && producto.marca_nombre.toLowerCase().includes(searchText));
					if (!matchSearch) return false;
				}

				// Filtro de marca
				if (marcaId && producto.marca_id != marcaId) {
					return false;
				}

				// Filtro de estado
				if (estado !== '' && producto.esta_activo != estado) {
					return false;
				}

				// Filtro de destacado
				if (destacado !== '' && producto.destacado != destacado) {
					return false;
				}

				return true;
			});

			renderProductsTable(filteredProducts);
		}

		function clearFilters() {
			document.getElementById('filterSearch').value = '';
			document.getElementById('filterMarca').value = '';
			document.getElementById('filterEstado').value = '';
			document.getElementById('filterDestacado').value = '';
			filteredProducts = [...allProducts];
			renderProductsTable(filteredProducts);
		}

		function renderProductsTable(productos) {
			const tbody = document.querySelector('#productsTable tbody');
			tbody.innerHTML = '';

			productos.forEach(producto => {
				const row = document.createElement('tr');
				const imageHtml = producto.imagen_principal ?
					`<img src="${resolveImageUrl(producto.imagen_principal)}" alt="${producto.nombre}" class="image-preview">` :
					'<i class="bi bi-image text-muted"></i>';
				row.innerHTML = `
					<td>
						${imageHtml}
					</td>
					<td>${producto.sku}</td>
					<td>${producto.nombre}</td>
					<td>${producto.marca_nombre || 'Sin marca'}</td>
					<td>$${parseFloat(producto.precio).toFixed(2)}</td>
					<td>${producto.stock}</td>
					<td>
						<span class="badge ${Number(producto.esta_activo) === 1 ? 'bg-success' : 'bg-secondary'}">
							${Number(producto.esta_activo) === 1 ? 'Activo' : 'Inactivo'}
						</span>
					</td>
					<td>
						${Number(producto.destacado) === 1 ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>'}
					</td>
					<td>
						<button class="btn btn-sm btn-outline-primary me-1" onclick="goToProduct(${producto.id})">
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

		function goToProduct(id) {
			window.location.href = id ? `index.php?page=articulos_form&id=${id}` : 'index.php?page=articulos_form';
		}

		async function deleteProduct(id) {
			const result = await CRUDAlerts.confirmDelete('artículo');

			if (!result.isConfirmed) {
				return;
			}

			try {
				const response = await fetch('./api/productos.php', {
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({ id })
				});

				const data = await response.json();

				if (data.success) {
					await loadProducts();
					applyFilters();
					CRUDAlerts.deleted('Artículo');
				} else {
					console.error('Error al eliminar artículo:', data.error);
					CRUDAlerts.deleteError(data.error);
				}
			} catch (error) {
				console.error('Error al eliminar artículo:', error);
				CRUDAlerts.connectionError();
			}
		}
	</script>
</body>
</html>
