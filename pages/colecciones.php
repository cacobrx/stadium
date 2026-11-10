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
    <title>Colecciones - Stadium</title>
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
                <h5 class="mb-0 d-inline">Colecciones</h5>
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
                <h2>Gestión de Colecciones</h2>
                <button class="btn btn-primary" onclick="openColeccionModal()">
                    <i class="bi bi-plus"></i> Nueva Colección
                </button>
            </div>

            <!-- Collections Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Colecciones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="coleccionesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
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

    <!-- Collection Modal -->
    <div class="modal fade" id="coleccionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Colección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="coleccionForm">
                        <input type="hidden" id="coleccionId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" placeholder="Se genera automáticamente">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen</label>
                            <input type="file" class="form-control" id="imagen_file" name="imagen_file" accept="image/*" onchange="previewImagenColeccion(this)">
                            <input type="hidden" id="imagen" name="imagen">
                            <div class="form-text">Formatos: JPG, PNG, GIF, WEBP (se convertirá automáticamente a WebP)</div>
                            <div class="mt-2" id="imagenPreview"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveColeccion()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let coleccionModal;
        let currentEditId = null;

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            coleccionModal = new bootstrap.Modal(document.getElementById('coleccionModal'));
            loadColecciones();
        });

        // Load collections from API
        async function loadColecciones() {
            try {
                const response = await fetch('./api/colecciones.php');
                const data = await response.json();
                
                if (data.success) {
                    renderColeccionesTable(data.colecciones);
                } else {
                    console.error('Error loading colecciones:', data.error);
                    alert('Error al cargar colecciones: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al cargar colecciones');
            }
        }

        // Render collections table
        function renderColeccionesTable(colecciones) {
            const tbody = document.querySelector('#coleccionesTable tbody');
            tbody.innerHTML = '';

            colecciones.forEach(coleccion => {
                const row = document.createElement('tr');
                let imagenDisplay = '-';
                if (coleccion.imagen) {
                    // Si la URL es relativa, usar base path actual
                    let imagenUrl = coleccion.imagen;
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
                    <td>${coleccion.id}</td>
                    <td>${imagenDisplay}</td>
                    <td>${coleccion.nombre || ''}</td>
                    <td>${coleccion.descripcion ? coleccion.descripcion.substring(0, 50) + (coleccion.descripcion.length > 50 ? '...' : '') : ''}</td>
                    <td>${coleccion.fecha_inicio || '-'}</td>
                    <td>${coleccion.fecha_fin || '-'}</td>
                    <td><span class="badge ${coleccion.activa ? 'bg-success' : 'bg-secondary'}">${coleccion.activa ? 'Activa' : 'Inactiva'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editColeccion(${coleccion.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteColeccion(${coleccion.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open collection modal for new collection
        function openColeccionModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nueva Colección';
            document.getElementById('coleccionForm').reset();
            document.getElementById('coleccionId').value = '';
            document.getElementById('activa').value = '1';
            // Clear image preview
            const preview = document.getElementById('imagenPreview');
            preview.innerHTML = '';
            coleccionModal.show();
        }

        // Edit collection
        async function editColeccion(id) {
            try {
                const response = await fetch(`./api/colecciones.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const coleccion = data.coleccion;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Colección';
                    document.getElementById('coleccionId').value = coleccion.id;
                    document.getElementById('nombre').value = coleccion.nombre || '';
                    document.getElementById('slug').value = coleccion.slug || '';
                    document.getElementById('descripcion').value = coleccion.descripcion || '';
                    document.getElementById('imagen').value = coleccion.imagen || '';
                    document.getElementById('imagen_file').value = '';
                    document.getElementById('fecha_inicio').value = coleccion.fecha_inicio || '';
                    document.getElementById('fecha_fin').value = coleccion.fecha_fin || '';
                    document.getElementById('activa').value = coleccion.activa || 1;
                    
                    // Show image preview if exists
                    const preview = document.getElementById('imagenPreview');
                    if (coleccion.imagen) {
                        // Si la URL es relativa, usar base path actual
                        let imagenUrl = coleccion.imagen;
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
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageColeccion()">
                                    <i class="bi bi-trash"></i> Quitar
                                </button>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = '';
                    }
                    
                    coleccionModal.show();
                } else {
                    alert('Error al cargar colección: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Save collection
        async function saveColeccion() {
            const form = document.getElementById('coleccionForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const coleccionData = {};
            for (let [key, value] of formData.entries()) {
                coleccionData[key] = value;
            }

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/colecciones.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(coleccionData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    coleccionModal.hide();
                    loadColecciones(); // Reload table
                    if (currentEditId) {
                        CRUDAlerts.updated('Colección');
                    } else {
                        CRUDAlerts.created('Colección');
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

        // Remover imagen
        function removeImageColeccion() {
            document.getElementById('imagen').value = '';
            document.getElementById('imagen_file').value = '';
            document.getElementById('imagenPreview').innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar cambios.</span>';
        }

        // Función de upload usando el patrón estándar del sistema
        async function uploadImagenColeccion(fileInput) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;

            const file = fileInput.files[0];

            // Si no es imagen, subir directamente
            if (!file.type.startsWith('image/')) {
                const fdRaw = new FormData();
                fdRaw.append('imagen', file, file.name);
                const rRaw = await fetch('./api/upload_imagen.php', {method: 'POST', body: fdRaw, credentials: 'same-origin'});
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

            const r = await fetch('./api/upload_imagen.php', {method: 'POST', body: fd, credentials: 'same-origin'});
            const d = await r.json();

            if (d.success) {
                return d.url;
            } else {
                throw new Error(d.error || 'Error al subir imagen');
            }
        }

        // Preview de imagen al seleccionar archivo
        async function previewImagenColeccion(inp) {
            const file = inp.files[0];
            const preview = document.getElementById('imagenPreview');
            const imagenField = document.getElementById('imagen');

            if (!file) {
                preview.innerHTML = '';
                imagenField.value = '';
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
                const uploadedUrl = await uploadImagenColeccion(inp);
                if (uploadedUrl) {
                    imagenField.value = uploadedUrl;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${uploadedUrl}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageColeccion()">
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
                imagenField.value = '';
            }
        }

        // Delete collection
        async function deleteColeccion(id) {
            const result = await CRUDAlerts.confirmDelete('colección');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/colecciones.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadColecciones(); // Reload table
                    CRUDAlerts.deleted('Colección');
                } else {
                    CRUDAlerts.deleteError(data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }
    </script>
</body>
</html>