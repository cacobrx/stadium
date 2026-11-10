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
    <title>Usuarios - Stadium</title>
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
                <h5 class="mb-0 d-inline">Usuarios</h5>
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
                <h2>Gestión de Usuarios</h2>
                <button class="btn btn-primary" onclick="openUserModal()">
                    <i class="bi bi-plus"></i> Nuevo Usuario
                </button>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Apellido</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Nivel</th>
                                    <th>Dashboard</th>
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="clave" name="clave">
                            <small class="text-muted">Dejar vacío para mantener la contraseña actual (solo en edición)</small>
                        </div>
                        <div class="mb-3">
                            <label for="acceso" class="form-label">Acceso</label>
                            <input type="text" class="form-control" id="acceso" name="acceso" placeholder="Permisos de acceso">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nivel" class="form-label">Nivel</label>
                                    <select class="form-control" id="nivel" name="nivel">
                                        <option value="0">Usuario</option>
                                        <option value="1">Administrador</option>
                                        <option value="2">Super Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="idcuit" class="form-label">ID CUIT</label>
                                    <input type="number" class="form-control" id="idcuit" name="idcuit" value="0" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dash" class="form-label">Dashboard</label>
                                    <select class="form-control" id="dash" name="dash">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="avisopago" class="form-label">Aviso Pago</label>
                                    <select class="form-control" id="avisopago" name="avisopago">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.19.0/js/md5.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let userModal;
        let currentEditId = null;

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            userModal = new bootstrap.Modal(document.getElementById('userModal'));
            loadUsers();
        });

        // Load users from API
        async function loadUsers() {
            return new Promise((resolve, reject) => {
                console.log('Cargando usuarios...');
                const xhr = new XMLHttpRequest();
                xhr.open('GET', './api/usuarios.php', true);
                
                xhr.onload = function() {
                    console.log('XHR Response status:', xhr.status);
                    console.log('XHR Response text:', xhr.responseText);
                    
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                renderUsersTable(data.users);
                                resolve(data);
                            } else {
                                console.error('Error loading users:', data.error);
                                alert('Error al cargar usuarios: ' + data.error);
                                reject(data.error);
                            }
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            alert('Error al procesar respuesta del servidor');
                            reject(e);
                        }
                    } else {
                        console.error('HTTP error:', xhr.status);
                        alert('Error de conexión al cargar usuarios');
                        reject('HTTP error: ' + xhr.status);
                    }
                };
                
                xhr.onerror = function() {
                    console.error('XHR Network error');
                    alert('Error de conexión al cargar usuarios');
                    reject('Network error');
                };
                
                xhr.send();
            });
        }

        // Render users table
        function renderUsersTable(users) {
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = '';

            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.apellido || ''}</td>
                    <td>${user.nombre || ''}</td>
                    <td>${user.email || ''}</td>
                    <td>${getNivelText(user.nivel)}</td>
                    <td>${user.dash ? 'Sí' : 'No'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Get nivel text
        function getNivelText(nivel) {
            switch(parseInt(nivel)) {
                case 0: return 'Usuario';
                case 1: return 'Admin';
                case 2: return 'Super Admin';
                default: return 'Usuario';
            }
        }

        // Open user modal for new user
        function openUserModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            userModal.show();
        }

        // Edit user
        async function editUser(id) {
            try {
                const response = await fetch(`./api/usuarios.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const user = data.user;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Usuario';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('apellido').value = user.apellido || '';
                    document.getElementById('nombre').value = user.nombre || '';
                    document.getElementById('email').value = user.email || '';
                    document.getElementById('clave').value = ''; // Don't show password
                    document.getElementById('acceso').value = user.acceso || '';
                    document.getElementById('nivel').value = user.nivel;
                    document.getElementById('idcuit').value = user.idcuit;
                    document.getElementById('dash').value = user.dash;
                    document.getElementById('avisopago').value = user.avisopago;
                    
                    userModal.show();
                } else {
                    alert('Error al cargar usuario: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Save user
        async function saveUser() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const userData = {};
            for (let [key, value] of formData.entries()) {
                userData[key] = value;
            }
            
            // Hash password if provided
            if (userData.clave && userData.clave.trim() !== '') {
                userData.clave = md5(userData.clave);
            } else if (currentEditId) {
                delete userData.clave; // Don't update password if empty on edit
            }

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/usuarios.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    userModal.hide();
                    loadUsers(); // Reload table
                    if (currentEditId) {
                        CRUDAlerts.updated('Usuario');
                    } else {
                        CRUDAlerts.created('Usuario');
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

        // Delete user
        async function deleteUser(id) {
            const result = await CRUDAlerts.confirmDelete('usuario');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/usuarios.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadUsers(); // Reload table
                    CRUDAlerts.deleted('Usuario');
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