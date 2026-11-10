<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos Fiscales - Stadium</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('stadium-theme');
            if (savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
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
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

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
                <h5 class="mb-0 d-inline">Datos Fiscales</h5>
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
                <h2><i class="bi bi-building-check me-2"></i>Datos Fiscales / CUIT</h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="bi bi-plus"></i> Nuevo Registro
                </button>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registros</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="cuitTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Razón Social</th>
                                    <th>Fantasía</th>
                                    <th>CUIT</th>
                                    <th>Cond. IVA</th>
                                    <th>Dirección</th>
                                    <th>Ciudad</th>
                                    <th>Pto. Venta</th>
                                    <th>Inicio Actividad</th>
                                    <th class="text-center">Test</th>
                                    <th class="text-center">Por defecto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuitTbody">
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        <i class="bi bi-hourglass-split"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="cuitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Registro Fiscal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cuitForm">
                        <input type="hidden" id="registroId">

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="razonsocial" class="form-label">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="razonsocial" placeholder="Ej: Mi Empresa S.A." required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cuit" class="form-label">CUIT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cuit" placeholder="XX-XXXXXXXX-X" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fantasia" class="form-label">Nombre de Fantasía</label>
                                <input type="text" class="form-control" id="fantasia" placeholder="Nombre comercial">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="condicioniva" class="form-label">Condición ante IVA</label>
                                <select class="form-select" id="condicioniva">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="Responsable Inscripto">Responsable Inscripto</option>
                                    <option value="Monotributista">Monotributista</option>
                                    <option value="Exento">Exento</option>
                                    <option value="Consumidor Final">Consumidor Final</option>
                                    <option value="No Responsable">No Responsable</option>
                                    <option value="Sujeto No Categorizado">Sujeto No Categorizado</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" placeholder="Calle y número">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="ciudad" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="ciudad" placeholder="Ciudad">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="puntoventa" class="form-label">Punto de Venta</label>
                                <input type="number" class="form-control" id="puntoventa" value="1" min="1" max="99999">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="fechainicio" class="form-label">Fecha Inicio de Actividades</label>
                                <input type="date" class="form-control" id="fechainicio">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="test" value="1">
                                    <label class="form-check-label" for="test">
                                        <strong>Test</strong>
                                    </label>
                                    <div class="form-text">Marcar si es un registro de prueba</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="pordefecto" value="1">
                                    <label class="form-check-label" for="pordefecto">
                                        <strong>Por defecto para facturar</strong>
                                    </label>
                                    <div class="form-text">Este registro se usará por defecto al emitir comprobantes</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveRegistro()">
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
        let cuitModal;
        let currentEditId = null;

        document.addEventListener('DOMContentLoaded', function () {
            loadTheme();
            cuitModal = new bootstrap.Modal(document.getElementById('cuitModal'));
            loadRegistros();
        });

        // ── Cargar tabla ──────────────────────────────────────────────────────
        async function loadRegistros() {
            try {
                const res  = await fetch('./api/cuit.php', { credentials: 'same-origin' });
                const data = await res.json();

                if (data.success) {
                    renderTable(data.registros);
                } else {
                    document.getElementById('cuitTbody').innerHTML =
                        `<tr><td colspan="12" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                document.getElementById('cuitTbody').innerHTML =
                    '<tr><td colspan="12" class="text-center text-danger">Error de conexión</td></tr>';
            }
        }

        function renderTable(registros) {
            const tbody = document.getElementById('cuitTbody');
            if (!registros.length) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">Sin registros. Agregue uno nuevo.</td></tr>';
                return;
            }
            tbody.innerHTML = registros.map(r => `
                <tr>
                    <td>${r.id}</td>
                    <td><strong>${escHtml(r.razonsocial)}</strong></td>
                    <td>${escHtml(r.fantasia)}</td>
                    <td><code>${escHtml(r.cuit)}</code></td>
                    <td>${escHtml(r.condicioniva)}</td>
                    <td>${escHtml(r.direccion)}</td>
                    <td>${escHtml(r.ciudad)}</td>
                    <td class="text-center">${r.puntoventa}</td>
                    <td>${r.fechainicio ? formatDate(r.fechainicio) : '<span class="text-muted">—</span>'}</td>
                    <td class="text-center">${r.test == 1 ? '<span class="badge bg-warning text-dark"><i class="bi bi-flask"></i> Test</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                    <td class="text-center">${r.pordefecto == 1 ? '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editRegistro(${r.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRegistro(${r.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // ── Helpers ───────────────────────────────────────────────────────────
        function escHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const [y, m, d] = dateStr.split('-');
            return `${d}/${m}/${y}`;
        }

        // ── Abrir modal (nuevo) ───────────────────────────────────────────────
        function openModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent  = 'Nuevo Registro Fiscal';
            document.getElementById('cuitForm').reset();
            document.getElementById('registroId').value        = '';
            document.getElementById('puntoventa').value        = '1';
            document.getElementById('test').checked            = false;
            document.getElementById('pordefecto').checked      = false;
            cuitModal.show();
        }

        // ── Editar ────────────────────────────────────────────────────────────
        async function editRegistro(id) {
            try {
                const res  = await fetch(`./api/cuit.php?id=${id}`, { credentials: 'same-origin' });
                const data = await res.json();

                if (!data.success) {
                    Swal.fire('Error', data.error, 'error');
                    return;
                }

                const r = data.registro;
                currentEditId = id;

                document.getElementById('modalTitle').textContent = 'Editar Datos Fiscales';
                document.getElementById('registroId').value    = r.id;
                document.getElementById('razonsocial').value   = r.razonsocial  || '';
                document.getElementById('fantasia').value      = r.fantasia      || '';
                document.getElementById('cuit').value          = r.cuit          || '';
                document.getElementById('condicioniva').value  = r.condicioniva  || '';
                document.getElementById('direccion').value     = r.direccion     || '';
                document.getElementById('ciudad').value        = r.ciudad        || '';
                document.getElementById('puntoventa').value    = r.puntoventa    || 1;
                document.getElementById('fechainicio').value   = r.fechainicio   || '';
                document.getElementById('test').checked        = r.test == 1;
                document.getElementById('pordefecto').checked  = r.pordefecto == 1;

                cuitModal.show();
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }

        // ── Guardar (crear o actualizar) ──────────────────────────────────────
        async function saveRegistro() {
            const razonsocial = document.getElementById('razonsocial').value.trim();
            const cuit        = document.getElementById('cuit').value.trim();

            if (!razonsocial) {
                Swal.fire('Validación', 'La razón social es requerida.', 'warning');
                return;
            }
            if (!cuit) {
                Swal.fire('Validación', 'El CUIT es requerido.', 'warning');
                return;
            }

            const payload = {
                razonsocial,
                cuit,
                fantasia:     document.getElementById('fantasia').value.trim(),
                condicioniva: document.getElementById('condicioniva').value,
                direccion:    document.getElementById('direccion').value.trim(),
                ciudad:       document.getElementById('ciudad').value.trim(),
                puntoventa:   parseInt(document.getElementById('puntoventa').value) || 1,
                fechainicio:  document.getElementById('fechainicio').value || null,
                test:         document.getElementById('test').checked       ? 1 : 0,
                pordefecto:   document.getElementById('pordefecto').checked ? 1 : 0,
            };

            const method = currentEditId ? 'PUT' : 'POST';
            if (currentEditId) payload.id = currentEditId;

            try {
                const res  = await fetch('./api/cuit.php', {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.success) {
                    cuitModal.hide();
                    loadRegistros();
                    currentEditId ? CRUDAlerts.updated('Datos Fiscales') : CRUDAlerts.created('Datos Fiscales');
                } else {
                    currentEditId ? CRUDAlerts.updateError(data.error) : CRUDAlerts.createError(data.error);
                }
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }

        // ── Eliminar ──────────────────────────────────────────────────────────
        async function deleteRegistro(id) {
            const result = await CRUDAlerts.confirmDelete('registro fiscal');
            if (!result.isConfirmed) return;

            try {
                const res  = await fetch('./api/cuit.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id }),
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.success) {
                    loadRegistros();
                    CRUDAlerts.deleted('Registro Fiscal');
                } else {
                    CRUDAlerts.deleteError(data.error);
                }
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }
    </script>
</body>
</html>
