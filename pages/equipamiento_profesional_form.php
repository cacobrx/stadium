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
    <title>Gestión de Equipamiento Profesional - Stadium</title>
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
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .nav-tabs .nav-link {
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            border-bottom-color: var(--bs-primary);
        }
        .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        /* Quill editor */
        .ql-editor { min-height: 140px; }

        [data-bs-theme="dark"] .ql-editor {
            background-color: var(--bs-dark) !important;
            color: var(--bs-light) !important;
            border-color: var(--bs-border-color) !important;
        }
        [data-bs-theme="dark"] .ql-toolbar {
            background-color: #343a40 !important;
            border-color: var(--bs-border-color) !important;
        }
        [data-bs-theme="dark"] .ql-toolbar .ql-stroke { stroke: var(--bs-light) !important; }
        [data-bs-theme="dark"] .ql-toolbar .ql-fill  { fill: var(--bs-light) !important; }
        [data-bs-theme="dark"] .ql-toolbar .ql-picker-label { color: var(--bs-light) !important; }
        [data-bs-theme="dark"] .ql-toolbar button:hover { background-color: rgba(255,255,255,0.1) !important; }
        [data-bs-theme="dark"] .ql-container { border-color: var(--bs-border-color) !important; }
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
                <h5 class="mb-0 d-inline">Equipamiento Profesional</h5>
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
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center align-items-start gap-3 mb-4">
                <h2 class="mb-0" id="formTitle">Nuevo Equipamiento</h2>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="index.php?page=equipamiento_profesional">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <button type="button" class="btn btn-primary" id="saveButton" onclick="saveEquipamiento()">
                        <i class="bi bi-floppy"></i> Guardar
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="equipamientoForm">
                        <input type="hidden" id="equipamientoId" name="id">
                        
                        <!-- Información General -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-2"></i>Información General
                            </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                            <select class="form-control" id="categoria" name="categoria" required>
                                                <option value="">Seleccionar categoría...</option>
                                                <!-- Las categorías se cargan dinámicamente desde tablas (CEQ) -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="deporte_id" class="form-label">Deporte</label>
                                            <select class="form-control" id="deporte_id" name="deporte_id">
                                                <option value="">Seleccionar deporte...</option>
                                                <!-- Los deportes se cargan dinámicamente -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="uso" class="form-label">Uso</label>
                                            <select class="form-control" id="uso" name="uso">
                                                <option value="">Sin especificar</option>
                                                <option value="Profesional">Profesional</option>
                                                <option value="Domestico">Dom&eacute;stico</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <div id="descripcionEditor"></div>
                                    <input type="hidden" id="descripcion" name="descripcion">
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tiempo_entrega" class="form-label">Tiempo de Entrega</label>
                                            <input type="text" class="form-control" id="tiempo_entrega" name="tiempo_entrega" placeholder="Ej: 15-20 días">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="garantia" class="form-label">Garantía</label>
                                            <input type="text" class="form-control" id="garantia" name="garantia" placeholder="Ej: 2 años">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="orden" class="form-label">Orden <small class="text-muted" id="ordenHint"></small></label>
                                            <input type="number" class="form-control" id="orden" name="orden" min="1" placeholder="Posición">
                                            <small class="text-muted">Ingresá la posición. Los registros existentes se desplazarán.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" checked>
                                            <label class="form-check-label" for="disponible">
                                                Disponible
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="instalacion" name="instalacion">
                                            <label class="form-check-label" for="instalacion">
                                                Incluye Instalación
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="financiacion" name="financiacion">
                                            <label class="form-check-label" for="financiacion">
                                                Acepta Financiación
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="esta_activo" name="esta_activo" checked>
                                            <label class="form-check-label" for="esta_activo">
                                                Activo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                            <label class="form-check-label" for="destacado">
                                                Destacado
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Especificaciones -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-list-check me-2"></i>Especificaciones Técnicas
                                </h5>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span></span>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addEspecificacion()">
                                        <i class="bi bi-plus"></i> Agregar Especificación
                                    </button>
                                </div>
                                <div id="especificaciones-container"></div>
                            </div>

                            <!-- Características -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-star me-2"></i>Características del Producto
                                </h5>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span></span>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addCaracteristica()">
                                        <i class="bi bi-plus"></i> Agregar Característica
                                    </button>
                                </div>
                                <div id="caracteristicas-container"></div>
                            </div>

                            <!-- Imágenes -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-images me-2"></i>Galería de Imágenes
                                </h5>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" onclick="addImageField()">
                                        <i class="bi bi-plus"></i> Agregar Imagen
                                    </button>
                                </div>
                                <div id="imagesList"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let currentEditId = null;
        let quillDesc;


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

        function normalizeImagePath(path) {
            if (!path) {
                return '';
            }
            let normalized = path.trim();
            if (/^https?:\/\//i.test(normalized) || normalized.startsWith('data:')) {
                return normalized;
            }
            if (normalized.startsWith(window.location.origin)) {
                normalized = normalized.substring(window.location.origin.length);
            }
            if (normalized.startsWith(appBasePath)) {
                normalized = normalized.substring(appBasePath.length);
            }
            normalized = normalized.replace(/^\//, '');
            return normalized;
        }

        document.addEventListener('DOMContentLoaded', async () => {
            loadTheme();

            // Inicializar Quill en el campo descripción
            quillDesc = new Quill('#descripcionEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['link'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            
            // Cargar categorías y deportes
            await Promise.all([loadCategorias(), loadDeportes()]);

            const params = new URLSearchParams(window.location.search);
            const equipamientoId = params.get('id');

            if (equipamientoId) {
                currentEditId = parseInt(equipamientoId, 10);
                await loadEquipamiento(currentEditId);
                document.getElementById('formTitle').textContent = 'Editar Equipamiento';
                document.getElementById('saveButton').innerHTML = '<i class="bi bi-floppy"></i> Guardar Cambios';
            } else {
                ensureDefaultEspecificacion();
                ensureDefaultCaracteristica();
                document.getElementById('formTitle').textContent = 'Nuevo Equipamiento';
                document.getElementById('saveButton').innerHTML = '<i class="bi bi-floppy"></i> Guardar';
                await fetchMaxOrden();
            }
        });
        
        async function fetchMaxOrden() {
            try {
                const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                const r = await fetch(basePath + 'api/equipamiento_profesional.php?max_orden=1');
                const d = await r.json();
                if (d.success && d.max_orden !== undefined) {
                    const next = d.max_orden + 1;
                    document.getElementById('orden').value = next;
                    document.getElementById('ordenHint').textContent = '(máx actual: ' + d.max_orden + ')';
                }
            } catch(e) { /* silencioso */ }
        }

        async function loadCategorias() {
            try {
                const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                const response = await fetch(basePath + 'api/categorias.php');
                const data = await response.json();
                
                if (data.success && data.categorias) {
                    const select = document.getElementById('categoria');
                    data.categorias.forEach(cat => {
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
                    const select = document.getElementById('deporte_id');
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

    async function loadEquipamiento(id) {
        try {
            const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
            const response = await fetch(`${basePath}api/equipamiento_profesional.php?id=${id}`);
            const data = await response.json();                if (!data.success) {
                    CRUDAlerts.loadError ? CRUDAlerts.loadError('equipamiento', data.error) : CRUDAlerts.connectionError();
                    return;
                }

                const equipamiento = data.equipamiento;
                document.getElementById('equipamientoId').value = equipamiento.id;
                document.getElementById('nombre').value = equipamiento.nombre || '';
                document.getElementById('categoria').value = equipamiento.categoria || '';
                document.getElementById('deporte_id').value = equipamiento.deporte_id || '';
                quillDesc.root.innerHTML = equipamiento.descripcion || '';
                document.getElementById('tiempo_entrega').value = equipamiento.tiempo_entrega || '';
                document.getElementById('garantia').value = equipamiento.garantia || '';
                document.getElementById('disponible').checked = equipamiento.disponible == 1;
                document.getElementById('instalacion').checked = equipamiento.instalacion == 1;
                document.getElementById('financiacion').checked = equipamiento.financiacion == 1;
                document.getElementById('esta_activo').checked = equipamiento.esta_activo == 1;
                document.getElementById('destacado').checked = equipamiento.destacado == 1;
                document.getElementById('orden').value = equipamiento.orden || '';
                document.getElementById('ordenHint').textContent = '(actual: ' + (equipamiento.orden || '-') + ')';
                document.getElementById('uso').value = equipamiento.uso || '';

                // Cargar especificaciones
                const especificacionesContainer = document.getElementById('especificaciones-container');
                especificacionesContainer.innerHTML = '';
                if (equipamiento.especificaciones && equipamiento.especificaciones.length > 0) {
                    equipamiento.especificaciones.forEach(esp => {
                        addEspecificacion(esp.etiqueta || '', esp.valor || '');
                    });
                }

                // Cargar características
                const caracteristicasContainer = document.getElementById('caracteristicas-container');
                caracteristicasContainer.innerHTML = '';
                if (equipamiento.caracteristicas && equipamiento.caracteristicas.length > 0) {
                    equipamiento.caracteristicas.forEach(car => {
                        addCaracteristica(car.caracteristica || '');
                    });
                }

                // Cargar imágenes
                const imagesList = document.getElementById('imagesList');
                imagesList.innerHTML = '';
                if (equipamiento.imagenes && equipamiento.imagenes.length > 0) {
                    equipamiento.imagenes.forEach((imagen, index) => {
                        addImageField(imagen.url, imagen.alt_text, imagen.es_principal ? true : index === 0);
                    });
                }

                ensureDefaultEspecificacion();
                ensureDefaultCaracteristica();
            } catch (error) {
                console.error('Error cargando equipamiento:', error);
                CRUDAlerts.connectionError();
            }
        }

        function addEspecificacion(etiqueta = '', valor = '') {
            const container = document.getElementById('especificaciones-container');
            container.insertAdjacentHTML('beforeend', `
                <div class="row mb-2 especificacion-row">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Etiqueta (ej: Altura)" name="especificacion_etiqueta[]" value="${etiqueta}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Valor (ej: 3 metros)" name="especificacion_valor[]" value="${valor}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeEspecificacion(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        }

        function removeEspecificacion(button) {
            const row = button.closest('.especificacion-row');
            if (row) {
                row.remove();
            }
            ensureDefaultEspecificacion();
        }

        function ensureDefaultEspecificacion() {
            const container = document.getElementById('especificaciones-container');
            if (container.children.length === 0) {
                addEspecificacion();
            }
        }

        function addCaracteristica(caracteristica = '') {
            const container = document.getElementById('caracteristicas-container');
            container.insertAdjacentHTML('beforeend', `
                <div class="row mb-2 caracteristica-row">
                    <div class="col-md-10">
                        <input type="text" class="form-control" placeholder="Característica del producto" name="caracteristica[]" value="${caracteristica}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeCaracteristica(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        }

        function removeCaracteristica(button) {
            const row = button.closest('.caracteristica-row');
            if (row) {
                row.remove();
            }
            ensureDefaultCaracteristica();
        }

        function ensureDefaultCaracteristica() {
            const container = document.getElementById('caracteristicas-container');
            if (container.children.length === 0) {
                addCaracteristica();
            }
        }

        function addImageField(url = '', altText = '', esPrincipal = false) {
            const imagesList = document.getElementById('imagesList');

            const normalizedUrl = normalizeImagePath(url);
            const previewHtml = normalizedUrl ? `<img src="${resolveImageUrl(normalizedUrl)}" alt="${altText}" class="image-preview" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"><small class="text-danger" style="display: none;">Error cargando imagen</small>` : '<small class="text-muted">Vista previa de la imagen</small>';

            const imageDiv = document.createElement('div');
            imageDiv.className = 'card mb-3 product-image-card';
            imageDiv.innerHTML = `
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-5">
                            <label class="form-label">Archivo de imagen</label>
                            <input type="file" accept="image/*" class="form-control image-file">
                            <input type="hidden" class="image-url" value="${normalizedUrl}">
                            <small class="text-muted d-block mt-1 image-url-display">${normalizedUrl ? normalizedUrl : 'Sin archivo subido'}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Texto alternativo</label>
                            <input type="text" class="form-control image-alt" value="${altText}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Principal</label>
                            <div class="form-check mt-1">
                                <input class="form-check-input imagen-principal" type="radio" name="imagen_principal" ${esPrincipal ? 'checked' : ''}>
                                <label class="form-check-label">Sí</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageField(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="image-preview-container text-center p-2 border rounded" style="min-height: 100px; background: #f8f9fa;">
                                ${previewHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            imagesList.appendChild(imageDiv);
            attachImageCardEvents(imageDiv);
            refreshImagePrincipalRadios();
        }

        function removeImageField(button) {
            const card = button.closest('.card');
            if (card) {
                card.remove();
            }
            refreshImagePrincipalRadios();
        }

        function attachImageCardEvents(card) {
            const fileInput = card.querySelector('.image-file');
            if (fileInput && !fileInput.dataset.bound) {
                fileInput.addEventListener('change', async (event) => {
                    await handleImageUpload(event.currentTarget);
                });
                fileInput.dataset.bound = 'true';
            }
        }

        async function handleImageUpload(input) {
            if (!input.files || !input.files[0]) {
                return;
            }

            const card = input.closest('.card');
            const previewContainer = card.querySelector('.image-preview-container');
            const urlDisplay = card.querySelector('.image-url-display');
            const hiddenUrl = card.querySelector('.image-url');

            const file = input.files[0];
            const formData = new FormData();
            formData.append('imagen', file);

        previewContainer.innerHTML = '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="visually-hidden">Subiendo...</span></div>';
        urlDisplay.textContent = 'Subiendo imagen...';

        try {
            const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
            const response = await fetch(basePath + 'api/upload_imagen.php', {
                method: 'POST',
                body: formData
            });                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Error desconocido al subir la imagen');
                }

                const normalizedUrl = normalizeImagePath(data.url);
                hiddenUrl.value = normalizedUrl;
                urlDisplay.textContent = normalizedUrl;
                updateImagePreview(previewContainer, normalizedUrl);
                input.value = '';
                refreshImagePrincipalRadios();
            } catch (error) {
                console.error('Error subiendo imagen:', error);
                showErrorAlert('Error al subir imagen', error.message);
                hiddenUrl.value = '';
                urlDisplay.textContent = 'Sin archivo subido';
                previewContainer.innerHTML = '<small class="text-danger">No se pudo subir la imagen</small>';
            }
        }

        function updateImagePreview(container, url) {
            if (url) {
                container.innerHTML = `
                    <img src="${resolveImageUrl(url)}" alt="Vista previa" class="image-preview" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <small class="text-danger" style="display: none;">Error cargando imagen</small>
                `;
            } else {
                container.innerHTML = '<small class="text-muted">Vista previa de la imagen</small>';
            }
        }

        function refreshImagePrincipalRadios() {
            const cards = document.querySelectorAll('#imagesList .card');
            const checked = document.querySelector('input[name="imagen_principal"]:checked');

            if (!checked && cards.length > 0) {
                const firstRadio = cards[0].querySelector('.imagen-principal');
                if (firstRadio) {
                    firstRadio.checked = true;
                }
            }
        }

        function showErrorAlert(title, message) {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                confirmButtonText: 'Aceptar'
            });
        }

        async function saveEquipamiento() {
            const form = document.getElementById('equipamientoForm');
            const formData = new FormData(form);
            const equipamientoData = {};

            for (const [key, value] of formData.entries()) {
                if (key === 'id' && !currentEditId) {
                    continue;
                }
                equipamientoData[key] = value;
            }

            // Sincronizar contenido del editor enriquecido
            equipamientoData.descripcion = quillDesc.root.innerHTML.trim() === '<p><br></p>' ? '' : quillDesc.root.innerHTML;

            // Checkboxes
            equipamientoData.disponible = document.getElementById('disponible').checked ? 1 : 0;
            equipamientoData.instalacion = document.getElementById('instalacion').checked ? 1 : 0;
            equipamientoData.financiacion = document.getElementById('financiacion').checked ? 1 : 0;
            equipamientoData.esta_activo = document.getElementById('esta_activo').checked ? 1 : 0;
            equipamientoData.destacado = document.getElementById('destacado').checked ? 1 : 0;

            // Orden
            const ordenVal = document.getElementById('orden').value;
            if (ordenVal !== '' && !isNaN(parseInt(ordenVal))) {
                equipamientoData.orden = parseInt(ordenVal);
            }

            // Especificaciones
            const especificaciones = [];
            const especificacionEtiquetas = document.querySelectorAll('input[name="especificacion_etiqueta[]"]');
            const especificacionValores = document.querySelectorAll('input[name="especificacion_valor[]"]');
            especificacionEtiquetas.forEach((etiquetaField, index) => {
                const valorField = especificacionValores[index];
                if (etiquetaField.value.trim() && valorField.value.trim()) {
                    especificaciones.push({
                        etiqueta: etiquetaField.value.trim(),
                        valor: valorField.value.trim()
                    });
                }
            });
            equipamientoData.especificaciones = especificaciones;

            // Características
            const caracteristicas = [];
            const caracteristicaFields = document.querySelectorAll('input[name="caracteristica[]"]');
            caracteristicaFields.forEach(field => {
                if (field.value.trim()) {
                    caracteristicas.push(field.value.trim());
                }
            });
            equipamientoData.caracteristicas = caracteristicas;

            // Imágenes
            const imagenes = [];
            const principalRadio = document.querySelector('input[name="imagen_principal"]:checked');
            document.querySelectorAll('#imagesList .product-image-card').forEach((card, index) => {
                const urlField = card.querySelector('.image-url');
                if (!urlField || !urlField.value.trim()) {
                    return;
                }
                const altField = card.querySelector('.image-alt');
                const normalizedUrl = normalizeImagePath(urlField.value.trim());
                if (!normalizedUrl) {
                    return;
                }
                const isPrincipal = principalRadio ? card.contains(principalRadio) : index === 0;
                imagenes.push({
                    url: normalizedUrl,
                    alt_text: altField && altField.value.trim() ? altField.value.trim() : (equipamientoData.nombre || ''),
                    es_principal: isPrincipal ? 1 : 0
                });
            });
            if (imagenes.length > 0 && !imagenes.some(imagen => imagen.es_principal === 1)) {
                imagenes[0].es_principal = 1;
            }
            equipamientoData.imagenes = imagenes;

            try {
                const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch(basePath + 'api/equipamiento_profesional.php', {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(equipamientoData)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    const alertPromise = currentEditId ? CRUDAlerts.updated('Equipamiento') : CRUDAlerts.created('Equipamiento');
                    alertPromise.then(() => {
                        window.location.href = 'index.php?page=equipamiento_profesional';
                    });
                } else {
                    console.error('Error en API de equipamiento:', data.error);
                    if (currentEditId) {
                        CRUDAlerts.updateError(data.error);
                    } else {
                        CRUDAlerts.createError(data.error);
                    }
                }
            } catch (error) {
                console.error('Error guardando equipamiento:', error);
                CRUDAlerts.connectionError();
            }
        }
    </script>
</body>
</html>
