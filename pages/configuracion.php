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
    <title>Configuración - Stadium</title>
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
        
        .config-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        .config-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            color: inherit;
        }
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
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
                <h5 class="mb-0 d-inline ms-2">Configuración del Sistema</h5>
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
            <div class="mb-4">
                <h4 class="fw-bold mb-1"><i class="bi bi-gear-fill text-primary me-2"></i>Panel de Configuración</h4>
                <p class="text-muted">Seleccioná una sección de configuración para administrar los parámetros e integraciones del sistema.</p>
            </div>

            <div class="row g-4">
                <!-- Actividad Mercado Pago Card -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="actividad_mp" class="card config-card shadow-sm h-100 p-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Actividad MP</h5>
                                    <span class="badge bg-info text-dark">Sandbox API</span>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-0">
                                Búsqueda de pagos vía API consumiendo el endpoint <code>GET /v1/payments/search</code> enviando AccessToken de pruebas (Sandbox). Visualizá las transacciones en JSON.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-primary text-end pt-0">
                            <strong>Acceder <i class="bi bi-arrow-right ms-1"></i></strong>
                        </div>
                    </a>
                </div>

                <!-- Datos Fiscales Card -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="cuit" class="card config-card shadow-sm h-100 p-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                                    <i class="bi bi-building-check"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Datos Fiscales</h5>
                                    <span class="badge bg-secondary">Facturación</span>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-0">
                                Configuración de CUIT, razón social, punto de venta y credenciales de integración de comprobantes fiscales (AFIP / Facturación).
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-success text-end pt-0">
                            <strong>Acceder <i class="bi bi-arrow-right ms-1"></i></strong>
                        </div>
                    </a>
                </div>

                <!-- Usuarios & Permisos Card -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="usuarios" class="card config-card shadow-sm h-100 p-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="bi bi-person-gear"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Usuarios</h5>
                                    <span class="badge bg-secondary">Seguridad</span>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-0">
                                Administración de usuarios administradores, operadores, claves de acceso y permisos dentro del sistema Stadium.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-warning text-end pt-0">
                            <strong>Acceder <i class="bi bi-arrow-right ms-1"></i></strong>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
</body>
</html>
