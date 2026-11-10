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
    <title>Proyectos - Stadium</title>
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
        
        /* Estilos específicos para proyectos */
        .proyecto-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        /* Hover adaptativo según el tema */
        [data-bs-theme="dark"] .proyecto-row:hover {
            background-color: rgba(255,255,255,0.05) !important;
        }
        
        [data-bs-theme="light"] .proyecto-row:hover {
            background-color: rgba(0,0,0,0.05) !important;
        }
        
        /* Fila de detalle adaptativa */
        [data-bs-theme="dark"] .detalle-row {
            background-color: #1a1d20;
            border-left: 3px solid var(--bs-primary);
            color: var(--bs-body-color);
        }
        
        [data-bs-theme="light"] .detalle-row {
            background-color: #f8f9fa;
            border-left: 3px solid var(--bs-primary);
            color: var(--bs-body-color);
        }
        
        .expand-icon {
            transition: transform 0.3s ease;
        }
        
        .proyecto-row {
            cursor: pointer;
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
        
        /* Estilos para tabla de especificaciones - adaptativos */
        .detalle-row .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--bs-body-color);
            color: inherit;
        }
        
        .detalle-row .table-bordered {
            border-color: var(--bs-border-color);
        }
        
        .detalle-row .table td,
        .detalle-row .table th {
            border-color: var(--bs-border-color);
            background-color: transparent;
            color: var(--bs-body-color);
        }
        
        /* Texto en la sección de detalles */
        .detalle-row h6,
        .detalle-row p,
        .detalle-row span,
        .detalle-row small {
            color: var(--bs-body-color);
        }
        
        /* Iconos en detalles */
        .detalle-row .text-muted {
            color: var(--bs-secondary-color) !important;
        }
        
        .badge-custom {
            background-color: #ffc107;
            color: #000;
        }
        
        .proyecto-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Estilos para galería de imágenes */
        .gallery-image-card {
            background-color: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color) !important;
        }
        
        .gallery-image-preview {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color) !important;
        }
        
        .gallery-empty-state {
            background-color: var(--bs-tertiary-bg);
            border: 2px dashed var(--bs-border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }
        
        .gallery-image-preview-mini {
            width: 50px;
            height: 50px;
            min-width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-tertiary-bg);
            border-radius: 6px;
            overflow: hidden;
        }
        
        .gallery-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .gallery-image-card {
            background-color: var(--bs-body-bg);
            transition: background-color 0.2s;
        }
        
        .gallery-image-card:hover {
            background-color: var(--bs-tertiary-bg);
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
                <h5 class="mb-0 d-inline">Proyectos</h5>
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
                <h2>Gestión de Proyectos</h2>
                <button class="btn btn-primary" onclick="mostrarFormulario()">
                    <i class="bi bi-plus"></i> Nuevo Proyecto
                </button>
            </div>

            <!-- Proyectos Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Proyectos</h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de búsqueda -->
                    <div class="mb-3">
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="filtro" placeholder="Buscar por título, cliente, ubicación..." onkeyup="filtrarProyectos()">
                        </div>
                    </div>
                    
                    <!-- Tabla de proyectos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaProyectos">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50px">
                                        <i class="bi bi-chevron-right"></i>
                                    </th>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Proyecto</th>
                                    <th>Cliente</th>
                                    <th>Ubicación</th>
                                    <th>Fecha</th>
                                    <th>Detalles</th>
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

    <!-- Modal para agregar/editar proyecto -->
    <div class="modal fade" id="modalProyecto" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModal">Nuevo Proyecto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProyecto">
                    <div class="modal-body">
                        <input type="hidden" id="proyectoId">
                        
                        <!-- Información básica -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Título *</label>
                                <input type="text" class="form-control" id="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug *</label>
                                <input type="text" class="form-control" id="slug" required>
                                <div class="form-text">URL amigable (se genera automáticamente)</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subtítulo</label>
                                <input type="text" class="form-control" id="subtitle">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Badge</label>
                                <input type="text" class="form-control" id="badge" placeholder="Ej: Proyecto Destacado">
                                <input type="hidden" id="hero_image"><!-- Se define desde la galería -->
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" rows="4"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="location" placeholder="Ej: Buenos Aires, Argentina">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="client" placeholder="Ej: Municipalidad de Buenos Aires">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha Finalización</label>
                                <input type="text" class="form-control" id="completion_date" placeholder="Ej: Marzo 2024">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Área</label>
                                <input type="text" class="form-control" id="area" placeholder="Ej: 1,200 m²">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Duración</label>
                                <input type="text" class="form-control" id="duration" placeholder="Ej: 3 meses">
                            </div>
                        </div>
                        
                        <!-- Sección de categorías -->
                        <hr>
                        <h6><i class="bi bi-tags"></i> Categorías del Proyecto</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" id="categoriaInput" placeholder="Ej: Baloncesto">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success d-block w-100" onclick="agregarCategoria()">
                                    <i class="bi bi-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                        
                        <div id="categoriasLista" class="mb-3"></div>
                        
                        <!-- Tabs para especificaciones, características y galería -->
                        <ul class="nav nav-tabs" id="proyectoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="especificaciones-tab" data-bs-toggle="tab" data-bs-target="#especificaciones" type="button" role="tab">
                                    Especificaciones
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab">
                                    Características
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="galeria-tab" data-bs-toggle="tab" data-bs-target="#galeria" type="button" role="tab">
                                    Galería
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="proyectoTabsContent">
                            <!-- Especificaciones -->
                            <div class="tab-pane fade show active" id="especificaciones" role="tabpanel">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="especLabel" placeholder="Etiqueta">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="especValue" placeholder="Valor">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success d-block w-100" onclick="agregarEspecificacion()">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="especificacionesTabla"></div>
                            </div>
                            
                            <!-- Características -->
                            <div class="tab-pane fade" id="features" role="tabpanel">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" id="featureTitle" placeholder="Título">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" id="featureDescription" placeholder="Descripción">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success d-block w-100" onclick="agregarFeature()">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="featuresTabla"></div>
                            </div>
                            
                            <!-- Galería -->
                            <div class="tab-pane fade" id="galeria" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Agregue imágenes a la galería del proyecto</span>
                                    <button type="button" class="btn btn-primary" onclick="agregarImagenGaleria()">
                                        <i class="bi bi-plus-circle"></i> Agregar Imagen
                                    </button>
                                </div>
                                <div id="galeriaContainer"></div>
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
        let proyectos = [];
        let categoriesDelProyecto = [];
        let specificationsDelProyecto = [];
        let featuresDelProyecto = [];
        let galleryDelProyecto = [];
        const modal = new bootstrap.Modal(document.getElementById('modalProyecto'));

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarProyectos();
            
            // Auto-generar slug desde el título
            document.getElementById('title').addEventListener('input', function() {
                const titulo = this.value;
                const slug = titulo.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
                document.getElementById('slug').value = slug;
            });
            
            // Event listeners para agregar elementos con Enter
            document.getElementById('categoriaInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarCategoria();
                }
            });
            
            document.getElementById('especValue').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarEspecificacion();
                }
            });
            
            document.getElementById('featureDescription').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarFeature();
                }
            });
        });

        async function cargarProyectos() {
            try {
                const response = await fetch('./api/proyectos.php');
                proyectos = await response.json();
                mostrarProyectos();
            } catch (error) {
                console.error('Error cargando proyectos:', error);
                CRUDAlerts.connectionError();
            }
        }

        function mostrarProyectos() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';
            
            proyectos.forEach(proyecto => {
                const tr = document.createElement('tr');
                tr.className = 'proyecto-row';
                tr.id = `proyecto-${proyecto.id}`;
                
                const imageHtml = proyecto.hero_image ? 
                    `<img src="${proyecto.hero_image}" alt="${proyecto.title}" class="proyecto-image">` : 
                    '<div class="proyecto-image bg-light d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted"></i></div>';
                
                tr.innerHTML = `
                    <td class="text-center">
                        <i class="bi bi-chevron-right expand-icon" id="icon-${proyecto.id}"></i>
                    </td>
                    <td>${proyecto.id}</td>
                    <td>${imageHtml}</td>
                    <td>
                        <div>
                            <strong>${proyecto.title}</strong>
                            ${proyecto.badge ? `<span class="badge badge-custom ms-2">${proyecto.badge}</span>` : ''}
                        </div>
                        ${proyecto.subtitle ? `<small class="text-muted d-block">${proyecto.subtitle}</small>` : ''}
                    </td>
                    <td>${proyecto.client || '-'}</td>
                    <td>${proyecto.location || '-'}</td>
                    <td>${proyecto.completion_date || '-'}</td>
                    <td class="text-center">
                        <span class="badge bg-primary">${proyecto.cantidad_categorias || 0} cat.</span>
                        <span class="badge bg-info">${proyecto.cantidad_especificaciones || 0} esp.</span>
                        <span class="badge bg-warning">${proyecto.cantidad_features || 0} caract.</span>
                        <span class="badge bg-success">${proyecto.cantidad_galeria || 0} img.</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info me-1" onclick="verProyecto(${proyecto.id})" title="Ver">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning me-1" onclick="editarProyecto(${proyecto.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarProyecto(${proyecto.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                
                tr.onclick = (e) => {
                    if (!e.target.closest('.btn')) {
                        toggleDetalle(proyecto.id);
                    }
                };
                
                tbody.appendChild(tr);
                
                // Crear fila de detalle
                const detalleRow = document.createElement('tr');
                detalleRow.id = `detalle-${proyecto.id}`;
                detalleRow.innerHTML = `
                    <td colspan="9">
                        <div class="detalle-container" id="detalle-container-${proyecto.id}">
                            <div class="p-3 detalle-row">
                                <h6><i class="bi bi-list-ul"></i> Detalle del Proyecto #${proyecto.id}</h6>
                                <div id="detalle-items-${proyecto.id}">
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

        async function toggleDetalle(proyectoId) {
            const container = document.getElementById(`detalle-container-${proyectoId}`);
            const icon = document.getElementById(`icon-${proyectoId}`);
            const row = document.getElementById(`proyecto-${proyectoId}`);
            
            if (!container.classList.contains('show')) {
                // Mostrar detalle
                container.classList.add('show');
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
                row.classList.add('expanded');
                
                // Cargar detalles del proyecto
                await cargarDetalleProyecto(proyectoId);
            } else {
                // Ocultar detalle
                container.classList.remove('show');
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
                row.classList.remove('expanded');
            }
        }

        async function cargarDetalleProyecto(proyectoId) {
            try {
                console.log('Cargando detalle para proyecto ID:', proyectoId);
                const response = await fetch(`./api/proyectos_det.php?id=${proyectoId}`);
                const detalles = await response.json();
                console.log('Detalles recibidos:', detalles);
                
                const container = document.getElementById(`detalle-items-${proyectoId}`);
                
                if (!Array.isArray(detalles) || detalles.length === 0) {
                    console.log('No hay detalles o no es un array');
                    container.innerHTML = '<div class="text-muted">No hay detalles para este proyecto.</div>';
                    return;
                }
                
                let html = `<div class="row">`;
                
                // Agrupar por tipo
                const categorias = detalles.filter(d => d.tipo === 'categoria');
                const especificaciones = detalles.filter(d => d.tipo === 'especificacion');
                const features = detalles.filter(d => d.tipo === 'feature');
                const imagenes = detalles.filter(d => d.tipo === 'imagen');
                
                // Categorías
                if (categorias.length > 0) {
                    html += `
                        <div class="col-md-6 mb-3">
                            <h6><i class="bi bi-tags"></i> Categorías</h6>
                            <div class="d-flex flex-wrap gap-1">
                                ${categorias.map(cat => `<span class="badge bg-secondary">${cat.valor}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }
                
                // Características
                if (features.length > 0) {
                    html += `
                        <div class="col-md-6 mb-3">
                            <h6><i class="bi bi-star"></i> Características</h6>
                            ${features.map(feat => `
                                <div class="d-flex align-items-start mb-2">
                                    ${feat.extra ? `<i class="bi bi-${feat.extra} me-2 text-primary"></i>` : ''}
                                    <small>${feat.valor}</small>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                // Especificaciones
                if (especificaciones.length > 0) {
                    html += `
                        <div class="col-12 mb-3">
                            <h6><i class="bi bi-gear"></i> Especificaciones Técnicas</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        ${especificaciones.map(spec => `
                                            <tr>
                                                <td><strong>${spec.extra}</strong></td>
                                                <td>${spec.valor.replace(spec.extra + ': ', '')}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }
                
                // Galería
                if (imagenes.length > 0) {
                    html += `
                        <div class="col-12 mb-3">
                            <h6><i class="bi bi-images"></i> Galería</h6>
                            <div class="row">
                                ${imagenes.map(img => `
                                    <div class="col-md-3 mb-2">
                                        <img src="${img.extra}" alt="${img.valor}" class="img-fluid rounded" style="height: 100px; object-fit: cover; width: 100%;">
                                        <small class="text-muted d-block">${img.valor}</small>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
                
                html += `</div>`;
                
                container.innerHTML = html;
            } catch (error) {
                document.getElementById(`detalle-items-${proyectoId}`).innerHTML = 
                    '<div class="text-danger">Error cargando detalles del proyecto.</div>';
            }
        }

        function filtrarProyectos() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            const filas = document.querySelectorAll('#cuerpoTabla tr.proyecto-row');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const visible = texto.includes(filtro);
                fila.style.display = visible ? '' : 'none';
                
                // También ocultar/mostrar la fila de detalle correspondiente
                const id = fila.id.replace('proyecto-', '');
                const detalleRow = document.getElementById(`detalle-${id}`);
                if (detalleRow) {
                    detalleRow.style.display = visible ? '' : 'none';
                }
            });
        }

        function mostrarFormulario(proyecto = null) {
            document.getElementById('formProyecto').reset();
            categoriesDelProyecto = [];
            specificationsDelProyecto = [];
            featuresDelProyecto = [];
            galleryDelProyecto = [];
            
            actualizarTablaCategorias();
            actualizarTablaEspecificaciones();
            actualizarTablaFeatures();
            mostrarEstadoVacioGaleria();
            
            if (proyecto) {
                document.getElementById('tituloModal').textContent = 'Editar Proyecto';
                document.getElementById('proyectoId').value = proyecto.id;
                document.getElementById('title').value = proyecto.title || '';
                document.getElementById('slug').value = proyecto.slug || '';
                document.getElementById('subtitle').value = proyecto.subtitle || '';
                document.getElementById('badge').value = proyecto.badge || '';
                document.getElementById('hero_image').value = proyecto.hero_image || '';
                document.getElementById('description').value = proyecto.description || '';
                document.getElementById('location').value = proyecto.location || '';
                document.getElementById('client').value = proyecto.client || '';
                document.getElementById('completion_date').value = proyecto.completion_date || '';
                document.getElementById('area').value = proyecto.area || '';
                document.getElementById('duration').value = proyecto.duration || '';
                
                // Cargar arrays
                if (proyecto.categories) categoriesDelProyecto = [...proyecto.categories];
                if (proyecto.specifications) specificationsDelProyecto = [...proyecto.specifications];
                if (proyecto.features) featuresDelProyecto = [...proyecto.features];
                if (proyecto.gallery) galleryDelProyecto = [...proyecto.gallery];
                
                actualizarTablaCategorias();
                actualizarTablaEspecificaciones();
                actualizarTablaFeatures();
                actualizarGaleriaDesdeArray();
            } else {
                document.getElementById('tituloModal').textContent = 'Nuevo Proyecto';
                document.getElementById('proyectoId').value = '';
            }
            
            modal.show();
        }

        // Funciones para categorías
        function agregarCategoria() {
            const input = document.getElementById('categoriaInput');
            const categoria = input.value.trim();
            
            if (!categoria) return;
            
            if (!categoriesDelProyecto.includes(categoria)) {
                categoriesDelProyecto.push(categoria);
                input.value = '';
                actualizarTablaCategorias();
            } else {
                Swal.fire('Error', 'Esta categoría ya fue agregada', 'warning');
            }
        }

        function eliminarCategoria(index) {
            categoriesDelProyecto.splice(index, 1);
            actualizarTablaCategorias();
        }

        function actualizarTablaCategorias() {
            const container = document.getElementById('categoriasLista');
            container.innerHTML = '';
            
            if (categoriesDelProyecto.length === 0) {
                container.innerHTML = '<div class="text-muted">No hay categorías agregadas</div>';
                return;
            }
            
            categoriesDelProyecto.forEach((categoria, index) => {
                const div = document.createElement('div');
                div.className = 'badge bg-secondary me-2 mb-2 d-inline-flex align-items-center';
                div.innerHTML = `
                    ${categoria}
                    <button type="button" class="btn-close btn-close-white ms-2" onclick="eliminarCategoria(${index})" style="font-size: 0.7em;"></button>
                `;
                container.appendChild(div);
            });
        }

        // Funciones para especificaciones
        function agregarEspecificacion() {
            const label = document.getElementById('especLabel').value.trim();
            const value = document.getElementById('especValue').value.trim();
            
            if (!label || !value) return;
            
            specificationsDelProyecto.push({ label, value });
            document.getElementById('especLabel').value = '';
            document.getElementById('especValue').value = '';
            actualizarTablaEspecificaciones();
        }

        function eliminarEspecificacion(index) {
            specificationsDelProyecto.splice(index, 1);
            actualizarTablaEspecificaciones();
        }

        function actualizarTablaEspecificaciones() {
            const container = document.getElementById('especificacionesTabla');
            
            if (specificationsDelProyecto.length === 0) {
                container.innerHTML = '<div class="text-muted">No hay especificaciones agregadas</div>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Etiqueta</th>
                                <th>Valor</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            specificationsDelProyecto.forEach((spec, index) => {
                html += `
                    <tr>
                        <td><strong>${spec.label}</strong></td>
                        <td>${spec.value}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger" onclick="eliminarEspecificacion(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }

        // Funciones para características
        function agregarFeature() {
            const title = document.getElementById('featureTitle').value.trim();
            const description = document.getElementById('featureDescription').value.trim();
            
            if (!title || !description) return;
            
            featuresDelProyecto.push({ title, description });
            document.getElementById('featureTitle').value = '';
            document.getElementById('featureDescription').value = '';
            actualizarTablaFeatures();
        }

        function eliminarFeature(index) {
            featuresDelProyecto.splice(index, 1);
            actualizarTablaFeatures();
        }

        function actualizarTablaFeatures() {
            const container = document.getElementById('featuresTabla');
            
            if (featuresDelProyecto.length === 0) {
                container.innerHTML = '<div class="text-muted">No hay características agregadas</div>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            featuresDelProyecto.forEach((feature, index) => {
                html += `
                    <tr>
                        <td><strong>${feature.title}</strong></td>
                        <td>${feature.description}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger" onclick="eliminarFeature(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }

        // =====================================================
        // FUNCIONES PARA GALERÍA DE IMÁGENES (nuevo sistema)
        // =====================================================
        
        function agregarImagenGaleria(url = '', alt = '', esPrincipal = false) {
            const container = document.getElementById('galeriaContainer');
            
            // Remover estado vacío si existe
            const emptyState = container.querySelector('.gallery-empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            const cardHtml = crearCardImagenGaleria(url, alt, esPrincipal);
            
            // Insertar al principio del contenedor
            container.insertAdjacentHTML('afterbegin', cardHtml);
            
            // Adjuntar eventos a la nueva tarjeta (ahora es el primer hijo)
            const newCard = container.firstElementChild;
            adjuntarEventosImagenGaleria(newCard);
            
            // Si no hay ninguna marcada como principal, marcar la primera
            actualizarRadiosPrincipal();
        }
        
        function crearCardImagenGaleria(url = '', alt = '', esPrincipal = false) {
            const previewHtml = url ? 
                `<img src="${url}" alt="${alt}" class="gallery-thumb">` : 
                '<span class="text-muted small">Sin imagen</span>';
            
            return `
                <div class="gallery-image-card d-flex align-items-center gap-2 p-2 mb-2 border rounded">
                    <div class="gallery-image-preview-mini">${previewHtml}</div>
                    <input type="file" accept="image/*" class="form-control form-control-sm gallery-image-file" style="max-width: 200px;">
                    <input type="hidden" class="gallery-image-url" value="${url}">
                    <input type="text" class="form-control form-control-sm gallery-image-alt" placeholder="Alt text" value="${alt}" style="max-width: 180px;">
                    <div class="form-check">
                        <input class="form-check-input gallery-image-principal" type="radio" name="imagen_principal" ${esPrincipal ? 'checked' : ''}>
                        <label class="form-check-label small">Principal</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarImagenGaleria(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        }
        
        function adjuntarEventosImagenGaleria(card) {
            const fileInput = card.querySelector('.gallery-image-file');
            if (fileInput && !fileInput.dataset.bound) {
                fileInput.addEventListener('change', async (e) => {
                    await subirImagenGaleriaCard(e.currentTarget);
                });
                fileInput.dataset.bound = 'true';
            }
        }
        
        async function subirImagenGaleriaCard(input) {
            if (!input.files || !input.files[0]) return;
            
            const card = input.closest('.gallery-image-card');
            const previewContainer = card.querySelector('.gallery-image-preview-mini');
            const hiddenUrl = card.querySelector('.gallery-image-url');
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('imagen', file);
            
            previewContainer.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Subiendo...</span></div>';
            
            try {
                const response = await fetch('./api/upload_imagen.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Error al subir la imagen');
                }
                
                hiddenUrl.value = data.url;
                previewContainer.innerHTML = `<img src="${data.url}" alt="Preview" class="gallery-thumb">`;
                input.value = '';
                
                actualizarRadiosPrincipal();
            } catch (error) {
                console.error('Error subiendo imagen:', error);
                CRUDAlerts.showError('Error', error.message || 'Error de conexión al subir la imagen');
                hiddenUrl.value = '';
                previewContainer.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle"></i></span>';
            }
        }
        
        function eliminarImagenGaleria(button) {
            const card = button.closest('.gallery-image-card');
            if (card) {
                card.remove();
            }
            
            actualizarRadiosPrincipal();
            mostrarEstadoVacioGaleria();
        }
        
        function actualizarRadiosPrincipal() {
            const cards = document.querySelectorAll('#galeriaContainer .gallery-image-card');
            const checkedRadio = document.querySelector('input[name="imagen_principal"]:checked');
            
            if (!checkedRadio && cards.length > 0) {
                const firstRadio = cards[0].querySelector('.gallery-image-principal');
                if (firstRadio) {
                    firstRadio.checked = true;
                }
            }
        }
        
        function mostrarEstadoVacioGaleria() {
            const container = document.getElementById('galeriaContainer');
            const cards = container.querySelectorAll('.gallery-image-card');
            
            if (cards.length === 0) {
                container.innerHTML = `
                    <div class="gallery-empty-state">
                        <i class="bi bi-images text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-0">No hay imágenes en la galería</p>
                        <small class="text-muted">Click en "Agregar Imagen" para comenzar</small>
                    </div>
                `;
            }
        }
        
        function actualizarGaleriaDesdeArray() {
            const container = document.getElementById('galeriaContainer');
            container.innerHTML = '';
            
            if (galleryDelProyecto.length === 0) {
                mostrarEstadoVacioGaleria();
                return;
            }
            
            // Encontrar cuál es la imagen principal (convertir a número para comparación segura)
            let indicePrincipal = galleryDelProyecto.findIndex(img => {
                const valor = parseInt(img.es_principal, 10);
                return valor === 1;
            });
            
            // Si no hay ninguna marcada, la primera será la principal
            if (indicePrincipal === -1) {
                indicePrincipal = 0;
            }
            
            // Agregar todas las imágenes
            galleryDelProyecto.forEach((img, index) => {
                const esPrincipal = (index === indicePrincipal);
                agregarImagenGaleriaSinActualizar(img.url, img.alt || '', esPrincipal);
            });
        }
        
        // Versión sin actualizar radios (para carga inicial)
        function agregarImagenGaleriaSinActualizar(url = '', alt = '', esPrincipal = false) {
            const container = document.getElementById('galeriaContainer');
            
            // Remover estado vacío si existe
            const emptyState = container.querySelector('.gallery-empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            const cardHtml = crearCardImagenGaleria(url, alt, esPrincipal);
            
            container.insertAdjacentHTML('beforeend', cardHtml);
            
            // Adjuntar eventos a la nueva tarjeta
            const newCard = container.lastElementChild;
            adjuntarEventosImagenGaleria(newCard);
        }
        
        function obtenerImagenesGaleria() {
            const imagenes = [];
            const cards = document.querySelectorAll('#galeriaContainer .gallery-image-card');
            
            cards.forEach((card, index) => {
                const urlField = card.querySelector('.gallery-image-url');
                const url = urlField ? urlField.value.trim() : '';
                
                if (!url) return;
                
                const altField = card.querySelector('.gallery-image-alt');
                const alt = altField ? altField.value.trim() : '';
                
                // Verificar si este card tiene el radio marcado
                const radioEnCard = card.querySelector('.gallery-image-principal');
                const isPrincipal = radioEnCard && radioEnCard.checked;
                
                imagenes.push({
                    url,
                    alt,
                    es_principal: isPrincipal ? 1 : 0
                });
            });
            
            // Asegurar que haya al menos una principal
            if (imagenes.length > 0 && !imagenes.some(img => img.es_principal === 1)) {
                imagenes[0].es_principal = 1;
            }
            
            return imagenes;
        }
        
        function obtenerHeroImage() {
            const imagenes = obtenerImagenesGaleria();
            const principal = imagenes.find(img => img.es_principal === 1);
            return principal ? principal.url : (imagenes.length > 0 ? imagenes[0].url : '');
        }

        async function editarProyecto(id) {
            try {
                const response = await fetch(`./api/proyectos.php?id=${id}`);
                const proyecto = await response.json();
                
                if (proyecto.error) {
                    CRUDAlerts.error('Error', proyecto.error);
                    return;
                }
                
                mostrarFormulario(proyecto);
            } catch (error) {
                console.error('Error al editar proyecto:', error);
                CRUDAlerts.connectionError();
            }
        }

        async function verProyecto(id) {
            try {
                const response = await fetch(`./api/proyectos.php?id=${id}`);
                const proyecto = await response.json();
                
                const categoriasList = proyecto.categories && proyecto.categories.length > 0 ? 
                    proyecto.categories.map(cat => `<span class="badge bg-secondary me-1">${cat}</span>`).join('') : 
                    'Sin categorías';
                
                Swal.fire({
                    title: 'Detalle del Proyecto',
                    html: `
                        <div class="text-start">
                            <p><strong>ID:</strong> ${proyecto.id}</p>
                            <p><strong>Título:</strong> ${proyecto.title}</p>
                            ${proyecto.subtitle ? `<p><strong>Subtítulo:</strong> ${proyecto.subtitle}</p>` : ''}
                            ${proyecto.badge ? `<p><strong>Badge:</strong> <span class="badge badge-custom">${proyecto.badge}</span></p>` : ''}
                            ${proyecto.client ? `<p><strong>Cliente:</strong> ${proyecto.client}</p>` : ''}
                            ${proyecto.location ? `<p><strong>Ubicación:</strong> ${proyecto.location}</p>` : ''}
                            ${proyecto.completion_date ? `<p><strong>Fecha:</strong> ${proyecto.completion_date}</p>` : ''}
                            ${proyecto.area ? `<p><strong>Área:</strong> ${proyecto.area}</p>` : ''}
                            ${proyecto.duration ? `<p><strong>Duración:</strong> ${proyecto.duration}</p>` : ''}
                            <p><strong>Categorías:</strong> ${categoriasList}</p>
                            ${proyecto.description ? `<p><strong>Descripción:</strong><br>${proyecto.description}</p>` : ''}
                        </div>
                    `,
                    icon: 'info',
                    width: '600px'
                });
            } catch (error) {
                CRUDAlerts.connectionError();
            }
        }

        async function eliminarProyecto(id) {
            const result = await CRUDAlerts.confirmDelete('proyecto');

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./api/proyectos.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        CRUDAlerts.deleted('Proyecto');
                        cargarProyectos();
                    } else {
                        CRUDAlerts.deleteError(data.error || 'Error desconocido');
                    }
                } catch (error) {
                    CRUDAlerts.connectionError();
                }
            }
        }

        document.getElementById('formProyecto').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Obtener imágenes de galería con el nuevo sistema
            const imagenesGaleria = obtenerImagenesGaleria();
            const heroImage = obtenerHeroImage();
            
            const datos = {
                title: document.getElementById('title').value.trim(),
                slug: document.getElementById('slug').value.trim(),
                subtitle: document.getElementById('subtitle').value.trim(),
                badge: document.getElementById('badge').value.trim(),
                hero_image: heroImage,
                description: document.getElementById('description').value.trim(),
                location: document.getElementById('location').value.trim(),
                client: document.getElementById('client').value.trim(),
                completion_date: document.getElementById('completion_date').value.trim(),
                area: document.getElementById('area').value.trim(),
                duration: document.getElementById('duration').value.trim(),
                categories: categoriesDelProyecto,
                specifications: specificationsDelProyecto,
                features: featuresDelProyecto,
                gallery: imagenesGaleria
            };
            
            if (!datos.title || !datos.slug) {
                Swal.fire('Error', 'El título y slug son obligatorios', 'error');
                return;
            }
            
            const id = document.getElementById('proyectoId').value;
            const url = id ? `./api/proyectos.php?id=${id}` : './api/proyectos.php';
            const method = id ? 'PUT' : 'POST';
            
            try {
                console.log('Enviando datos de proyecto:', datos);
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                });
                
                console.log('Response status:', response.status);
                const text = await response.text();
                console.log('Response text:', text);
                
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.log('Raw response:', text);
                    throw new Error('Respuesta del servidor no válida: ' + text);
                }
                
                if (data.success) {
                    if (id) {
                        CRUDAlerts.updated('Proyecto');
                    } else {
                        CRUDAlerts.created('Proyecto');
                    }
                    modal.hide();
                    cargarProyectos();
                } else {
                    console.error('Error en la respuesta:', data);
                    if (id) {
                        CRUDAlerts.updateError(data.error || 'Error desconocido');
                    } else {
                        CRUDAlerts.createError(data.error || 'Error desconocido');
                    }
                }
            } catch (error) {
                console.error('Error en fetch:', error);
                CRUDAlerts.connectionError();
            }
        });
    </script>
</body>
</html>