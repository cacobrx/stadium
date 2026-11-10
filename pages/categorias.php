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
    <title>Categorías - Stadium</title>
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
                <h5 class="mb-0 d-inline">Categorías</h5>
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
                <h2>Gestión de Categorías</h2>
                <button class="btn btn-primary" onclick="openCategoriaModal()">
                    <i class="bi bi-plus"></i> Nueva Categoría
                </button>
            </div>

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Categorías</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="categoriasTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Principal</th>
                                    <th>Activa</th>
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

    <!-- Category Modal -->
    <div class="modal fade" id="categoriaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="categoriaForm">
                        <input type="hidden" id="categoriaId" name="id">
                        <input type="hidden" id="imagenUrl" name="imagen">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="activa" class="form-label">Estado</label>
                                    <select class="form-control" id="activa" name="activa">
                                        <option value="1">Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoria_padre_id" class="form-label">Categoría Padre</label>
                                    <select class="form-control" id="categoria_padre_id" name="categoria_padre_id">
                                        <option value="">-- Sin categoría padre --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" placeholder="Se genera automáticamente">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="number" class="form-control" id="orden" name="orden" value="0" min="0">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary"><i class="bi bi-image"></i> Imagen y Destacado</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="principal" name="principal" value="1">
                                        <label class="form-check-label" for="principal">
                                            <strong>Categoría Principal</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Marcar si esta categoría es principal/destacada</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Imagen</label>
                                    <input type="file" class="form-control" id="imagen_file" name="imagen_file" accept="image/*" onchange="previewImagenCategoria(this)">
                                    <div class="form-text">Formatos: JPG, PNG, GIF, WEBP (se convertirá automáticamente a WebP)</div>
                                    <div id="imagenPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveCategoria()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        const B = './';
        let categoriaModal;
        let currentEditId = null;

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            categoriaModal = new bootstrap.Modal(document.getElementById('categoriaModal'));
            loadCategorias();
            loadCategoriasParent();
        });

        // Load categories from API
        async function loadCategorias() {
            try {
                const response = await fetch('./api/categorias.php', { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success) {
                    renderCategoriasTable(data.categorias);
                } else {
                    console.error('Error loading categorias:', data.error);
                    alert('Error al cargar categorías: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al cargar categorías');
            }
        }

        // Load categories for parent selector
        async function loadCategoriasParent() {
            try {
                const response = await fetch('./api/categorias.php', { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('categoria_padre_id');
                    select.innerHTML = '<option value="">-- Sin categoría padre --</option>';
                    
                    data.categorias.forEach(categoria => {
                        if (categoria.activa == 1) {
                            const option = document.createElement('option');
                            option.value = categoria.id;
                            option.textContent = categoria.nombre;
                            select.appendChild(option);
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading parent categories:', error);
            }
        }

        // Render categories table
        function renderCategoriasTable(categorias) {
            const tbody = document.querySelector('#categoriasTable tbody');
            tbody.innerHTML = '';

            categorias.forEach(categoria => {
                const row = document.createElement('tr');
                let imagenDisplay = '-';
                if (categoria.imagen) {
                    // Si la URL es relativa, usar base path actual
                    let imagenUrl = categoria.imagen;
                    if (!imagenUrl.startsWith('http://') && !imagenUrl.startsWith('https://')) {
                        // Si no tiene protocolo, es una URL relativa
                        const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                        if (imagenUrl.startsWith('/')) {
                            imagenUrl = imagenUrl;
                        } else if (imagenUrl.startsWith('uploads/')) {
                            imagenUrl = basePath + imagenUrl;
                        } else {
                            imagenUrl = basePath + imagenUrl;
                        }
                    }
                    imagenDisplay = `<img src="${imagenUrl}" alt="Imagen" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`;
                }
                
                row.innerHTML = `
                    <td>${categoria.id}</td>
                    <td>${imagenDisplay}</td>
                    <td>${categoria.nombre || ''}</td>
                    <td>${categoria.descripcion ? categoria.descripcion.substring(0, 50) + (categoria.descripcion.length > 50 ? '...' : '') : ''}</td>
                    <td>${categoria.principal == 1 ? '<span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                    <td><span class="badge ${categoria.activa ? 'bg-success' : 'bg-secondary'}">${categoria.activa ? 'Activa' : 'Inactiva'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editCategoria(${categoria.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategoria(${categoria.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open category modal for new category
        function openCategoriaModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nueva Categoría';
            document.getElementById('categoriaForm').reset();
            document.getElementById('categoriaId').value = '';
            document.getElementById('imagenUrl').value = '';
            document.getElementById('activa').value = '1';
            document.getElementById('principal').checked = false;
            // Clear image preview
            const preview = document.getElementById('imagenPreview');
            preview.innerHTML = '';
            loadCategoriasParent();
            categoriaModal.show();
        }

        // Edit category
        async function editCategoria(id) {
            try {
                const response = await fetch(`./api/categorias.php?id=${id}`, { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success) {
                    const categoria = data.categoria;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Categoría';
                    document.getElementById('categoriaId').value = categoria.id;
                    document.getElementById('nombre').value = categoria.nombre || '';
                    document.getElementById('descripcion').value = categoria.descripcion || '';
                    document.getElementById('slug').value = categoria.slug || '';
                    document.getElementById('orden').value = categoria.orden || 0;
                    document.getElementById('activa').value = categoria.activa || 1;
                    document.getElementById('principal').checked = categoria.principal == 1;
                    document.getElementById('imagenUrl').value = categoria.imagen || '';
                    
                    // Clear file input to allow selecting a new image
                    document.getElementById('imagen_file').value = '';
                    
                    // Show image preview if exists
                    const preview = document.getElementById('imagenPreview');
                    if (categoria.imagen) {
                        // Si la URL es relativa, usar base path actual
                        let imagenUrl = categoria.imagen;
                        if (!imagenUrl.startsWith('http://') && !imagenUrl.startsWith('https://')) {
                            // Si no tiene protocolo, es una URL relativa
                            const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                            if (imagenUrl.startsWith('/')) {
                                imagenUrl = imagenUrl;
                            } else if (imagenUrl.startsWith('uploads/')) {
                                imagenUrl = basePath + imagenUrl;
                            } else {
                                imagenUrl = basePath + imagenUrl;
                            }
                        }
                        preview.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <img src="${imagenUrl}" alt="Imagen actual" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageCategoria()">
                                    <i class="bi bi-trash"></i> Quitar
                                </button>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = '';
                    }
                    
                    await loadCategoriasParent();
                    document.getElementById('categoria_padre_id').value = categoria.categoria_padre_id || '';
                    
                    categoriaModal.show();
                } else {
                    alert('Error al cargar categoría: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Remover imagen
        function removeImageCategoria() {
            document.getElementById('imagenUrl').value = '';
            document.getElementById('imagen_file').value = '';
            document.getElementById('imagenPreview').innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar cambios.</span>';
        }

        // Save category
        async function saveCategoria() {
            const form = document.getElementById('categoriaForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const categoriaData = {};
            for (let [key, value] of formData.entries()) {
                categoriaData[key] = value;
            }
            
            // Manejar checkbox principal (si no está marcado, no viene en FormData)
            const principalCheckbox = document.getElementById('principal');
            categoriaData.principal = principalCheckbox.checked ? 1 : 0;

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/categorias.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(categoriaData),
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    categoriaModal.hide();
                    loadCategorias(); // Reload table
                    loadCategoriasParent(); // Reload parent options
                    if (currentEditId) {
                        CRUDAlerts.updated('Categoría');
                    } else {
                        CRUDAlerts.created('Categoría');
                    }
                } else {
                    if (currentEditId) {
                        CRUDAlerts.updateError(data.error);
                    } else {
                        CRUDAlerts.createError(data.error);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Delete category
        async function deleteCategoria(id) {
            const result = await CRUDAlerts.confirmDelete('categoría');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/categorias.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id }),
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadCategorias(); // Reload table
                    loadCategoriasParent(); // Reload parent options
                    CRUDAlerts.deleted('Categoría');
                } else {
                    CRUDAlerts.deleteError(data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Subida y preview de imagen para categoría
        async function uploadCategoriaImagen(fileInput) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;

            const file = fileInput.files[0];

            // Si no es imagen, subir directamente
            if (!file.type.startsWith('image/')) {
                const fdRaw = new FormData();
                fdRaw.append('imagen', file, file.name);
                const rRaw = await fetch(B + 'api/upload_imagen.php', {method: 'POST', body: fdRaw, credentials: 'same-origin'});
                const dRaw = await rRaw.json();
                if (dRaw.success) return dRaw.url;
                throw new Error(dRaw.error || 'Error al subir archivo');
            }

            const MAX_DIM = 1600;
            const QUALITY = 0.8;

            const img = await new Promise((res, rej) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const i = new Image();
                    i.onload = () => res(i);
                    i.onerror = rej;
                    i.src = reader.result;
                };
                reader.onerror = rej;
                reader.readAsDataURL(file);
            });

            let width = img.width;
            let height = img.height;
            if (width > MAX_DIM || height > MAX_DIM) {
                if (width > height) {
                    height = Math.round((MAX_DIM / width) * height);
                    width = MAX_DIM;
                } else {
                    width = Math.round((MAX_DIM / height) * width);
                    height = MAX_DIM;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            const tryWebP = (callback) => {
                if (canvas.toBlob) {
                    try {
                        canvas.toBlob((blob) => callback(blob), 'image/webp', QUALITY);
                    } catch (e) {
                        callback(null);
                    }
                } else {
                    callback(null);
                }
            };

            const blob = await new Promise((res) => {
                tryWebP((b) => {
                    if (b) return res(b);
                    canvas.toBlob((bj) => res(bj), 'image/jpeg', QUALITY);
                });
            });

            if (!blob) throw new Error('No se pudo procesar la imagen en el navegador');

            const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
            const fileName = `${file.name.replace(/\.[^/.]+$/, '')}.${ext}`;

            const fd = new FormData();
            fd.append('imagen', blob, fileName);

            const r = await fetch(B + 'api/upload_imagen.php', {method: 'POST', body: fd, credentials: 'same-origin'});
            const d = await r.json();

            if (d.success) {
                return d.url;
            } else {
                throw new Error(d.error || 'Error al subir imagen');
            }
        }

        async function previewImagenCategoria(inp) {
            const file = inp.files[0];
            const preview = document.getElementById('imagenPreview');
            const imagenUrlField = document.getElementById('imagenUrl');
            if (!file) {
                preview.innerHTML = '';
                imagenUrlField.value = '';
                return;
            }
            // Mostrar preview inmediato
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">`;
            };
            reader.readAsDataURL(file);
            // Subir inmediatamente
            try {
                const uploadedUrl = await uploadCategoriaImagen(inp);
                if (uploadedUrl) {
                    imagenUrlField.value = uploadedUrl;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${uploadedUrl}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageCategoria()">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Error upload preview:', err);
                alert('Error al subir imagen: ' + err.message);
                inp.value = '';
                preview.innerHTML = '';
                imagenUrlField.value = '';
            }
        }
    </script>
</body>
</html>