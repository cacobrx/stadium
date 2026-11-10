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
    <title>Slider - Stadium</title>
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
                <h5 class="mb-0 d-inline">Slider Principal</h5>
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
                <h2>Gestión de Slider</h2>
                <button class="btn btn-primary" onclick="openSliderModal()">
                    <i class="bi bi-plus"></i> Nuevo Slide
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Diapositivas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="sliderTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Subtítulo</th>
                                    <th>Orden</th>
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

    <!-- Slider Modal -->
    <div class="modal fade" id="sliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="sliderForm">
                        <input type="hidden" id="sliderId" name="id">
                        <input type="hidden" id="imagenUrl" name="imagen">
                        <input type="hidden" id="imagenMobileUrl" name="imagen_mobile">
                        
                        <div class="row">
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="number" class="form-control" id="orden" name="orden" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subtitulo" class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" id="subtitulo" name="subtitulo">
                        </div>

                        <div class="mb-3">
                            <label for="etiquetas" class="form-label">Etiquetas (separadas por coma)</label>
                            <input type="text" class="form-control" id="etiquetas" name="etiquetas">
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="botontexto" class="form-label">Texto Botón</label>
                                    <input type="text" class="form-control" id="botontexto" name="botontexto">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="botonlink" class="form-label">Link Botón</label>
                                    <input type="text" class="form-control" id="botonlink" name="botonlink">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="botontarget" class="form-label">Abrir en</label>
                                    <select class="form-control" id="botontarget" name="botontarget">
                                        <option value="1">Misma ventana</option>
                                        <option value="2">Nueva ventana</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="activo" class="form-label">Estado</label>
                                    <select class="form-control" id="activo" name="activo">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Imagen Web</label>
                                    <input type="file" class="form-control" id="imagen_file" accept="image/*" onchange="uploadSliderImage(this, 'web')">
                                    <div id="imagenPreview" class="mt-2 text-center"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Imagen Celular</label>
                                    <input type="file" class="form-control" id="imagen_mobile_file" accept="image/*" onchange="uploadSliderImage(this, 'mobile')">
                                    <div id="imagenMobilePreview" class="mt-2 text-center"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveSlider()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Slider Modal -->
    <div class="modal fade" id="viewSliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewSliderContent">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        const API_URL = 'api/slider.php';
        let sliderModal, viewSliderModal;

        document.addEventListener('DOMContentLoaded', function() {
            sliderModal = new bootstrap.Modal(document.getElementById('sliderModal'));
            viewSliderModal = new bootstrap.Modal(document.getElementById('viewSliderModal'));
            loadSlider();
        });

        async function loadSlider() {
            try {
                const response = await fetch(API_URL);
                const result = await response.json();
                
                if (result.success) {
                    const tbody = document.querySelector('#sliderTable tbody');
                    tbody.innerHTML = '';
                    
                    result.data.forEach(item => {
                        const tr = document.createElement('tr');
                        const imgPath = item.imagen ? `uploads/slider/${item.imagen}?t=${Date.now()}` : 'assets/img/no-image.jpg';
                        
                        tr.innerHTML = `
                            <td>${item.id}</td>
                            <td><img src="${imgPath}" style="height: 50px; width: 100px; object-fit: cover; border-radius: 4px;"></td>
                            <td>${item.titulo || '-'}</td>
                            <td>${item.subtitulo || '-'}</td>
                            <td>${item.orden}</td>
                            <td>
                                <span class="badge ${item.activo == 1 ? 'bg-success' : 'bg-danger'}">
                                    ${item.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" title="Ver" onclick="viewSlider(${item.id})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" title="Editar" onclick="editSlider(${item.id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="Eliminar" onclick="deleteSlider(${item.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorAlert('Error', 'Error al cargar los datos');
            }
        }

        async function viewSlider(id) {
            try {
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const item = result.data;
                    const imgPath = item.imagen ? `uploads/slider/${item.imagen}?t=${Date.now()}` : 'assets/img/no-image.jpg';
                    const imgMobilePath = item.imagen_mobile ? `uploads/slider/${item.imagen_mobile}?t=${Date.now()}` : 'assets/img/no-image.jpg';
                    const targetText = item.botontarget == 1 ? 'Misma ventana' : 'Nueva ventana';
                    const activeBadge = item.activo == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                    
                    document.getElementById('viewSliderContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6 mb-4 text-center">
                                <h6>Imagen Web</h6>
                                <img src="${imgPath}" class="img-fluid rounded shadow-sm border" style="max-height: 200px; width: 100%; object-fit: cover;">
                            </div>
                            <div class="col-md-6 mb-4 text-center">
                                <h6>Imagen Celular</h6>
                                <img src="${imgMobilePath}" class="img-fluid rounded shadow-sm border" style="max-height: 200px; width: auto; object-fit: cover;">
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Título:</strong><br>${item.titulo || '<span class="text-muted">Sin título</span>'}</p>
                                <p class="mb-2"><strong>Subtítulo:</strong><br>${item.subtitulo || '<span class="text-muted">Sin subtítulo</span>'}</p>
                                <p class="mb-2"><strong>Etiquetas:</strong><br>${item.etiquetas || '<span class="text-muted">Sin etiquetas</span>'}</p>
                                <p class="mb-2"><strong>Estado:</strong><br>${activeBadge}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Texto Botón:</strong><br>${item.botontexto || '<span class="text-muted">Sin texto</span>'}</p>
                                <p class="mb-2"><strong>Link Botón:</strong><br><span class="text-break">${item.botonlink || '<span class="text-muted">Sin link</span>'}</span></p>
                                <p class="mb-2"><strong>Abrir en:</strong><br>${targetText}</p>
                                <p class="mb-2"><strong>Orden:</strong><br>${item.orden}</p>
                            </div>
                        </div>
                    `;
                    viewSliderModal.show();
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorAlert('Error', 'Error al obtener detalles');
            }
        }

        function openSliderModal() {
            document.getElementById('sliderForm').reset();
            document.getElementById('sliderId').value = '';
            document.getElementById('imagenUrl').value = '';
            document.getElementById('imagenPreview').innerHTML = '';
            document.getElementById('imagenMobileUrl').value = '';
            document.getElementById('imagenMobilePreview').innerHTML = '';
            document.getElementById('modalTitle').textContent = 'Nuevo Slide';
            sliderModal.show();
        }

        async function editSlider(id) {
            try {
                const response = await fetch(`${API_URL}?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const item = result.data;
                    document.getElementById('sliderId').value = item.id;
                    document.getElementById('titulo').value = item.titulo;
                    document.getElementById('subtitulo').value = item.subtitulo;
                    document.getElementById('etiquetas').value = item.etiquetas;
                    document.getElementById('botontexto').value = item.botontexto;
                    document.getElementById('botonlink').value = item.botonlink;
                    document.getElementById('botontarget').value = item.botontarget;
                    document.getElementById('orden').value = item.orden;
                    document.getElementById('activo').value = item.activo;
                    document.getElementById('imagenUrl').value = item.imagen;
                    document.getElementById('imagenMobileUrl').value = item.imagen_mobile || '';
                    
                    if (item.imagen) {
                        document.getElementById('imagenPreview').innerHTML = `<img src="uploads/slider/${item.imagen}?t=${Date.now()}" class="img-fluid rounded border" style="max-height: 150px">`;
                    } else {
                        document.getElementById('imagenPreview').innerHTML = '';
                    }

                    if (item.imagen_mobile) {
                        document.getElementById('imagenMobilePreview').innerHTML = `<img src="uploads/slider/${item.imagen_mobile}?t=${Date.now()}" class="img-fluid rounded border" style="max-height: 150px">`;
                    } else {
                        document.getElementById('imagenMobilePreview').innerHTML = '';
                    }
                    
                    document.getElementById('modalTitle').textContent = 'Editar Slide';
                    sliderModal.show();
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorAlert('Error', 'Error al obtener datos');
            }
        }

        async function uploadSliderImage(input, type = 'web') {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('imagen', input.files[0]);
                
                try {
                    const response = await fetch(`${API_URL}?action=upload`, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        if (type === 'web') {
                            document.getElementById('imagenUrl').value = result.url;
                            document.getElementById('imagenPreview').innerHTML = `<img src="uploads/slider/${result.url}?t=${Date.now()}" class="img-fluid rounded border" style="max-height: 150px">`;
                        } else {
                            document.getElementById('imagenMobileUrl').value = result.url;
                            document.getElementById('imagenMobilePreview').innerHTML = `<img src="uploads/slider/${result.url}?t=${Date.now()}" class="img-fluid rounded border" style="max-height: 150px">`;
                        }
                    } else {
                        showErrorAlert('Error', result.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showErrorAlert('Error', 'Error al subir imagen');
                }
            }
        }

        async function saveSlider() {
            const formData = {
                id: document.getElementById('sliderId').value,
                titulo: document.getElementById('titulo').value,
                subtitulo: document.getElementById('subtitulo').value,
                etiquetas: document.getElementById('etiquetas').value,
                botontexto: document.getElementById('botontexto').value,
                botonlink: document.getElementById('botonlink').value,
                botontarget: document.getElementById('botontarget').value,
                orden: document.getElementById('orden').value,
                activo: document.getElementById('activo').value,
                imagen: document.getElementById('imagenUrl').value,
                imagen_mobile: document.getElementById('imagenMobileUrl').value
            };

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                
                if (result.success) {
                    sliderModal.hide();
                    loadSlider();
                    showSuccessAlert('¡Éxito!', 'Guardado correctamente');
                } else {
                    showErrorAlert('Error', result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorAlert('Error', 'Error al guardar');
            }
        }

        async function deleteSlider(id) {
            const confirmedResult = await showConfirmation('¿Estás seguro?', 'Esta acción no se puede deshacer', 'Sí, eliminar', 'Cancelar');
            if (confirmedResult.isConfirmed) {
                try {
                    const response = await fetch(`${API_URL}?id=${id}`, {
                        method: 'DELETE'
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        loadSlider();
                        showSuccessAlert('Eliminado', 'Eliminado correctamente');
                    } else {
                        showErrorAlert('Error', result.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showErrorAlert('Error', 'Error al eliminar');
                }
            }
        }
    </script>
</body>
</html>
