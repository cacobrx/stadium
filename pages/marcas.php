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
    <title>Marcas - Stadium</title>
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
                <h5 class="mb-0 d-inline">Marcas</h5>
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
                <h2>Gestión de Marcas</h2>
                <button class="btn btn-primary" onclick="openMarcaModal()">
                    <i class="bi bi-plus"></i> Nueva Marca
                </button>
            </div>

            <!-- Brands Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Marcas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="marcasTable">
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

    <!-- Brand Modal -->
    <div class="modal fade" id="marcaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="marcaForm">
                        <input type="hidden" id="marcaId" name="id">
                        <input type="hidden" id="logoUrl" name="logo">
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

                        <hr>
                        <h6 class="text-primary"><i class="bi bi-image"></i> Imagen y Destacado</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="principal" name="principal" value="1">
                                        <label class="form-check-label" for="principal">
                                            <strong>Marca Principal</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Marcar si esta marca es principal/destacada</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Imagen</label>
                                    <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*" onchange="previewLogoImg(this)">
                                    <div class="form-text">Formatos: JPG, PNG, GIF, WEBP (se convertirá automáticamente a WebP)</div>
                                    <div class="mt-2" id="logoPreview"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Se elimina campo URL; solo se permite subir archivo -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveMarca()">Guardar</button>
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
        let marcaModal;
        let currentEditId = null;

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            marcaModal = new bootstrap.Modal(document.getElementById('marcaModal'));
            loadMarcas();
        });

        // Load brands from API
        async function loadMarcas() {
            try {
                const response = await fetch('./api/marcas.php', { credentials: 'same-origin' });
                const data = await response.json();
                
                if (data.success) {
                    renderMarcasTable(data.marcas);
                } else {
                    console.error('Error loading marcas:', data.error);
                    alert('Error al cargar marcas: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al cargar marcas');
            }
        }

        // Render brands table
        function renderMarcasTable(marcas) {
            const tbody = document.querySelector('#marcasTable tbody');
            tbody.innerHTML = '';

            marcas.forEach(marca => {
                const row = document.createElement('tr');
                let logoDisplay = '-';
                if (marca.logo) {
                    // Si la URL es relativa, usar base path actual
                    let logoUrl = marca.logo;
                    if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                        // Si no tiene protocolo, es una URL relativa
                        const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                        if (logoUrl.startsWith('/')) {
                            logoUrl = logoUrl;
                        } else if (logoUrl.startsWith('uploads/')) {
                            logoUrl = basePath + logoUrl;
                        } else {
                            logoUrl = basePath + logoUrl;
                        }
                    }
                    logoDisplay = `<img src="${logoUrl}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">`;
                }
                
                row.innerHTML = `
                    <td>${marca.id}</td>
                    <td>${logoDisplay}</td>
                    <td>${marca.nombre || ''}</td>
                    <td>${marca.descripcion ? marca.descripcion.substring(0, 50) + (marca.descripcion.length > 50 ? '...' : '') : ''}</td>
                    <td>${marca.principal == 1 ? '<span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                    <td><span class="badge ${marca.activa ? 'bg-success' : 'bg-secondary'}">${marca.activa ? 'Activa' : 'Inactiva'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editMarca(${marca.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMarca(${marca.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open brand modal for new brand
        function openMarcaModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nueva Marca';
            document.getElementById('marcaForm').reset();
            document.getElementById('marcaId').value = '';
            document.getElementById('logoUrl').value = '';
            document.getElementById('activa').value = '1';
            document.getElementById('principal').checked = false;
            // Limpiar preview
            const preview = document.getElementById('logoPreview');
            preview.innerHTML = '';
            marcaModal.show();
        }

        // Edit brand
        async function editMarca(id) {
            try {
                const response = await fetch(`./api/marcas.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const marca = data.marca;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Marca';
                    document.getElementById('marcaId').value = marca.id;
                    document.getElementById('nombre').value = marca.nombre || '';
                    document.getElementById('descripcion').value = marca.descripcion || '';
                    // Cargar logo URL en campo oculto
                    document.getElementById('logoUrl').value = marca.logo || '';
                    // Preview
                    const preview = document.getElementById('logoPreview');
                    if (marca.logo) {
                        // Si la URL es relativa, usar base path actual
                        let logoUrl = marca.logo;
                        if (!logoUrl.startsWith('http://') && !logoUrl.startsWith('https://')) {
                            // Si no tiene protocolo, es una URL relativa
                            const basePath = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';
                            if (logoUrl.startsWith('/')) {
                                logoUrl = logoUrl;
                            } else if (logoUrl.startsWith('uploads/')) {
                                logoUrl = basePath + logoUrl;
                            } else {
                                logoUrl = basePath + logoUrl;
                            }
                        }
                        preview.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <img src="${logoUrl}" alt="Logo actual" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageMarca()">
                                    <i class="bi bi-trash"></i> Quitar
                                </button>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = '';
                    }
                    // Clear file input
                    document.getElementById('logo_file').value = '';
                    document.getElementById('activa').value = marca.activa || 1;
                    document.getElementById('principal').checked = marca.principal == 1;
                    
                    marcaModal.show();
                } else {
                    alert('Error al cargar marca: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Remover imagen
        function removeImageMarca() {
            document.getElementById('logoUrl').value = '';
            document.getElementById('logo_file').value = '';
            document.getElementById('logoPreview').innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar cambios.</span>';
        }

        // Save brand
        async function saveMarca() {
            const form = document.getElementById('marcaForm');
            const formData = new FormData(form);
            
            // Convert to JSON (el logo ya fue subido por uploadImage)
            const marcaData = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'logo_file') { // Omitir el archivo
                    marcaData[key] = value;
                }
            }
            
            // Manejar checkbox principal (si no está marcado, no viene en FormData)
            const principalCheckbox = document.getElementById('principal');
            marcaData.principal = principalCheckbox.checked ? 1 : 0;
            
            // Agregar la URL del logo desde el campo oculto
            const logoUrl = document.getElementById('logoUrl');
            if (logoUrl && logoUrl.value) {
                marcaData.logo = logoUrl.value;
            }

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/marcas.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(marcaData),
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    marcaModal.hide();
                    loadMarcas(); // Reload table
                    if (currentEditId) {
                        CRUDAlerts.updated('Marca');
                    } else {
                        CRUDAlerts.created('Marca');
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

        // Delete brand
        async function deleteMarca(id) {
            const result = await CRUDAlerts.confirmDelete('marca');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/marcas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id }),
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadMarcas(); // Reload table
                    CRUDAlerts.deleted('Marca');
                } else {
                    CRUDAlerts.deleteError(data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Función de upload usando el patrón estándar del sistema
        async function uploadImage(fileInput) {
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

        // Preview de imagen al seleccionar archivo (patrón estándar)
        async function previewLogoImg(inp) {
            const file = inp.files[0];
            const preview = document.getElementById('logoPreview');
            const logoUrlField = document.getElementById('logoUrl');

            if (!file) {
                preview.innerHTML = '';
                logoUrlField.value = '';
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
                const uploadedUrl = await uploadImage(inp);
                if (uploadedUrl) {
                    logoUrlField.value = uploadedUrl;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${uploadedUrl}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImageMarca()">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                }
            } catch (err) {
                console.error('Error upload preview:', err);
                showError('Error al subir imagen: ' + err.message);
                inp.value = '';
                preview.innerHTML = '';
                logoUrlField.value = '';
            }
        }
    </script>
</body>
</html>