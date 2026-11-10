<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Stock - Stadium</title>
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
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>

        .mov-entrada  { border-left: 4px solid #198754; }
        .mov-salida   { border-left: 4px solid #dc3545; }
        .badge-entrada { background-color: #198754; }
        .badge-salida  { background-color: #dc3545; }

        .saldo-positivo { color: #198754; font-weight: 700; }
        .saldo-negativo { color: #dc3545; font-weight: 700; }
        .saldo-cero     { color: #6c757d; font-weight: 700; }

        #tablaMovimientos tbody tr:hover { background-color: var(--bs-tertiary-bg); }

        .resumen-card .card-body { padding: 1rem 1.25rem; }
        .resumen-numero { font-size: 1.6rem; font-weight: 700; }

        .selector-card .card-body { background-color: var(--bs-tertiary-bg); }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <div class="main-content" id="mainContent">
        <!-- Top Nav -->
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline">Informe de Stock Detallado</h5>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                    <i class="bi bi-sun-fill" id="themeIcon"></i>
                </button>
                <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
            </div>
        </div>

        <div class="content-area">

            <!-- Selector de variante -->
            <div class="card mb-4 selector-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Seleccionar Artículo</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Artículo / Variante</label>
                            <select class="form-select form-select-lg" id="varianteSel">
                                <option value="">— Seleccionar variante —</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-lg w-100" onclick="cargarInforme()" id="btnCargar" disabled>
                                <i class="bi bi-bar-chart-line me-1"></i> Ver movimientos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen (oculto hasta que se cargue) -->
            <div id="seccionResumen" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 id="tituloVariante" class="mb-0"></h4>
                    <button class="btn btn-outline-secondary btn-sm" onclick="exportarCSV()">
                        <i class="bi bi-download me-1"></i> Exportar CSV
                    </button>
                </div>

                <!-- Tarjetas resumen -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card resumen-card text-center border-success">
                            <div class="card-body">
                                <div class="text-success resumen-numero" id="resEntradas">0</div>
                                <div class="text-muted small">Total entradas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card resumen-card text-center border-danger">
                            <div class="card-body">
                                <div class="text-danger resumen-numero" id="resSalidas">0</div>
                                <div class="text-muted small">Total salidas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card resumen-card text-center border-primary">
                            <div class="card-body">
                                <div class="text-primary resumen-numero" id="resStockActual">0</div>
                                <div class="text-muted small">Stock actual</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card resumen-card text-center border-secondary">
                            <div class="card-body">
                                <div class="text-secondary resumen-numero" id="resMovimientos">0</div>
                                <div class="text-muted small">Movimientos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de movimientos -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Movimientos de Stock</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge badge-entrada">Entrada</span>
                            <span class="badge badge-salida">Salida</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="tablaMovimientos">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-center">Tipo</th>
                                    <th>Referencia</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Saldo</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoMovimientos">
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-semibold" id="filaStockInicial" style="display:none">
                                    <td colspan="4" class="text-end text-muted small">Stock inicial (antes del primer movimiento)</td>
                                    <td class="text-end" id="tdStockInicial"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer text-muted small" id="skuInfo"></div>
                </div>
            </div>

            <!-- Estado vacío inicial -->
            <div id="seccionVacia" class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-bar-chart-line fs-1 d-block mb-3"></i>
                    <p class="mb-0">Seleccioná una variante para ver su historial de movimientos de stock.</p>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script><?php include __DIR__ . '/../includes/dashboard-functions.js'; ?></script>
    <script>
        let datosInforme = null;

        document.addEventListener('DOMContentLoaded', function () {
            loadTheme();
            cargarVariantes();

            document.getElementById('varianteSel').addEventListener('change', function () {
                document.getElementById('btnCargar').disabled = !this.value;
            });
        });

        // ── Cargar variantes en el select ────────────────────────────────────

        async function cargarVariantes() {
            try {
                const r        = await fetch('./api/informe_stock.php');
                const variantes = await r.json();
                const sel      = document.getElementById('varianteSel');

                sel.innerHTML = '<option value="">— Seleccionar variante —</option>';

                // Agrupar por producto
                const grupos = {};
                variantes.forEach(v => {
                    const gk = v.producto_nombre;
                    if (!grupos[gk]) grupos[gk] = [];
                    grupos[gk].push(v);
                });

                Object.keys(grupos).sort().forEach(prod => {
                    const og = document.createElement('optgroup');
                    og.label = prod;
                    grupos[prod].forEach(v => {
                        let label = v.variante_nombre;
                        if (v.atributos) label += ' — ' + v.atributos;
                        if (v.sku_variante) label += ' [' + v.sku_variante + ']';
                        label += '  (Stock: ' + v.stock_actual + ')';

                        const opt = document.createElement('option');
                        opt.value = v.id;
                        opt.textContent = label;
                        if (parseInt(v.stock_actual) <= 0) opt.style.color = 'red';
                        og.appendChild(opt);
                    });
                    sel.appendChild(og);
                });
            } catch (e) {
                console.error('Error cargando variantes:', e);
            }
        }

        // ── Cargar informe ───────────────────────────────────────────────────

        async function cargarInforme() {
            const varianteId = document.getElementById('varianteSel').value;
            if (!varianteId) return;

            const btn = document.getElementById('btnCargar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';

            try {
                const r    = await fetch(`./api/informe_stock.php?variante=${varianteId}`);
                const data = await r.json();

                if (data.error) throw new Error(data.error);

                datosInforme = data;
                renderizarInforme(data);
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'No se pudo cargar el informe: ' + e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-bar-chart-line me-1"></i> Ver movimientos';
            }
        }

        function renderizarInforme(data) {
            const v    = data.variante;
            const movs = data.movimientos;

            // Título
            let titulo = v.producto_nombre;
            if (v.variante_nombre && v.variante_nombre !== v.producto_nombre) titulo += ' · ' + v.variante_nombre;
            document.getElementById('tituloVariante').textContent = titulo;

            // SKU info
            document.getElementById('skuInfo').textContent = v.sku_variante ? 'SKU: ' + v.sku_variante : '';

            // Resumen
            let totalEntradas = 0, totalSalidas = 0;
            movs.forEach(m => {
                if (m.tipo === 'entrada') totalEntradas += m.cantidad;
                else                      totalSalidas  += m.cantidad;
            });

            document.getElementById('resEntradas').textContent  = totalEntradas;
            document.getElementById('resSalidas').textContent   = totalSalidas;
            document.getElementById('resStockActual').textContent = v.stock_actual;
            document.getElementById('resMovimientos').textContent = movs.length;

            // Stock inicial (fila tfoot)
            const filaIni  = document.getElementById('filaStockInicial');
            const tdStockI = document.getElementById('tdStockInicial');
            if (data.stock_inicial !== 0 || movs.length === 0) {
                filaIni.style.display = '';
                tdStockI.textContent  = data.stock_inicial;
                tdStockI.className    = 'text-end ' + saldoClass(data.stock_inicial);
            } else {
                filaIni.style.display = 'none';
            }

            // Tabla de movimientos
            const tbody = document.getElementById('cuerpoMovimientos');
            tbody.innerHTML = '';

            if (movs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay movimientos registrados para esta variante.
                        </td>
                    </tr>`;
            } else {
                movs.forEach(m => {
                    const esEntrada  = m.tipo === 'entrada';
                    const signo      = esEntrada ? '+' : '−';
                    const claseRow   = esEntrada ? 'mov-entrada' : 'mov-salida';
                    const claseBadge = esEntrada ? 'badge-entrada' : 'badge-salida';

                    const tr = document.createElement('tr');
                    tr.className = claseRow;
                    tr.innerHTML = `
                        <td>${formatFecha(m.fecha)}</td>
                        <td class="text-center">
                            <span class="badge ${claseBadge}">${esEntrada ? 'Entrada' : 'Salida'}</span>
                        </td>
                        <td class="small text-muted">${escHtml(m.detalle)}</td>
                        <td class="text-center fw-semibold ${esEntrada ? 'text-success' : 'text-danger'}">
                            ${signo}${m.cantidad}
                        </td>
                        <td class="text-end ${saldoClass(m.saldo)}">${m.saldo}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // Mostrar sección
            document.getElementById('seccionVacia').classList.add('d-none');
            document.getElementById('seccionResumen').classList.remove('d-none');
        }

        // ── Helpers ──────────────────────────────────────────────────────────

        function formatFecha(fecha) {
            if (!fecha) return '-';
            // fecha viene como YYYY-MM-DD
            const [y, m, d] = fecha.split('-');
            return `${d}/${m}/${y}`;
        }

        function saldoClass(n) {
            n = parseInt(n);
            if (n > 0) return 'saldo-positivo';
            if (n < 0) return 'saldo-negativo';
            return 'saldo-cero';
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ── Exportar CSV ─────────────────────────────────────────────────────

        function exportarCSV() {
            if (!datosInforme) return;
            const v    = datosInforme.variante;
            const movs = datosInforme.movimientos;

            const encabezado = ['Fecha', 'Tipo', 'Referencia', 'Cantidad', 'Saldo'];
            const filas      = movs.map(m => [
                m.fecha,
                m.tipo === 'entrada' ? 'Entrada' : 'Salida',
                '"' + (m.detalle || '').replace(/"/g, '""') + '"',
                (m.tipo === 'entrada' ? '' : '-') + m.cantidad,
                m.saldo
            ]);

            const csv  = [encabezado, ...filas].map(r => r.join(';')).join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const a    = document.createElement('a');
            a.href     = URL.createObjectURL(blob);
            a.download = `stock_${v.sku_variante || v.id}_${new Date().toISOString().slice(0,10)}.csv`;
            a.click();
        }
    </script>
</body>
</html>
