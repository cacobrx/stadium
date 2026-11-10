<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrada de Artículos - Stadium</title>
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

        .entrada-row { cursor: pointer; transition: background-color .2s; }
        .entrada-row:hover { background-color: var(--bs-light) !important; }

        .expand-icon { transition: transform .3s; }
        .expanded .expand-icon { transform: rotate(90deg); }

        .detalle-container { max-height: 0; overflow: hidden; transition: max-height .3s ease-out; opacity: 0; }
        .detalle-container.show { max-height: 600px; opacity: 1; }

        .detalle-row { background-color: #f8f9fa; border-left: 3px solid #198754; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline">Entrada de Artículos</h5>
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
                <h2>Entrada de Artículos</h2>
                <a href="index.php?page=entradas_form" class="btn btn-success">
                    <i class="bi bi-plus"></i> Nueva Entrada
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Entradas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="input-group" style="max-width:400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="filtro" placeholder="Buscar por proveedor, fecha..." onkeyup="filtrarEntradas()">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaEntradas">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50px"><i class="bi bi-chevron-right"></i></th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th class="text-center">Cant. Artículos</th>
                                    <th class="text-center">Unidades</th>
                                    <th>Notas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let entradas    = [];
        let proveedores = [];
        let articulos   = [];

        document.addEventListener('DOMContentLoaded', function () {
            Promise.all([cargarProveedores(), cargarArticulos()]).then(() => cargarEntradas());
        });

        async function cargarProveedores() {
            try {
                const r = await fetch('./api/proveedores.php');
                proveedores = await r.json();
            } catch (e) { console.error('Error cargando proveedores:', e); }
        }

        async function cargarArticulos() {
            try {
                const r    = await fetch('./api/inventario.php');
                const data = await r.json();
                if (data.success) articulos = data.inventario;
            } catch (e) { console.error('Error cargando inventario:', e); }
        }

        async function cargarEntradas() {
            try {
                const r  = await fetch('./api/entradas.php');
                entradas = await r.json();
                mostrarEntradas();
            } catch (e) {
                console.error('Error cargando entradas:', e);
                CRUDAlerts.connectionError();
            }
        }

        function nombreProveedor(id) {
            const p = proveedores.find(p => p.id == id);
            return p ? (p.razonsocial || p.contacto || `Proveedor #${p.id}`) : 'Desconocido';
        }

        function nombreArticulo(idart) {
            const item = articulos.find(a => a.id == idart);
            if (!item) return `Variante ID: ${idart}`;
            let n = item.producto_nombre;
            if (item.atributos && item.atributos.length) {
                n += ' - ' + item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
            }
            if (item.sku_variante) n += ` [${item.sku_variante}]`;
            return n;
        }

        function mostrarEntradas() {
            const tbody = document.getElementById('cuerpoTabla');
            tbody.innerHTML = '';

            entradas.forEach(entrada => {
                const pNombre  = nombreProveedor(entrada.proveedor);
                const cantArt  = entrada.cantidad_items  || 0;
                const totalUnd = entrada.total_unidades  || 0;
                const notas    = entrada.notas ? entrada.notas.substring(0, 40) + (entrada.notas.length > 40 ? '…' : '') : '-';

                const tr = document.createElement('tr');
                tr.className = 'entrada-row';
                tr.id = `entrada-${entrada.id}`;
                tr.innerHTML = `
                    <td class="text-center"><i class="bi bi-chevron-right expand-icon" id="icon-${entrada.id}"></i></td>
                    <td>${entrada.id}</td>
                    <td>${new Date(entrada.fecha).toLocaleDateString()}</td>
                    <td>${pNombre}</td>
                    <td class="text-center"><span class="badge bg-success">${cantArt}</span></td>
                    <td class="text-center"><span class="badge bg-secondary">${totalUnd}</span></td>
                    <td class="text-muted small">${notas}</td>
                    <td>
                        <a href="index.php?page=entradas_form&id=${entrada.id}" class="btn btn-sm btn-warning me-1" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="eliminarEntrada(${entrada.id})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tr.onclick = (e) => { if (!e.target.closest('.btn, a')) toggleDetalle(entrada.id); };
                tbody.appendChild(tr);

                // Fila de detalle
                const det = document.createElement('tr');
                det.id = `detalle-${entrada.id}`;
                det.innerHTML = `
                    <td colspan="8">
                        <div class="detalle-container" id="detalle-container-${entrada.id}">
                            <div class="p-3 detalle-row">
                                <h6><i class="bi bi-list-ul"></i> Detalle de la Entrada #${entrada.id}</h6>
                                <div id="detalle-items-${entrada.id}">
                                    <div class="text-center text-muted">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Cargando detalles...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(det);
            });
        }

        async function toggleDetalle(id) {
            const container = document.getElementById(`detalle-container-${id}`);
            const icon = document.getElementById(`icon-${id}`);
            const row  = document.getElementById(`entrada-${id}`);

            if (!container.classList.contains('show')) {
                container.classList.add('show');
                icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
                row.classList.add('expanded');
                await cargarDetalleEntrada(id);
            } else {
                container.classList.remove('show');
                icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
                row.classList.remove('expanded');
            }
        }

        async function cargarDetalleEntrada(id) {
            try {
                const r       = await fetch(`./api/entradas.php?detalle=${id}`);
                const dets    = await r.json();
                const container = document.getElementById(`detalle-items-${id}`);

                if (!Array.isArray(dets) || dets.length === 0) {
                    container.innerHTML = '<div class="text-muted">No hay ítems en esta entrada.</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr><th>Artículo</th><th class="text-center">Cantidad</th></tr>
                            </thead>
                            <tbody>
                `;
                dets.forEach(d => {
                    html += `<tr><td>${nombreArticulo(d.idart)}</td><td class="text-center">${d.cantidad}</td></tr>`;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            } catch (e) {
                document.getElementById(`detalle-items-${id}`).innerHTML =
                    '<div class="text-danger">Error al cargar detalles.</div>';
            }
        }

        function filtrarEntradas() {
            const filtro = document.getElementById('filtro').value.toLowerCase();
            document.querySelectorAll('#cuerpoTabla tr.entrada-row').forEach(fila => {
                const visible = fila.textContent.toLowerCase().includes(filtro);
                fila.style.display = visible ? '' : 'none';
                const id  = fila.id.replace('entrada-', '');
                const det = document.getElementById(`detalle-${id}`);
                if (det) det.style.display = visible ? '' : 'none';
            });
        }

        async function eliminarEntrada(id) {
            const result = await CRUDAlerts.confirmDelete('entrada');
            if (!result.isConfirmed) return;
            try {
                const r    = await fetch(`./api/entradas.php?id=${id}`, { method: 'DELETE' });
                const data = await r.json();
                if (data.success) {
                    CRUDAlerts.deleted('Entrada');
                    cargarEntradas();
                } else {
                    CRUDAlerts.deleteError(data.message);
                }
            } catch (e) {
                CRUDAlerts.connectionError();
            }
        }
    </script>
</body>
</html>
