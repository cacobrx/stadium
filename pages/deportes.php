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
    <title>Deportes - Stadium</title>
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
                <h5 class="mb-0 d-inline">Deportes</h5>
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
                <h2>Gestión de Deportes</h2>
                <button class="btn btn-primary" onclick="openDeporteModal()">
                    <i class="bi bi-plus"></i> Nuevo Deporte
                </button>
            </div>

            <!-- Sports Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Deportes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="deportesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Principal</th>
                                    <th>Activo</th>
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

    <!-- Sport Modal -->
    <div class="modal fade" id="deporteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Deporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deporteForm">
                        <input type="hidden" id="deporteId" name="id">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="activo" class="form-label">Estado</label>
                                    <select class="form-control" id="activo" name="activo">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
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
                                        <input class="form-check-input" type="checkbox" id="principal" name="principal">
                                        <label class="form-check-label" for="principal">
                                            <strong>Deporte Principal</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Marcar si este deporte es principal/destacado</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="imagenFile" class="form-label">Imagen del Deporte</label>
                                    <input type="file" class="form-control" id="imagenFile" name="imagenFile" accept="image/*">
                                    <input type="hidden" id="imagen" name="imagen">
                                    <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                                    <div id="imagenPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveDeporte()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let deporteModal;
        let currentEditId = null;

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            deporteModal = new bootstrap.Modal(document.getElementById('deporteModal'));
            loadDeportes();
        });

        // Load sports from API
        async function loadDeportes() {
            try {
                const response = await fetch('./api/deportes.php');
                const data = await response.json();
                
                if (data.success) {
                    renderDeportesTable(data.deportes);
                } else {
                    console.error('Error loading deportes:', data.error);
                    alert('Error al cargar deportes: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión al cargar deportes');
            }
        }

        // Render sports table
        function renderDeportesTable(deportes) {
            const tbody = document.querySelector('#deportesTable tbody');
            tbody.innerHTML = '';

            deportes.forEach(deporte => {
                const row = document.createElement('tr');
                
                const imagenHtml = deporte.imagen 
                    ? `<img src="./uploads/deportes/${deporte.imagen}" alt="Imagen" style="max-width: 50px; max-height: 50px;" class="rounded">` 
                    : '<span class="text-muted">-</span>';
                
                const principalBadge = deporte.principal == 1 
                    ? '<span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Sí</span>' 
                    : '<span class="badge bg-secondary">No</span>';
                
                row.innerHTML = `
                    <td>${deporte.id}</td>
                    <td>${imagenHtml}</td>
                    <td>${deporte.nombre || ''}</td>
                    <td>${deporte.descripcion ? deporte.descripcion.substring(0, 50) + (deporte.descripcion.length > 50 ? '...' : '') : ''}</td>
                    <td>${principalBadge}</td>
                    <td><span class="badge ${deporte.activo ? 'bg-success' : 'bg-secondary'}">${deporte.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editDeporte(${deporte.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDeporte(${deporte.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open sport modal for new sport
        function openDeporteModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nuevo Deporte';
            document.getElementById('deporteForm').reset();
            document.getElementById('deporteId').value = '';
            document.getElementById('activo').value = '1';
            document.getElementById('principal').checked = false;
            document.getElementById('imagen').value = '';
            document.getElementById('imagenFile').value = '';
            document.getElementById('imagenPreview').innerHTML = '';
            deporteModal.show();
        }
        
        // Preview de imagen
        document.getElementById('imagenFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagenPreview');
            
            if (file) {
                // Validar tamaño antes de mostrar preview
                if (file.size > 5 * 1024 * 1024) {
                    preview.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> El archivo es demasiado grande. Máximo 5MB</span>';
                    e.target.value = '';
                    return;
                }
                
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
            } else {
                preview.innerHTML = '';
            }
        });
        
        // Remover imagen
        function removeImage() {
            document.getElementById('imagen').value = '';
            document.getElementById('imagenFile').value = '';
            document.getElementById('imagenPreview').innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar cambios.</span>';
        }

        // Edit sport
        async function editDeporte(id) {
            try {
                const response = await fetch(`./api/deportes.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const deporte = data.deporte;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Deporte';
                    document.getElementById('deporteId').value = deporte.id;
                    document.getElementById('nombre').value = deporte.nombre || '';
                    document.getElementById('descripcion').value = deporte.descripcion || '';
                    document.getElementById('activo').value = deporte.activo || 1;
                    
                    // Campos de principal e imagen
                    document.getElementById('principal').checked = deporte.principal == 1;
                    document.getElementById('imagen').value = deporte.imagen || '';
                    document.getElementById('imagenFile').value = '';
                    
                    // Preview de imagen existente
                    const preview = document.getElementById('imagenPreview');
                    if (deporte.imagen) {
                        preview.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <img src="./uploads/deportes/${deporte.imagen}" alt="Imagen actual" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                    <i class="bi bi-trash"></i> Quitar
                                </button>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = '';
                    }
                    
                    deporteModal.show();
                } else {
                    alert('Error al cargar deporte: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Save sport
        async function saveDeporte() {
            const form = document.getElementById('deporteForm');
            const fileInput = document.getElementById('imagenFile');
            
            // Si hay una imagen nueva, primero subirla
            let imagenFilename = document.getElementById('imagen').value;
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Subiendo imagen...',
                    html: `
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mb-2">Procesando imagen (${fileSizeMB} MB)</p>
                            <small class="text-muted">Convirtiendo a formato WebP para optimizar...</small>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const imageFormData = new FormData();
                imageFormData.append('imagen', file);
                
                try {
                    const uploadResponse = await fetch('./api/deportes.php?action=upload', {
                        method: 'POST',
                        body: imageFormData,
                        credentials: 'same-origin'
                    });
                    const uploadData = await uploadResponse.json();
                    
                    if (uploadData.success) {
                        imagenFilename = uploadData.filename;
                        // Cerrar el loader
                        Swal.close();
                    } else {
                        Swal.close();
                        CRUDAlerts.createError('Error al subir imagen: ' + uploadData.error);
                        return;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.close();
                    CRUDAlerts.connectionError();
                    return;
                }
            }
            
            const formData = new FormData(form);
            
            // Convert to JSON
            const deporteData = {};
            for (let [key, value] of formData.entries()) {
                if (key === 'imagenFile') continue; // Skip file input
                deporteData[key] = value;
            }
            
            // Agregar campos de principal e imagen
            deporteData.principal = document.getElementById('principal').checked ? 1 : 0;
            deporteData.imagen = imagenFilename || null;

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/deportes.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(deporteData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    deporteModal.hide();
                    loadDeportes(); // Reload table
                    if (currentEditId) {
                        CRUDAlerts.updated('Deporte');
                    } else {
                        CRUDAlerts.created('Deporte');
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

        // Delete sport
        async function deleteDeporte(id) {
            const result = await CRUDAlerts.confirmDelete('deporte');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/deportes.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadDeportes(); // Reload table
                    CRUDAlerts.deleted('Deporte');
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