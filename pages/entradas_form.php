<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$idEntrada = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $idEntrada ? 'Editar Entrada' : 'Nueva Entrada'; ?> - Stadium</title>
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

        .agregar-section {
            background-color: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: .5rem;
            padding: 1rem;
        }

        #articulosTabla tbody tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <a href="index.php?page=entradas" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <h5 class="mb-0"><?php echo $idEntrada ? 'Editar Entrada #' . $idEntrada : 'Nueva Entrada'; ?></h5>
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
            <form id="formEntrada">
                <input type="hidden" id="entradaId" value="<?php echo $idEntrada ?? ''; ?>">

                <!-- Cabecera -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-box-arrow-in-down me-2"></i>
                            <?php echo $idEntrada ? 'Editar Entrada #' . $idEntrada : 'Nueva Entrada de Artículos'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Proveedor *</label>
                                <select class="form-select" id="proveedor" required>
                                    <option value="">Seleccionar proveedor...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Total unidades</label>
                                <input type="text" class="form-control fw-bold text-success" id="totalUnidades" value="0" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notas</label>
                                <textarea class="form-control" id="notas" rows="2" placeholder="Observaciones opcionales..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agregar artículo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Agregar Artículo</h5>
                    </div>
                    <div class="card-body agregar-section">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Artículo</label>
                                <select class="form-select" id="articuloSelect">
                                    <option value="">Seleccionar artículo del inventario...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control" id="cantidadInput" min="1" value="1">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-success w-100" onclick="agregarArticulo()" title="Agregar">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de artículos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Artículos de la Entrada</h5>
                        <span class="badge bg-success" id="cantidadItems">0 ítems</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="articulosTabla">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Artículo</th>
                                        <th class="text-center" style="width:150px">Cantidad</th>
                                        <th class="text-center" style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoArticulos">
                                    <tr id="filaVacia">
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                            No hay artículos agregados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="index.php?page=entradas" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success px-4" id="btnGuardar">
                        <i class="bi bi-floppy me-1"></i> Guardar Entrada
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let articulos      = [];
        let articulosForm  = [];   // lista que se enviará al API
        const entradaId    = document.getElementById('entradaId').value;

        document.addEventListener('DOMContentLoaded', async function () {
            loadTheme();
            document.getElementById('fecha').value = new Date().toISOString().split('T')[0];

            await Promise.all([cargarProveedores(), cargarArticulos()]);

            if (entradaId) await cargarEntrada(entradaId);
        });

        // ── Carga datos de combos ────────────────────────────────────────────

        async function cargarProveedores() {
            try {
                const r = await fetch('./api/proveedores.php');
                const proveedores = await r.json();
                const sel = document.getElementById('proveedor');
                sel.innerHTML = '<option value="">Seleccionar proveedor...</option>';
                proveedores.forEach(p => {
                    const nombre = p.razonsocial || p.contacto || `Proveedor #${p.id}`;
                    sel.innerHTML += `<option value="${p.id}">${nombre}</option>`;
                });
            } catch (e) { console.error('Error cargando proveedores:', e); }
        }

        async function cargarArticulos() {
            try {
                const r    = await fetch('./api/inventario.php');
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const data = await r.json();
                if (!data.success) throw new Error('Error en API inventario');

                articulos = data.inventario;
                const sel = document.getElementById('articuloSelect');
                sel.innerHTML = '<option value="">Seleccionar artículo...</option>';

                articulos.forEach(item => {
                    let nombre = item.producto_nombre;
                    if (item.atributos && item.atributos.length) {
                        nombre += ' - ' + item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                    }
                    if (item.sku_variante) nombre += ` [${item.sku_variante}]`;

                    const stock     = parseInt(item.stock || 0);
                    const stockTxt  = stock > 0 ? ` (Stock: ${stock})` : ' (Sin stock)';
                    const sinStock  = stock <= 0;

                    sel.innerHTML += `<option value="${item.id}" data-stock="${stock}" ${sinStock ? 'style="color:red;"' : ''}>${nombre}${stockTxt}</option>`;
                });
            } catch (e) {
                console.error('Error cargando inventario:', e);
                document.getElementById('articuloSelect').innerHTML = '<option value="">Error al cargar inventario</option>';
            }
        }

        // Colorear el select cuando el ítem tiene stock 0
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('articuloSelect').addEventListener('change', function () {
                const opt   = this.selectedOptions[0];
                const stock = opt && opt.value ? parseInt(opt.dataset.stock || 0) : 1;
                this.style.color = (stock <= 0) ? 'red' : '';
            });
        });

        // ── Cargar entrada existente ─────────────────────────────────────────

        async function cargarEntrada(id) {
            try {
                const [rEntrada, rDet] = await Promise.all([
                    fetch(`./api/entradas.php?id=${id}`),
                    fetch(`./api/entradas.php?detalle=${id}`)
                ]);

                const entrada  = await rEntrada.json();
                const detalles = await rDet.json();

                if (entrada.error) throw new Error(entrada.error);

                document.getElementById('fecha').value     = entrada.fecha;
                document.getElementById('proveedor').value = entrada.proveedor;
                document.getElementById('notas').value     = entrada.notas || '';

                articulosForm = [];
                if (Array.isArray(detalles)) {
                    detalles.forEach(d => {
                        const item = articulos.find(a => a.id == d.idart);
                        let nombre = item ? (() => {
                            let n = item.producto_nombre;
                            if (item.atributos && item.atributos.length) n += ' - ' + item.atributos.map(a => `${a.nombre}: ${a.valor}`).join(', ');
                            if (item.sku_variante) n += ` [${item.sku_variante}]`;
                            return n;
                        })() : `Variante ID: ${d.idart}`;

                        articulosForm.push({
                            idart:    parseInt(d.idart),
                            nombre:   nombre,
                            cantidad: parseInt(d.cantidad)
                        });
                    });
                }
                actualizarTabla();
            } catch (e) {
                console.error('Error cargando entrada:', e);
                CRUDAlerts.connectionError();
            }
        }

        // ── Agregar artículo a la lista local ────────────────────────────────

        function agregarArticulo() {
            const sel      = document.getElementById('articuloSelect');
            const cantidad = parseInt(document.getElementById('cantidadInput').value) || 0;

            if (!sel.value || cantidad <= 0) {
                Swal.fire('Error', 'Debe seleccionar un artículo e ingresar una cantidad válida.', 'error');
                return;
            }

            // Si ya existe, sumar cantidades
            const existing = articulosForm.find(a => a.idart == parseInt(sel.value));
            if (existing) {
                existing.cantidad += cantidad;
            } else {
                articulosForm.push({
                    idart:    parseInt(sel.value),
                    nombre:   sel.selectedOptions[0].text,
                    cantidad: cantidad
                });
            }

            actualizarTabla();
            sel.value = '';
            sel.style.color = '';
            document.getElementById('cantidadInput').value = '1';
        }

        function quitarArticulo(idx) {
            articulosForm.splice(idx, 1);
            actualizarTabla();
        }

        function actualizarTabla() {
            const tbody    = document.getElementById('cuerpoArticulos');
            const filaVacia = document.getElementById('filaVacia');
            let totalUnd   = 0;

            tbody.querySelectorAll('tr.fila-art').forEach(r => r.remove());

            if (articulosForm.length === 0) {
                filaVacia.style.display = '';
            } else {
                filaVacia.style.display = 'none';
                articulosForm.forEach((a, idx) => {
                    totalUnd += a.cantidad;
                    const tr = document.createElement('tr');
                    tr.className = 'fila-art';
                    tr.innerHTML = `
                        <td>${a.nombre}</td>
                        <td class="text-center">${a.cantidad}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarArticulo(${idx})" title="Quitar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('totalUnidades').value = totalUnd;
            document.getElementById('cantidadItems').textContent = `${articulosForm.length} ítem${articulosForm.length !== 1 ? 's' : ''}`;
        }

        // ── Guardar ──────────────────────────────────────────────────────────

        document.getElementById('formEntrada').addEventListener('submit', async function (e) {
            e.preventDefault();

            if (articulosForm.length === 0) {
                Swal.fire('Error', 'Debe agregar al menos un artículo a la entrada.', 'error');
                return;
            }

            const datos = {
                fecha:     document.getElementById('fecha').value,
                proveedor: parseInt(document.getElementById('proveedor').value),
                notas:     document.getElementById('notas').value.trim() || null,
                articulos: articulosForm.map(a => ({ idart: a.idart, cantidad: a.cantidad }))
            };

            const method = entradaId ? 'PUT' : 'POST';
            const url    = entradaId ? `./api/entradas.php?id=${entradaId}` : './api/entradas.php';

            const btn = document.getElementById('btnGuardar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            try {
                const r    = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });
                const text = await r.text();
                let data;
                try { data = JSON.parse(text); } catch { throw new Error('Respuesta inválida: ' + text); }

                if (data.success) {
                    const alerta = entradaId ? CRUDAlerts.updated('Entrada') : CRUDAlerts.created('Entrada');
                    alerta.then(() => window.location.href = 'index.php?page=entradas');
                } else {
                    Swal.fire('Error', data.message || 'No se pudo guardar la entrada.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-floppy me-1"></i> Guardar Entrada';
                }
            } catch (error) {
                console.error('Error guardando entrada:', error);
                CRUDAlerts.connectionError();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-floppy me-1"></i> Guardar Entrada';
            }
        });
    </script>
</body>
</html>
