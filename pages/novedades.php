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
    <title>Novedades - Stadium</title>
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
    <!-- Editor de texto enriquecido -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
    </script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
        
        /* Estilos para la tabla de novedades */
        .table td {
            vertical-align: middle;
        }
        
        .tipo-badge {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .etiqueta-badge {
            font-size: 0.7rem;
            margin: 2px;
        }
        
        .editor-container {
            height: 300px;
        }
        
        /* Adaptación del editor Quill para tema oscuro */
        [data-bs-theme="dark"] .ql-editor {
            background-color: var(--bs-dark) !important;
            color: var(--bs-light) !important;
            border-color: var(--bs-border-color) !important;
        }
        
        [data-bs-theme="dark"] .ql-toolbar {
            background-color: #343a40 !important;
            border-color: var(--bs-border-color) !important;
        }
        
        [data-bs-theme="dark"] .ql-toolbar .ql-stroke {
            stroke: var(--bs-light) !important;
        }
        
        [data-bs-theme="dark"] .ql-toolbar .ql-fill {
            fill: var(--bs-light) !important;
        }
        
        [data-bs-theme="dark"] .ql-toolbar .ql-picker-label {
            color: var(--bs-light) !important;
        }
        
        [data-bs-theme="dark"] .ql-toolbar button:hover {
            background-color: rgba(255,255,255,0.1) !important;
        }
        
        [data-bs-theme="dark"] .ql-container {
            border-color: var(--bs-border-color) !important;
        }
        
        .tag-input-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 8px;
            border: 1px solid var(--bs-border-color);
            border-radius: 4px;
            min-height: 42px;
            background-color: var(--bs-body-bg);
        }
        
        /* Adaptación para tema oscuro */
        [data-bs-theme="dark"] .tag-input-container {
            background-color: var(--bs-dark);
            border-color: var(--bs-border-color);
        }
        
        [data-bs-theme="dark"] .tag-input-container input {
            background-color: transparent !important;
            color: var(--bs-light) !important;
        }
        
        .tag-item {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            background: var(--bs-primary);
            color: white;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .tag-item .remove-tag {
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .imagenes-galeria {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .imagen-galeria-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        
        .imagen-galeria-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .imagen-galeria-item .remove-imagen {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--bs-danger);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            border: 2px solid var(--bs-body-bg);
        }
        
        /* Mejoras para tema oscuro */
        [data-bs-theme="dark"] .modal-content {
            background-color: var(--bs-dark);
            border-color: var(--bs-border-color);
        }
        
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: var(--bs-dark);
            border-color: var(--bs-border-color);
            color: var(--bs-light);
        }
        
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background-color: var(--bs-dark);
            border-color: var(--bs-primary);
            color: var(--bs-light);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        /* Mejor visibilidad de iconos en tema oscuro */
        [data-bs-theme="dark"] .bi {
            color: #ffffff !important;
            stroke: #ffffff !important;
            fill: #ffffff !important;
            opacity: 0.95;
        }

        /* Forzar iconos dentro de botones outline a blanco en modo oscuro */
        [data-bs-theme="dark"] .btn-outline-primary .bi,
        [data-bs-theme="dark"] .btn-outline-secondary .bi,
        [data-bs-theme="dark"] .btn-outline-danger .bi,
        [data-bs-theme="dark"] .btn-outline-info .bi,
        [data-bs-theme="dark"] .btn-outline-success .bi,
        [data-bs-theme="dark"] .btn-outline-warning .bi {
            color: #ffffff !important;
            stroke: #ffffff !important;
            fill: #ffffff !important;
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
                <h5 class="mb-0 d-inline">Novedades</h5>
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
                <h2>Gestión de Novedades</h2>
                <button class="btn btn-primary" onclick="openNovedadModal()">
                    <i class="bi bi-plus"></i> Nueva Novedad
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="filtroTipo" onchange="loadNovedades()">
                                <option value="">Todos</option>
                                <option value="evento">Evento</option>
                                <option value="noticia">Noticia</option>
                                <option value="logro">Logro</option>
                                <option value="promocion">Promoción</option>
                                <option value="proyecto">Proyecto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filtroActivo" onchange="loadNovedades()">
                                <option value="">Todos</option>
                                <option value="1" selected>Activas</option>
                                <option value="0">Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Destacado</label>
                            <select class="form-select" id="filtroDestacado" onchange="loadNovedades()">
                                <option value="">Todos</option>
                                <option value="1">Destacados</option>
                                <option value="0">No destacados</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordenar por</label>
                            <select class="form-select" id="filtroOrden" onchange="loadNovedades()">
                                <option value="fecha DESC">Fecha (más reciente)</option>
                                <option value="fecha ASC">Fecha (más antigua)</option>
                                <option value="titulo ASC">Título (A-Z)</option>
                                <option value="titulo DESC">Título (Z-A)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Novedades -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Imagen</th>
                                <th>Título</th>
                                <th style="width: 100px;">Tipo</th>
                                <th style="width: 120px;">Fecha</th>
                                <th style="width: 150px;">Ubicación</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="novedadesContainer">
                            <!-- Las filas se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Novedad -->
    <div class="modal fade" id="novedadModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Novedad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="novedadForm">
                        <input type="hidden" id="novedadId" name="id">
                        
                        <!-- Información básica -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="evento">Evento</option>
                                    <option value="noticia">Noticia</option>
                                    <option value="logro">Logro</option>
                                    <option value="promocion">Promoción</option>
                                    <option value="proyecto">Proyecto</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo">
                                    <label class="form-check-label" for="activo">Activo</label>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                    <label class="form-check-label" for="destacado">
                                        <strong><i class="bi bi-star-fill text-warning"></i> Marcar como Destacada</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="subtitulo" class="form-label">Subtítulo</label>
                                <input type="text" class="form-control" id="subtitulo" name="subtitulo" maxlength="255">
                            </div>
                        </div>

                        <!-- Preview dinámico de la novedad -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div id="novedadPreview" class="card mb-3" style="max-width: 540px;">
                                    <div class="row g-0">
                                        <div class="col-md-4 d-flex align-items-center justify-content-center bg-light" id="previewImagenContainer">
                                            <img id="previewImagen" src="https://via.placeholder.com/120x80?text=Sin+Imagen" class="img-fluid rounded-start" alt="Preview">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body py-2">
                                                <h6 class="card-title mb-1" id="previewTitulo">Título de ejemplo</h6>
                                                <p class="card-text small mb-1 text-muted" id="previewSubtitulo">Subtítulo</p>
                                                <p class="card-text small text-muted mb-1"><span id="previewTipo">Tipo</span> · <span id="previewFecha">Fecha</span></p>
                                                <div id="previewBadges"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Etiquetas (movidas arriba) -->
                        <div class="mb-3">
                            <h6 class="text-primary mb-2"><i class="bi bi-tags"></i> Etiquetas</h6>
                            <div class="tag-input-container" id="tagContainer" onclick="focusTagInput()">
                                <input type="text" id="tagInput" placeholder="Escriba y presione Enter" 
                                    style="border: none; outline: none; flex: 1; min-width: 150px;" 
                                    onkeypress="handleTagInput(event)">
                            </div>
                            <small class="text-muted">Presione Enter después de escribir cada etiqueta</small>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Corta * <small class="text-muted">(para cards)</small></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2" required maxlength="500"></textarea>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Máximo 500 caracteres</small>
                                <small class="text-muted"><span id="descCounter">0</span>/500</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="autor" class="form-label">Autor</label>
                                <input type="text" class="form-control" id="autor" name="autor" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="ubicacion" class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="ubicacion" name="ubicacion" maxlength="255">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenido HTML <small class="text-muted">(para detalle completo)</small></label>
                            <div id="contenidoEditor" class="editor-container bg-white"></div>
                            <input type="hidden" id="contenido" name="contenido">
                        </div>

                        <hr>
                        
                        <!-- Imagen Principal -->
                        <h6 class="text-primary mb-3"><i class="bi bi-image"></i> Imagen Principal</h6>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="imagenFile" accept="image/*">
                            <input type="hidden" id="imagen" name="imagen">
                            <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                            <div id="imagenPreview" class="mt-2"></div>
                        </div>

                        <hr>
                        
                        <!-- Galería de Imágenes -->
                        <h6 class="text-primary mb-3"><i class="bi bi-images"></i> Galería de Imágenes</h6>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="galeriaFiles" accept="image/*" multiple>
                            <small class="text-muted">Puede seleccionar múltiples imágenes</small>
                            <div id="galeriaPreview" class="imagenes-galeria"></div>
                        </div>

                        <hr>

                        <!-- Slug oculto: se genera automáticamente en el servidor y no se muestra en la UI -->
                        <input type="hidden" id="slug" name="slug">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveNovedad()">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let novedadModal;
        let currentEditId = null;
        let quillEditor;
        let etiquetasArray = [];
        let galeriaImagenes = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
            novedadModal = new bootstrap.Modal(document.getElementById('novedadModal'));
            
            // Inicializar editor Quill
            quillEditor = new Quill('#contenidoEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['link', 'image'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
            
            loadNovedades();
        });

        // --- Dinámica del formulario ---
        function slugify(text) {
            return text.toString().toLowerCase()
                .normalize('NFD').replace(/\p{Diacritic}/gu, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }

        function updateSlugFromTitle() {
            const title = document.getElementById('titulo').value || '';
            document.getElementById('slug').value = slugify(title);
        }

        function updateDescripcionCounter() {
            const textarea = document.getElementById('descripcion');
            const counter = document.getElementById('descCounter');
            counter.textContent = textarea.value.length;
        }

        function updateActivoLabel() {
            // Asegura que la etiqueta muestre el estado actual (puede usarse si se desea)
            const activo = document.getElementById('activo');
            const label = document.querySelector('label[for="activo"]');
            if (activo && label) {
                label.textContent = activo.checked ? 'Activo' : 'Inactivo';
            }
        }

        function updatePreview() {
            const titulo = document.getElementById('titulo').value || 'Título de ejemplo';
            const subtitulo = document.getElementById('subtitulo').value || 'Subtítulo';
            const tipo = document.getElementById('tipo').value || 'Tipo';
            const fecha = document.getElementById('fecha').value ? formatDate(document.getElementById('fecha').value) : 'Sin fecha';
            const destacado = document.getElementById('destacado').checked;

            document.getElementById('previewTitulo').textContent = titulo;
            document.getElementById('previewSubtitulo').textContent = subtitulo;
            document.getElementById('previewTipo').textContent = tipo;
            document.getElementById('previewFecha').textContent = fecha;

            const badges = document.getElementById('previewBadges');
            badges.innerHTML = '';
            if (destacado) {
                badges.innerHTML += '<span class="badge bg-warning text-dark me-1"><i class="bi bi-star-fill"></i> Destacada</span>';
            }
            if (document.getElementById('activo').checked) {
                badges.innerHTML += '<span class="badge bg-success">Activa</span>';
            } else {
                badges.innerHTML += '<span class="badge bg-secondary">Inactiva</span>';
            }
        }

        // Escuchar inputs para actualizar dinámicamente
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'titulo') {
                updateSlugFromTitle();
                updatePreview();
            }
            if (e.target && e.target.id === 'subtitulo') updatePreview();
            if (e.target && e.target.id === 'descripcion') updateDescripcionCounter();
        });

        document.addEventListener('change', function(e) {
            if (!e.target) return;
            if (e.target.id === 'tipo' || e.target.id === 'fecha' || e.target.id === 'destacado' || e.target.id === 'activo') {
                updatePreview();
                updateActivoLabel();
            }
            if (e.target.id === 'imagenFile') {
                // Mostrar preview local antes de subir
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const img = document.getElementById('previewImagen');
                        img.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('previewImagen').src = 'https://via.placeholder.com/120x80?text=Sin+Imagen';
                }
            }
        });

        // Al abrir modal, inicializar contadores y preview
        function initFormState() {
            updateDescripcionCounter();
            updateSlugFromTitle();
            updatePreview();
            updateActivoLabel();
            const hiddenImg = document.getElementById('imagen').value;
            const pImg = document.getElementById('previewImagen');
            if (pImg) pImg.src = hiddenImg ? `./uploads/novedades/${hiddenImg}` : 'https://via.placeholder.com/120x80?text=Sin+Imagen';
        }

        // Llamar init cuando se abre el modal (sobre-escribe parte de openNovedadModal y editNovedad ya existentes)

        // Cargar novedades
        async function loadNovedades() {
            try {
                let url = './api/novedades.php?';
                
                const tipo = document.getElementById('filtroTipo').value;
                const activo = document.getElementById('filtroActivo').value;
                const destacado = document.getElementById('filtroDestacado').value;
                const orden = document.getElementById('filtroOrden').value;
                
                if (tipo) url += `tipo=${tipo}&`;
                if (activo !== '') url += `activo=${activo}&`;
                if (destacado !== '') url += `destacado=${destacado}&`;
                if (orden) url += `order=${encodeURIComponent(orden)}&`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    renderNovedades(data.novedades);
                } else {
                    showError('Error al cargar novedades: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión al cargar novedades');
            }
        }

        // Renderizar novedades
        function renderNovedades(novedades) {
            const container = document.getElementById('novedadesContainer');
            
            if (novedades.length === 0) {
                container.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No se encontraron novedades con los filtros aplicados.
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            container.innerHTML = novedades.map(novedad => {
                const tipoBadgeClass = {
                    'evento': 'bg-primary',
                    'noticia': 'bg-info',
                    'logro': 'bg-success',
                    'promocion': 'bg-warning text-dark',
                    'proyecto': 'bg-secondary'
                }[novedad.tipo] || 'bg-secondary';
                
                const imagenSrc = novedad.imagen 
                    ? `./uploads/novedades/${novedad.imagen}` 
                    : 'https://via.placeholder.com/60x60?text=Sin+Imagen';
                
                const etiquetas = novedad.etiquetas && novedad.etiquetas.length > 0
                    ? `<div class="mt-1">${novedad.etiquetas.map(tag => `<span class="badge bg-secondary etiqueta-badge">${tag}</span>`).join(' ')}</div>`
                    : '';
                
                const destacadoBadge = novedad.destacado == 1 
                    ? '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-star-fill"></i></span>' 
                    : '';
                
                return `
                    <tr>
                        <td>
                            <img src="${imagenSrc}" alt="${novedad.titulo}" 
                                 class="img-thumbnail" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                        </td>
                        <td>
                            <div>
                                <strong>${novedad.titulo}</strong>${destacadoBadge}
                                ${novedad.subtitulo ? `<br><small class="text-muted">${novedad.subtitulo}</small>` : ''}
                                ${etiquetas}
                            </div>
                        </td>
                        <td>
                            <span class="badge ${tipoBadgeClass} tipo-badge">${novedad.tipo}</span>
                        </td>
                        <td>
                            <small><i class="bi bi-calendar"></i> ${formatDate(novedad.fecha)}</small>
                        </td>
                        <td>
                            <small>${novedad.ubicacion ? `<i class="bi bi-geo-alt"></i> ${novedad.ubicacion}` : '-'}</small>
                        </td>
                        <td>
                            <span class="badge ${novedad.activo == 1 ? 'bg-success' : 'bg-secondary'}">
                                ${novedad.activo == 1 ? 'Activa' : 'Inactiva'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editNovedad(${novedad.id})" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteNovedad(${novedad.id})" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Formatear fecha
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString + 'T00:00:00').toLocaleDateString('es-ES', options);
        }

        // Abrir modal para nueva novedad
        function openNovedadModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nueva Novedad';
            document.getElementById('novedadForm').reset();
            document.getElementById('novedadId').value = '';
            document.getElementById('activo').checked = true;
            document.getElementById('destacado').checked = false;
            document.getElementById('imagen').value = '';
            document.getElementById('imagenFile').value = '';
            document.getElementById('imagenPreview').innerHTML = '';
            document.getElementById('galeriaFiles').value = '';
            document.getElementById('galeriaPreview').innerHTML = '';
            quillEditor.setContents([]);
            etiquetasArray = [];
            galeriaImagenes = [];
            renderTags();
            
            // Establecer fecha de hoy por defecto
            document.getElementById('fecha').valueAsDate = new Date();
            initFormState();
            novedadModal.show();
        }

        // Editar novedad
        async function editNovedad(id) {
            try {
                const response = await fetch(`./api/novedades.php?id=${id}`);
                const data = await response.json();
                
                if (!data.success) {
                    showError('Error al cargar novedad: ' + data.error);
                    return;
                }
                
                const novedad = data.novedad;
                currentEditId = id;
                
                document.getElementById('modalTitle').textContent = 'Editar Novedad';
                document.getElementById('novedadId').value = novedad.id;
                document.getElementById('tipo').value = novedad.tipo;
                document.getElementById('titulo').value = novedad.titulo;
                document.getElementById('subtitulo').value = novedad.subtitulo || '';
                document.getElementById('descripcion').value = novedad.descripcion;
                document.getElementById('fecha').value = novedad.fecha;
                document.getElementById('autor').value = novedad.autor || '';
                document.getElementById('ubicacion').value = novedad.ubicacion || '';
                document.getElementById('activo').checked = novedad.activo == 1;
                document.getElementById('destacado').checked = novedad.destacado == 1;
                document.getElementById('slug').value = novedad.slug;
                
                // Contenido HTML
                if (novedad.contenido) {
                    quillEditor.root.innerHTML = novedad.contenido;
                } else {
                    quillEditor.setContents([]);
                }
                
                // Imagen principal
                document.getElementById('imagen').value = novedad.imagen || '';
                if (novedad.imagen) {
                    document.getElementById('imagenPreview').innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="./uploads/novedades/${novedad.imagen}" alt="Imagen actual" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                    // Actualizar preview dinámico
                    const pImg = document.getElementById('previewImagen');
                    if (pImg) pImg.src = `./uploads/novedades/${novedad.imagen}`;
                } else {
                    document.getElementById('imagenPreview').innerHTML = '';
                    const pImg = document.getElementById('previewImagen');
                    if (pImg) pImg.src = 'https://via.placeholder.com/120x80?text=Sin+Imagen';
                }
                
                // Galería de imágenes
                galeriaImagenes = novedad.imagenes || [];
                renderGaleriaPreview();
                
                // Etiquetas
                etiquetasArray = novedad.etiquetas || [];
                renderTags();
                
                initFormState();
                novedadModal.show();
            } catch (error) {
                console.error('Error:', error);
                showError('Error al cargar la novedad');
            }
        }

        // Guardar novedad
        async function saveNovedad() {
            const form = document.getElementById('novedadForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            try {
                // Obtener contenido del editor
                const contenidoHTML = quillEditor.root.innerHTML;
                
                const novedadData = {
                    tipo: document.getElementById('tipo').value,
                    titulo: document.getElementById('titulo').value,
                    subtitulo: document.getElementById('subtitulo').value,
                    descripcion: document.getElementById('descripcion').value,
                    contenido: contenidoHTML,
                    imagen: document.getElementById('imagen').value,
                    imagenes: galeriaImagenes,
                    fecha: document.getElementById('fecha').value,
                    autor: document.getElementById('autor').value,
                    ubicacion: document.getElementById('ubicacion').value,
                    destacado: document.getElementById('destacado').checked ? 1 : 0,
                    etiquetas: etiquetasArray,
                    activo: document.getElementById('activo').checked ? 1 : 0
                };
                
                let url = './api/novedades.php';
                let method = 'POST';
                
                if (currentEditId) {
                    novedadData.id = currentEditId;
                    method = 'PUT';
                }
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(novedadData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(currentEditId ? 'Novedad actualizada exitosamente' : 'Novedad creada exitosamente');
                    novedadModal.hide();
                    loadNovedades();
                } else {
                    showError('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al guardar la novedad');
            }
        }

        // Eliminar novedad
        async function deleteNovedad(id) {
            const result = await Swal.fire({
                title: '¿Está seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch(`./api/novedades.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Novedad eliminada exitosamente');
                    loadNovedades();
                } else {
                    showError('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al eliminar la novedad');
            }
        }

        // Manejo de imagen principal
        document.getElementById('imagenFile').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagenPreview');
            
            if (!file) {
                preview.innerHTML = '';
                return;
            }
            
            // Validar tamaño antes de mostrar preview
            if (file.size > 5 * 1024 * 1024) {
                preview.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> El archivo es demasiado grande. Máximo 5MB</span>';
                e.target.value = '';
                return;
            }
            
            // Mostrar preview temporal
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                preview.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                        <small class="text-muted">${fileSizeMB} MB</small>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
            
            try {
                showLoading('Subiendo imagen...');
                
                const formData = new FormData();
                formData.append('imagen', file);
                
                const response = await fetch('./api/novedades.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('imagen').value = data.filename;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="./uploads/novedades/${data.filename}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                    showSuccess('Imagen subida exitosamente');
                } else {
                    showError('Error al subir imagen: ' + data.error);
                    e.target.value = '';
                    preview.innerHTML = '';
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al subir la imagen');
                e.target.value = '';
                preview.innerHTML = '';
            } finally {
                hideLoading();
            }
        });

        // Remover imagen principal
        function removeImage() {
            document.getElementById('imagen').value = '';
            document.getElementById('imagenFile').value = '';
            document.getElementById('imagenPreview').innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar cambios.</span>';
        }

        // Manejo de galería de imágenes
        document.getElementById('galeriaFiles').addEventListener('change', async function(e) {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;
            
            showLoading(`Subiendo ${files.length} imagen(es)...`);
            
            for (const file of files) {
                // Validar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    showError(`El archivo ${file.name} es demasiado grande. Máximo 5MB`);
                    continue;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('imagen', file);
                    
                    const response = await fetch('./api/novedades.php?action=upload', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        galeriaImagenes.push(data.filename);
                    } else {
                        showError('Error al subir ' + file.name + ': ' + data.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Error al subir ' + file.name);
                }
            }
            
            renderGaleriaPreview();
            e.target.value = '';
            hideLoading();
        });

        // Renderizar preview de galería
        function renderGaleriaPreview() {
            const container = document.getElementById('galeriaPreview');
            container.innerHTML = galeriaImagenes.map((filename, index) => `
                <div class="imagen-galeria-item">
                    <img src="./uploads/novedades/${filename}" alt="Imagen ${index + 1}">
                    <div class="remove-imagen" onclick="removeGaleriaImagen(${index})">×</div>
                </div>
            `).join('');
        }

        // Remover imagen de galería
        function removeGaleriaImagen(index) {
            galeriaImagenes.splice(index, 1);
            renderGaleriaPreview();
        }

        // Manejo de etiquetas
        function handleTagInput(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const input = document.getElementById('tagInput');
                const value = input.value.trim();
                
                if (value && !etiquetasArray.includes(value)) {
                    etiquetasArray.push(value);
                    renderTags();
                    input.value = '';
                }
            }
        }

        function renderTags() {
            const container = document.getElementById('tagContainer');
            const input = document.getElementById('tagInput');
            
            const tagsHTML = etiquetasArray.map(tag => `
                <span class="tag-item">
                    ${tag}
                    <span class="remove-tag" onclick="removeTag('${tag}')">×</span>
                </span>
            `).join('');
            
            container.innerHTML = tagsHTML;
            container.appendChild(input);
        }

        function removeTag(tag) {
            etiquetasArray = etiquetasArray.filter(t => t !== tag);
            renderTags();
        }

        function focusTagInput() {
            document.getElementById('tagInput').focus();
        }

        // Funciones auxiliares
        function showLoading(message) {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        function hideLoading() {
            Swal.close();
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    </script>
</body>
</html>
