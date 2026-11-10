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
    <title>Cupones - Stadium</title>
    <script>
        (function() {
            const t = localStorage.getItem('stadium-theme');
            if (t) document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script><?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?></script>
    <style><?php include __DIR__ . '/../includes/dashboard-styles.css'; ?></style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/menu.php'; ?>

<div class="main-content" id="mainContent">
    <div class="top-nav d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña"><i class="bi bi-box-arrow-up-right"></i></button>
            <h5 class="mb-0 d-inline">Cupones de Descuento</h5>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                <i class="bi bi-sun-fill" id="themeIcon"></i>
            </button>
            <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
        </div>
    </div>

    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-ticket-perforated"></i> Cupones</h2>
            <button class="btn btn-primary" onclick="abrirModal()">
                <i class="bi bi-plus"></i> Nuevo Cupón
            </button>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Lista de Cupones</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="cuponesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Cantidad</th>
                                <th>% Descuento</th>
                                <th>Tope ($)</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuponesBody">
                            <tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cupón -->
<div class="modal fade" id="modalCupon" tabindex="-1" aria-labelledby="modalCuponLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCuponLabel">Nuevo Cupón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCupon" novalidate>
                    <input type="hidden" id="cuponId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código *</label>
                            <input type="text" class="form-control text-uppercase" id="codigo" maxlength="50" placeholder="Ej: VERANO20" required>
                            <div class="invalid-feedback">El código es obligatorio.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad (usos disponibles) *</label>
                            <input type="number" class="form-control" id="cantidad" min="1" value="1" required>
                            <div class="invalid-feedback">Ingrese una cantidad válida.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">% de Descuento *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="porcentaje_descuento" min="0" max="100" step="0.01" placeholder="Ej: 15" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="invalid-feedback">Ingrese un porcentaje válido (0–100).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tope de Descuento (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="tope_descuento" min="0" step="0.01" placeholder="Sin tope">
                            </div>
                            <small class="text-muted">Monto máximo a descontar. Dejar vacío para sin límite.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio de Vigencia *</label>
                            <input type="date" class="form-control" id="fecha_inicio" required>
                            <div class="invalid-feedback">La fecha de inicio es obligatoria.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Fin de Vigencia *</label>
                            <input type="date" class="form-control" id="fecha_fin" required>
                            <div class="invalid-feedback">La fecha de fin es obligatoria.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarCupon()">
                    <i class="bi bi-check-lg"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script><?php include __DIR__ . '/../includes/dashboard-functions.js'; ?></script>
<script>
let modalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    modalInstance = new bootstrap.Modal(document.getElementById('modalCupon'));
    cargarCupones();
});

async function cargarCupones() {
    try {
        const res  = await fetch('./api/cupones.php');
        const data = await res.json();
        renderTabla(data);
    } catch (e) {
        document.getElementById('cuponesBody').innerHTML =
            '<tr><td colspan="9" class="text-center text-danger">Error al cargar cupones</td></tr>';
    }
}

function renderTabla(cupones) {
    const tbody = document.getElementById('cuponesBody');
    if (!cupones.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No hay cupones registrados</td></tr>';
        return;
    }
    const hoy = new Date().toISOString().split('T')[0];
    tbody.innerHTML = cupones.map(c => {
        const vigente = c.activo == 1 && c.fecha_inicio <= hoy && c.fecha_fin >= hoy;
        const estadoBadge = vigente
            ? '<span class="badge bg-success">Vigente</span>'
            : (c.activo == 0
                ? '<span class="badge bg-secondary">Inactivo</span>'
                : '<span class="badge bg-warning text-dark">Vencido</span>');
        const tope = c.tope_descuento ? `$${parseFloat(c.tope_descuento).toFixed(2)}` : '<span class="text-muted">Sin tope</span>';
        return `
            <tr>
                <td>${c.id}</td>
                <td><code class="fs-6">${c.codigo}</code></td>
                <td class="text-center">${c.cantidad}</td>
                <td class="text-center"><strong>${parseFloat(c.porcentaje_descuento).toFixed(0)}%</strong></td>
                <td class="text-end">${tope}</td>
                <td>${formatFecha(c.fecha_inicio)}</td>
                <td>${formatFecha(c.fecha_fin)}</td>
                <td>${estadoBadge}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editarCupon(${c.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarCupon(${c.id}, '${c.codigo}')" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`;
    }).join('');
}

function formatFecha(f) {
    if (!f) return '-';
    const [y, m, d] = f.split('-');
    return `${d}/${m}/${y}`;
}

function abrirModal(cupon = null) {
    const form = document.getElementById('formCupon');
    form.classList.remove('was-validated');

    document.getElementById('cuponId').value           = cupon ? cupon.id : '';
    document.getElementById('codigo').value            = cupon ? cupon.codigo : '';
    document.getElementById('cantidad').value          = cupon ? cupon.cantidad : 1;
    document.getElementById('porcentaje_descuento').value = cupon ? cupon.porcentaje_descuento : '';
    document.getElementById('tope_descuento').value    = cupon ? (cupon.tope_descuento ?? '') : '';
    document.getElementById('fecha_inicio').value      = cupon ? cupon.fecha_inicio : '';
    document.getElementById('fecha_fin').value         = cupon ? cupon.fecha_fin : '';
    document.getElementById('activo').value            = cupon ? cupon.activo : 1;

    document.getElementById('modalCuponLabel').textContent = cupon ? 'Editar Cupón' : 'Nuevo Cupón';
    modalInstance.show();
}

async function editarCupon(id) {
    try {
        const res   = await fetch(`./api/cupones.php?id=${id}`);
        const cupon = await res.json();
        abrirModal(cupon);
    } catch (e) {
        Swal.fire('Error', 'No se pudo cargar el cupón', 'error');
    }
}

async function guardarCupon() {
    const form = document.getElementById('formCupon');
    form.classList.add('was-validated');
    if (!form.checkValidity()) return;

    const id = document.getElementById('cuponId').value;
    const payload = {
        codigo:               document.getElementById('codigo').value.toUpperCase().trim(),
        cantidad:             parseInt(document.getElementById('cantidad').value),
        porcentaje_descuento: parseFloat(document.getElementById('porcentaje_descuento').value),
        tope_descuento:       document.getElementById('tope_descuento').value || null,
        fecha_inicio:         document.getElementById('fecha_inicio').value,
        fecha_fin:            document.getElementById('fecha_fin').value,
        activo:               parseInt(document.getElementById('activo').value)
    };

    if (payload.fecha_fin < payload.fecha_inicio) {
        Swal.fire('Error', 'La fecha de fin no puede ser anterior a la de inicio', 'error');
        return;
    }

    const url    = id ? `./api/cupones.php?id=${id}` : './api/cupones.php';
    const method = id ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, { method, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Error', data.error || 'Error al guardar', 'error'); return; }
        modalInstance.hide();
        if (id) CRUDAlerts.updated('Cupón'); else CRUDAlerts.created('Cupón');
        cargarCupones();
    } catch (e) {
        CRUDAlerts.connectionError();
    }
}

async function eliminarCupon(id, codigo) {
    const result = await Swal.fire({
        title: '¿Eliminar cupón?',
        html: `El cupón <strong>${codigo}</strong> será eliminado permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        const res  = await fetch(`./api/cupones.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (!res.ok) { Swal.fire('Error', data.error || 'Error al eliminar', 'error'); return; }
        CRUDAlerts.deleted('Cupón');
        cargarCupones();
    } catch (e) {
        CRUDAlerts.connectionError();
    }
}
</script>
</body>
</html>
