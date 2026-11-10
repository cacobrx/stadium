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
    <title>Dashboard - Stadium</title>
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
                <h5 class="mb-0 d-inline ms-3">Dashboard</h5>
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
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section">
                <h2 class="mb-4">Dashboard</h2>
                <div class="row" id="statsCards">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                            <small>Productos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                            <small>Clientes</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                            <small>Pedidos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                            <small>Ventas Totales</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Ventas Diarias (Últimos 7 días)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Actividad Reciente</h5>
                            </div>
                            <div class="card-body" id="actividadReciente">
                                <div class="d-flex justify-content-center">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Artículos Section -->
            <div id="articulos-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Artículos</h2>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Lista de Artículos</h5>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nuevo Artículo
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Producto Demo</td>
                                        <td>$150.00</td>
                                        <td>25</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clientes Section -->
            <div id="clientes-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Clientes</h2>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Lista de Clientes</h5>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nuevo Cliente
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Gestión de clientes del sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Pedidos Section -->
            <div id="pedidos-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Pedidos</h2>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Lista de Pedidos</h5>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nuevo Pedido
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Gestión de pedidos del sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Informes Section -->
            <div id="informes-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Informes</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Informe de Ventas</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Generar informes de ventas por período.</p>
                                <button class="btn btn-primary">Generar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Informe de Inventario</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Estado actual del inventario.</p>
                                <button class="btn btn-primary">Generar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuarios Section -->
            <div id="usuarios-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Usuarios</h2>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Gestión de Usuarios</h5>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nuevo Usuario
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Administración de usuarios del sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Tablas Section -->
            <div id="tablas-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Tablas</h2>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Gestión de Tablas</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Configuración de tablas del sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Configuración Section -->
            <div id="configuracion-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Configuración</h2>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Configuración General</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Configuración general del sistema.</p>
                    </div>
                </div>
            </div>

            <!-- Auditoría Section -->
            <div id="auditoria-section" class="content-section" style="display: none;">
                <h2 class="mb-4">Auditoría</h2>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Registro de Auditoría</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Historial de actividades del sistema.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        let salesChart;
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar tema guardado
            loadTheme();
            
            // Inicializar gráfico vacío
            initChart();
            
            // Cargar datos reales
            cargarDashboardData();
        });

        function initChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Ventas ($)',
                        data: [],
                        borderColor: '#FE3333',
                        backgroundColor: 'rgba(254, 51, 51, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Ventas: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        async function cargarDashboardData() {
            try {
                console.log('Cargando datos del dashboard...');
                console.log('Current location:', window.location);
                
                // Obtener la ruta base dinámicamente
                let basePath = window.location.pathname;
                if (basePath.includes('stadium')) {
                    basePath = basePath.substring(0, basePath.indexOf('stadium') + 'stadium'.length) + '/';
                } else {
                    basePath = '/';
                }
                
                const apiUrl = basePath + 'api/dashboard_stats.php';
                console.log('Base path:', basePath);
                console.log('API URL:', apiUrl);
                
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-cache'
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.log('Error response text:', errorText);
                    throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('Datos recibidos:', data);
                
                if (!data.success) {
                    throw new Error(data.error || 'Error desconocido en la respuesta');
                }
                
                // Actualizar cards de estadísticas
                if (data.estadisticas) {
                    actualizarStatsCards(data.estadisticas);
                }
                
                // Actualizar gráfico de ventas
                if (data.ventas_diarias) {
                    actualizarGraficoVentas(data.ventas_diarias);
                }
                
                // Actualizar actividad reciente
                if (data.actividad_reciente) {
                    actualizarActividadReciente(data.actividad_reciente);
                }
                
                console.log('Dashboard actualizado correctamente');
                
            } catch (error) {
                console.error('Error cargando dashboard:', error);
                mostrarErrorDashboard(error.message);
            }
        }

        function actualizarStatsCards(stats) {
            const cards = document.querySelectorAll('#statsCards .stats-card');
            
            // Productos
            cards[0].innerHTML = `
                <h3>${stats.total_productos}</h3>
                <small>Productos</small>
            `;
            
            // Clientes
            cards[1].innerHTML = `
                <h3>${stats.total_clientes}</h3>
                <small>Clientes</small>
            `;
            
            // Pedidos
            cards[2].innerHTML = `
                <h3>${stats.total_pedidos}</h3>
                <small>Pedidos</small>
            `;
            
            // Ventas totales
            cards[3].innerHTML = `
                <h3>$${Number(stats.ventas_totales).toLocaleString('es-AR', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</h3>
                <small>Ventas Totales</small>
            `;
        }

        function actualizarGraficoVentas(ventasData) {
            // Si no hay datos, usar los últimos 7 días con ceros
            if (!ventasData || ventasData.length === 0) {
                const labels = [];
                const valores = [];
                
                for (let i = 6; i >= 0; i--) {
                    const fecha = new Date();
                    fecha.setDate(fecha.getDate() - i);
                    labels.push(fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }));
                    valores.push(0);
                }
                
                salesChart.data.labels = labels;
                salesChart.data.datasets[0].data = valores;
            } else {
                const labels = ventasData.map(item => {
                    const fecha = new Date(item.fecha);
                    return fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                });
                
                const valores = ventasData.map(item => parseFloat(item.total));
                
                salesChart.data.labels = labels;
                salesChart.data.datasets[0].data = valores;
            }
            
            salesChart.update();
        }

        function actualizarActividadReciente(actividades) {
            const container = document.getElementById('actividadReciente');
            
            if (actividades.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No hay actividad reciente</p>';
                return;
            }
            
            let html = '';
            actividades.forEach(actividad => {
                let icono = 'bi-info-circle';
                let color = 'text-info';
                
                switch(actividad.tipo) {
                    case 'pedido':
                        icono = 'bi-cart';
                        color = 'text-success';
                        break;
                    case 'cliente':
                        icono = 'bi-person';
                        color = 'text-primary';
                        break;
                    case 'producto':
                        icono = 'bi-box';
                        color = 'text-warning';
                        break;
                }
                
                html += `
                    <div class="d-flex mb-3">
                        <i class="bi ${icono} ${color} me-2"></i>
                        <small>${actividad.descripcion}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function mostrarErrorDashboard(mensaje) {
            // Mostrar error en las cards
            document.querySelectorAll('#statsCards .stats-card').forEach(card => {
                card.innerHTML = `
                    <h3>-</h3>
                    <small class="text-danger">Error</small>
                `;
            });
            
            // Mostrar error en actividad
            document.getElementById('actividadReciente').innerHTML = 
                `<p class="text-danger text-center">Error: ${mensaje}</p>`;
        }
    </script>
</body>
</html>