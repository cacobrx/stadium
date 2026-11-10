<?php
// Incluir bootstrap y autenticación
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
    <title>Actividad MP - Stadium</title>
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
        
        .json-viewer {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.875rem;
            padding: 1.25rem;
            border-radius: 8px;
            max-height: 550px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        .json-null { color: #569cd6; }

        .stat-card-custom {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .badge-endpoint {
            font-family: monospace;
            background: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        [data-bs-theme="dark"] .badge-endpoint {
            background: rgba(13, 110, 253, 0.3);
            color: #6ea8fe;
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
                <h5 class="mb-0 d-inline ms-2">Utilidades &raquo; Actividad Mercado Pago</h5>
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
            
            <!-- Banner Informativo -->
            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                <div class="d-flex align-items-start">
                    <div class="fs-2 text-info me-3">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">
                            Búsqueda de pagos via API
                        </h5>
                        <p class="mb-2">
                            Podés consumir el endpoint <span class="badge-endpoint">GET /v1/payments/search</span> enviando tus AccessToken de pruebas (Sandbox). Esto te devolverá el listado en JSON de todas las transacciones generadas durante las pruebas.
                        </p>
                        <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                            <span><i class="bi bi-shield-lock-fill text-warning me-1"></i> Modo: <strong>Sandbox (Pruebas)</strong></span>
                            <span>&bull;</span>
                            <span><i class="bi bi-key-fill text-primary me-1"></i> AccessToken: <strong id="lblTokenUsed">Cargando...</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard / Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card-custom shadow-sm bg-primary bg-gradient text-white h-100">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold">Transacciones Totales</small>
                                <h3 class="fw-bold mb-0" id="statTotal">0</h3>
                                <small class="text-white-50" id="statTotalSub">Resultados de búsqueda</small>
                            </div>
                            <div class="fs-1 text-white-50">
                                <i class="bi bi-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card-custom shadow-sm bg-success bg-gradient text-white h-100">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold">Monto Aprobado</small>
                                <h3 class="fw-bold mb-0" id="statApprovedAmount">$0</h3>
                                <small class="text-white-50" id="statApprovedCount">0 aprobadas</small>
                            </div>
                            <div class="fs-1 text-white-50">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card-custom shadow-sm bg-warning bg-gradient text-dark h-100">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-dark-50 text-uppercase fw-bold">Monto Pendiente</small>
                                <h3 class="fw-bold mb-0" id="statPendingAmount">$0</h3>
                                <small class="text-dark-50" id="statPendingCount">0 pendientes</small>
                            </div>
                            <div class="fs-1 text-dark-50">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card-custom shadow-sm bg-danger bg-gradient text-white h-100">
                        <div class="card-body p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold">Rechazadas / Canceladas</small>
                                <h3 class="fw-bold mb-0" id="statRejectedCount">0</h3>
                                <small class="text-white-50">En periodo seleccionado</small>
                            </div>
                            <div class="fs-1 text-white-50">
                                <i class="bi bi-x-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Filtros -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-transparent py-3 border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-funnel-fill text-primary me-2"></i>Filtros de Búsqueda de Pagos (API MP)</h6>
                </div>
                <div class="card-body pt-0">
                    <form id="searchForm" onsubmit="buscarPagos(event)">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="filterStatus" class="form-label small fw-bold">Estado del Pago</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">-- Todos los estados --</option>
                                    <option value="approved">Aprobados (approved)</option>
                                    <option value="pending">Pendientes (pending / in_process)</option>
                                    <option value="rejected">Rechazados (rejected)</option>
                                    <option value="refunded">Reembolsados (refunded)</option>
                                    <option value="cancelled">Cancelados (cancelled)</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="filterExtRef" class="form-label small fw-bold">Referencia Externa (Pedido ID)</label>
                                <input type="text" class="form-control" id="filterExtRef" placeholder="Ej: PED-10045">
                            </div>
                            <div class="col-12 col-md-2">
                                <label for="filterPaymentId" class="form-label small fw-bold">ID Transacción MP</label>
                                <input type="text" class="form-control" id="filterPaymentId" placeholder="Ej: 1346389275">
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="filterLimit" class="form-label small fw-bold">Límite</label>
                                <select class="form-select" id="filterLimit">
                                    <option value="10">10 resultados</option>
                                    <option value="30" selected>30 resultados</option>
                                    <option value="50">50 resultados</option>
                                    <option value="100">100 resultados</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="filterSort" class="form-label small fw-bold">Orden</label>
                                <select class="form-select" id="filterSort">
                                    <option value="date_created_desc" selected>Más recientes</option>
                                    <option value="date_created_asc">Más antiguos</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                            <div>
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search me-1"></i> Buscar via API
                                </button>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="limpiarFiltros()">
                                    <i class="bi bi-x-circle me-1"></i> Limpiar
                                </button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-dark me-2" onclick="abrirModalJsonCompleto()">
                                    <i class="bi bi-code-slash me-1"></i> Ver JSON Completo API
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Resultados -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i>Transacciones Encontradas</h6>
                    <span class="badge bg-secondary" id="badgeResultCount">0 transacciones</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablePayments">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Pago MP</th>
                                    <th>Fecha Creación</th>
                                    <th>Pagador</th>
                                    <th>Referencia Externa</th>
                                    <th>Método</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPayments">
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="spinner-border text-primary me-2" role="status"></div>
                                        <span>Consultando endpoint GET /v1/payments/search Mercado Pago...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal JSON Inspector -->
    <div class="modal fade" id="modalJson" tabindex="-1" aria-labelledby="modalJsonLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJsonLabel">
                        <i class="bi bi-filetype-json text-primary me-2"></i>
                        Inspección de JSON - GET /v1/payments/search
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted" id="jsonModalSubtitle">Respuesta cruda devuelta por la API de Mercado Pago</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="copiarJson()">
                                <i class="bi bi-clipboard me-1"></i> Copiar JSON
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="descargarJson()">
                                <i class="bi bi-download me-1"></i> Descargar .json
                            </button>
                        </div>
                    </div>
                    <pre class="json-viewer mb-0" id="jsonViewerContent">Cargando datos JSON...</pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Pago en Pedido Pendiente -->
    <div class="modal fade" id="modalVincularPedido" tabindex="-1" aria-labelledby="modalVincularLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success bg-gradient text-white">
                    <h5 class="modal-title fw-bold" id="modalVincularLabel">
                        <i class="bi bi-link-45deg me-2"></i>Registrar Pago MP en Pedido Pendiente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Resumen del Pago Mercado Pago -->
                    <div class="card bg-light border-0 rounded-3 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 text-success"><i class="bi bi-wallet2 me-2"></i>Datos de la Transacción Mercado Pago</h6>
                            <div class="row g-3 small">
                                <div class="col-6 col-md-3"><strong>ID Pago MP:</strong> <span id="vincMpId" class="font-monospace fw-bold text-primary d-block">-</span></div>
                                <div class="col-6 col-md-3"><strong>Monto MP:</strong> <span id="vincMpMonto" class="fw-bold text-success d-block">$0</span></div>
                                <div class="col-6 col-md-3"><strong>Estado MP:</strong> <span id="vincMpEstado" class="d-block">-</span></div>
                                <div class="col-6 col-md-3"><strong>Ref Externa:</strong> <span id="vincMpRef" class="d-block">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Selección de Pedido -->
                    <form id="formVincularPago" onsubmit="ejecutarVincularPago(event)">
                        <div class="mb-3">
                            <label for="selectPedidoPendiente" class="form-label fw-bold">
                                Seleccionar Pedido Pendiente (Estado 0):
                            </label>
                            <select class="form-select form-select-lg" id="selectPedidoPendiente" onchange="alCambiarPedidoSeleccionado()">
                                <option value="">-- Cargando pedidos pendientes... --</option>
                            </select>
                            <div class="form-text">Se muestran los pedidos que actualmente están en estado <strong>Pendiente</strong> en la base de datos.</div>
                        </div>

                        <!-- Opción de ingresar ID de Pedido Manual -->
                        <div class="mb-3">
                            <label for="inputPedidoIdManual" class="form-label small fw-bold">O ingresar ID de Pedido manualmente:</label>
                            <div class="input-group">
                                <span class="input-group-text">#</span>
                                <input type="number" class="form-control" id="inputPedidoIdManual" placeholder="Ej: 10045" oninput="alCambiarPedidoManual()">
                            </div>
                        </div>

                        <!-- Cards de Alerta de Monto -->
                        <div class="alert alert-warning border-0 d-none" id="alertMontoDiferencia">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Atención:</strong> El monto del pedido (<span id="vincPedMonto">$0</span>) difiere del monto reportado por MP (<span id="vincMpMontoAlert">$0</span>).
                        </div>
                        <div class="alert alert-success border-0 d-none" id="alertMontoCoincide">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            El monto del pedido coincide con el pago de Mercado Pago.
                        </div>

                        <div class="modal-footer px-0 pb-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success fw-bold" id="btnConfirmarVincular">
                                <i class="bi bi-check-circle-fill me-1"></i> Confirmar y Registrar Pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>

        let lastApiResponse = null;
        let jsonModalInstance = null;
        let vincularModalInstance = null;
        let itemSeleccionadoVincular = null;
        let pedidosPendientesCache = [];

        document.addEventListener('DOMContentLoaded', function() {
            jsonModalInstance = new bootstrap.Modal(document.getElementById('modalJson'));
            vincularModalInstance = new bootstrap.Modal(document.getElementById('modalVincularPedido'));
            buscarPagos();
        });

        function buscarPagos(e) {
            if (e) e.preventDefault();

            const tbody = document.getElementById('tbodyPayments');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary me-2" role="status"></div>
                        <span>Consultando API Sandbox de Mercado Pago...</span>
                    </td>
                </tr>
            `;

            const status = document.getElementById('filterStatus').value;
            const extRef = document.getElementById('filterExtRef').value.trim();
            const paymentId = document.getElementById('filterPaymentId').value.trim();
            const limit = document.getElementById('filterLimit').value;
            const sortVal = document.getElementById('filterSort').value;

            const sortParts = sortVal.split('_');
            const criteria = sortParts.pop(); // 'asc' or 'desc'
            const sort = sortParts.join('_'); // 'date_created'

            const params = new URLSearchParams({
                limit: limit,
                sort: sort,
                criteria: criteria
            });

            if (status) params.append('status', status);
            if (extRef) params.append('external_reference', extRef);
            if (paymentId) params.append('payment_id', paymentId);

            fetch('api/mercadopago/search_payments.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    lastApiResponse = data;

                    if (!data.success) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-4 text-danger">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2"></i>
                                    <strong>Error al consultar Mercado Pago:</strong> ${escapeHtml(data.error || 'Error desconocido')}
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    document.getElementById('lblTokenUsed').innerText = data.token_used || 'Sandbox';
                    actualizarEstadisticas(data.summary, data.paging);
                    renderizarTabla(data.results);
                })
                .catch(err => {
                    console.error("Error al buscar pagos:", err);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-4 text-danger">
                                <i class="bi bi-wifi-off fs-3 d-block mb-2"></i>
                                <strong>Error de red o conexión:</strong> ${escapeHtml(err.message)}
                            </td>
                        </tr>
                    `;
                });
        }

        function actualizarEstadisticas(summary, paging) {
            if (!summary) return;

            document.getElementById('statTotal').innerText = summary.total_found || 0;
            document.getElementById('statTotalSub').innerText = `${summary.total_in_page || 0} en esta página`;

            const approvedAmount = (summary.approved_amount || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            document.getElementById('statApprovedAmount').innerText = approvedAmount;
            document.getElementById('statApprovedCount').innerText = `${summary.approved_count || 0} transacciones aprobadas`;

            const pendingAmount = (summary.pending_amount || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            document.getElementById('statPendingAmount').innerText = pendingAmount;
            document.getElementById('statPendingCount').innerText = `${summary.pending_count || 0} transacciones pendientes`;

            const rejectedTotal = (summary.rejected_count || 0) + (summary.cancelled_count || 0);
            document.getElementById('statRejectedCount').innerText = rejectedTotal;

            document.getElementById('badgeResultCount').innerText = `${summary.total_in_page || 0} de ${summary.total_found || 0} pagos`;
        }

        function renderizarTabla(results) {
            const tbody = document.getElementById('tbodyPayments');

            if (!results || results.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            <span>No se encontraron pagos con los filtros seleccionados.</span>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            results.forEach((item, index) => {
                const id = item.id || '-';
                const dateStr = item.date_created ? formatearFecha(item.date_created) : '-';
                
                let pagador = 'Desconocido';
                if (item.payer) {
                    if (item.payer.email) pagador = item.payer.email;
                    else if (item.payer.first_name || item.payer.last_name) {
                        pagador = `${item.payer.first_name || ''} ${item.payer.last_name || ''}`.trim();
                    }
                }

                const extRef = item.external_reference || '<span class="text-muted italic">Sin ref</span>';
                const method = item.payment_method_id ? item.payment_method_id.toUpperCase() : '-';
                const amount = item.transaction_amount ? item.transaction_amount.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }) : '$0';
                
                const badgeStatus = obtenerBadgeEstado(item.status, item.status_detail);

                html += `
                    <tr>
                        <td><strong class="font-monospace">${id}</strong></td>
                        <td class="small">${dateStr}</td>
                        <td class="small" title="${escapeHtml(pagador)}">${escapeHtml(pagador)}</td>
                        <td><span class="badge bg-light text-dark border">${extRef}</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">${method}</span></td>
                        <td class="text-end fw-bold">${amount}</td>
                        <td class="text-center">${badgeStatus}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-success me-1" onclick="abrirModalVincular(${index})" title="Registrar este pago en un pedido pendiente">
                                <i class="bi bi-link-45deg"></i> Registrar en Pedido
                            </button>
                            <button class="btn btn-sm btn-outline-dark me-1" onclick="verJsonDetalle(${index})" title="Ver JSON completo de este pago">
                                <i class="bi bi-filetype-json"></i> JSON
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function obtenerBadgeEstado(status, detail) {
            switch(status) {
                case 'approved':
                    return `<span class="badge bg-success text-white" title="${escapeHtml(detail || '')}"><i class="bi bi-check-circle me-1"></i> Aprobado</span>`;
                case 'pending':
                case 'in_process':
                case 'authorized':
                    return `<span class="badge bg-warning text-dark" title="${escapeHtml(detail || '')}"><i class="bi bi-clock me-1"></i> Pendiente</span>`;
                case 'rejected':
                    return `<span class="badge bg-danger text-white" title="${escapeHtml(detail || '')}"><i class="bi bi-x-circle me-1"></i> Rechazado</span>`;
                case 'refunded':
                case 'charged_back':
                    return `<span class="badge bg-info text-dark" title="${escapeHtml(detail || '')}"><i class="bi bi-arrow-counterclockwise me-1"></i> Reembolsado</span>`;
                case 'cancelled':
                    return `<span class="badge bg-secondary text-white" title="${escapeHtml(detail || '')}"><i class="bi bi-slash-circle me-1"></i> Cancelado</span>`;
                default:
                    return `<span class="badge bg-dark text-white">${escapeHtml(status || 'Desconocido')}</span>`;
            }
        }

        function formatearFecha(isoDate) {
            try {
                const date = new Date(isoDate);
                return date.toLocaleString('es-AR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch (e) {
                return isoDate;
            }
        }

        function limpiarFiltros() {
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterExtRef').value = '';
            document.getElementById('filterPaymentId').value = '';
            document.getElementById('filterLimit').value = '30';
            document.getElementById('filterSort').value = 'date_created_desc';
            buscarPagos();
        }

        function abrirModalJsonCompleto() {
            if (!lastApiResponse) {
                Swal.fire('Atención', 'Aún no se ha realizado ninguna consulta.', 'warning');
                return;
            }
            document.getElementById('jsonModalSubtitle').innerText = 'Respuesta completa enviada por GET /v1/payments/search';
            renderJsonViewer(lastApiResponse.raw_response || lastApiResponse);
            jsonModalInstance.show();
        }

        function verJsonDetalle(index) {
            if (!lastApiResponse || !lastApiResponse.results || !lastApiResponse.results[index]) {
                return;
            }
            const item = lastApiResponse.results[index];
            document.getElementById('jsonModalSubtitle').innerText = `Detalle del pago ID: ${item.id || ''}`;
            renderJsonViewer(item);
            jsonModalInstance.show();
        }

        function renderJsonViewer(jsonObj) {
            const container = document.getElementById('jsonViewerContent');
            const jsonString = JSON.stringify(jsonObj, null, 2);
            container.innerHTML = highlightJson(jsonString);
        }

        function highlightJson(json) {
            json = escapeHtml(json);
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

        function copiarJson() {
            const container = document.getElementById('jsonViewerContent');
            const text = container.innerText;
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'JSON copiado al portapapeles',
                    showConfirmButton: false,
                    timer: 2000
                });
            }).catch(err => {
                Swal.fire('Error', 'No se pudo copiar el contenido', 'error');
            });
        }

        function descargarJson() {
            const container = document.getElementById('jsonViewerContent');
            const text = container.innerText;
            const blob = new Blob([text], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `mp_payments_search_${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function escapeHtml(text) {
            if (typeof text !== 'string') return text;
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // ── Funciones para Vincular Pago a Pedido Pendiente ───────────────────
        function abrirModalVincular(index) {
            if (!lastApiResponse || !lastApiResponse.results || !lastApiResponse.results[index]) {
                return;
            }
            itemSeleccionadoVincular = lastApiResponse.results[index];
            const item = itemSeleccionadoVincular;

            document.getElementById('vincMpId').innerText = item.id || '-';
            const mpAmount = item.transaction_amount ? item.transaction_amount.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }) : '$0';
            document.getElementById('vincMpMonto').innerText = mpAmount;
            document.getElementById('vincMpEstado').innerHTML = obtenerBadgeEstado(item.status, item.status_detail);
            document.getElementById('vincMpRef').innerText = item.external_reference || 'Sin ref';

            document.getElementById('inputPedidoIdManual').value = '';
            document.getElementById('alertMontoDiferencia').classList.add('d-none');
            document.getElementById('alertMontoCoincide').classList.add('d-none');

            const select = document.getElementById('selectPedidoPendiente');
            select.innerHTML = '<option value="">-- Cargando pedidos pendientes... --</option>';

            vincularModalInstance.show();

            // Cargar lista de pedidos pendientes desde la API
            fetch('api/mercadopago/vincular_pago_pedido.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        select.innerHTML = '<option value="">-- Error al cargar pedidos pendientes --</option>';
                        return;
                    }
                    pedidosPendientesCache = data.pedidos_pendientes || [];

                    if (pedidosPendientesCache.length === 0) {
                        select.innerHTML = '<option value="">-- No hay pedidos pendientes (estado 0) --</option>';
                        return;
                    }

                    let optionsHtml = '<option value="">-- Seleccione un pedido pendiente --</option>';
                    let matchedId = null;

                    const extRefRaw = (item.external_reference || '').replace(/\D/g, '');

                    pedidosPendientesCache.forEach(p => {
                        const isMatch = (extRefRaw && String(p.id) === extRefRaw) || (item.external_reference && String(p.id) === String(item.external_reference));
                        if (isMatch) matchedId = p.id;

                        const pMontoFmt = p.total ? p.total.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }) : '$0';
                        optionsHtml += `<option value="${p.id}" ${isMatch ? 'selected' : ''}>
                            Pedido #${p.id} - ${escapeHtml(p.cliente || 'Cliente')} (${p.email || ''}) - Total: ${pMontoFmt} [Fecha: ${p.fecha}]
                        </option>`;
                    });

                    select.innerHTML = optionsHtml;

                    if (matchedId) {
                        alCambiarPedidoSeleccionado();
                    }
                })
                .catch(err => {
                    console.error("Error al cargar pedidos pendientes:", err);
                    select.innerHTML = '<option value="">-- Error de conexión --</option>';
                });
        }

        function alCambiarPedidoSeleccionado() {
            const selectVal = document.getElementById('selectPedidoPendiente').value;
            document.getElementById('inputPedidoIdManual').value = '';
            evaluarCoincidenciaMonto(selectVal);
        }

        function alCambiarPedidoManual() {
            const manualVal = document.getElementById('inputPedidoIdManual').value.trim();
            if (manualVal) {
                document.getElementById('selectPedidoPendiente').value = '';
            }
            evaluarCoincidenciaMonto(manualVal);
        }

        function evaluarCoincidenciaMonto(idped) {
            const alertDiff = document.getElementById('alertMontoDiferencia');
            const alertMatch = document.getElementById('alertMontoCoincide');

            alertDiff.classList.add('d-none');
            alertMatch.classList.add('d-none');

            if (!idped || !itemSeleccionadoVincular) return;

            const ped = pedidosPendientesCache.find(p => String(p.id) === String(idped));
            if (!ped) return;

            const pedTotal = ped.total || 0;
            const mpTotal = itemSeleccionadoVincular.transaction_amount || 0;

            const pedTotalFmt = pedTotal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
            const mpTotalFmt = mpTotal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

            if (Math.abs(pedTotal - mpTotal) > 0.01) {
                document.getElementById('vincPedMonto').innerText = pedTotalFmt;
                document.getElementById('vincMpMontoAlert').innerText = mpTotalFmt;
                alertDiff.classList.remove('d-none');
            } else {
                alertMatch.classList.remove('d-none');
            }
        }

        function ejecutarVincularPago(e) {
            e.preventDefault();

            if (!itemSeleccionadoVincular) return;

            const selectVal = document.getElementById('selectPedidoPendiente').value;
            const manualVal = document.getElementById('inputPedidoIdManual').value.trim();
            const idped = selectVal || manualVal;

            if (!idped) {
                Swal.fire('Atención', 'Por favor selecciona un pedido pendiente o ingresá el ID del pedido.', 'warning');
                return;
            }

            const paymentId = itemSeleccionadoVincular.id;

            showConfirmation(
                '¿Confirmar registro de pago?',
                `¿Deseás vincular el Pago MP #${paymentId} al Pedido #${idped} y marcar el pedido como Confirmado/Pagado?`,
                'Sí, Registrar Pago',
                'Cancelar'
            ).then((res) => {
                if (res.isConfirmed) {
                    const btn = document.getElementById('btnConfirmarVincular');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

                    fetch('api/mercadopago/vincular_pago_pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            idped: parseInt(idped),
                            payment_id: String(paymentId),
                            payment_data: itemSeleccionadoVincular
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirmar y Registrar Pago';

                        if (data.success) {
                            vincularModalInstance.hide();
                            Swal.fire({
                                icon: 'success',
                                title: '¡Pago registrado exitosamente!',
                                text: data.message,
                                confirmButtonText: 'Excelente'
                            }).then(() => {
                                buscarPagos();
                            });
                        } else {
                            Swal.fire('Error', data.error || 'No se pudo vincular el pago.', 'error');
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirmar y Registrar Pago';
                        console.error("Error al registrar pago:", err);
                        Swal.fire('Error', 'Ocurrió un error de red al intentar vincular el pago.', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>
