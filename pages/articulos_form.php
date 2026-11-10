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
    <title>Gestión de Artículo - Stadium</title>
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
        
        /* Estilos adaptativos para modo claro/oscuro */
        .image-preview-container {
            background-color: var(--bs-tertiary-bg) !important;
            border-color: var(--bs-border-color) !important;
        }
        
        .variant-image-item {
            background-color: var(--bs-tertiary-bg) !important;
            border-color: var(--bs-border-color) !important;
        }
        
        .variant-image-preview {
            background-color: var(--bs-body-bg) !important;
            border-color: var(--bs-border-color) !important;
        }
        
        .variant-item {
            background-color: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color) !important;
        }
        
        /* Asegurar que los textos sean visibles en ambos temas */
        .variant-item label,
        .variant-item .form-label {
            color: var(--bs-body-color);
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
                <h5 class="mb-0 d-inline">Artículos</h5>
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
                <h2 class="mb-0" id="formTitle">Nuevo Artículo</h2>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="index.php?page=articulos">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <button type="button" class="btn btn-primary" id="saveButton" onclick="saveProduct()">
                        <i class="bi bi-floppy"></i> Guardar
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="productForm">
                        <input type="hidden" id="productId" name="id">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                    <i class="bi bi-info-circle me-1"></i>General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                                    <i class="bi bi-tags me-1"></i>Categorías
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images" type="button" role="tab">
                                    <i class="bi bi-images me-1"></i>Imágenes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="dimensions-tab" data-bs-toggle="tab" data-bs-target="#dimensions" type="button" role="tab">
                                    <i class="bi bi-rulers me-1"></i>Dimensiones
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="variants-tab" data-bs-toggle="tab" data-bs-target="#variants" type="button" role="tab">
                                    <i class="bi bi-boxes me-1"></i>Variantes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes" type="button" role="tab">
                                    <i class="bi bi-list-ul me-1"></i>Atributos
                                </button>
                            </li>
                        </ul>

                        <!-- Tab content -->
                        <div class="tab-content mt-3" id="productTabContent">
                            <!-- General Tab -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="sku" name="sku" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio_descuento" class="form-label">Precio Descuento</label>
                                            <input type="number" class="form-control" id="precio_descuento" name="precio_descuento" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="marca_id" class="form-label">Marca</label>
                                            <select class="form-control" id="marca_id" name="marca_id">
                                                <option value="">Seleccionar marca...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="deporte" class="form-label">Deporte</label>
                                            <select class="form-control" id="deporte" name="deporte">
                                                <option value="">Seleccionar deporte...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="esta_activo" name="esta_activo" checked>
                                            <label class="form-check-label" for="esta_activo">
                                                Activo
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                            <label class="form-check-label" for="destacado">
                                                Destacado
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Categories Tab -->
                            <div class="tab-pane fade" id="categories" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Categorías</h6>
                                        <div id="categoriesList"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Colecciones</h6>
                                        <div id="coleccionesList"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Images Tab -->
                            <div class="tab-pane fade" id="images" role="tabpanel">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" onclick="addImageField()">
                                        <i class="bi bi-plus"></i> Agregar Imagen
                                    </button>
                                </div>
                                <div id="imagesList"></div>
                            </div>

                            <!-- Dimensions Tab -->
                            <div class="tab-pane fade" id="dimensions" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="peso" class="form-label">Peso (kg)</label>
                                            <input type="number" class="form-control" id="peso" name="peso" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="alto" class="form-label">Alto (cm)</label>
                                            <input type="number" class="form-control" id="alto" name="alto" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ancho" class="form-label">Ancho (cm)</label>
                                            <input type="number" class="form-control" id="ancho" name="ancho" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="profundidad" class="form-label">Profundidad (cm)</label>
                                            <input type="number" class="form-control" id="profundidad" name="profundidad" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Variantes Tab -->
                            <div class="tab-pane fade" id="variants" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Variantes del Producto</h6>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addVariant()">
                                        <i class="bi bi-plus"></i> Agregar Variante
                                    </button>
                                </div>
                                <div id="variants-container">
                                    <p class="text-muted">No hay variantes definidas</p>
                                </div>
                            </div>

                            <!-- Atributos Tab -->
                            <div class="tab-pane fade" id="attributes" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Atributos del Producto</h6>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addAttribute()">
                                        <i class="bi bi-plus"></i> Agregar Atributo
                                    </button>
                                </div>
                                <div id="attributes-container"></div>
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
        let marcas = [];
        let deportes = [];
        let categorias = [];
        let colecciones = [];
        let selectedCategoriasIds = [];
        let selectedColeccionesIds = [];

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

            try {
                await Promise.all([
                    loadMarcas(),
                    loadDeportes(),
                    loadCategorias(),
                    loadColecciones()
                ]);
            } catch (error) {
                console.error('Error inicializando catálogos:', error);
                CRUDAlerts.connectionError();
            }

            const params = new URLSearchParams(window.location.search);
            const productId = params.get('id');

            if (productId) {
                currentEditId = parseInt(productId, 10);
                await loadProduct(currentEditId);
                document.getElementById('formTitle').textContent = 'Editar Artículo';
                document.getElementById('saveButton').textContent = 'Guardar Cambios';
            } else {
                ensureDefaultAttributeRow();
                document.getElementById('formTitle').textContent = 'Nuevo Artículo';
                document.getElementById('saveButton').textContent = 'Guardar';
            }
        });

        async function loadMarcas() {
            try {
                const response = await fetch('./api-marcas-simple.php');
                const data = await response.json();

                if (data.success) {
                    marcas = data.marcas;
                    const select = document.getElementById('marca_id');
                    select.innerHTML = '<option value="">Seleccionar marca...</option>';
                    marcas.forEach(marca => {
                        const option = document.createElement('option');
                        option.value = marca.id;
                        option.textContent = marca.nombre;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error cargando marcas:', error);
            }
        }

        async function loadDeportes() {
            try {
                const response = await fetch('./api-deportes-simple.php');
                const data = await response.json();

                if (data.success) {
                    deportes = data.deportes;
                    const select = document.getElementById('deporte');
                    select.innerHTML = '<option value="">Seleccionar deporte...</option>';
                    deportes.forEach(deporte => {
                        const option = document.createElement('option');
                        option.value = deporte.id; // Guardar el id como valor
                        option.textContent = deporte.nombre;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error cargando deportes:', error);
            }
        }

        async function loadCategorias() {
            try {
                const response = await fetch('./api-categorias-simple.php');
                const data = await response.json();

                if (data.success) {
                    categorias = data.categorias || [];
                    renderCategorias();
                }
            } catch (error) {
                console.error('Error cargando categorías:', error);
            }
        }

        async function loadColecciones() {
            try {
                const response = await fetch('./api-colecciones-simple.php');
                const data = await response.json();

                if (data.success) {
                    colecciones = data.colecciones || [];
                    renderColecciones();
                }
            } catch (error) {
                console.error('Error cargando colecciones:', error);
            }
        }

        function renderCategorias(preselected = selectedCategoriasIds) {
            const normalized = (preselected || []).map(id => parseInt(id, 10));
            const container = document.getElementById('categoriesList');
            container.innerHTML = '';

            categorias.forEach(categoria => {
                const isChecked = normalized.includes(parseInt(categoria.id, 10));
                container.insertAdjacentHTML('beforeend', `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="categorias[]" value="${categoria.id}" id="cat_${categoria.id}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label" for="cat_${categoria.id}">
                            ${categoria.nombre}
                        </label>
                    </div>
                `);
            });
        }

        function renderColecciones(preselected = selectedColeccionesIds) {
            const normalized = (preselected || []).map(id => parseInt(id, 10));
            const container = document.getElementById('coleccionesList');
            container.innerHTML = '';

            colecciones.forEach(coleccion => {
                const isChecked = normalized.includes(parseInt(coleccion.id, 10));
                container.insertAdjacentHTML('beforeend', `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="colecciones[]" value="${coleccion.id}" id="col_${coleccion.id}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label" for="col_${coleccion.id}">
                            ${coleccion.nombre}
                        </label>
                    </div>
                `);
            });
        }

        async function loadProduct(id) {
            try {
                const response = await fetch(`./api/productos.php?id=${id}`);
                const data = await response.json();

                if (!data.success) {
                    CRUDAlerts.loadError ? CRUDAlerts.loadError('producto', data.error) : CRUDAlerts.connectionError();
                    return;
                }

                const producto = data.producto;
                document.getElementById('productId').value = producto.id;
                document.getElementById('sku').value = producto.sku || '';
                document.getElementById('nombre').value = producto.nombre || '';
                document.getElementById('descripcion').value = producto.descripcion || '';
                document.getElementById('precio').value = producto.precio || '';
                document.getElementById('precio_descuento').value = producto.precio_descuento || '';
                document.getElementById('stock').value = producto.stock || 0;
                document.getElementById('marca_id').value = producto.marca_id || '';
                document.getElementById('deporte').value = producto.deporte || '';
                document.getElementById('esta_activo').checked = producto.esta_activo == 1;
                document.getElementById('destacado').checked = producto.destacado == 1;
                document.getElementById('peso').value = producto.peso || '';
                document.getElementById('alto').value = producto.alto || '';
                document.getElementById('ancho').value = producto.ancho || '';
                document.getElementById('profundidad').value = producto.profundidad || '';

                selectedCategoriasIds = (producto.categorias || []).map(cat => parseInt(cat.id, 10));
                renderCategorias(selectedCategoriasIds);

                selectedColeccionesIds = (producto.colecciones || []).map(col => parseInt(col.id, 10));
                renderColecciones(selectedColeccionesIds);

                const imagesList = document.getElementById('imagesList');
                imagesList.innerHTML = '';
                if (producto.imagenes && producto.imagenes.length > 0) {
                    producto.imagenes.forEach((imagen) => {
                        // Solo marcar como principal si viene explícito desde la API (es_principal === 1)
                        addImageField(imagen.url, imagen.alt_text, imagen.es_principal == 1);
                    });
                }

                const variantsContainer = document.getElementById('variants-container');
                variantsContainer.innerHTML = '';
                if (producto.variantes && producto.variantes.length > 0) {
                    producto.variantes.forEach((variante, variantIndex) => {
                        addVariantFromData(variante, variantIndex);
                    });
                } else {
                    variantsContainer.innerHTML = '<p class="text-muted">No hay variantes definidas</p>';
                }

                const attributesContainer = document.getElementById('attributes-container');
                attributesContainer.innerHTML = '';
                if (producto.atributos && producto.atributos.length > 0) {
                    producto.atributos.forEach(atributo => {
                        addAttribute(atributo.nombre || '', atributo.valor || '');
                    });
                }

                ensureDefaultAttributeRow();
            } catch (error) {
                console.error('Error cargando producto:', error);
                CRUDAlerts.connectionError();
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
                            <div class="image-preview-container text-center p-2 border rounded" style="min-height: 100px;">
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

        async function removeImageField(button) {
            const card = button.closest('.card');
            if (!card) return;
            
            // Obtener la URL de la imagen antes de eliminar el card
            const urlInput = card.querySelector('.image-url');
            const imageUrl = urlInput ? urlInput.value : null;
            
            // Si hay una URL válida, intentar eliminar el archivo físico
            if (imageUrl && imageUrl.trim() !== '') {
                try {
                    const response = await fetch('./api/delete_imagen.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ url: imageUrl })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        console.log('Archivo eliminado:', result.path);
                    } else {
                        console.warn('Error eliminando archivo:', result.error);
                    }
                } catch (error) {
                    console.error('Error al eliminar archivo físico:', error);
                }
            }
            
            card.remove();
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
                const response = await fetch('./api/upload_imagen.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

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

            // No marcar automáticamente ninguna imagen como principal al crear.
            // Solo respetar el valor si existe (por ejemplo al editar un producto).
            // Si se necesita comportamiento de auto-selección en edición, se establece desde `loadProduct`.
            return;
        }

        function addVariant() {
            const container = document.getElementById('variants-container');
            const noVariantsMessage = container.querySelector('p.text-muted');
            if (noVariantsMessage) {
                noVariantsMessage.remove();
            }
            const variantIndex = container.querySelectorAll('.variant-item').length;
            container.insertAdjacentHTML('beforeend', createVariantHtml(variantIndex));
            const allVariants = container.querySelectorAll('.variant-item');
            const newVariant = allVariants[allVariants.length - 1];
            newVariant.dataset.variantKey = generateVariantKey();
            attachVariantItemEvents(newVariant);
            refreshVariantHeaders();
        }

        function addVariantFromData(variante, variantIndex) {
            const container = document.getElementById('variants-container');
            container.insertAdjacentHTML('beforeend', createVariantHtml(variantIndex, variante));
            const allVariants = container.querySelectorAll('.variant-item');
            const variantElement = allVariants[allVariants.length - 1];
            const variantKey = variante && variante.id ? `var-${variante.id}` : generateVariantKey();
            variantElement.dataset.variantKey = variantKey;

            const optionsContainer = variantElement.querySelector('.variant-options-container');
            optionsContainer.innerHTML = '';
            if (variante.atributos && variante.atributos.length > 0) {
                variante.atributos.forEach(atributo => {
                    optionsContainer.insertAdjacentHTML('beforeend', createVariantOptionHtml(atributo.nombre, atributo.valor));
                });
            } else {
                optionsContainer.insertAdjacentHTML('beforeend', createVariantOptionHtml());
            }

            const imagesContainer = variantElement.querySelector('.variant-images-container');
            imagesContainer.innerHTML = '';
            if (variante.imagenes && variante.imagenes.length > 0) {
                variante.imagenes.forEach((imagen, imageIndex) => {
                    imagesContainer.insertAdjacentHTML('beforeend', createVariantImageHtml(variantKey, imageIndex, imagen.url, imagen.alt_text, imagen.es_principal == 1));
                    attachVariantImageEvents(imagesContainer.lastElementChild);
                });
            } else {
                imagesContainer.innerHTML = '<p class="text-muted small">No hay imágenes para esta variante</p>';
            }

            attachVariantItemEvents(variantElement);
            refreshVariantHeaders();
        }

        function createVariantHtml(index, variante = null) {
            const sku = variante ? (variante.sku_variante || variante.sku || '') : '';
            const stock = variante ? (variante.stock || 0) : 0;
            const price = variante ? (variante.precio || '') : '';

            return `
                <div class="variant-item border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Variante ${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeVariant(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="form-label">SKU Variante</label>
                            <input type="text" class="form-control variant-sku" placeholder="SKU único" value="${sku}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control variant-stock" min="0" value="${stock}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio Específico</label>
                            <input type="number" class="form-control variant-price" step="0.01" min="0" placeholder="Opcional" value="${price}">
                        </div>
                    </div>
                    <div class="variant-options">
                        <label class="form-label">Opciones (ej: Talle, Color)</label>
                        <div class="variant-options-container">
                            ${variante ? '' : createVariantOptionHtml()}
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" onclick="addVariantOption(this)">
                            <i class="bi bi-plus"></i> Agregar Opción
                        </button>
                    </div>
                    <div class="variant-images mt-3 border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">🖼️ Imágenes de la Variante</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantImage(this)">
                                <i class="bi bi-plus"></i> Agregar Imagen
                            </button>
                        </div>
                        <div class="variant-images-container">
                            ${variante && variante.imagenes && variante.imagenes.length > 0 ? '' : '<p class="text-muted small">No hay imágenes para esta variante</p>'}
                        </div>
                    </div>
                </div>
            `;
        }

        function createVariantOptionHtml(name = '', value = '') {
            return `
                <div class="row mb-2 variant-option-row">
                    <div class="col-md-5">
                        <input type="text" class="form-control variant-option-name" placeholder="Nombre (ej: Talle)" value="${name}">
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control variant-option-value" placeholder="Valor (ej: M)" value="${value}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeVariantOption(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        function createVariantImageHtml(variantKey, imageIndex, url = '', alt = '', isPrincipal = false) {
            const normalizedUrl = normalizeImagePath(url);
            const previewHtml = normalizedUrl ? `<img src="${resolveImageUrl(normalizedUrl)}" alt="${alt}" style="max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"><small class="text-danger" style="display: none;">Error cargando imagen</small>` : '<small class="text-muted">Vista previa de la imagen</small>';
            return `
                <div class="variant-image-item border rounded p-2 mb-2">
                    <div class="row align-items-end g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Archivo de imagen</label>
                            <input type="file" accept="image/*" class="form-control form-control-sm variant-image-file">
                            <input type="hidden" class="variant-image-url" value="${normalizedUrl}">
                            <small class="text-muted d-block mt-1 variant-image-url-display">${normalizedUrl ? normalizedUrl : 'Sin archivo subido'}</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Texto Alternativo</label>
                            <input type="text" class="form-control form-control-sm variant-image-alt" placeholder="Descripción" value="${alt}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Principal</label>
                            <div class="form-check mt-1">
                                <input class="form-check-input variant-image-principal" type="radio" name="variant_image_principal_${variantKey}" ${isPrincipal ? 'checked' : ''}>
                                <label class="form-check-label small">Sí</label>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeVariantImage(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="variant-image-preview text-center" style="min-height: 60px; border: 1px dashed var(--bs-border-color); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                ${previewHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function removeVariant(button) {
            const variantItem = button.closest('.variant-item');
            if (variantItem) {
                variantItem.remove();
            }
            const container = document.getElementById('variants-container');
            if (container.querySelectorAll('.variant-item').length === 0) {
                container.innerHTML = '<p class="text-muted">No hay variantes definidas</p>';
            }
            refreshVariantHeaders();
        }

        function addVariantOption(button) {
            const variantItem = button.closest('.variant-item');
            const optionsContainer = variantItem.querySelector('.variant-options-container');
            optionsContainer.insertAdjacentHTML('beforeend', createVariantOptionHtml());
        }

        function removeVariantOption(button) {
            const row = button.closest('.variant-option-row');
            if (row) {
                row.remove();
            }
        }

        function addVariantImage(button) {
            const variantItem = button.closest('.variant-item');
            const imagesContainer = variantItem.querySelector('.variant-images-container');
            const emptyMessage = imagesContainer.querySelector('p.text-muted');
            if (emptyMessage) {
                emptyMessage.remove();
            }
            const imageIndex = imagesContainer.querySelectorAll('.variant-image-item').length;
            let variantKey = variantItem.dataset.variantKey;
            if (!variantKey) {
                variantKey = generateVariantKey();
                variantItem.dataset.variantKey = variantKey;
            }
            imagesContainer.insertAdjacentHTML('beforeend', createVariantImageHtml(variantKey, imageIndex));
            attachVariantImageEvents(imagesContainer.lastElementChild);
            ensureVariantPrincipal(imagesContainer);
        }

        async function removeVariantImage(button) {
            const imagesContainer = button.closest('.variant-images-container');
            const imageItem = button.closest('.variant-image-item');
            if (!imageItem) return;
            
            // Obtener la URL de la imagen antes de eliminar el item
            const urlInput = imageItem.querySelector('.variant-image-url');
            const imageUrl = urlInput ? urlInput.value : null;
            
            // Si hay una URL válida, intentar eliminar el archivo físico
            if (imageUrl && imageUrl.trim() !== '') {
                try {
                    const response = await fetch('./api/delete_imagen.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ url: imageUrl })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        console.log('Archivo eliminado:', result.path);
                    } else {
                        console.warn('Error eliminando archivo:', result.error);
                    }
                } catch (error) {
                    console.error('Error al eliminar archivo físico:', error);
                }
            }
            
            const wasPrincipal = imageItem.querySelector('.variant-image-principal')?.checked;
            imageItem.remove();
            if (wasPrincipal) {
                ensureVariantPrincipal(imagesContainer);
            }
            
            if (imagesContainer.querySelectorAll('.variant-image-item').length === 0) {
                imagesContainer.innerHTML = '<p class="text-muted small">No hay imágenes para esta variante</p>';
            }
        }

        function generateVariantKey() {
            return `var-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
        }

        function attachVariantItemEvents(variantElement) {
            if (!variantElement) {
                return;
            }
            variantElement.querySelectorAll('.variant-image-item').forEach(item => {
                attachVariantImageEvents(item);
            });
            ensureVariantPrincipal(variantElement.querySelector('.variant-images-container'));
        }

        function attachVariantImageEvents(imageItem) {
            if (!imageItem) {
                return;
            }
            const fileInput = imageItem.querySelector('.variant-image-file');
            if (fileInput && !fileInput.dataset.bound) {
                fileInput.addEventListener('change', async (event) => {
                    await handleVariantImageUpload(event.currentTarget);
                });
                fileInput.dataset.bound = 'true';
            }
        }

        async function handleVariantImageUpload(input) {
            if (!input.files || !input.files[0]) {
                return;
            }

            const imageItem = input.closest('.variant-image-item');
            const previewContainer = imageItem.querySelector('.variant-image-preview');
            const urlDisplay = imageItem.querySelector('.variant-image-url-display');
            const hiddenUrl = imageItem.querySelector('.variant-image-url');

            const file = input.files[0];
            const formData = new FormData();
            formData.append('imagen', file);

            previewContainer.innerHTML = '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="visually-hidden">Subiendo...</span></div>';
            urlDisplay.textContent = 'Subiendo imagen...';

            try {
                const response = await fetch('./api/upload_imagen.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Error desconocido al subir la imagen');
                }

                const normalizedUrl = normalizeImagePath(data.url);
                hiddenUrl.value = normalizedUrl;
                urlDisplay.textContent = normalizedUrl;
                updateVariantImagePreview(previewContainer, normalizedUrl);
                input.value = '';
                ensureVariantPrincipal(imageItem.closest('.variant-images-container'));
            } catch (error) {
                console.error('Error subiendo imagen de variante:', error);
                showErrorAlert('Error al subir imagen', error.message);
                hiddenUrl.value = '';
                urlDisplay.textContent = 'Sin archivo subido';
                previewContainer.innerHTML = '<small class="text-danger">No se pudo subir la imagen</small>';
            }
        }

        function updateVariantImagePreview(container, url) {
            if (url) {
                container.innerHTML = `
                    <img src="${resolveImageUrl(url)}" alt="Vista previa" style="max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <small class="text-danger" style="display: none;">Error cargando imagen</small>
                `;
            } else {
                container.innerHTML = '<small class="text-muted">Vista previa de la imagen</small>';
            }
        }

        function ensureVariantPrincipal(imagesContainer) {
            if (!imagesContainer) {
                return;
            }
            const radios = imagesContainer.querySelectorAll('.variant-image-principal');
            const checked = imagesContainer.querySelector('.variant-image-principal:checked');
            if (!checked && radios.length > 0) {
                radios[0].checked = true;
            }
        }

        function refreshVariantHeaders() {
            const variants = document.querySelectorAll('#variants-container .variant-item');
            variants.forEach((variant, index) => {
                const title = variant.querySelector('h6');
                if (title) {
                    title.textContent = `Variante ${index + 1}`;
                }
            });
        }

        function addAttribute(name = '', value = '') {
            const container = document.getElementById('attributes-container');
            container.insertAdjacentHTML('beforeend', `
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Nombre del atributo" name="attribute_name[]" value="${name}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Valor del atributo" name="attribute_value[]" value="${value}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeAttribute(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        }

        function ensureDefaultAttributeRow() {
            const container = document.getElementById('attributes-container');
            if (container.children.length === 0) {
                addAttribute();
            }
        }

        function removeAttribute(button) {
            const row = button.closest('.row');
            if (row) {
                row.remove();
            }
            ensureDefaultAttributeRow();
        }

        async function saveProduct() {
            const form = document.getElementById('productForm');
            const formData = new FormData(form);
            const productData = {};

            for (const [key, value] of formData.entries()) {
                if (key === 'id' && !currentEditId) {
                    continue;
                }
                productData[key] = value;
            }

            // Asegurar que el campo deporte se capture correctamente del select
            const deporteSelect = document.getElementById('deporte');
            productData.deporte = deporteSelect ? deporteSelect.value : '';

            // Asegurar que el ID se incluya si estamos editando
            if (currentEditId) {
                productData.id = currentEditId;
            }

            const categoriasSeleccionadas = [];
            document.querySelectorAll('input[name="categorias[]"]:checked').forEach(cb => {
                categoriasSeleccionadas.push(parseInt(cb.value, 10));
            });
            productData.categorias = categoriasSeleccionadas;

            const coleccionesSeleccionadas = [];
            document.querySelectorAll('input[name="colecciones[]"]:checked').forEach(cb => {
                coleccionesSeleccionadas.push(parseInt(cb.value, 10));
            });
            productData.colecciones = coleccionesSeleccionadas;

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
                const isPrincipal = principalRadio ? card.contains(principalRadio) : false;
                imagenes.push({
                    url: normalizedUrl,
                    alt_text: altField && altField.value.trim() ? altField.value.trim() : (productData.nombre || ''),
                    es_principal: isPrincipal ? 1 : 0
                });
            });
            // No seleccionar automáticamente ninguna imagen como principal.
            productData.imagenes = imagenes;

            const variantes = [];
            document.querySelectorAll('.variant-item').forEach((variantItem, variantIndex) => {
                const skuField = variantItem.querySelector('.variant-sku');
                const stockField = variantItem.querySelector('.variant-stock');
                const priceField = variantItem.querySelector('.variant-price');

                if (skuField && skuField.value.trim()) {
                    const variante = {
                        sku: skuField.value.trim(),
                        nombre: `${productData.nombre || ''} - Variante ${variantIndex + 1}`,
                        stock: parseInt(stockField.value || '0', 10),
                        precio: priceField.value ? parseFloat(priceField.value) : null,
                        activa: 1,
                        atributos: [],
                        imagenes: []
                    };

                    variantItem.querySelectorAll('.variant-option-row').forEach(optionRow => {
                        const nameField = optionRow.querySelector('.variant-option-name');
                        const valueField = optionRow.querySelector('.variant-option-value');
                        if (nameField && valueField && nameField.value.trim() && valueField.value.trim()) {
                            variante.atributos.push({
                                nombre: nameField.value.trim(),
                                valor: valueField.value.trim()
                            });
                        }
                    });

                    const variantPrincipal = variantItem.querySelector('.variant-image-principal:checked');
                    const variantImageItems = variantItem.querySelectorAll('.variant-image-item');
                    variantImageItems.forEach((imageItem, imageIndex) => {
                        const urlField = imageItem.querySelector('.variant-image-url');
                        if (!urlField || !urlField.value.trim()) {
                            return;
                        }
                        const altField = imageItem.querySelector('.variant-image-alt');
                        const normalizedUrl = normalizeImagePath(urlField.value.trim());
                        if (!normalizedUrl) {
                            return;
                        }
                        const isPrincipal = variantPrincipal ? imageItem.contains(variantPrincipal) : false;
                        variante.imagenes.push({
                            url: normalizedUrl,
                            alt_text: altField && altField.value.trim() ? altField.value.trim() : `${variante.nombre} - Imagen ${imageIndex + 1}`,
                            es_principal: isPrincipal ? 1 : 0
                        });
                    });
                    // No seleccionar automáticamente ninguna imagen de variante como principal.

                    variantes.push(variante);
                }
            });
            productData.variantes = variantes;

            const atributos = [];
            const attributeNames = document.querySelectorAll('input[name="attribute_name[]"]');
            const attributeValues = document.querySelectorAll('input[name="attribute_value[]"]');
            attributeNames.forEach((nameField, index) => {
                const valueField = attributeValues[index];
                if (nameField.value.trim() && valueField.value.trim()) {
                    atributos.push({
                        nombre: nameField.value.trim(),
                        valor: valueField.value.trim()
                    });
                }
            });
            productData.atributos = atributos;

            productData.esta_activo = document.getElementById('esta_activo').checked ? 1 : 0;
            productData.destacado = document.getElementById('destacado').checked ? 1 : 0;

            try {
                // Validar límite de variantes
                if (productData.variantes && productData.variantes.length > 50) {
                    CRUDAlerts.createError('Se permiten máximo 50 variantes por producto');
                    return;
                }
                
                const method = currentEditId ? 'PUT' : 'POST';
                
                // Mostrar mensaje de carga
                Swal.fire({
                    title: 'Guardando...',
                    html: 'Por favor espere mientras se guarda el producto',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 segundos timeout
                
                const response = await fetch('./api/productos.php', {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(productData),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
                }

                const data = await response.json();

                if (data.success) {
                    const alertPromise = currentEditId ? CRUDAlerts.updated('Artículo') : CRUDAlerts.created('Artículo');
                    alertPromise.then(() => {
                        window.location.href = 'index.php?page=articulos';
                    });
                } else {
                    console.error('Error en API de artículos:', data.error);
                    Swal.close();
                    if (currentEditId) {
                        CRUDAlerts.updateError(data.error);
                    } else {
                        CRUDAlerts.createError(data.error);
                    }
                }
            } catch (error) {
                console.error('Error guardando artículo:', error);
                Swal.close();
                if (error.name === 'AbortError') {
                    CRUDAlerts.createError('La operación tomó demasiado tiempo. Por favor, reduce el número de variantes o imágenes e intenta nuevamente.');
                } else {
                    CRUDAlerts.connectionError();
                }
            }
        }
    </script>
</body>
</html>
