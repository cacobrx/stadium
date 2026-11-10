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
    <title>Tablas - Stadium</title>
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
        .table-selector {
            background: var(--bs-light);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        [data-bs-theme="dark"] .table-selector {
            background: var(--bs-dark);
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
                <h5 class="mb-0 d-inline">Tablas</h5>
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
                <h2>Gestión de Tablas</h2>
                <button class="btn btn-primary" onclick="openTableModal()">
                    <i class="bi bi-plus"></i> Nueva Tabla
                </button>
            </div>

            <!-- Table Type Selector -->
            <div class="table-selector">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="tipoTabla" class="form-label fw-bold">Seleccionar tipo de tabla:</label>
                        <select class="form-select" id="tipoTabla" onchange="loadTablesByType()">
                            <option value="">-- Seleccione un tipo --</option>
                        </select>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" onclick="openTableModal()" id="btnNuevoRegistro" style="display: none;">
                            <i class="bi bi-plus"></i> Nuevo Registro
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tables List -->
            <div class="card" id="tablesCard" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0" id="cardTitle">Registros de Tabla</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="tablesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Valor</th>
                                    <th>Orden</th>
                                    <th>Valor C</th>
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

    <!-- Table Modal -->
    <div class="modal fade" id="tableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nueva Tabla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tableForm">
                        <input type="hidden" id="tableId" name="id">
                        <div class="mb-3">
                            <label for="codtab" class="form-label">Código de Tabla *</label>
                            <input type="text" class="form-control" id="codtab" name="codtab" maxlength="3" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción *</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" maxlength="100" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="valor" class="form-label">Valor</label>
                                    <input type="number" class="form-control" id="valor" name="valor">
                                    <small class="text-muted">Dejar vacío para NULL</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="number" class="form-control" id="orden" name="orden">
                                    <small class="text-muted">Dejar vacío para NULL</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="valorc" class="form-label">Valor C</label>
                                    <input type="text" class="form-control" id="valorc" name="valorc" maxlength="20">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveTable()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let tableModal;
        let currentEditId = null;
        let currentSelectedType = '';

        // Initialize modal
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            tableModal = new bootstrap.Modal(document.getElementById('tableModal'));
            loadTableDefinitions();
        });

        // Load table definitions (codtab='DEF')
        async function loadTableDefinitions() {
            try {
                const response = await fetch('./api/tablas.php?definiciones=1');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('tipoTabla');
                    select.innerHTML = '<option value="">-- Seleccione un tipo --</option>';
                    
                    data.definiciones.forEach(def => {
                        const option = document.createElement('option');
                        option.value = def.valorc;
                        option.textContent = def.descripcion;
                        select.appendChild(option);
                    });
                } else {
                    console.error('Error loading definitions:', data.error);
                    alert('Error al cargar definiciones de tablas: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Load tables by type
        async function loadTablesByType() {
            const tipo = document.getElementById('tipoTabla').value;
            currentSelectedType = tipo;
            
            if (!tipo) {
                document.getElementById('tablesCard').style.display = 'none';
                document.getElementById('btnNuevoRegistro').style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`./api/tablas.php?tipo=${encodeURIComponent(tipo)}`);
                const data = await response.json();
                
                if (data.success) {
                    renderTablesTable(data.registros);
                    document.getElementById('cardTitle').textContent = `Registros de ${tipo}`;
                    document.getElementById('tablesCard').style.display = 'block';
                    document.getElementById('btnNuevoRegistro').style.display = 'inline-block';
                } else {
                    console.error('Error loading tables:', data.error);
                    alert('Error al cargar registros: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Render tables table
        function renderTablesTable(tables) {
            const tbody = document.querySelector('#tablesTable tbody');
            tbody.innerHTML = '';

            tables.forEach(table => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${table.id}</td>
                    <td>${table.codtab || ''}</td>
                    <td>${table.descripcion || ''}</td>
                    <td>${table.valor || ''}</td>
                    <td>${table.orden || ''}</td>
                    <td>${table.valorc || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editTable(${table.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTable(${table.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open table modal for new table
        function openTableModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Nueva Tabla';
            document.getElementById('tableForm').reset();
            document.getElementById('tableId').value = '';
            
            // Si hay un tipo seleccionado, pre-llenar el código
            if (currentSelectedType) {
                document.getElementById('codtab').value = currentSelectedType;
            }
            
            tableModal.show();
        }

        // Edit table
        async function editTable(id) {
            try {
                const response = await fetch(`./api/tablas.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const table = data.tabla;
                    currentEditId = id;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Tabla';
                    document.getElementById('tableId').value = table.id;
                    document.getElementById('codtab').value = table.codtab || '';
                    document.getElementById('descripcion').value = table.descripcion || '';
                    document.getElementById('valor').value = table.valor || '';
                    document.getElementById('orden').value = table.orden || '';
                    document.getElementById('valorc').value = table.valorc || '';
                    
                    tableModal.show();
                } else {
                    alert('Error al cargar tabla: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                CRUDAlerts.connectionError();
            }
        }

        // Save table
        async function saveTable() {
            const form = document.getElementById('tableForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const tableData = {};
            for (let [key, value] of formData.entries()) {
                if (value.trim() === '') {
                    if (key === 'valor' || key === 'orden') {
                        tableData[key] = null;
                    } else if (key !== 'id') {
                        tableData[key] = key === 'codtab' || key === 'descripcion' ? value : value || null;
                    }
                } else {
                    if (key === 'valor' || key === 'orden') {
                        tableData[key] = parseInt(value);
                    } else {
                        tableData[key] = value;
                    }
                }
            }

            try {
                const method = currentEditId ? 'PUT' : 'POST';
                const response = await fetch('./api/tablas.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(tableData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    tableModal.hide();
                    if (currentSelectedType) {
                        loadTablesByType(); // Reload current view
                    }
                    loadTableDefinitions(); // Reload definitions in case it was a DEF record
                    
                    if (currentEditId) {
                        CRUDAlerts.updated('Tabla');
                    } else {
                        CRUDAlerts.created('Tabla');
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

        // Delete table
        async function deleteTable(id) {
            const result = await CRUDAlerts.confirmDelete('tabla');

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch('./api/tablas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (currentSelectedType) {
                        loadTablesByType(); // Reload current view
                    }
                    loadTableDefinitions(); // Reload definitions in case it was a DEF record
                    CRUDAlerts.deleted('Tabla');
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