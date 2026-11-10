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
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Equipamiento Profesional - Stadium</title>
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
		.categoria-badge {
			font-size: 0.85rem;
			padding: 0.35em 0.65em;
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
				<h5 class="mb-0 d-inline ms-3">Equipamiento Profesional</h5>
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
				<h2 class="mb-0">Gestión de Equipamiento Profesional</h2>
				<div class="d-flex gap-2">
					<button class="btn btn-warning" onclick="openHeroConfig()">
						<i class="bi bi-layout-text-window-reverse"></i> Configurar Hero
					</button>
					<button class="btn btn-success" onclick="imprimirEquipamiento()">
						<i class="bi bi-printer"></i> Imprimir
					</button>
					<button class="btn btn-info" onclick="exportarEquipamiento()">
						<i class="bi bi-download"></i> Exportar
					</button>
					<a class="btn btn-primary" href="index.php?page=equipamiento_profesional_form">
						<i class="bi bi-plus"></i> Nuevo Equipamiento
					</a>
				</div>
			</div>

			<!-- Filtros -->
			<div class="card mb-3">
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-2">
							<label for="filterCategoria" class="form-label">Categoría</label>
							<select class="form-select" id="filterCategoria" onchange="loadEquipamiento()">
								<option value="">Todas</option>
								<!-- Las categorías se cargan dinámicamente -->
							</select>
						</div>
						<div class="col-md-2">
							<label for="filterDeporte" class="form-label">Deporte</label>
							<select class="form-select" id="filterDeporte" onchange="loadEquipamiento()">
								<option value="">Todos</option>
								<!-- Los deportes se cargan dinámicamente -->
							</select>
						</div>
						<div class="col-md-2">
							<label for="filterDisponible" class="form-label">Disponibilidad</label>
							<select class="form-select" id="filterDisponible" onchange="loadEquipamiento()">
								<option value="">Todos</option>
								<option value="1">Disponible</option>
								<option value="0">No disponible</option>
							</select>
						</div>
						<div class="col-md-2">
							<label for="filterUso" class="form-label">Uso</label>
							<select class="form-select" id="filterUso" onchange="loadEquipamiento()">
								<option value="">Todos</option>
								<option value="Profesional">Profesional</option>
								<option value="Domestico">Dom&eacute;stico</option>
							</select>
						</div>
						<div class="col-md-4">
							<label for="filterSearch" class="form-label">Buscar</label>
							<input type="text" class="form-control" id="filterSearch" placeholder="Nombre..." onkeyup="loadEquipamiento()">
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h5 class="mb-0">Lista de Equipamiento</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped" id="equipamientoTable">
							<thead>
								<tr>
									<th style="width:50px">Orden</th>
									<th>Imagen</th>
									<th>Nombre</th>
									<th>Categoría</th>
									<th>Deporte</th>										<th>Uso</th>									<th>Tiempo Entrega</th>
									<th>Garantía</th>
									<th>Instalación</th>
									<th>Financiación</th>
									<th>Disponible</th>
									<th>Destacado</th>
									<th>Estado</th>
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

		let categoriaLabels = {};

		document.addEventListener('DOMContentLoaded', () => {
			loadTheme();
			Promise.all([loadCategorias(), loadDeportes()]).then(() => loadEquipamiento());
		});

		async function loadCategorias() {
			try {
				const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
				const response = await fetch(basePath + 'api/categorias.php');
				const data = await response.json();
				
				if (data.success && data.categorias) {
					const select = document.getElementById('filterCategoria');
					data.categorias.forEach(cat => {
						categoriaLabels[cat.id] = cat.nombre;
						const option = document.createElement('option');
						option.value = cat.id;
						option.textContent = cat.nombre;
						select.appendChild(option);
					});
				}
			} catch (error) {
				console.error('Error al cargar categorías:', error);
			}
		}
		
		async function loadDeportes() {
			try {
				const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
				const response = await fetch(basePath + 'api/deportes.php');
				const data = await response.json();
				
				if (data.success && data.deportes) {
					const select = document.getElementById('filterDeporte');
					data.deportes.forEach(dep => {
						const option = document.createElement('option');
						option.value = dep.id;
						option.textContent = dep.nombre;
						select.appendChild(option);
					});
				}
			} catch (error) {
				console.error('Error al cargar deportes:', error);
			}
		}

	async function loadEquipamiento() {
		try {
			// Usar ruta absoluta desde la raíz del sitio
			const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
			let url = basePath + 'api/equipamiento_profesional.php';
			const params = new URLSearchParams();				const categoria = document.getElementById('filterCategoria').value;
				const deporte = document.getElementById('filterDeporte').value;
				const disponible = document.getElementById('filterDisponible').value;
				const uso = document.getElementById('filterUso').value;
				const search = document.getElementById('filterSearch').value;

				if (categoria) params.append('categoria', categoria);
				if (deporte) params.append('deporte', deporte);
				if (disponible) params.append('disponible', disponible);
				if (uso) params.append('uso', uso);
				if (search) params.append('search', search);

				if (params.toString()) {
					url += '?' + params.toString();
				}

				const response = await fetch(url);
				const data = await response.json();

				if (data.success) {
					renderEquipamientoTable(data.equipamiento);
				} else {
					console.error('Error al cargar equipamiento:', data.error);
					CRUDAlerts.connectionError();
				}
			} catch (error) {
				console.error('Error al cargar equipamiento:', error);
				CRUDAlerts.connectionError();
			}
		}

		function renderEquipamientoTable(equipamiento) {
			const tbody = document.querySelector('#equipamientoTable tbody');
			tbody.innerHTML = '';

			if (equipamiento.length === 0) {
				tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">No se encontró equipamiento</td></tr>';
				return;
			}

			equipamiento.forEach((item, index) => {
				const row = document.createElement('tr');
				row.dataset.id = item.id;
				const imageHtml = item.imagen_principal ?
					`<img src="${resolveImageUrl(item.imagen_principal)}" alt="${item.nombre}" class="image-preview">` :
					'<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
				
				const isFirst = index === 0;
				const isLast  = index === equipamiento.length - 1;
				
				row.innerHTML = `
					<td class="text-center">
						<div class="d-flex flex-column align-items-center gap-1">
							<span class="fw-bold text-muted">${item.orden}</span>
							<button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverEquipamiento(${item.id}, 'up')" ${isFirst ? 'disabled' : ''} title="Subir">
								<i class="bi bi-chevron-up"></i>
							</button>
							<button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moverEquipamiento(${item.id}, 'down')" ${isLast ? 'disabled' : ''} title="Bajar">
								<i class="bi bi-chevron-down"></i>
							</button>
						</div>
					</td>
					<td class="text-center">
						${imageHtml}
					</td>
					<td>${item.nombre}</td>
					<td>
						<span class="badge categoria-badge bg-info">
							${item.categoria_nombre || 'Sin categoría'}
						</span>
					</td>
					<td>
						<span class="badge bg-secondary">
							${item.deporte_nombre || 'Sin deporte'}
						</span>
					</td>
					<td>
							${item.uso ? `<span class="badge ${item.uso === 'Profesional' ? 'bg-primary' : 'bg-success'}">${item.uso === 'Domestico' ? 'Doméstico' : item.uso}</span>` : '<span class="text-muted">-</span>'}
					</td>
					<td>${item.tiempo_entrega || '-'}</td>
					<td>${item.garantia || '-'}</td>
					<td class="text-center">
						${item.instalacion == 1 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'}
					</td>
					<td class="text-center">
						${item.financiacion == 1 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'}
					</td>
					<td>
						<span class="badge ${item.disponible ? 'bg-success' : 'bg-danger'}">
							${item.disponible ? 'Disponible' : 'No disponible'}
						</span>
					</td>
					<td class="text-center">
						<i class="bi ${item.destacado == 1 ? 'bi-star-fill text-warning' : 'bi-star text-muted'}"
						   style="cursor:pointer;font-size:1.2rem"
						   title="${item.destacado == 1 ? 'Quitar destaque' : 'Marcar como destacado'}"
						   onclick="toggleDestacado(${item.id}, this)"></i>
					</td>
					<td>
						<span class="badge ${item.esta_activo ? 'bg-success' : 'bg-secondary'}">
							${item.esta_activo ? 'Activo' : 'Inactivo'}
						</span>
					</td>
					<td>
						<button class="btn btn-sm btn-outline-primary me-1" onclick="goToEquipamiento(${item.id})" title="Editar">
							<i class="bi bi-pencil"></i>
						</button>
						<button class="btn btn-sm btn-outline-danger" onclick="deleteEquipamiento(${item.id})" title="Eliminar">
							<i class="bi bi-trash"></i>
						</button>
					</td>
				`;
				tbody.appendChild(row);
			});
		}

		function goToEquipamiento(id) {
			window.location.href = id ? `index.php?page=equipamiento_profesional_form&id=${id}` : 'index.php?page=equipamiento_profesional_form';
		}

		async function toggleDestacado(id, el) {
			const esDestacado = el.classList.contains('bi-star-fill');
			try {
				const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
				const response = await fetch(basePath + 'api/equipamiento_profesional.php', {
					method: 'PATCH',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id, toggle_destacado: true })
				});
				const data = await response.json();
				if (data.success) {
					const nuevoDestacado = !esDestacado;
					el.classList.toggle('bi-star-fill', nuevoDestacado);
					el.classList.toggle('text-warning', nuevoDestacado);
					el.classList.toggle('bi-star', !nuevoDestacado);
					el.classList.toggle('text-muted', !nuevoDestacado);
					el.title = nuevoDestacado ? 'Quitar destaque' : 'Marcar como destacado';
				} else {
					console.error('Error al cambiar destacado:', data.error);
				}
			} catch (error) {
				console.error('Error al cambiar destacado:', error);
			}
		}

		async function moverEquipamiento(id, direction) {
			try {
				const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
				const response = await fetch(basePath + 'api/equipamiento_profesional.php', {
					method: 'PATCH',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ id, direction })
				});
				const data = await response.json();
				if (data.success) {
					loadEquipamiento();
				} else {
					console.error('Error al reordenar:', data.error);
				}
			} catch (error) {
				console.error('Error al reordenar:', error);
			}
		}

	async function deleteEquipamiento(id) {
		const result = await CRUDAlerts.confirmDelete('equipamiento');

		if (!result.isConfirmed) {
			return;
		}

		try {
			const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
			const response = await fetch(basePath + 'api/equipamiento_profesional.php', {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({ id })
			});				const data = await response.json();

				if (data.success) {
					loadEquipamiento();
					CRUDAlerts.deleted('Equipamiento');
				} else {
					console.error('Error al eliminar equipamiento:', data.error);
					CRUDAlerts.deleteError(data.error);
				}
			} catch (error) {
				console.error('Error al eliminar equipamiento:', error);
				CRUDAlerts.connectionError();
			}
		}

		// Función para imprimir la lista de equipamiento profesional
		function imprimirEquipamiento() {
			window.open('?page=equipamiento_profesional_prn', '_blank');
		}

		// Función para exportar equipamiento profesional a Excel
		function exportarEquipamiento() {
			window.open('?page=equipamiento_profesional_exp', '_self');
		}

        /* Lógica Hero Config */
        let listBadges = [];

        function openHeroConfig() {
            const m = new bootstrap.Modal(document.getElementById('heroConfigModal'));
            loadHeroData();
            m.show();
        }

        async function loadHeroData() {
            try {
                const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                const r = await fetch(basePath + 'api/equipamiento_profesional_hero.php');
                const d = await r.json();
                if(d.success) {
                    const h = d.data || {};
                    document.getElementById('h_etiqueta').value = h.etiqueta || '';
                    document.getElementById('h_titulo').value = h.titulo || '';
                    document.getElementById('h_titulo_destacado').value = h.titulo_destacado || '';
                    document.getElementById('h_descripcion').value = h.descripcion || '';
                    document.getElementById('h_boton_principal').value = h.boton_principal || '';
                    document.getElementById('h_boton_principal_link').value = h.boton_principal_link || ''; // No estaba en el JSON pedido pero sí en la API
                    document.getElementById('h_boton_secundario').value = h.boton_secundario || '';
                    document.getElementById('h_boton_secundario_link').value = h.boton_secundario_link || '';
                    document.getElementById('h_esta_activo').checked = h.esta_activo == 1;

                    // Imagen
                    const preview = document.getElementById('hero-preview');
                    document.getElementById('h_imagen_fondo').value = h.imagen_fondo || '';
                    if (h.imagen_fondo) {
                        const imgUrl = h.imagen_fondo.startsWith('http') ? h.imagen_fondo : basePath + h.imagen_fondo;
                        preview.innerHTML = `<div class="d-flex align-items-center gap-2"><img src="${imgUrl}?t=${Date.now()}" class="img-thumbnail" style="max-height:100px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="clearHeroImg()">Quitar</button></div>`;
                    } else {
                        preview.innerHTML = '';
                    }

                    // Badges
                    listBadges = h.badges ? h.badges.split(',').filter(x => x.trim()) : [];
                    renderBadges();
                }
            } catch(e) { console.error(e); }
        }

        function renderBadges() {
            const c = document.getElementById('badges-container');
            c.innerHTML = '';
            listBadges.forEach((b, i) => {
                const div = document.createElement('div');
                div.className = 'badge bg-light text-dark border me-1 mb-1 p-2 d-inline-flex align-items-center gap-2';
                div.innerHTML = `<span>${b}</span><i class="bi bi-x cursor-pointer" onclick="removeBadge(${i})" style="cursor:pointer"></i>`;
                c.appendChild(div);
            });
        }

        function addBadge() {
            const inp = document.getElementById('h_new_badge');
            const val = inp.value.trim();
            if(val) {
                listBadges.push(val);
                inp.value = '';
                renderBadges();
            }
        }

        function removeBadge(i) {
            listBadges.splice(i, 1);
            renderBadges();
        }

        async function previewHeroImg(inp) {
            const file = inp.files[0];
            if(!file) return;
            
            // Subir usando nombre equi_1
            try {
                const url = await uploadImage(inp, 'equi_1', true);
                if(url) {
                    document.getElementById('h_imagen_fondo').value = url;
                    const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                    const fullUrl = url.startsWith('http') ? url : basePath + url;
                    const preview = document.getElementById('hero-preview');
                    preview.innerHTML = `<div class="d-flex align-items-center gap-2"><img src="${fullUrl}?t=${Date.now()}" class="img-thumbnail" style="max-height:100px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="clearHeroImg()">Quitar</button></div>`;
                }
            } catch(e) {
                Swal.fire('Error', e.message, 'error');
            }
        }

        function clearHeroImg() {
            document.getElementById('h_imagen_fondo').value = '';
            document.getElementById('hero_file').value = '';
            document.getElementById('hero-preview').innerHTML = '';
        }

        async function saveHeroConfig() {
            const data = {
                etiqueta: document.getElementById('h_etiqueta').value,
                titulo: document.getElementById('h_titulo').value,
                titulo_destacado: document.getElementById('h_titulo_destacado').value,
                descripcion: document.getElementById('h_descripcion').value,
                imagen_fondo: document.getElementById('h_imagen_fondo').value,
                boton_principal: document.getElementById('h_boton_principal').value,
                boton_principal_link: document.getElementById('h_boton_principal_link').value,
                boton_secundario: document.getElementById('h_boton_secundario').value,
                boton_secundario_link: document.getElementById('h_boton_secundario_link').value,
                badges: listBadges,
                esta_activo: document.getElementById('h_esta_activo').checked ? 1 : 0
            };

            try {
                const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                const r = await fetch(basePath + 'api/equipamiento_profesional_hero.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({hero: data})
                });
                const res = await r.json();
                if(res.success) {
                    Swal.fire('Guardado', 'Configuración actualizada', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('heroConfigModal')).hide();
                } else {
                    Swal.fire('Error', res.error || 'Error desconocido', 'error');
                }
            } catch(e) {
                Swal.fire('Error', e.message, 'error');
            }
        }

        // Reutilizable uploadImage
        async function uploadImage(fileInput, customName = null, useExactName = false) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;
            const file = fileInput.files[0];
            const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
            
            const fd = new FormData();
            fd.append('imagen', file);
            if(customName) fd.append('custom_name', customName);
            if(useExactName) fd.append('use_exact_name', 'true');

            // API upload_imagen.php maneja conversión
            const r = await fetch(basePath + 'api/upload_imagen.php', {
                method: 'POST', body: fd
            });
            const d = await r.json();
            if(d.success) return d.url;
            throw new Error(d.error || 'Fallo upload');
        }
	</script>

    <!-- Modal Hero -->
    <div class="modal fade" id="heroConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configurar Hero (Equipamiento)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="h_esta_activo">
                                <label class="form-check-label" for="h_esta_activo">Sección Activa</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Etiqueta</label>
                            <input type="text" class="form-control" id="h_etiqueta">
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" id="h_titulo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Título Destacado</label>
                            <input type="text" class="form-control" id="h_titulo_destacado">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="h_descripcion" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Imagen de Fondo</label>
                            <input type="file" class="form-control mb-2" id="hero_file" onchange="previewHeroImg(this)">
                            <div id="hero-preview"></div>
                            <input type="hidden" id="h_imagen_fondo">
                            <small class="text-muted">Se guardará como equi_1.webp</small>
                        </div>
                        <div class="col-md-6">
                             <label class="form-label">Botón Principal</label>
                             <input type="text" class="form-control mb-1" id="h_boton_principal" placeholder="Texto">
                             <input type="text" class="form-control" id="h_boton_principal_link" placeholder="Enlace (# / http...)">
                        </div>
                        <div class="col-md-6">
                             <label class="form-label">Botón Secundario</label>
                             <input type="text" class="form-control mb-1" id="h_boton_secundario" placeholder="Texto">
                             <input type="text" class="form-control" id="h_boton_secundario_link" placeholder="Enlace">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Badges</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="h_new_badge" placeholder="Nuevo badge...">
                                <button class="btn btn-outline-secondary" type="button" onclick="addBadge()">Agregar</button>
                            </div>
                            <div id="badges-container" class="p-2 border rounded bg-white" style="min-height: 40px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveHeroConfig()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
